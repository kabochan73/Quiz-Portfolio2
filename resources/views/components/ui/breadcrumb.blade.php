{{--
    パンくずリスト(design-guide.md 7.7、screens.md 1.3)。レイアウトが本文の一番上に表示する。
    - 最後の項目は現在地。リンクにせず濃い文字にし、aria-current="page" を付ける
    - スマホでは途中を省略して「… › 現在地」にする。「…」は1つ上の階層へのリンク

    items は [['label' => '表示名', 'href' => 'URL'], ..., ['label' => '現在地']] の形。
--}}
@props([
    'items' => [],
])

@php
    $current = end($items);
    $ancestors = array_slice($items, 0, -1);
    $parent = end($ancestors);

    $linkClasses = 'rounded transition-colors hover:text-zinc-900 '
        .'focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-brand-200';
@endphp

<nav aria-label="パンくずリスト" {{ $attributes }}>
    <ol class="flex min-w-0 items-center gap-1.5 text-sm text-zinc-500">
        @if ($parent)
            {{-- スマホ: 途中を「…」にまとめ、1つ上の階層へ戻れるようにする --}}
            <li class="flex items-center gap-1.5 lg:hidden">
                <a href="{{ $parent['href'] }}" aria-label="{{ $parent['label'] }}に戻る"
                    class="-mx-2 inline-flex min-h-11 items-center px-2 {{ $linkClasses }}">…</a>
                <span aria-hidden="true">›</span>
            </li>
        @endif

        @foreach ($ancestors as $item)
            <li class="hidden items-center gap-1.5 lg:flex">
                <a href="{{ $item['href'] }}" class="{{ $linkClasses }}">{{ $item['label'] }}</a>
                <span aria-hidden="true">›</span>
            </li>
        @endforeach

        <li class="min-w-0">
            <span aria-current="page" class="block truncate text-zinc-900">{{ $current['label'] }}</span>
        </li>
    </ol>
</nav>
