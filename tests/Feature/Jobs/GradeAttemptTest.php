<?php

use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Jobs\GradeAttempt;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Services\Grading\FakeGradingService;
use App\Services\Grading\Grade;
use App\Services\Grading\GradingResult;
use App\Services\Grading\GradingService;
use Illuminate\Support\Collection;

/**
 * 3問の回答を持つ挑戦を作る。
 */
function attemptToGrade(AttemptStatus $status = AttemptStatus::Pending): Attempt
{
    $section = Section::factory()->create();
    $attempt = Attempt::factory()->for($section)->create(['status' => $status]);

    foreach (range(0, 2) as $position) {
        $question = Question::factory()->for($section)->create();
        Answer::factory()->for($attempt)->for($question)->create(['position' => $position]);
    }

    return $attempt;
}

/**
 * 決まった結果を返す、または例外を投げる採点サービスを作る。
 */
function gradingServiceReturning(Closure $grade): GradingService
{
    return new class($grade) implements GradingService
    {
        public function __construct(private Closure $grade) {}

        public function grade(Collection $answers, GradingLevel $level): GradingResult
        {
            return ($this->grade)($answers, $level);
        }
    };
}

it('採点結果を問題ごとに保存し、挑戦を完了にして、モデル名と利用量も記録する', function () {
    $attempt = attemptToGrade();
    $grader = gradingServiceReturning(fn (Collection $answers) => new GradingResult(
        grades: $answers->mapWithKeys(fn ($answer) => [$answer->position => new Grade(70 + $answer->position, '良い点', '改善点', '改善例')])->all(),
        model: 'claude-sonnet-5-5',
        inputTokens: 1200,
        outputTokens: 300,
        webSearchRequests: 1,
    ));

    (new GradeAttempt($attempt))->handle($grader);

    $attempt->refresh();
    expect($attempt->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->graded_at)->not->toBeNull()
        ->and($attempt->model)->toBe('claude-sonnet-5-5')
        ->and($attempt->input_tokens)->toBe(1200)
        ->and($attempt->output_tokens)->toBe(300)
        ->and($attempt->web_search_requests)->toBe(1)
        ->and($attempt->answers->map(fn ($answer) => $answer->score->score)->all())->toBe([70, 71, 72])
        ->and($attempt->answers->first()->score->improvements)->toBe('改善点');
});

it('採点サービスには、回答を問題と一緒に、挑戦の採点レベルで渡す', function () {
    $attempt = attemptToGrade();
    $attempt->update(['grading_level' => GradingLevel::Hard]);
    $received = null;

    $grader = gradingServiceReturning(function (Collection $answers, GradingLevel $level) use (&$received) {
        $received = ['positions' => $answers->pluck('position')->all(), 'hasQuestion' => $answers->every->relationLoaded('question'), 'level' => $level];

        return (new FakeGradingService)->grade($answers, $level);
    });

    (new GradeAttempt($attempt->fresh()))->handle($grader);

    expect($received)->toBe(['positions' => [0, 1, 2], 'hasQuestion' => true, 'level' => GradingLevel::Hard]);
});

it('再試行(採点中のまま)でも採点を続ける', function () {
    $attempt = attemptToGrade(AttemptStatus::Grading);

    (new GradeAttempt($attempt))->handle(new FakeGradingService);

    expect($attempt->fresh()->status)->toBe(AttemptStatus::Completed);
});

it('すでに完了・失敗した挑戦は採点し直さない', function (AttemptStatus $status) {
    $attempt = attemptToGrade($status);
    $grader = gradingServiceReturning(fn () => throw new LogicException('呼ばれてはいけない'));

    (new GradeAttempt($attempt))->handle($grader);

    expect($attempt->fresh()->status)->toBe($status)
        ->and(Score::count())->toBe(0);
})->with([AttemptStatus::Completed, AttemptStatus::Failed]);

it('採点サービスが失敗したら例外をそのまま投げ(→ Job の再試行)、何も保存しない', function () {
    $attempt = attemptToGrade();
    $grader = gradingServiceReturning(fn () => throw new RuntimeException('Claude API の呼び出しに失敗しました'));

    expect(fn () => (new GradeAttempt($attempt))->handle($grader))
        ->toThrow(RuntimeException::class, 'Claude API の呼び出しに失敗しました');

    expect($attempt->fresh()->status)->toBe(AttemptStatus::Grading)
        ->and(Score::count())->toBe(0);
});

it('採点結果が足りなければ、途中まで保存した分も取り消す', function () {
    $attempt = attemptToGrade();
    // 3問中2問分しか結果がない
    $grader = gradingServiceReturning(fn () => new GradingResult(grades: [
        0 => new Grade(80, '良い点', '改善点', '改善例'),
        1 => new Grade(60, '良い点', '改善点', '改善例'),
    ]));

    expect(fn () => (new GradeAttempt($attempt))->handle($grader))->toThrow(OutOfBoundsException::class);

    expect(Score::count())->toBe(0)
        ->and($attempt->fresh()->status)->toBe(AttemptStatus::Grading);
});

it('3回とも失敗したら挑戦を失敗にして、理由を記録する', function () {
    $attempt = attemptToGrade(AttemptStatus::Grading);

    (new GradeAttempt($attempt))->failed(new RuntimeException('Claude API の呼び出しに失敗しました'));

    $attempt->refresh();
    expect($attempt->status)->toBe(AttemptStatus::Failed)
        ->and($attempt->error_message)->toBe('Claude API の呼び出しに失敗しました');
});

it('試行回数・制限時間・再試行の間隔・重複防止のキーを設計どおりに設定している', function () {
    $attempt = attemptToGrade();
    $job = new GradeAttempt($attempt);

    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(240)
        ->and($job->backoff())->toBe([15, 60])
        ->and($job->uniqueId())->toBe((string) $attempt->id)
        // retry_after が制限時間より短いと、採点中の Job がもう一度実行されてしまう
        ->and(config('queue.connections.database.retry_after'))->toBeGreaterThan($job->timeout);
});
