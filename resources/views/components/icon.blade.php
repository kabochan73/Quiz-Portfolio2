{{--
    アイコン(design-guide.md 6章)。Heroicons(Outline)の SVG を埋め込む。外部の CDN からは読み込まない。
    - 色は文字色に合わせる(currentColor)。大きさは既定で 20px(size-5)、class で上書きできる
    - 飾りとして扱い、読み上げでは飛ばす(aria-hidden)。アイコンだけのボタンには、ボタン側に aria-label を付ける
    - 使うアイコンが増えたら、ここに Heroicons の path を追加する。未登録の名前はエラーにして気づけるようにする

    例: <x-icon name="plus" class="size-4" />
--}}
@props([
    'name',
])

@php
    $path = match ($name) {
        'bars-3' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'x-mark' => 'M6 18 18 6M6 6l12 12',
        'chevron-right' => 'm8.25 4.5 7.5 7.5-7.5 7.5',
        'plus' => 'M12 4.5v15m7.5-7.5h-15',
        'check-circle' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'exclamation-circle' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
    };
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
    aria-hidden="true" {{ $attributes->class('size-5 shrink-0') }}>
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
</svg>
