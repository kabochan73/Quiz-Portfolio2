<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;

it('採点状態の日本語ラベルを返す', function (AttemptStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [AttemptStatus::Pending, '待機中'],
    [AttemptStatus::Grading, '採点中'],
    [AttemptStatus::Completed, '完了'],
    [AttemptStatus::Failed, '失敗'],
]);

it('待機中と採点中だけを「採点が終わっていない」とみなす', function (AttemptStatus $status, bool $inProgress) {
    expect($status->isInProgress())->toBe($inProgress);
})->with([
    [AttemptStatus::Pending, true],
    [AttemptStatus::Grading, true],
    [AttemptStatus::Completed, false],
    [AttemptStatus::Failed, false],
]);

it('採点レベルの日本語ラベルを返す', function (GradingLevel $level, string $label) {
    expect($level->label())->toBe($label);
})->with([
    [GradingLevel::Easy, '優しい'],
    [GradingLevel::Normal, '普通'],
    [GradingLevel::Hard, '厳しい'],
]);

it('採点レベルごとに異なる採点方針の文言を返す', function () {
    $policies = array_map(fn (GradingLevel $level) => $level->policyText(), GradingLevel::cases());

    expect(array_unique($policies))->toHaveCount(3)
        ->and(GradingLevel::Easy->policyText())->toContain('優しめ')
        ->and(GradingLevel::Hard->policyText())->toContain('厳しめ');
});

it('種別の日本語ラベルを返す', function (AttemptMode $mode, string $label) {
    expect($mode->label())->toBe($label);
})->with([
    [AttemptMode::All, '全問'],
    [AttemptMode::Weak, '苦手'],
]);
