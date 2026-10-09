{{--
    カテゴリ詳細(screens.md 2.4)。中のセクションを1枚のカードに行として並べる。
    まだ置いていないもの(リンク先がないため):
    - 「+ セクションを追加」と、行からセクション詳細へのリンク → implementation-plan.md 4-2
    - 見出しの「…」メニュー(編集・削除) → implementation-plan.md 4-1 の続き
    - 行ごとの平均点・最終挑戦日 → implementation-plan.md 6-4
--}}
<x-layouts.app :title="$category->name" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $category->name],
]">
    <x-ui.page-header :title="$category->name">
        <x-slot:meta>{{ $sections->count() }}セクション</x-slot:meta>
    </x-ui.page-header>

    <h2 class="mb-3 text-lg font-semibold text-zinc-900">セクション</h2>

    @if ($sections->isEmpty())
        <x-ui.empty-state icon="folder" title="まだセクションがありません" description="セクションを追加して、問題を登録しましょう。" />
    @else
        <x-ui.card :padding="false" class="divide-y divide-zinc-200">
            @foreach ($sections as $section)
                <div class="flex min-h-11 items-center gap-3 px-4 py-3 text-sm lg:px-5">
                    <span class="min-w-0 flex-1 truncate font-medium text-zinc-900">{{ $section->name }}</span>
                    <span class="shrink-0 tabular-nums text-zinc-500">{{ $section->questions_count }}問</span>
                </div>
            @endforeach
        </x-ui.card>
    @endif
</x-layouts.app>
