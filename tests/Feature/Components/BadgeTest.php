<?php

use App\Enums\AttemptStatus;

it('汎用バッジは tone に応じた色になり、既定はグレー', function (?string $tone, string $expectedClass) {
    $blade = $tone === null
        ? '<x-ui.badge>全問</x-ui.badge>'
        : '<x-ui.badge :tone="$tone">全問</x-ui.badge>';

    $this->blade($blade, ['tone' => $tone])
        ->assertSee('全問')
        ->assertSee($expectedClass, false);
})->with([
    '既定' => [null, 'bg-zinc-100'],
    'brand' => ['brand', 'bg-brand-50'],
    'success' => ['success', 'bg-emerald-50'],
    'danger' => ['danger', 'bg-rose-50'],
]);

it('状態バッジは状態ごとのラベルと色で表示する', function (AttemptStatus $status, string $label, string $expectedClass) {
    $this->blade('<x-ui.status-badge :status="$status" />', ['status' => $status])
        ->assertSee($label)
        ->assertSee($expectedClass, false);
})->with([
    [AttemptStatus::Pending, '待機中', 'bg-brand-50'],
    [AttemptStatus::Grading, '採点中', 'bg-brand-50'],
    [AttemptStatus::Completed, '完了', 'bg-emerald-50'],
    [AttemptStatus::Failed, '失敗', 'bg-rose-50'],
]);
