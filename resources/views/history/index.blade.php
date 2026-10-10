{{--
    履歴一覧(screens.md 2.11)。セクションの挑戦を新しい順に並べ、各行を結果画面(履歴詳細)へのリンクにする。
    採点が終わった挑戦は平均点、終わっていない挑戦は状態のバッジを右端に出す。
--}}
<x-layouts.app title="履歴" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
    ['label' => $section->name, 'href' => route('sections.show', $section)],
    ['label' => '履歴'],
]">
    <x-ui.page-header title="履歴">
        <x-slot:meta>{{ $section->name }}・{{ $total }}件</x-slot:meta>
    </x-ui.page-header>

    @if ($attempts->isEmpty())
        <x-ui.empty-state icon="clock" title="まだ挑戦していません" description="セクションの問題に回答すると、ここに履歴が残ります。">
            <x-slot:action>
                <x-ui.button href="{{ route('answers.create', $section) }}">全問に回答する</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <x-ui.card :padding="false" class="divide-y divide-zinc-200">
            @foreach ($attempts as $attempt)
                <a href="{{ route('history.show', [$section, $attempt]) }}"
                    class="flex min-h-11 flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3 text-sm transition-colors first:rounded-t-xl last:rounded-b-xl hover:bg-zinc-50 focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none lg:px-5">
                    <span class="tabular-nums text-zinc-500">{{ $attempt->created_at->format('n/j H:i') }}</span>
                    <x-ui.badge>{{ $attempt->mode->label() }}</x-ui.badge>
                    <span class="text-zinc-700">{{ $attempt->grading_level->label() }}・{{ $attempt->answers_count }}問</span>

                    <span class="ml-auto flex items-center gap-2">
                        @if ($attempt->status === App\Enums\AttemptStatus::Completed)
                            {{-- 平均点は一覧の問い合わせでまとめて求めた値(scores_avg_score)。小数第1位まで --}}
                            <x-ui.score-badge :score="$attempt->scores_avg_score === null ? null : round($attempt->scores_avg_score, 1)" />
                        @else
                            <x-ui.status-badge :status="$attempt->status" />
                        @endif
                        <x-icon name="chevron-right" class="size-4 text-zinc-500" />
                    </span>
                </a>
            @endforeach
        </x-ui.card>

        {{-- 21件以上あるときのページ送り --}}
        @if ($attempts->hasPages())
            <div class="mt-4 flex items-center justify-between gap-2">
                @if ($attempts->previousPageUrl())
                    <x-ui.button variant="secondary" href="{{ $attempts->previousPageUrl() }}">新しい履歴へ</x-ui.button>
                @else
                    <span></span>
                @endif
                @if ($attempts->nextPageUrl())
                    <x-ui.button variant="secondary" href="{{ $attempts->nextPageUrl() }}">さらに古い履歴</x-ui.button>
                @endif
            </div>
        @endif
    @endif
</x-layouts.app>
