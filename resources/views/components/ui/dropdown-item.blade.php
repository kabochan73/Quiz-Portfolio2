{{--
    「…」メニューの1項目(design-guide.md 7.10)。<x-ui.dropdown> の中で使う。
    - href を渡すと <a>(編集画面への移動など)、渡さないと <button>(確認モーダルを開くなど)
    - danger を付けると赤字にする(削除など、取り消せない操作)
--}}
@props([
    'href' => null,
    'danger' => false,
])

@php
    $classes = 'flex min-h-11 w-full items-center rounded-lg px-3 text-left text-sm transition-colors lg:min-h-9 '
        .'focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none '
        .($danger ? 'text-rose-600 hover:bg-rose-50' : 'text-zinc-700 hover:bg-zinc-100');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
