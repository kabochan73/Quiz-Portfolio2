{{--
    フォーム項目の枠(design-guide.md 7.2)。ラベル・入力部品・補足文・エラーを縦に並べる。
    見た目だけを担当し、エラーの取得や入力値の復元は input / textarea / select 側で行う。
    - ラベルは入力欄の上に置く(プレースホルダだけで済ませない)
    - エラーがあるときは補足文の代わりにエラーを表示する
--}}
@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'errorId' => null,
])

<div {{ $attributes->class('space-y-1.5') }}>
    @if ($label)
        <label for="{{ $for }}" class="block text-sm font-medium text-zinc-700">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($error)
        <p id="{{ $errorId }}" class="text-sm text-rose-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-xs text-zinc-500">{{ $hint }}</p>
    @endif
</div>
