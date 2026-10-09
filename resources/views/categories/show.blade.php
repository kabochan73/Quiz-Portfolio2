{{--
    カテゴリ詳細(screens.md 2.4)。中のセクションを1枚のカードに行として並べる。
    まだ置いていないもの(リンク先がないため):
    - 「+ セクションを追加」と、行からセクション詳細へのリンク → implementation-plan.md 4-2
    - 行ごとの平均点・最終挑戦日 → implementation-plan.md 6-4
--}}
<x-layouts.app :title="$category->name" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $category->name],
]">
    <x-ui.page-header :title="$category->name">
        <x-slot:meta>{{ $sections->count() }}セクション</x-slot:meta>
        <x-slot:actions>
            <x-ui.dropdown label="カテゴリの操作">
                <x-ui.dropdown-item href="{{ route('categories.edit', $category) }}">編集</x-ui.dropdown-item>
                <x-ui.dropdown-item danger x-on:click="$dispatch('open-modal', 'delete-category')">削除</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- design-guide.md 7.6: 一緒に消えるデータの件数を明示する --}}
    <x-ui.confirm-modal name="delete-category" title="カテゴリを削除しますか?" :action="route('categories.destroy', $category)">
        「{{ $category->name }}」と、その中のセクション{{ $sections->count() }}件・問題{{ $questionCount }}件・履歴{{ $attemptCount }}件もまとめて削除されます。この操作は取り消せません。
    </x-ui.confirm-modal>

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
