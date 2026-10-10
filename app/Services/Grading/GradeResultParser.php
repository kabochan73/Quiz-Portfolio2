<?php

namespace App\Services\Grading;

use App\Models\Answer;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Claude が submit_grades ツールで提出した採点結果を検証し、position ごとの Grade に変換する(architecture.md 4.4)。
 *
 * 次のどれかに当てはまれば不正な結果として例外を投げる(採点 Job が再試行する):
 * - grades が配列でない / 件数が問題数と合わない
 * - position に重複・抜け・余分がある
 * - フィードバックに空の項目がある
 *
 * 点数が 0〜100 の範囲外のときは、例外にせず範囲内に収める(DB の CHECK 制約でも弾かれるが、
 * 1問の点数のずれのために全体をやり直すほどではないため)。
 */
class GradeResultParser
{
    private const FEEDBACK_FIELDS = ['good_points', 'improvements', 'example'];

    /**
     * @param  array<string, mixed>  $input  submit_grades の引数(tool_use の input)
     * @param  Collection<int, Answer>  $answers  採点を頼んだ回答
     * @return array<int, Grade> position をキーにした採点結果
     *
     * @throws UnexpectedValueException
     */
    public function parse(array $input, Collection $answers): array
    {
        $grades = $input['grades'] ?? null;

        if (! is_array($grades)) {
            throw new UnexpectedValueException('採点結果に grades がありません。');
        }

        if (count($grades) !== $answers->count()) {
            throw new UnexpectedValueException(sprintf(
                '採点結果の件数(%d件)が問題数(%d問)と合いません。', count($grades), $answers->count(),
            ));
        }

        $expectedPositions = $answers->pluck('position')->map(fn ($position) => (int) $position)->sort()->values()->all();
        $receivedPositions = collect($grades)->pluck('position')->map(fn ($position) => (int) $position)->sort()->values()->all();

        if ($expectedPositions !== $receivedPositions) {
            throw new UnexpectedValueException('採点結果の position が問題と対応していません(重複・抜けがあります)。');
        }

        $result = [];

        foreach ($grades as $grade) {
            foreach (self::FEEDBACK_FIELDS as $field) {
                if (trim((string) ($grade[$field] ?? '')) === '') {
                    throw new UnexpectedValueException("{$grade['position']}番目の採点結果の {$field} が空です。");
                }
            }

            $result[(int) $grade['position']] = new Grade(
                score: max(0, min(100, (int) $grade['score'])),
                goodPoints: trim($grade['good_points']),
                improvements: trim($grade['improvements']),
                example: trim($grade['example']),
            );
        }

        ksort($result);

        return $result;
    }
}
