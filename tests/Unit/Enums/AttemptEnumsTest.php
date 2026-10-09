<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;

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

it('種別の日本語ラベルを返す', function (AttemptMode $mode, string $label) {
    expect($mode->label())->toBe($label);
})->with([
    [AttemptMode::All, '全問'],
    [AttemptMode::Weak, '苦手'],
]);
