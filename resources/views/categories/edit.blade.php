{{-- カテゴリの編集(screens.md 2.3)。作成と同じフォームを使い、ボタンの文言だけ変える --}}
<x-layouts.app title="カテゴリを編集" narrow :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $category->name, 'href' => route('categories.show', $category)],
    ['label' => '編集'],
]">
    <x-ui.page-header title="カテゴリを編集" />

    <x-ui.card>
        <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-6"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('PUT')
            @include('categories._form', [
                'category' => $category,
                'submitLabel' => '保存する',
                'cancelHref' => route('categories.show', $category),
            ])
        </form>
    </x-ui.card>
</x-layouts.app>
