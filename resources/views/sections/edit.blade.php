{{-- セクションの編集(screens.md 2.5)。変更できるのは名前だけ --}}
<x-layouts.app title="セクションを編集" narrow :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
    ['label' => $section->name, 'href' => route('sections.show', $section)],
    ['label' => '編集'],
]">
    <x-ui.page-header title="セクションを編集" />

    <x-ui.card>
        <form method="POST" action="{{ route('sections.update', $section) }}" class="space-y-6"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('PUT')
            @include('sections._form', [
                'section' => $section,
                'submitLabel' => '保存する',
                'cancelHref' => route('sections.show', $section),
            ])
        </form>
    </x-ui.card>
</x-layouts.app>
