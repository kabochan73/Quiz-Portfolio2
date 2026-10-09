{{-- カテゴリの作成(screens.md 2.3) --}}
<x-layouts.app title="カテゴリを作成" narrow :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => '新規作成'],
]">
    <x-ui.page-header title="カテゴリを作成" />

    <x-ui.card>
        {{-- 送信中はボタンを無効にして二重送信を防ぐ --}}
        <form method="POST" action="{{ route('categories.store') }}" class="space-y-6"
            x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @include('categories._form', [
                'category' => null,
                'submitLabel' => '作成する',
                'cancelHref' => route('categories.index'),
            ])
        </form>
    </x-ui.card>
</x-layouts.app>
