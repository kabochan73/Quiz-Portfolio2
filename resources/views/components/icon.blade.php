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
        'ellipsis-horizontal' => 'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
        'folder' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
        'document-text' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'arrow-right-start-on-rectangle' => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
        'check-circle' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'exclamation-circle' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
    };
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
    aria-hidden="true" {{ $attributes->class('size-5 shrink-0') }}>
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
</svg>
