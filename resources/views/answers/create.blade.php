{{--
    回答フォーム(screens.md 2.9)。セクションの全問を並べ、まとめて送信する。
    進捗表示(「3 / 5 問 回答済み」)と下書きの自動保存は implementation-plan.md 5-7 で追加する。
--}}
<x-layouts.app title="全問に回答する" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
    ['label' => $section->name, 'href' => route('sections.show', $section)],
    ['label' => '回答'],
]">
    <x-ui.page-header title="全問に回答する">
        <x-slot:meta>{{ $section->name }}・{{ $questions->count() }}問</x-slot:meta>
    </x-ui.page-header>

    <form method="POST" action="{{ route('answers.store', $section) }}" class="space-y-6"
        x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

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
                    <p class="mb-2 text-xs font-medium text-zinc-500">問題 {{ $loop->iteration }}</p>
                    <p class="text-body whitespace-pre-wrap text-zinc-900">{{ $question->body }}</p>
                </div>

                <input type="hidden" name="answers[{{ $loop->index }}][question_id]" value="{{ $question->id }}">
                <x-ui.textarea name="answers[{{ $loop->index }}][body]" label="あなたの回答" :maxlength="5000" rows="6"
                    placeholder="回答を入力してください" required />
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
</x-layouts.app>
