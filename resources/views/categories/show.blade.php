{{--
    カテゴリ詳細(screens.md 2.4)。中のセクションを1枚のカードに行として並べる。
    各行に、問題数・直近の全問の挑戦の平均点・最終挑戦日を出す(スマホでは最終挑戦日を2行目に回す)。
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

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-zinc-900">セクション</h2>
        {{-- 0件のときは空の状態の中に同じボタンがあるので、ここには出さない --}}
        @if ($sections->isNotEmpty())
            <x-ui.button href="{{ route('categories.sections.create', $category) }}">+ セクションを追加</x-ui.button>
        @endif
    </div>

    @if ($sections->isEmpty())
        <x-ui.empty-state icon="folder" title="まだセクションがありません" description="セクションを追加して、問題を登録しましょう。">
            <x-slot:action>
                <x-ui.button href="{{ route('categories.sections.create', $category) }}">セクションを追加</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        {{-- 各行がセクション詳細へのリンク --}}
        <x-ui.card :padding="false" class="divide-y divide-zinc-200">
            @foreach ($sections as $section)
                <a href="{{ route('sections.show', $section) }}"
                    class="flex min-h-11 items-center gap-3 px-4 py-3 text-sm transition-colors first:rounded-t-xl last:rounded-b-xl hover:bg-zinc-50 focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none lg:px-5">
                    @php
                        $lastAttempt = $section->attempts_max_created_at
                            ? '最終 '.\Illuminate\Support\Carbon::parse($section->attempts_max_created_at)->format('n/j')
                            : '未挑戦';
                    @endphp
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium text-zinc-900">{{ $section->name }}</span>
                        <span class="block text-xs text-zinc-500 tabular-nums sm:hidden">{{ $lastAttempt }}</span>
                    </span>
                    <span class="shrink-0 tabular-nums text-zinc-500">{{ $section->questions_count }}問</span>
                    <x-ui.score-badge :score="$averages[$section->id] ?? null" />
                    <span class="hidden w-16 shrink-0 text-right text-xs text-zinc-500 tabular-nums sm:inline">{{ $lastAttempt }}</span>
                    <x-icon name="chevron-right" class="size-4 text-zinc-500" />
                </a>
            @endforeach
        </x-ui.card>
    @endif
</x-layouts.app>
