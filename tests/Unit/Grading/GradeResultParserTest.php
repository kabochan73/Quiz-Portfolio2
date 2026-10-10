<?php

use App\Models\Answer;
use App\Services\Grading\Grade;
use App\Services\Grading\GradeResultParser;
use Illuminate\Support\Collection;

/**
 * position だけを持つ、DB に保存しない回答を作る。
 */
function answersAt(array $positions): Collection
{
    return collect($positions)->map(fn (int $position) => new Answer(['position' => $position]));
}

function gradeInput(int $position, mixed $score = 80, array $overrides = []): array
{
    return array_merge([
        'position' => $position,
        'score' => $score,
        'good_points' => '良い点',
        'improvements' => '改善点',
        'example' => '改善例',
    ], $overrides);
}

it('正しい採点結果を、position をキーにした Grade に変換する', function () {
    $grades = (new GradeResultParser)->parse(
        ['grades' => [gradeInput(1, 45), gradeInput(0, 85)]],   // 順番が入れ替わっていてもよい
        answersAt([0, 1]),
    );

    expect(array_keys($grades))->toBe([0, 1])
        ->and($grades[0])->toBeInstanceOf(Grade::class)
        ->and($grades[0]->score)->toBe(85)
        ->and($grades[1]->score)->toBe(45)
        ->and($grades[1]->improvements)->toBe('改善点');
});

it('フィードバックの前後の空白は取り除く', function () {
    $grades = (new GradeResultParser)->parse(
        ['grades' => [gradeInput(0, 80, ['good_points' => "  要点を押さえています。\n"])]],
        answersAt([0]),
    );

    expect($grades[0]->goodPoints)->toBe('要点を押さえています。');
});

it('点数が 0〜100 の範囲外なら、範囲内に収める', function (int $score, int $expected) {
    $grades = (new GradeResultParser)->parse(['grades' => [gradeInput(0, $score)]], answersAt([0]));

    expect($grades[0]->score)->toBe($expected);
})->with([
    '101点 → 100点' => [101, 100],
    '-5点 → 0点' => [-5, 0],
]);

it('grades がない・配列でなければ不正な結果とする', function (array $input) {
    (new GradeResultParser)->parse($input, answersAt([0]));
})->with([
    'grades がない' => [[]],
    '文字列' => [['grades' => '[{"position":0}]']],
])->throws(UnexpectedValueException::class, 'grades がありません');

it('件数が問題数と合わなければ不正な結果とする', function () {
    (new GradeResultParser)->parse(['grades' => [gradeInput(0)]], answersAt([0, 1]));
})->throws(UnexpectedValueException::class, '採点結果の件数(1件)が問題数(2問)と合いません。');

it('position に重複・抜けがあれば不正な結果とする', function (array $grades) {
    (new GradeResultParser)->parse(['grades' => $grades], answersAt([0, 1]));
})->with([
    '重複' => [[gradeInput(0), gradeInput(0)]],
    '存在しない position' => [[gradeInput(0), gradeInput(5)]],
])->throws(UnexpectedValueException::class, 'position が問題と対応していません');

it('フィードバックに空の項目があれば不正な結果とする', function (string $field) {
    (new GradeResultParser)->parse(['grades' => [gradeInput(0, 80, [$field => '   '])]], answersAt([0]));
})->with(['good_points', 'improvements', 'example'])
    ->throws(UnexpectedValueException::class, 'が空です');
