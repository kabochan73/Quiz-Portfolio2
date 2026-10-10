{{--
    回答フォーム(screens.md 2.9)。全問、または苦手問題だけ(mode=weak)を並べ、まとめて送信する。
    進捗表示・下書きの自動保存・離脱の確認は resources/js/answer-form.js。
    下書きのキーは結果画面(history/show.blade.php)で消すときと同じ形にする。
--}}
@php
    $isWeak = $mode === App\Enums\AttemptMode::Weak;
    $heading = $isWeak ? '苦手な問題に再挑戦' : '全問に回答する';
    // 全問と苦手で、下書きは別々に保存する
    $draftKey = "quiz:draft:section:{$section->id}:{$mode->value}";
@endphp

<x-layouts.app :title="$heading" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
    ['label' => $section->name, 'href' => route('sections.show', $section)],
    ['label' => '回答'],
]">
    <div x-data="answerForm(@js([
        'storageKey' => $draftKey,
        'questionIds' => $questions->pluck('id')->map(fn ($id) => (string) $id)->all(),
        // 送信エラーで戻ってきたときは、サーバーが戻した入力を優先し、下書きで上書きしない
        'hasOldInput' => ! empty(old()),
    ]))">
        <x-ui.page-header :title="$heading">
            <x-slot:meta>
                @if ($isWeak)
                    前回{{ config('quiz.weak_threshold') }}点未満だった{{ $questions->count() }}問です
                @else
                    {{ $section->name }}・{{ $questions->count() }}問
                @endif
            </x-slot:meta>
            <x-slot:actions>
                <div class="text-right">
                    <p class="text-sm font-medium tabular-nums text-zinc-900" aria-live="polite">
                        <span x-text="answeredCount">0</span> / {{ $questions->count() }} 問 回答済み
                    </p>
                    <p class="mt-0.5 flex items-center justify-end gap-2 text-xs text-zinc-500">
                        <span x-show="savedVisible" x-cloak x-transition.opacity>✓ 下書きを保存しました</span>
                        <button type="button" @click="discard()" class="rounded underline-offset-2 hover:text-zinc-900 hover:underline focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none">下書きを破棄</button>
                    </p>
                </div>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="POST" action="{{ route('answers.store', $section) }}" class="space-y-6"
            @input="onInput($event)" @submit="onSubmit()">
            @csrf
            <input type="hidden" name="mode" value="{{ $mode->value }}">

            {{-- 問題の組み合わせが変わっていたときなど、特定の入力欄に結び付かないエラー --}}
            @error('answers')
                <div role="alert" class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
            @enderror

            <x-ui.card>
                <x-ui.radio-group name="grading_level" label="採点レベル" :options="$gradingLevels" value="normal" />
            </x-ui.card>

            @foreach ($questions as $question)
                <x-ui.card class="space-y-4">
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="text-xs font-medium text-zinc-500">問題 {{ $loop->iteration }}</p>
                            <span x-show="isAnswered('{{ $question->id }}')" x-cloak class="text-xs font-medium text-emerald-700">✓ 回答済</span>
                        </div>
                        <p class="text-body whitespace-pre-wrap text-zinc-900">{{ $question->body }}</p>
                    </div>

                    <input type="hidden" name="answers[{{ $loop->index }}][question_id]" value="{{ $question->id }}">
                    <x-ui.textarea name="answers[{{ $loop->index }}][body]" label="あなたの回答" :maxlength="5000" rows="6"
                        placeholder="回答を入力してください" data-question-id="{{ $question->id }}" required />
                </x-ui.card>
            @endforeach

            {{-- スマホでは画面の下に固定して、長いフォームのどこからでも送信できるようにする(screens.md 2.9) --}}
            <div class="sticky bottom-0 -mx-4 border-t border-zinc-200 bg-white px-4 py-3 lg:static lg:mx-0 lg:border-0 lg:bg-transparent lg:p-0">
                <x-ui.button type="submit" class="w-full" ::disabled="submitting">
                    <span x-show="!submitting">採点する({{ $questions->count() }}問)</span>
                    <span x-show="submitting" x-cloak>送信中…</span>
                </x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.app>
