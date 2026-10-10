{{-- カテゴリ一覧(screens.md 2.2)。ログイン後の着地点 --}}
<x-layouts.app title="カテゴリ" :breadcrumbs="[['label' => 'カテゴリ']]">
    <x-ui.page-header title="カテゴリ">
        {{-- 0件のときは空の状態の中に同じボタンがあるので、見出し側には出さない --}}
        @if ($categories->isNotEmpty())
            <x-slot:actions>
                <x-ui.button href="{{ route('categories.create') }}">+ カテゴリを作成</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($categories->isEmpty())
        <x-ui.empty-state icon="folder" title="まだカテゴリがありません" description="最初のカテゴリを作りましょう。">
            <x-slot:action>
                <x-ui.button href="{{ route('categories.create') }}">カテゴリを作成</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        {{-- カード全体がカテゴリ詳細へのリンク --}}
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($categories as $category)
                <x-ui.card href="{{ route('categories.show', $category) }}">
                    <p class="truncate text-base font-medium text-zinc-900">{{ $category->name }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $category->sections_count }}セクション・{{ $category->questions_count }}問</p>
                    <p class="mt-0.5 text-xs text-zinc-500 tabular-nums">
                        @if ($category->attempts_max_created_at)
                            最終挑戦 {{ \Illuminate\Support\Carbon::parse($category->attempts_max_created_at)->format('n/j') }}
                        @else
                            まだ挑戦していません
                        @endif
                    </p>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</x-layouts.app>
