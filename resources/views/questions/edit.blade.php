{{-- 問題の編集(screens.md 2.7)。変更できるのは問題文だけ --}}
<x-layouts.app title="問題を編集" narrow :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $question->section->category->name, 'href' => route('categories.show', $question->section->category)],
    ['label' => $question->section->name, 'href' => route('sections.show', $question->section)],
    ['label' => '問題 '.$number, 'href' => route('questions.show', $question)],
    ['label' => '編集'],
]">
    <x-ui.page-header title="問題を編集" />

    <x-ui.card>
        <form method="POST" action="{{ route('questions.update', $question) }}" class="space-y-6"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('PUT')
            @include('questions._form', [
                'question' => $question,
                'submitLabel' => '保存する',
                'cancelHref' => route('questions.show', $question),
            ])
        </form>
    </x-ui.card>
</x-layouts.app>
