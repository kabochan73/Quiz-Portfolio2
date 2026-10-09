{{--
    ログイン後の全画面で使うレイアウト(screens.md 1章)
    - PC(lg 以上): 左に幅 256px のサイドバーを固定し、メイン領域だけをスクロールさせる
    - スマホ: 上部に ☰ とアプリ名のヘッダーを固定し、☰ でサイドバーをドロワーとして出す
    - narrow を付けると本文の最大幅を 640px にする(作成・編集フォーム用、design-guide.md 4.2)
    - breadcrumbs を渡すと本文の一番上にパンくずを出す。どのページでも同じ位置に出るよう、レイアウトで表示する

    サイドバーのカテゴリ一覧は、ビューに共有された $sidebarCategories から受け取る
    (実データの共有は implementation-plan.md 4-4 で View Composer を使って行う)。

    例: <x-layouts.app title="カテゴリ"> ... </x-layouts.app>
        <x-layouts.app title="カテゴリを作成" narrow :breadcrumbs="[
            ['label' => 'カテゴリ', 'href' => route('categories.index')],
            ['label' => '新規作成'],
        ]"> ... </x-layouts.app>
--}}
@props([
    'title' => null,
    'narrow' => false,
    'breadcrumbs' => [],
])

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- design-guide.md 8章: 「ページ名 | Quiz」の形 --}}
    <title>{{ $title ? "{$title} | " : '' }}{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh" x-data="{ drawerOpen: false }" @keydown.escape.window="drawerOpen = false">
    {{-- PC: 固定のサイドバー --}}
    <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-zinc-200 bg-white lg:block">
        <x-layout.sidebar :categories="$sidebarCategories ?? []" />
    </aside>

    {{-- スマホ: 上部に固定するヘッダー --}}
    <header class="sticky top-0 z-30 flex h-14 items-center gap-1 border-b border-zinc-200 bg-white px-2 lg:hidden">
        <button type="button" @click="drawerOpen = true" aria-label="メニューを開く" :aria-expanded="drawerOpen.toString()"
            class="inline-flex size-11 items-center justify-center rounded-lg text-zinc-600 transition-colors hover:bg-zinc-100">
            <x-icon name="bars-3" class="size-6" />
        </button>
        <span class="text-base font-semibold tracking-tight text-zinc-900">{{ config('app.name') }}</span>
    </header>

    {{-- スマホ: ドロワー
         x-trap で、開いている間はフォーカスを中に閉じ込め(.inert で外側を操作不可に)、背景のスクロールも止める(.noscroll)。
         背景のタップ・Esc キー・中のリンクの選択で閉じる。 --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="メニュー">
        <div x-show="drawerOpen" x-transition.opacity.duration.200ms @click="drawerOpen = false"
            class="absolute inset-0 bg-zinc-900/40"></div>

        <div x-show="drawerOpen" x-trap.inert.noscroll="drawerOpen"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
            @click="if ($event.target.closest('a')) drawerOpen = false"
            class="absolute inset-y-0 left-0 w-72 bg-white shadow-xl">
            <button type="button" @click="drawerOpen = false" aria-label="メニューを閉じる"
                class="absolute top-1.5 right-1.5 inline-flex size-11 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100">
                <x-icon name="x-mark" class="size-6" />
            </button>
            <x-layout.sidebar :categories="$sidebarCategories ?? []" />
        </div>
    </div>

    <div class="lg:pl-64">
        <main @class(['mx-auto px-4 py-6 lg:px-8 lg:py-10', 'max-w-2xl' => $narrow, 'max-w-3xl' => ! $narrow])>
            @if (count($breadcrumbs) > 0)
                <x-ui.breadcrumb :items="$breadcrumbs" class="mb-4" />
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
