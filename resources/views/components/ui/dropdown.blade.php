{{--
    「…」メニュー(design-guide.md 7.10)。編集・削除などの副操作をまとめ、画面を操作ボタンで埋めないようにする。
    - メニューの外のクリック・Esc キー・項目の選択で閉じる
    - Esc で閉じたときは「…」ボタンにフォーカスを戻す(キーボード操作で迷子にならないように)
    - 読み上げ向けに、label を「…」ボタンの aria-label にする

    例:
    <x-ui.dropdown label="カテゴリの操作">
        <x-ui.dropdown-item href="...">編集</x-ui.dropdown-item>
        <x-ui.dropdown-item danger x-on:click="$dispatch('open-modal', 'delete-category')">削除</x-ui.dropdown-item>
    </x-ui.dropdown>
--}}
@props([
    'label' => '操作',
])

<div x-data="{ open: false }" @click.outside="open = false"
    @keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"
    {{ $attributes->class('relative inline-block') }}>
    <button type="button" x-ref="trigger" @click="open = !open" :aria-expanded="open.toString()" aria-label="{{ $label }}"
        class="inline-flex size-11 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900 focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none lg:size-10">
        <x-icon name="ellipsis-horizontal" />
    </button>

    <div x-show="open" x-cloak @click="open = false"
        x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute right-0 z-20 mt-1 min-w-40 origin-top-right rounded-xl border border-zinc-200 bg-white p-1 shadow-lg">
        {{ $slot }}
    </div>
</div>
