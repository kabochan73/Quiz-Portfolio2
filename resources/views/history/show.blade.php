{{--
    結果画面 = 履歴詳細(screens.md 2.10)。挑戦の状態で表示を切り替える。
    まだ置いていないもの:
    - 失敗時の再採点ボタン → implementation-plan.md 5-4 の続き(C3)
    - 平均点の前回比 → 6-3 / 利用量・コスト → 5-6 / 「苦手だけ再挑戦」 → 6-5 / パンくずの「履歴」 → 6-2
--}}
@php
    use App\Enums\AttemptStatus;

    $weakThreshold = config('quiz.weak_threshold');
    $scores = $attempt->answers->map(fn ($answer) => $answer->score?->score)->filter(fn ($score) => $score !== null);
@endphp

<x-layouts.app title="採点結果" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
    ['label' => $section->name, 'href' => route('sections.show', $section)],
    ['label' => $attempt->created_at->format('n/j H:i')],
]">
    <x-ui.page-header title="採点結果">
        <x-slot:meta>{{ $attempt->created_at->format('n月j日 H:i') }}・{{ $attempt->answers->count() }}問</x-slot:meta>
        <x-slot:actions>
            <x-ui.badge>{{ $attempt->mode->label() }}</x-ui.badge>
            <x-ui.badge>{{ $attempt->grading_level->label() }}</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($attempt->status->isInProgress())
        {{-- 採点中(screens.md 2.10 (a))。3秒ごとに状態を確かめ、終わったら再読み込みする(resources/js/attempt-poller.js) --}}
        <div x-data="attemptPoller(@js(route('attempts.status', $attempt)))" class="space-y-3">
            <x-ui.card role="status" aria-live="polite">
                <div x-show="!timedOut">
                    <p class="text-base font-medium text-zinc-900">採点しています…</p>
                    <p class="mt-1 text-sm text-zinc-500">AIが{{ $attempt->answers->count() }}問の回答を確認しています。1分ほどかかることがあります。このページを離れても採点は続きます。</p>
                </div>
                <div x-show="timedOut" x-cloak>
                    <p class="text-base font-medium text-zinc-900">時間がかかっています</p>
                    <p class="mt-1 text-sm text-zinc-500">しばらくしてから、このページを再読み込みしてください。</p>
                </div>
            </x-ui.card>

            {{-- 結果カードの形を先に見せ、待っている間も画面が止まって見えないようにする --}}
            @foreach ($attempt->answers as $answer)
                <x-ui.card class="space-y-3">
                    <div class="flex items-center justify-between">
                        <x-ui.skeleton class="h-4 w-20" />
                        <x-ui.skeleton class="h-7 w-12 rounded-full" />
                    </div>
                    <x-ui.skeleton class="h-4 w-full" />
                    <x-ui.skeleton class="h-4 w-2/3" />
                </x-ui.card>
            @endforeach
        </div>
    @elseif ($attempt->status === AttemptStatus::Failed)
        <x-ui.card>
            <p class="text-base font-medium text-zinc-900">採点できませんでした</p>
            <p class="mt-1 text-sm text-zinc-500">AIの採点中にエラーが発生しました。</p>
        </x-ui.card>
    @else
        {{-- 平均点のカード(screens.md 2.10 (b)) --}}
        <x-ui.card class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-zinc-500">平均点</p>
                <p class="text-4xl font-semibold tabular-nums text-zinc-900">{{ $attempt->averageScore() ?? '—' }}</p>
            </div>
            <dl class="space-y-0.5 text-right text-sm text-zinc-500">
                <div><dt class="inline">80点以上</dt> <dd class="inline tabular-nums text-zinc-900">{{ $scores->filter(fn ($score) => $score >= 80)->count() }}問</dd></div>
                <div><dt class="inline">{{ $weakThreshold }}点未満</dt> <dd class="inline tabular-nums text-zinc-900">{{ $scores->filter(fn ($score) => $score < $weakThreshold)->count() }}問</dd></div>
            </dl>
        </x-ui.card>

        <div class="mt-4 mb-8 flex flex-wrap gap-2">
            <x-ui.button href="{{ route('answers.create', $section) }}">もう一度挑戦する</x-ui.button>
        </div>

        <div class="space-y-3">
            @foreach ($attempt->answers as $answer)
                @include('history._answer-card', ['answer' => $answer, 'number' => $loop->iteration])
            @endforeach
        </div>
    @endif
</x-layouts.app>
