<?php

namespace App\Queries;

use App\Models\Section;

/**
 * 苦手問題の判定(requirements.md 3.3、architecture.md 3.1)。
 * 苦手問題 = 最新の点数が基準点(config('quiz.weak_threshold')、既定 60点)未満の問題。
 * 一度も採点されていない問題は含めない。
 *
 * セクション詳細・回答フォーム・回答の保存・結果画面で、同じ判定を使うためにまとめている。
 */
class WeakQuestions
{
    public function __construct(private readonly LatestScores $latestScores) {}

    /**
     * セクションの苦手問題の ID を、作成順に返す。
     *
     * @return list<int>
     */
    public function ids(Section $section): array
    {
        $questionIds = $section->questions()->orderBy('id')->pluck('id');

        return $this->filter($this->latestScores->forQuestions($questionIds));
    }

    /**
     * すでに求めてある最新の点数から、苦手問題の ID を取り出す(同じ問い合わせを繰り返さないため)。
     *
     * @param  array<int, int>  $latestScores  問題 ID => 最新の点数
     * @return list<int>
     */
    public function filter(array $latestScores): array
    {
        $threshold = config('quiz.weak_threshold');

        return collect($latestScores)
            ->filter(fn (int $score) => $score < $threshold)
            ->keys()
            ->sort()
            ->values()
            ->all();
    }
}
