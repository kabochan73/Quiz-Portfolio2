<?php

use Anthropic\Beta\Messages\BetaMessage;
use App\Enums\GradingLevel;
use App\Models\Answer;
use App\Models\Question;
use App\Services\Grading\ClaudeGradingService;
use App\Services\Grading\ClaudeMessages;
use App\Services\Grading\GradeResultParser;
use App\Services\Grading\GradingPrompt;
use Illuminate\Support\Collection;

/**
 * 用意した応答を順に返し、送られてきた引数を記録する、Claude API の偽物。
 */
class FakeClaudeMessages implements ClaudeMessages
{
    /** @var list<array{params: array<string, mixed>, timeout: float}> */
    public array $calls = [];

    /** @param list<BetaMessage> $responses */
    public function __construct(private array $responses) {}

    public function create(array $params, float $timeout): BetaMessage
    {
        $this->calls[] = ['params' => $params, 'timeout' => $timeout];

        return array_shift($this->responses) ?? throw new LogicException('用意した応答より多く呼ばれました。');
    }
}

/**
 * API と同じ形の応答を、SDK の BetaMessage にする。
 *
 * @param  list<array<string, mixed>>  $content
 */
function claudeResponse(array $content, string $stopReason = 'tool_use', array $usage = [], string $model = 'claude-sonnet-5-5'): BetaMessage
{
    return BetaMessage::fromArray([
        'id' => 'msg_'.uniqid(),
        'type' => 'message',
        'role' => 'assistant',
        'model' => $model,
        'content' => $content,
        'stop_reason' => $stopReason,
        'stop_sequence' => null,
        'stop_details' => $stopReason === 'refusal' ? ['type' => 'refusal', 'category' => 'cyber', 'explanation' => null] : null,
        'usage' => array_merge(['input_tokens' => 100, 'output_tokens' => 50], $usage),
    ]);
}

function submitGradesBlock(array $scores): array
{
    return [
        'type' => 'tool_use',
        'id' => 'toolu_'.uniqid(),
        'name' => 'submit_grades',
        'input' => ['grades' => collect($scores)->map(fn (int $score, int $position) => [
            'position' => $position, 'score' => $score,
            'good_points' => '良い点', 'improvements' => '改善点', 'example' => '改善例',
        ])->values()->all()],
    ];
}

function twoAnswers(): Collection
{
    return collect([0, 1])->map(fn (int $position) => (new Answer(['position' => $position, 'body' => "回答{$position}"]))
        ->setRelation('question', new Question(['body' => "問題{$position}"])));
}

function claudeService(FakeClaudeMessages $messages): ClaudeGradingService
{
    return new ClaudeGradingService($messages, new GradingPrompt, new GradeResultParser, 'claude-sonnet-5-5');
}

it('submit_grades で提出された結果を GradingResult にし、利用量とモデル名を記録する', function () {
    $messages = new FakeClaudeMessages([
        claudeResponse([['type' => 'text', 'text' => '採点しました。'], submitGradesBlock([85, 45])], usage: [
            'input_tokens' => 1200, 'output_tokens' => 300, 'server_tool_use' => ['web_search_requests' => 2, 'web_fetch_requests' => 0],
        ]),
    ]);

    $result = claudeService($messages)->grade(twoAnswers(), GradingLevel::Hard);

    expect($result->gradeFor(0)->score)->toBe(85)
        ->and($result->gradeFor(1)->score)->toBe(45)
        ->and($result->model)->toBe('claude-sonnet-5-5')
        ->and($result->inputTokens)->toBe(1200)
        ->and($result->outputTokens)->toBe(300)
        ->and($result->webSearchRequests)->toBe(2)
        ->and($messages->calls)->toHaveCount(1);
});

it('送る内容に、モデル・採点レベルの方針・問題と回答・2つのツール・拒否時の自動切り替えを入れ、呼び出しは強制しない', function () {
    $messages = new FakeClaudeMessages([claudeResponse([submitGradesBlock([80, 80])])]);

    claudeService($messages)->grade(twoAnswers(), GradingLevel::Hard);

    $params = $messages->calls[0]['params'];
    expect($params['model'])->toBe('claude-sonnet-5-5')
        ->and($params['maxTokens'])->toBe(16000)
        ->and($params['system'])->toContain(GradingLevel::Hard->policyText())
        ->and($params['messages'][0]['content'])->toContain('<item position="1">')
        ->and(array_column($params['tools'], 'name'))->toBe(['web_search', 'submit_grades'])
        ->and($params['toolChoice'])->toBe(['type' => 'auto'])
        ->and($params['fallbacks'])->toBe('default')
        ->and($params['betas'])->toBe(['server-side-fallback-2026-07-01'])
        // 1回の採点の時間の上限(200秒)の残りを、制限時間として渡す
        ->and($messages->calls[0]['timeout'])->toBeLessThanOrEqual(200)->toBeGreaterThan(190);
});

