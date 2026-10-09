{{--
    サイドバーの中身(screens.md 1.1)。PC では画面左に固定し、スマホではドロワーの中に同じものを出す。
    - 上にアプリ名、中央にカテゴリとセクションのナビゲーション
    - 現在のページが属するカテゴリは最初から開き、現在地は brand 色で強調する

    categories は次の形の配列。実データとのつなぎ込みは implementation-plan.md 4-4 で行う。
    [
        'name' => 'カテゴリ名', 'href' => '...', 'current' => 現在地か, 'open' => 最初から開くか,
        'sections' => [['name' => 'セクション名', 'href' => '...', 'current' => 現在地か], ...],
    ]
--}}
@props([
    'categories' => [],
])

@php
    // カテゴリの作成画面はフェーズ4で作る。それまではリンク先がないので # にしておく
    $createHref = Route::has('categories.create') ? route('categories.create') : '#';
    $homeHref = Route::has('categories.index') ? route('categories.index') : '#';

    $linkBase = 'flex min-h-11 flex-1 items-center rounded-md px-2 text-sm transition-colors lg:min-h-9 '
        .'focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-brand-200';
    $linkCurrent = 'bg-brand-50 font-medium text-brand-600';
@endphp

<div class="flex h-full flex-col">
    <div class="flex h-14 shrink-0 items-center px-5">
        <a href="{{ $homeHref }}" class="text-base font-semibold tracking-tight text-zinc-900">{{ config('app.name') }}</a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-4" aria-label="カテゴリとセクション">
        <div class="flex items-center justify-between py-1 pl-2">
            <span class="text-xs font-medium text-zinc-500">カテゴリ</span>
            <a href="{{ $createHref }}" aria-label="カテゴリを作成"
                class="inline-flex size-9 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900">
                <x-icon name="plus" class="size-4" />
            </a>
        </div>

        @if (count($categories) === 0)
            <p class="px-2 py-2 text-sm text-zinc-500">まだカテゴリがありません</p>
        @else
            <ul class="space-y-0.5">
                @foreach ($categories as $category)
                    <li x-data="{ open: @js($category['open'] ?? $category['current']) }">
                        <div class="flex items-center">
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                aria-label="{{ $category['name'] }}のセクションを表示"
                                class="inline-flex size-9 shrink-0 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100">
                                <x-icon name="chevron-right" class="size-4 transition-transform" ::class="open && 'rotate-90'" />
                            </button>
                            <a href="{{ $category['href'] }}"
                                @if ($category['current']) aria-current="page" @endif
                                @class([$linkBase, $linkCurrent => $category['current'], 'text-zinc-700 hover:bg-zinc-100' => ! $category['current']])>
                                <span class="truncate">{{ $category['name'] }}</span>
                            </a>
                        </div>

                        @if (count($category['sections']) > 0)
                            <ul x-show="open" x-collapse x-cloak class="ml-9 space-y-0.5 py-0.5">
                                @foreach ($category['sections'] as $section)
                                    <li>
                                        <a href="{{ $section['href'] }}"
                                            @if ($section['current']) aria-current="page" @endif
                                            @class([$linkBase, $linkCurrent => $section['current'], 'text-zinc-600 hover:bg-zinc-100' => ! $section['current']])>
                                            <span class="truncate">{{ $section['name'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>

    {{-- 一番下にログアウト(screens.md 1.1)。ログインしていない画面(見本ページなど)では出さない --}}
    @auth
        <form method="POST" action="{{ route('logout') }}" class="shrink-0 border-t border-zinc-200 p-3">
            @csrf
            <button type="submit"
                class="flex min-h-11 w-full items-center gap-2 rounded-md px-2 text-sm text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none lg:min-h-9">
                <x-icon name="arrow-right-start-on-rectangle" class="size-4" />
                ログアウト
            </button>
        </form>
    @endauth
</div>
