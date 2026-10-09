{{--
    チェックボックス(design-guide.md 7.2)。ラベル全体を押せるようにし、押せる高さを 44px(PC は 36px)確保する。
    - バリデーションエラーで戻ってきたときは、送信時の状態(old)を復元する
    - チェックの色はアクセントの青(accent-color)

    例: <x-ui.checkbox name="remember" label="ログイン状態を保持する" />
--}}
@props([
    'name',
    'label',
    'checked' => false,
    'value' => '1',
    'id' => null,
])

@php
    $id ??= "field-{$name}";
    // 送信して戻ってきた場合(old の入力がある場合)は、チェックしていなければ値自体が送られないので、
    // 値があるかどうかで判定する。そうでなければ呼び出し側の指定に従う
    $isChecked = old() ? old($name) !== null : $checked;
@endphp

<label for="{{ $id }}" {{ $attributes->class('inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-zinc-700 lg:min-h-9') }}>
    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @checked($isChecked)
        class="size-4 rounded border-zinc-300 accent-brand-600 focus-visible:ring-3 focus-visible:ring-brand-200 focus-visible:outline-none">
    {{ $label }}
</label>
