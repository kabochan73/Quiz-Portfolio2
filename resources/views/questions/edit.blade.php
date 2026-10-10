{{-- 問題の編集(screens.md 2.7)。問題文に加えて、所属セクションを変更できる --}}
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
                'sectionOptions' => $sectionOptions,
                'fullSectionIds' => $fullSectionIds,
                'submitLabel' => '保存する',
                'cancelHref' => route('questions.show', $question),
            ])
        </form>
    </x-ui.card>
</x-layouts.app>
