<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Section;

/**
 * 回答の履歴を増やしすぎないための整理(requirements.md 3.4、architecture.md 4.5)。
 *
 * - 同じ問題の回答は、新しいものから config('quiz.answer_retention_limit') 件(既定10件)だけ残し、
 *   それより古いものは削除する(採点結果は外部キーの cascade で一緒に消える)
 * - 削除によって回答が1件もなくなった挑戦も削除する(中身のない履歴を残さないため)
 *
 * 採点中・失敗した挑戦の回答も件数に数える。呼び出し側のトランザクションの中で使う。
 */
class AnswerRetentionService
{
    /**
     * @param  iterable<int>  $questionIds  今回回答した問題の ID(どれも $section の問題)
     */
    public function prune(Section $section, iterable $questionIds): void
    {
        $limit = config('quiz.answer_retention_limit');

        foreach ($questionIds as $questionId) {
            // 同じ時刻に作られた回答もあるので、ID でも並べて「新しい順」を一意に決める
            $keepIds = Answer::query()
                ->where('question_id', $questionId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id');

            Answer::query()
                ->where('question_id', $questionId)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }

        $this->deleteEmptyAttempts($section);
    }

    /**
     * 回答が1件もない挑戦を削除する。古い回答の整理のあとと、問題を削除したあとに使う。
     */
    public function deleteEmptyAttempts(Section $section): void
    {
        $section->attempts()->doesntHave('answers')->delete();
    }
}
