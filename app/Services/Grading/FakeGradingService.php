<?php

namespace App\Services\Grading;

use App\Enums\GradingLevel;
use Illuminate\Support\Collection;

/**
 * Claude API を呼ばずに、決まった採点結果を返すフェイク(GRADING_DRIVER=fake)。
 * 画面の作り込みとテストで使い、API の料金をかけずに回答〜結果表示までを動かせるようにする。
 *
 * 点数は回答の順番で 85 → 72 → 45 を繰り返す。緑・黄・赤の点数バッジや、
 * 苦手問題(60点未満)の表示を画面で確かめられるようにするため。
 */
class FakeGradingService implements GradingService
{
    private const SCORES = [85, 72, 45];

    public function grade(Collection $answers, GradingLevel $level): GradingResult
    {
        $grades = $answers->mapWithKeys(fn ($answer) => [
            $answer->position => new Grade(
                score: self::SCORES[$answer->position % count(self::SCORES)],
                goodPoints: '(フェイクの採点)要点を押さえて説明できています。',
                improvements: '(フェイクの採点)具体例を1つ加えると、理解が伝わりやすくなります。',
                example: '(フェイクの採点)たとえば、次のような場面で使われます。',
            ),
        ])->all();

        // 利用量は分からないので null(画面ではコストを表示しない)
        return new GradingResult(grades: $grades, model: 'fake');
    }
}
