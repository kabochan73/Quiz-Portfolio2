<?php

namespace App\Jobs;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Services\Grading\GradingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 1つの挑戦の回答をまとめて採点する Job(requirements.md 3.3、architecture.md 4.3)。
 *
 * v1 は回答を送ったリクエストの中で採点していたため、採点が長引くと画面がタイムアウトしていた。
 * v2 では回答を保存したらすぐ結果画面へ移り、採点はこの Job で worker が裏で行う。
 *
 * 状態の移り変わり: pending →(着手)→ grading →(成功)→ completed
 *                                              →(3回とも失敗)→ failed
 */
class GradeAttempt implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    // Claude API の一時的な失敗(通信エラー・混雑など)を吸収するため、3回まで試す
    public int $tries = 3;

    // 1回の試行の制限時間。Claude の呼び出し2回分(architecture.md 4.4)+ 余裕。
    // config/queue.php の retry_after はこれより長くしておく必要がある
    public int $timeout = 240;

    public function __construct(public Attempt $attempt) {}

    /**
     * 再試行までの待ち時間(秒)。2回目は15秒後、3回目は60秒後。
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [15, 60];
    }

    /**
     * 同じ挑戦の Job が同時に2つ動かないよう、挑戦の ID で重複を防ぐ(ShouldBeUnique)。
     */
    public function uniqueId(): string
    {
        return (string) $this->attempt->id;
    }

    public function handle(GradingService $grader): void
    {
        // 採点待ち・採点中のときだけ「採点中」にする。すでに完了・失敗していれば何もしない(二重に採点しない)。
        // 再試行(2回目以降)は「採点中」から始まるので、grading も対象に含める
        $started = Attempt::query()
            ->whereKey($this->attempt->id)
            ->whereIn('status', [AttemptStatus::Pending, AttemptStatus::Grading])
            ->update(['status' => AttemptStatus::Grading]);

        if ($started === 0) {
            return;
        }

        $answers = $this->attempt->answers()->with('question')->get();

        // 時間のかかる API の呼び出しは、トランザクションの外で行う(DB をロックしたまま待たないように)
        $result = $grader->grade($answers, $this->attempt->grading_level);

        // 採点結果の保存と「完了」への更新は、どちらかだけが反映されることのないよう1つのトランザクションで行う
        DB::transaction(function () use ($answers, $result) {
            foreach ($answers as $answer) {
                // 結果が足りなければ例外になり、ここまでの保存も取り消される(→ Job の再試行へ)
                $grade = $result->gradeFor($answer->position);

                $answer->score()->create([
                    'score' => $grade->score,
                    'good_points' => $grade->goodPoints,
                    'improvements' => $grade->improvements,
                    'example' => $grade->example,
                ]);
            }

            $this->attempt->update([
                'status' => AttemptStatus::Completed,
                'graded_at' => now(),
                'model' => $result->model,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'web_search_requests' => $result->webSearchRequests,
            ]);
        });
    }

    /**
     * 3回とも失敗したときに Laravel が呼ぶ。挑戦を「失敗」にし、結果画面から再採点できるようにする。
     * 失敗の理由は画面には出さず、ログと error_message に残す(architecture.md 2.4)。
     */
    public function failed(?Throwable $exception): void
    {
        Attempt::query()->whereKey($this->attempt->id)->update([
            'status' => AttemptStatus::Failed,
            'error_message' => $exception?->getMessage(),
        ]);

        Log::error('採点に失敗しました', [
            'attempt_id' => $this->attempt->id,
            'exception' => $exception,
        ]);
    }
}
