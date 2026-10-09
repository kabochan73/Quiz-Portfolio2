{{--
    確認モーダル(design-guide.md 7.6)。削除の確認に使う(ブラウザ標準の confirm() は使わない)。
    - $dispatch('open-modal', '名前') で開く。名前で開くので、1ページに複数置ける
    - 中身は action への送信フォーム(既定は DELETE)。本文(slot)には、一緒に消えるデータの件数を書く
    - 開いたときのフォーカスは「キャンセル」に置く(Enter キーを押しても削除されないように)
    - 開いている間はフォーカスを中に閉じ込め、背景のスクロールを止める(x-trap)。Esc キー・背景のクリックで閉じる
    - 送信中は「削除する」を無効にして二重送信を防ぐ

    例:
    <x-ui.confirm-modal name="delete-category" title="カテゴリを削除しますか?"
        :action="route('categories.destroy', $category)">
        セクション3件・問題24件・履歴12件もまとめて削除されます。この操作は取り消せません。
    </x-ui.confirm-modal>
--}}
@props([
    'name',
    'title',
    'action',
    'method' => 'DELETE',
    'confirmLabel' => '削除する',
])

@php
    $titleId = "modal-{$name}-title";
@endphp

<div x-data="{ open: false, submitting: false }"
    @open-modal.window="if ($event.detail === @js($name)) open = true"
    @keydown.escape.window="open = false"
    x-show="open" x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}">
    <div x-show="open" x-transition.opacity.duration.200ms @click="open = false" class="absolute inset-0 bg-zinc-900/40"></div>

    <div x-show="open" x-trap.inert.noscroll="open"
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 id="{{ $titleId }}" class="text-lg font-semibold text-zinc-900">{{ $title }}</h2>
        <div class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $slot }}</div>

        {{-- スマホでは縦並び(危険な操作を上、キャンセルを下)、PC では横並び(キャンセルを左、危険な操作を右) --}}
        <form method="POST" action="{{ $action }}" @submit="submitting = true"
            class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            @csrf
            @method($method)
            <x-ui.button variant="secondary" autofocus x-on:click="open = false">キャンセル</x-ui.button>
            <x-ui.button type="submit" variant="danger" ::disabled="submitting">{{ $confirmLabel }}</x-ui.button>
        </form>
    </div>
</div>
