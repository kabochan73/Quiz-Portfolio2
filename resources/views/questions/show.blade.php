{{--
    問題詳細(screens.md 2.8)。問題文の全文を表示する。
    「最近の点数」は implementation-plan.md 6-6 で追加する。
--}}
<x-layouts.app :title="'問題 '.$number" :breadcrumbs="[
    ['label' => 'カテゴリ', 'href' => route('categories.index')],
    ['label' => $question->section->category->name, 'href' => route('categories.show', $question->section->category)],
    ['label' => $question->section->name, 'href' => route('sections.show', $question->section)],
    ['label' => '問題 '.$number],
]">
    <x-ui.page-header :title="'問題 '.$number">
        <x-slot:meta>セクション: {{ $question->section->name }}</x-slot:meta>
        <x-slot:actions>
            <x-ui.dropdown label="問題の操作">
                <x-ui.dropdown-item href="{{ route('questions.edit', $question) }}">編集</x-ui.dropdown-item>
                <x-ui.dropdown-item danger x-on:click="$dispatch('open-modal', 'delete-question')">削除</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- design-guide.md 7.6: 一緒に消えるデータの件数を明示する --}}
    <x-ui.confirm-modal name="delete-question" title="問題を削除しますか?" :action="route('questions.destroy', $question)">
        この問題と、回答の履歴{{ $answerCount }}件も削除されます。この操作は取り消せません。
    </x-ui.confirm-modal>

    {{-- 改行を保持して全文を表示する(design-guide.md 3.2) --}}
    <x-ui.card>
        <p class="text-body whitespace-pre-wrap text-zinc-900">{{ $question->body }}</p>
    </x-ui.card>
</x-layouts.app>
