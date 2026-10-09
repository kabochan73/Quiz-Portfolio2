{{--
    採点状態のバッジ(design-guide.md 2.3 / 7.4)。
    状態ごとの色の割り当て(見た目の都合)はここで持ち、Enum には日本語ラベルだけを持たせる。

    例: <x-ui.status-badge :status="$attempt->status" />
--}}
@props([
    'status',
])

@php
    $tone = match ($status) {
        \App\Enums\AttemptStatus::Pending, \App\Enums\AttemptStatus::Grading => 'brand',
        \App\Enums\AttemptStatus::Completed => 'success',
        \App\Enums\AttemptStatus::Failed => 'danger',
    };
@endphp

<x-ui.badge :tone="$tone" {{ $attributes }}>{{ $status->label() }}</x-ui.badge>
