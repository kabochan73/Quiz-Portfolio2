{{--
    問題詳細(screens.md 2.8)。問題文の全文を表示する。
    下に「最近の点数」(採点済みの直近5回分)を並べ、各行からその回の結果画面へ移れるようにする。
--}}
<x-layouts.app :title="'問題 '.$number" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $question->section->category->name, 'href' => route('categories.show', $question->section->category)],
    ['label' => $question->section->name, 'href' => route('sections.show', $question->section)],
    ['label' => '問題 '.$number],
]">
    <x-ui.page-header :title="'問題 '.$number">
        <x-slot:meta>セクション: {{ $question->section->name }}</x-slot:meta>
        <x-slot:actions>
            <x-ui.dropdown label="問題の操作">
                <x-ui.dropdown-item href="{{ route('questions.edit', $question) }}">編集</x-ui.dropdown-item>
                <x-ui.dropdown-item danger x-on:click="$dispatch('open-modal', 'delete-question')">削除</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- design-guide.md 7.6: 一緒に消えるデータの件数を明示する --}}
    <x-ui.confirm-modal name="delete-question" title="問題を削除しますか?" :action="route('questions.destroy', $question)">
        この問題と、回答の履歴{{ $answerCount }}件も削除されます。この操作は取り消せません。
    </x-ui.confirm-modal>

    {{-- 改行を保持して全文を表示する(design-guide.md 3.2) --}}
    <x-ui.card>
        <p class="text-body whitespace-pre-wrap text-zinc-900">{{ $question->body }}</p>
    </x-ui.card>

    <h2 class="mt-8 mb-3 text-lg font-semibold text-zinc-900">最近の点数</h2>

    @if ($recentAnswers->isEmpty())
        <p class="text-sm text-zinc-500">まだ採点された回答がありません</p>
    @else
        <x-ui.card :padding="false" class="divide-y divide-zinc-200">
            @foreach ($recentAnswers as $answer)
                <a href="{{ route('history.show', [$answer->attempt->section_id, $answer->attempt]) }}"
                    class="flex min-h-11 items-center gap-3 px-4 py-3 text-sm transition-colors first:rounded-t-xl last:rounded-b-xl hover:bg-zinc-50 focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none lg:px-5">
                    <span class="w-12 shrink-0 tabular-nums text-zinc-500">{{ $answer->created_at->format('n/j') }}</span>
                    <x-ui.score-badge :score="$answer->score->score" />
                    <span class="text-zinc-700">{{ $answer->attempt->grading_level->label() }}</span>
                    @if ($answer->attempt->mode === App\Enums\AttemptMode::Weak)
                        <x-ui.badge>{{ $answer->attempt->mode->label() }}</x-ui.badge>
                    @endif
                    <x-icon name="chevron-right" class="ml-auto size-4 text-zinc-500" />
                </a>
            @endforeach
        </x-ui.card>
    @endif
</x-layouts.app>
