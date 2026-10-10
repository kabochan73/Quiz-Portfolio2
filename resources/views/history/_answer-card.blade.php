{{--
    結果画面の、問題ごとのカード(screens.md 2.10 (b))。見出し行を押すと開閉する。
    最初は苦手の基準点(60点)未満の問題だけ開いておき、見直すべき問題に目が行くようにする。
    点数の横に、その問題の前回の点数との差(▲16 など)を出す。

    受け取る値: $answer(question と score を読み込み済み)、$number(何問目か)、
              $previousScore(その問題の前回の点数。初回・失敗の画面では null または渡さない)
--}}
@php
    $score = $answer->score;
    $initiallyOpen = $score !== null && $score->score < config('quiz.weak_threshold');
@endphp

<x-ui.card :padding="false" x-data="{ open: {{ $initiallyOpen ? 'true' : 'false' }} }">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
        class="flex min-h-11 w-full items-center gap-3 rounded-xl px-4 py-3 text-left transition-colors hover:bg-zinc-50 focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none lg:px-5">
        <span class="text-sm font-medium text-zinc-900">問題 {{ $number }}</span>
        <span class="ml-auto flex items-center gap-2">
            <x-ui.score-badge :score="$score?->score" size="lg" />
            @if ($score)
                <x-ui.score-delta :current="$score->score" :previous="$previousScore ?? null" />
            @endif
        </span>
        <x-icon name="chevron-right" class="size-4 text-zinc-500 transition-transform" ::class="open && 'rotate-90'" />
    </button>

    <div x-show="open" x-collapse @if (! $initiallyOpen) x-cloak @endif>
        <div class="space-y-5 border-t border-zinc-200 px-4 py-4 lg:px-5">
            <p class="text-body whitespace-pre-wrap text-zinc-900">{{ $answer->question->body }}</p>

            <div>
                <p class="mb-1 text-xs font-medium text-zinc-500">あなたの回答</p>
                <blockquote class="text-body border-l-2 border-zinc-200 pl-3 whitespace-pre-wrap text-zinc-700">{{ $answer->body }}</blockquote>
            </div>

            @if ($score)
                <dl class="space-y-4">
                    <div>
                        <dt class="mb-1 text-xs font-medium text-emerald-700">良い点</dt>
                        <dd class="text-body whitespace-pre-wrap text-zinc-700">{{ $score->good_points }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-xs font-medium text-rose-700">改善点</dt>
                        <dd class="text-body whitespace-pre-wrap text-zinc-700">{{ $score->improvements }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-xs font-medium text-brand-700">改善例</dt>
                        <dd class="text-body border-l-2 border-zinc-200 pl-3 whitespace-pre-wrap text-zinc-700">{{ $score->example }}</dd>
                    </div>
                </dl>
            @endif
        </div>
    </div>
</x-ui.card>
