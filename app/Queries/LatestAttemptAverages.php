<?php

namespace App\Queries;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use Illuminate\Support\Facades\DB;

/**
 * セクションごとに、直近の「全問」の採点済みの挑戦の平均点を求める(screens.md 2.4 のセクションの行の「平均」)。
 *
 * - 苦手モードの挑戦は苦手な問題だけに回答しているので、平均が低く出る。セクションの実力を表す数字としては、
 *   全問に回答した挑戦の平均を使う
 * - 採点中・失敗の挑戦は除く。全問で採点済みの挑戦がないセクションは結果に含めない
 * - PostgreSQL の DISTINCT ON で、セクションがいくつあっても1回の問い合わせで全セクション分を求める
 */
class LatestAttemptAverages
{
    /**
     * @param  iterable<int>  $sectionIds
     * @return array<int, float> セクション ID => 平均点(小数第1位まで)
     */
    public function forSections(iterable $sectionIds): array
    {
        $ids = collect($sectionIds)->all();

        if ($ids === []) {
            return [];
        }

        return DB::table('attempts')
            ->select('attempts.section_id')
            // 挑戦ごとの平均点(その挑戦の回答の採点結果の平均)
            ->selectSub(
                DB::table('answers')
                    ->join('scores', 'scores.answer_id', '=', 'answers.id')
                    ->whereColumn('answers.attempt_id', 'attempts.id')
                    ->selectRaw('avg(scores.score)'),
                'average',
            )
            ->where('attempts.status', AttemptStatus::Completed->value)
            ->where('attempts.mode', AttemptMode::All->value)
            ->whereIn('attempts.section_id', $ids)
            // セクションごとに1行。どの1行かは下の並び順で決まる(セクションごとに一番新しい挑戦)
            ->distinct('attempts.section_id')
            ->orderBy('attempts.section_id')
            ->orderByDesc('attempts.created_at')
            ->orderByDesc('attempts.id')
            ->get()
            ->filter(fn ($row) => $row->average !== null)
            ->mapWithKeys(fn ($row) => [$row->section_id => round((float) $row->average, 1)])
            ->all();
    }
}