it('拒否で別のモデルに切り替わった場合は、実際に採点したモデル名を残す', function () {
    $messages = new FakeClaudeMessages([claudeResponse([submitGradesBlock([80, 80])], model: 'claude-opus-5-5')]);

    expect(claudeService($messages)->grade(twoAnswers(), GradingLevel::Normal)->model)->toBe('claude-opus-5-5');
});

it('途中で止まった応答は、会話に足して続けさせ、利用量を合計する', function () {
    $messages = new FakeClaudeMessages([
        claudeResponse([['type' => 'text', 'text' => '検索しています…']], 'pause_turn', ['server_tool_use' => ['web_search_requests' => 1, 'web_fetch_requests' => 0]]),
        claudeResponse([submitGradesBlock([70, 60])], usage: ['server_tool_use' => ['web_search_requests' => 1, 'web_fetch_requests' => 0]]),
    ]);

    $result = claudeService($messages)->grade(twoAnswers(), GradingLevel::Normal);

    $secondConversation = $messages->calls[1]['params']['messages'];
    expect($messages->calls)->toHaveCount(2)
        ->and($secondConversation)->toHaveCount(2)
        ->and($secondConversation[1]['role'])->toBe('assistant')
        ->and($result->inputTokens)->toBe(200)
        ->and($result->outputTokens)->toBe(100)
        ->and($result->webSearchRequests)->toBe(2);
});

it('submit_grades が呼ばれなければ、催促を1回だけ送る', function () {
    $messages = new FakeClaudeMessages([
        claudeResponse([['type' => 'text', 'text' => '採点結果は次のとおりです…']], 'end_turn'),
        claudeResponse([submitGradesBlock([90, 30])]),
    ]);

    $result = claudeService($messages)->grade(twoAnswers(), GradingLevel::Normal);

    $secondConversation = $messages->calls[1]['params']['messages'];
    expect($result->gradeFor(1)->score)->toBe(30)
        ->and(end($secondConversation))->toBe(['role' => 'user', 'content' => (new GradingPrompt)->reminderMessage()]);
});

it('催促しても submit_grades が呼ばれなければ、例外を投げる(→ Job の再試行)', function () {
    $messages = new FakeClaudeMessages([
        claudeResponse([['type' => 'text', 'text' => '…']], 'end_turn'),
        claudeResponse([['type' => 'text', 'text' => '…']], 'end_turn'),
    ]);

    expect(fn () => claudeService($messages)->grade(twoAnswers(), GradingLevel::Normal))
        ->toThrow(RuntimeException::class, 'submit_grades で採点結果を提出しませんでした');
    expect($messages->calls)->toHaveCount(2);
});

it('途中で止まった応答を続けさせるのは3回までにする', function () {
    $paused = fn () => claudeResponse([['type' => 'text', 'text' => '…']], 'pause_turn');
    // 3回続けさせ、4回目も止まったら催促、それでも提出されなければ例外
    $messages = new FakeClaudeMessages([$paused(), $paused(), $paused(), $paused(), $paused()]);

    expect(fn () => claudeService($messages)->grade(twoAnswers(), GradingLevel::Normal))
        ->toThrow(RuntimeException::class);
    expect($messages->calls)->toHaveCount(5);
});

it('拒否されたら、理由の分類を付けて例外を投げる', function () {
    $messages = new FakeClaudeMessages([claudeResponse([], 'refusal')]);

    expect(fn () => claudeService($messages)->grade(twoAnswers(), GradingLevel::Normal))
        ->toThrow(RuntimeException::class, 'Claude が採点を拒否しました(category: cyber)');
});

it('提出された結果が不正なら、検証の例外をそのまま投げる', function () {
    $messages = new FakeClaudeMessages([claudeResponse([submitGradesBlock([80])])]); // 2問なのに1件

    expect(fn () => claudeService($messages)->grade(twoAnswers(), GradingLevel::Normal))
        ->toThrow(UnexpectedValueException::class, '採点結果の件数(1件)が問題数(2問)と合いません。');
});
