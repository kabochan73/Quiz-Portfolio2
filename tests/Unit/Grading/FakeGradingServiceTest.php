<?php

use App\Enums\GradingLevel;
use App\Models\Answer;
use App\Services\Grading\FakeGradingService;
use App\Services\Grading\Grade;
use App\Services\Grading\GradingResult;
use Illuminate\Support\Collection;

/**
 * DB に保存しない回答(position だけを持つ)を、指定した数だけ作る。
 */
function unsavedAnswers(int $count): Collection
{
    return collect(range(0, $count - 1))->map(fn (int $position) => new Answer(['position' => $position]));
}

it('渡した回答の数だけ、position ごとに採点結果を返す', function () {
    $result = (new FakeGradingService)->grade(unsavedAnswers(4), GradingLevel::Normal);

    expect($result)->toBeInstanceOf(GradingResult::class)
        ->and(array_keys($result->grades))->toBe([0, 1, 2, 3])
        ->and($result->gradeFor(2))->toBeInstanceOf(Grade::class);
});

it('点数は 85 → 72 → 45 を繰り返し、緑・黄・赤がそろう', function () {
    $result = (new FakeGradingService)->grade(unsavedAnswers(4), GradingLevel::Normal);

    $scores = array_map(fn (Grade $grade) => $grade->score, $result->grades);

    expect($scores)->toBe([85, 72, 45, 85]);
});

it('フィードバックの3項目がすべて入り、フェイクであることが分かる', function () {
    $grade = (new FakeGradingService)->grade(unsavedAnswers(1), GradingLevel::Normal)->gradeFor(0);

    expect($grade->goodPoints)->toContain('フェイク')
        ->and($grade->improvements)->not->toBeEmpty()
        ->and($grade->example)->not->toBeEmpty();
});

it('モデル名は fake で、利用量は分からないので null にする', function () {
    $result = (new FakeGradingService)->grade(unsavedAnswers(1), GradingLevel::Normal);

    expect($result->model)->toBe('fake')
        ->and($result->inputTokens)->toBeNull()
        ->and($result->outputTokens)->toBeNull()
        ->and($result->webSearchRequests)->toBeNull();
});

it('存在しない順番の採点結果を求めるとエラーにする', function () {
    (new FakeGradingService)->grade(unsavedAnswers(2), GradingLevel::Normal)->gradeFor(5);
})->throws(OutOfBoundsException::class, '5番目の回答の採点結果がありません。');
