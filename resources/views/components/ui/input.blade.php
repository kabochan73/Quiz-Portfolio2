{{--
    1行の入力欄(design-guide.md 7.2)
    name を渡すだけで、入力値の復元(old)・エラー表示・ラベルとの関連付けを行う。
    required や autocomplete など、それ以外の属性は <input> にそのまま渡す。

    例: <x-ui.input name="name" label="カテゴリ名" :value="$category->name ?? ''" required />
--}}
@props([
    'name',
    'label' => null,
    'hint' => null,
    'type' => 'text',
    'value' => null,
    'id' => null,
])

@php
    // answers[0][body] のような配列形式の name を、old() やエラーで使う answers.0.body の形にする
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id ??= 'field-'.str_replace('.', '-', $key);
    // $errors は web ミドルウェアが共有する。共有されていない場面(コンポーネント単体の描画など)でも動くようにする
    $error = ($errors ?? null)?->first($key);
    $errorId = "{$id}-error";

    // 3.2: 文字は 16px(text-base)以上。iOS で入力時に画面が拡大されるのを防ぐ
    $classes = 'block min-h-11 w-full rounded-lg border bg-white px-3 py-2 text-base text-zinc-900 '
        .'placeholder:text-zinc-500 transition-colors focus:outline-none focus:ring-3 '
        .($error
            ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100'
            : 'border-zinc-300 focus:border-brand-500 focus:ring-brand-200');
@endphp

<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :error-id="$errorId">
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($key, $value) }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
        {{ $attributes->class($classes) }}
    >
</x-ui.field>
