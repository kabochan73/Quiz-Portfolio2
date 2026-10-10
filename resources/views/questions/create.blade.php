{{-- 問題の作成(screens.md 2.7)。セクションは URL で決まっているので選ばせない --}}
<x-layouts.app title="問題を追加" narrow :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
    ['label' => $section->name, 'href' => route('sections.show', $section)],
    ['label' => '問題を追加'],
]">
    <x-ui.page-header title="問題を追加">
        <x-slot:meta>セクション: {{ $section->name }}</x-slot:meta>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('sections.questions.store', $section) }}" class="space-y-6"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @include('questions._form', [
                'question' => null,
                'submitLabel' => '追加する',
                'cancelHref' => route('sections.show', $section),
            ])
        </form>
    </x-ui.card>
</x-layouts.app>
