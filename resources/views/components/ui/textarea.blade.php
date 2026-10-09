{{--
    複数行の入力欄(design-guide.md 7.2)。問題文・回答の入力に使う。
    name を渡すだけで、入力値の復元(old)・エラー表示・ラベルとの関連付けを行う。

    maxlength を渡すと、右下に「120 / 2000」の形で文字数を表示する。
    HTML の maxlength 属性で入力を止めることはしない(貼り付けた文章が黙って切り捨てられるのを避けるため)。
    上限を超えたら数字を赤くして知らせ、最終的な判定はサーバー側のバリデーションに任せる。

    例: <x-ui.textarea name="body" label="問題文" :maxlength="2000" rows="8" />
--}}
@props([
    'name',
    'label' => null,
    'hint' => null,
    'value' => null,
    'id' => null,
    'maxlength' => null,
])

@php
    // answers[0][body] のような配列形式の name を、old() やエラーで使う answers.0.body の形にする
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id ??= 'field-'.str_replace('.', '-', $key);
    // $errors は web ミドルウェアが共有する。共有されていない場面(コンポーネント単体の描画など)でも動くようにする
    $error = ($errors ?? null)?->first($key);
    $errorId = "{$id}-error";
    $current = (string) old($key, $value);

    $classes = 'block w-full rounded-lg border bg-white px-3 py-2 text-base leading-relaxed text-zinc-900 '
        .'placeholder:text-zinc-500 transition-colors focus:outline-none focus:ring-3 '
        .($error
            ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100'
            : 'border-zinc-300 focus:border-brand-500 focus:ring-brand-200');
@endphp

<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$error" :error-id="$errorId">
    {{-- 文字数はサーバー側(mb_strlen)と同じく、絵文字なども1文字として数える(Array.from) --}}
    <div @if ($maxlength) x-data="{ count: {{ mb_strlen($current) }} }" @endif>
        <textarea
            name="{{ $name }}"
            id="{{ $id }}"
            @if ($maxlength) @input="count = Array.from($event.target.value).length" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
            {{ $attributes->merge(['rows' => 6])->class($classes) }}
        >{{ $current }}</textarea>

        @if ($maxlength)
            <p class="mt-1 text-right text-xs tabular-nums"
                :class="count > {{ $maxlength }} ? 'text-rose-600' : 'text-zinc-500'">
                <span x-text="count">{{ mb_strlen($current) }}</span> / {{ $maxlength }}
            </p>
        @endif
    </div>
</x-ui.field>
