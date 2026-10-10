{{--
    問題詳細(screens.md 2.8)。問題文の全文を表示する。
    まだ置いていないもの:
    - 見出しの「…」メニュー(編集・削除) → implementation-plan.md 4-3 の続き
    - 「最近の点数」 → implementation-plan.md 6-6
--}}
<x-layouts.app :title="'問題 '.$number" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $question->section->category->name, 'href' => route('categories.show', $question->section->category)],
    ['label' => $question->section->name, 'href' => route('sections.show', $question->section)],
    ['label' => '問題 '.$number],
]">
    <x-ui.page-header :title="'問題 '.$number">
        <x-slot:meta>セクション: {{ $question->section->name }}</x-slot:meta>
    </x-ui.page-header>

    {{-- 改行を保持して全文を表示する(design-guide.md 3.2) --}}
    <x-ui.card>
        <p class="text-body whitespace-pre-wrap text-zinc-900">{{ $question->body }}</p>
    </x-ui.card>
</x-layouts.app>
