<?php

namespace App\Services\Grading;

use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Beta\Messages\BetaToolUseBlock;
use App\Enums\GradingLevel;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Claude API で本物の採点をする(GRADING_DRIVER=claude、architecture.md 4.4)。
 *
 * 流れ:
 * 1. system プロンプト・問題と回答・ツール(Web 検索と submit_grades)を送る
 * 2. 応答が途中で止まった(pause_turn: Web 検索が長引いたときなど)なら、応答をそのまま会話に足して続けさせる
 * 3. submit_grades が呼ばれなかったら、催促を1回だけ送る
 * 4. submit_grades の引数を GradeResultParser で検証し、GradingResult にする(利用量は全呼び出しの合計)
 *
 * ツールの呼び出しは強制しない(Claude Sonnet 5.5 では tool_choice の tool / any が 400 になるため)。
 * 代わりに submit_grades を strict にし、プロンプトと催促で提出を指示する。
 *
 * うまくいかない場合(拒否・提出なし・不正な結果・通信エラー)は例外を投げ、採点 Job の再試行に任せる。
 */
class ClaudeGradingService implements GradingService
{
    // 1回の採点にかける時間の上限(秒)。採点 Job の制限時間 240 秒に、保存などの余裕を残して収める
    private const TIME_BUDGET_SECONDS = 200;

    // 残り時間がこれより短ければ、呼び出しても間に合わないので諦める
    private const MIN_CALL_SECONDS = 10;

    // 途中で止まった応答を続けさせる回数の上限
    private const MAX_PAUSE_CONTINUATIONS = 3;

    // 思考(adaptive thinking)と全問のフィードバックが収まるだけの出力上限
    private const MAX_TOKENS = 16000;

    // 安全上の理由で拒否されたとき、同じリクエストを API 側で別のモデルに自動で回す(fallbacks: default)
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    public function __construct(
        private readonly ClaudeMessages $messages,
        private readonly GradingPrompt $prompt,
        private readonly GradeResultParser $parser,
        private readonly string $model,
    ) {}

    public function grade(Collection $answers, GradingLevel $level): GradingResult
    {
        $deadline = microtime(true) + self::TIME_BUDGET_SECONDS;

        $baseParams = [
            'model' => $this->model,
            'maxTokens' => self::MAX_TOKENS,
            'system' => $this->prompt->system($level),
            'tools' => [
                $this->prompt->webSearchTool($answers->count()),
                $this->prompt->submitGradesTool(),
            ],
            'toolChoice' => ['type' => 'auto'],
            'fallbacks' => 'default',
            'betas' => [self::FALLBACK_BETA],
        ];

        $conversation = [['role' => 'user', 'content' => $this->prompt->userMessage($answers)]];
        $usage = ['input' => 0, 'output' => 0, 'search' => 0];
        $pauses = 0;
        $reminded = false;

        while (true) {
            $response = $this->call($baseParams, $conversation, $deadline);
            $this->addUsage($usage, $response);

            if ($response->stopReason === 'refusal') {
                throw new RuntimeException('Claude が採点を拒否しました(category: '.($response->stopDetails?->category ?? '不明').')。');
            }

            $submission = $this->findSubmission($response);

            if ($submission !== null) {
                return new GradingResult(
                    grades: $this->parser->parse($submission->input, $answers),
                    // 拒否で別のモデルに切り替わった場合も、実際に採点したモデル名を残す
                    model: $response->model,
                    inputTokens: $usage['input'],
                    outputTokens: $usage['output'],
                    webSearchRequests: $usage['search'],
                );
            }

            // 応答はそのまま会話に足す(思考のブロックなども含めて渡し直す必要があるため)
            $conversation[] = ['role' => 'assistant', 'content' => $response->content];

            if ($response->stopReason === 'pause_turn' && $pauses < self::MAX_PAUSE_CONTINUATIONS) {
                $pauses++;

                continue;
            }

            if (! $reminded) {
                $reminded = true;
                $conversation[] = ['role' => 'user', 'content' => $this->prompt->reminderMessage()];

                continue;
            }

            throw new RuntimeException('Claude が submit_grades で採点結果を提出しませんでした(stop_reason: '.$response->stopReason.')。');
        }
    }

    /**
     * 残り時間を、この1回の呼び出しの制限時間として渡す。
     *
     * @param  array<string, mixed>  $baseParams
     * @param  array<int, array<string, mixed>>  $conversation
     */
    private function call(array $baseParams, array $conversation, float $deadline): BetaMessage
    {
        $remaining = $deadline - microtime(true);

        if ($remaining < self::MIN_CALL_SECONDS) {
            throw new RuntimeException('採点の制限時間('.self::TIME_BUDGET_SECONDS.'秒)を超えました。');
        }

        return $this->messages->create([...$baseParams, 'messages' => $conversation], $remaining);
    }

    private function findSubmission(BetaMessage $response): ?BetaToolUseBlock
    {
        foreach ($response->content as $block) {
            if ($block instanceof BetaToolUseBlock && $block->name === GradingPrompt::SUBMIT_TOOL_NAME) {
                return $block;
            }
        }

        return null;
    }

    /**
     * @param  array{input: int, output: int, search: int}  $usage
     */
    private function addUsage(array &$usage, BetaMessage $response): void
    {
        $usage['input'] += $response->usage->inputTokens;
        $usage['output'] += $response->usage->outputTokens;
        $usage['search'] += $response->usage->serverToolUse?->webSearchRequests ?? 0;
    }
}
