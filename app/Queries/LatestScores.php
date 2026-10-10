<?php

namespace App\Queries;

use App\Enums\AttemptStatus;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * 問題ごとの「最新の点数」を求める(architecture.md 3.1 / 3.2)。
 *
 * - 採点が完了した挑戦の回答だけを対象にする(採点中・失敗の回答には点数がない)
 * - 一度も採点されていない問題は結果に含めない
 * - PostgreSQL の DISTINCT ON で、問題がいくつあっても1回の問い合わせで全問分を求める
 *
 * 使う場面:
 * - セクション詳細の点数バッジ、苦手問題の判定(最新の点数が基準点未満)
 * - 結果画面の前回比($before に挑戦の日時を渡し、それより前の最新の点数 = 前回の点数を求める)
 */
class LatestScores
{
    /**
     * @param  iterable<int>  $questionIds
     * @param  DateTimeInterface|null  $before  これより前に作られた回答だけを対象にする
     * @return array<int, int> 問題 ID => 最新の点数
     */
    public function forQuestions(iterable $questionIds, ?DateTimeInterface $before = null): array
    {
        $ids = collect($questionIds)->all();

        if ($ids === []) {
            return [];
        }

        return DB::table('answers')
            ->join('attempts', 'attempts.id', '=', 'answers.attempt_id')
            ->join('scores', 'scores.answer_id', '=', 'answers.id')
            ->where('attempts.status', AttemptStatus::Completed->value)
            ->whereIn('answers.question_id', $ids)
            ->when($before, fn ($query) => $query->where('answers.created_at', '<', $before))
            // 問題ごとに1行だけ取り出す。どの1行かは下の並び順で決まる(問題ごとに一番新しい回答)
            ->distinct('answers.question_id')
            ->orderBy('answers.question_id')
            ->orderByDesc('answers.created_at')
            ->orderByDesc('answers.id')
            ->pluck('scores.score', 'answers.question_id')
            ->map(fn ($score) => (int) $score)
            ->all();
    }
}
