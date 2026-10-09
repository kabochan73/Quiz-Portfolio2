{{--
    空の状態(design-guide.md 7.8)。一覧が0件のときに、次の行動を案内する。
    点線の枠のカードに、アイコン・見出し・説明・ボタンを中央寄せで置く。説明とボタンは省略できる。

    例:
    <x-ui.empty-state icon="document-text" title="まだ問題がありません" description="最初の問題を追加しましょう。">
        <x-slot:action><x-ui.button href="...">+ 問題を追加</x-ui.button></x-slot:action>
    </x-ui.empty-state>
--}}
@props([
    'icon' => 'folder',
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center') }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-500">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="mt-4 text-base font-medium text-zinc-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-zinc-500">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-6">{{ $action }}</div>
    @endisset
</div>
