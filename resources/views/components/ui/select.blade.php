{{--
    セレクトボックス(design-guide.md 7.2)。問題の所属セクションの変更などに使う。
    name を渡すだけで、選択値の復元(old)・エラー表示・ラベルとの関連付けを行う。

    options は [値 => 表示名] の配列。選べない選択肢(上限に達したセクションなど)は disabled に値を並べる。
    例: <x-ui.select name="section_id" label="セクション" :options="$sections" :value="$question->section_id"
                     :disabled="$fullSectionIds" />
--}}
@props([
    'name',
    'options' => [],
    'label' => null,
    'hint' => null,
    'value' => null,
    'id' => null,
    'placeholder' => null,
    'disabled' => [],
])

@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id ??= 'field-'.str_replace('.', '-', $key);
    // $errors は web ミドルウェアが共有する。共有されていない場面(コンポーネント単体の描画など)でも動くようにする
    $error = ($errors ?? null)?->first($key);
    $errorId = "{$id}-error";
    $selected = (string) old($key, $value);

    $classes = 'block min-h-11 w-full rounded-lg border bg-white px-3 py-2 text-base text-zinc-900 '
        .'transition-colors focus:outline-none focus:ring-3 '
        .($error
            ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100'
            : 'border-zinc-300 focus:border-brand-500 focus:ring-brand-200');
@endphp

<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :error-id="$errorId">
    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
        {{ $attributes->class($classes) }}
    >
        @if ($placeholder)
            <option value="" disabled @selected($selected === '')>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue) @disabled(in_array($optionValue, $disabled))>{{ $optionLabel }}</option>
        @endforeach
    </select>
</x-ui.field>
