{{-- セクションの作成(screens.md 2.5)。所属カテゴリは URL で決まっているので選ばせない --}}
<x-layouts.app title="セクションを追加" narrow :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $category->name, 'href' => route('categories.show', $category)],
    ['label' => 'セクションを追加'],
]">
    <x-ui.page-header title="セクションを追加">
        <x-slot:meta>カテゴリ: {{ $category->name }}</x-slot:meta>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('categories.sections.store', $category) }}" class="space-y-6"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @include('sections._form', [
                'section' => null,
                'submitLabel' => '作成する',
                'cancelHref' => route('categories.show', $category),
            ])
        </form>
    </x-ui.card>
</x-layouts.app>
