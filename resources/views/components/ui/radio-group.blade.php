{{--
    横並びの切り替えボタン(design-guide.md 7.11)。少ない選択肢から1つを選ぶ入力に使う(採点レベルなど)。
    - 中身は普通のラジオボタンなので、キーボードの矢印キーでも選べる
    - 選ばれている項目は brand 色で強調する(Tailwind の has-checked)
    - バリデーションエラーで戻ってきたときは、送信時の選択(old)を復元する

    例: <x-ui.radio-group name="grading_level" label="採点レベル" :options="$levels" value="normal" />
--}}
@props([
    'name',
    'options' => [],
    'label' => null,
    'value' => null,
])

@php
    $selected = (string) old($name, $value);
    // $errors は web ミドルウェアが共有する。共有されていない場面(コンポーネント単体の描画など)でも動くようにする
    $error = ($errors ?? null)?->first($name);
@endphp

<fieldset {{ $attributes->class('space-y-1.5') }}>
    @if ($label)
        <legend class="mb-1.5 text-sm font-medium text-zinc-700">{{ $label }}</legend>
    @endif

    {{-- 選択肢の数に関係なく、横幅を等分する --}}
    <div class="grid auto-cols-fr grid-flow-col gap-2">
        @foreach ($options as $optionValue => $optionLabel)
            <label class="flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-zinc-300 bg-white px-3 text-sm text-zinc-700 transition-colors hover:bg-zinc-50 has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:font-medium has-checked:text-brand-700 has-focus-visible:ring-3 has-focus-visible:ring-brand-200 lg:min-h-10">
                <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" class="sr-only"
                    @checked($selected === (string) $optionValue)>
                {{ $optionLabel }}
            </label>
        @endforeach
    </div>

    @if ($error)
        <p class="text-sm text-rose-600">{{ $error }}</p>
    @endif
</fieldset>
