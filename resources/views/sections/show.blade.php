{{--
    セクション詳細(screens.md 2.6)。問題を1枚のカードに行として並べる。
    まだ置いていないもの(リンク先がないため):
    - 「+ 問題を追加」と、行から問題詳細へのリンク → implementation-plan.md 4-3
    - 「全問に回答する」「苦手だけ再挑戦」「履歴を見る」の操作エリア → フェーズ5・6
    - 問題ごとの最新点数のバッジ → implementation-plan.md 6-4
--}}
<x-layouts.app :title="$section->name" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
    ['label' => $section->name],
]">
    <x-ui.page-header :title="$section->name">
        <x-slot:meta>{{ $questions->count() }} / {{ $maxQuestions }}問</x-slot:meta>
        <x-slot:actions>
            <x-ui.dropdown label="セクションの操作">
                <x-ui.dropdown-item href="{{ route('sections.edit', $section) }}">編集</x-ui.dropdown-item>
                <x-ui.dropdown-item danger x-on:click="$dispatch('open-modal', 'delete-section')">削除</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- design-guide.md 7.6: 一緒に消えるデータの件数を明示する --}}
    <x-ui.confirm-modal name="delete-section" title="セクションを削除しますか?" :action="route('sections.destroy', $section)">
        「{{ $section->name }}」と、その中の問題{{ $questions->count() }}件・履歴{{ $attemptCount }}件もまとめて削除されます。この操作は取り消せません。
    </x-ui.confirm-modal>

    <h2 class="mb-3 text-lg font-semibold text-zinc-900">問題</h2>

    @if ($questions->isEmpty())
        <x-ui.empty-state icon="document-text" title="まだ問題がありません" description="最初の問題を追加しましょう。" />
    @else
        <x-ui.card :padding="false" class="divide-y divide-zinc-200">
            @foreach ($questions as $question)
                <div class="flex min-h-11 items-center gap-3 px-4 py-3 text-sm lg:px-5">
                    <span class="w-5 shrink-0 text-right tabular-nums text-zinc-500">{{ $loop->iteration }}</span>
                    <span class="min-w-0 flex-1 truncate text-zinc-900">{{ $question->excerpt() }}</span>
                </div>
            @endforeach
        </x-ui.card>
    @endif
</x-layouts.app>
