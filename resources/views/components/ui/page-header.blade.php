{{--
    ページ見出し(screens.md 1.3)。左に見出しと補足情報、右に操作ボタンを置く。
    スマホで幅が足りないときは、ボタンを見出しの下に回り込ませる(flex-wrap)。

    例:
    <x-ui.page-header title="ネットワーク">
        <x-slot:meta>8 / 10問・最終挑戦 10/7</x-slot:meta>
        <x-slot:actions><x-ui.button href="...">+ 問題を追加</x-ui.button></x-slot:actions>
    </x-ui.page-header>
--}}
@props([
    'title',
])

<div {{ $attributes->class('mb-6 flex flex-wrap items-start justify-between gap-x-4 gap-y-3') }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight break-words text-zinc-900">{{ $title }}</h1>
        @isset($meta)
            <p class="mt-1 text-sm text-zinc-500">{{ $meta }}</p>
        @endisset
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
