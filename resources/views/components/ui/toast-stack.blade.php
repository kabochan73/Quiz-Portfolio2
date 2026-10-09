{{--
    トーストの表示場所(design-guide.md 7.5)。レイアウトが1つだけ置く。
    - PC では右下、スマホでは下中央に、新しいものを下にして積み重ねる
    - 動き(追加・4秒で消す・閉じる)は resources/js/toast.js
    - 文言は x-text で入れるので、HTML として解釈されることはない

    initial はサーバーから渡すトーストの配列: [['type' => 'success' | 'error', 'message' => '...'], ...]
--}}
@props([
    'initial' => [],
])

<div x-data="toastStack(@js($initial))" @toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 lg:items-end lg:p-6">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            :role="toast.type === 'error' ? 'alert' : 'status'"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-lg">
            <span x-show="toast.type === 'success'" class="text-emerald-600"><x-icon name="check-circle" /></span>
            <span x-show="toast.type === 'error'" class="text-rose-600"><x-icon name="exclamation-circle" /></span>
            <p class="flex-1 text-sm text-zinc-900" x-text="toast.message"></p>
            <button type="button" @click="remove(toast.id)" aria-label="通知を閉じる"
                class="-my-1 -mr-1 inline-flex size-8 shrink-0 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900">
                <x-icon name="x-mark" class="size-4" />
            </button>
        </div>
    </template>
</div>
