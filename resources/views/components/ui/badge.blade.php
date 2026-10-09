{{--
    汎用バッジ(design-guide.md 7.4)。種別(全問 / 苦手)などの短いラベルに使う。
    tone: neutral(グレー)/ brand(青: 処理中)/ success(緑)/ danger(赤)

    例: <x-ui.badge>{{ $attempt->mode->label() }}</x-ui.badge>
--}}
@props([
    'tone' => 'neutral',
])

@php
    $toneClasses = match ($tone) {
        'neutral' => 'bg-zinc-100 text-zinc-600',
        'brand' => 'bg-brand-50 text-brand-700',
        'success' => 'bg-emerald-50 text-emerald-700',
        'danger' => 'bg-rose-50 text-rose-700',
    };
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap {$toneClasses}") }}>{{ $slot }}</span>
