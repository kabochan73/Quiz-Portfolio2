{{--
    カード(design-guide.md 7.3)
    - href を渡すと、カード全体が <a> になり、ホバー時に枠と背景の色が変わる
    - :padding="false" にすると内側の余白をなくす。中に行を並べて区切り線で分ける一覧
      (screens.md 2.4 のセクション一覧など)で、行ごとに余白を持たせたいときに使う
--}}
@props([
    'href' => null,
    'padding' => true,
])

@php
    $classes = 'block rounded-xl border border-zinc-200 bg-white shadow-xs';

    if ($padding) {
        $classes .= ' p-4 lg:p-5';
    }

    if ($href) {
        $classes .= ' transition-colors hover:border-zinc-300 hover:bg-zinc-50 '
            .'focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-brand-200';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->class($classes) }}>{{ $slot }}</div>
@endif
