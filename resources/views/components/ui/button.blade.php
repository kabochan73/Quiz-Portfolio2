{{--
    ボタン(design-guide.md 7.1)
    - href を渡すと <a>、渡さないと <button> として出力する。見た目は同じ
    - type の既定値は "button"。HTML 標準の "submit" のままだと、フォーム内の「キャンセル」などを
      押したときに誤って送信されるため。送信ボタンでは type="submit" を明示する
    - variant / size に想定外の値が来たら、match が例外を投げて気づけるようにしている
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium whitespace-nowrap transition-colors '
        .'focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-brand-200 '
        .'disabled:pointer-events-none disabled:opacity-50';

    $variantClasses = match ($variant) {
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700',
        'secondary' => 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700',
    };

    // 4.4: 押せる領域はスマホで 44px 以上。PC では 40px まで下げる
    $sizeClasses = match ($size) {
        'md' => 'min-h-11 px-4 text-sm lg:min-h-10',
        'sm' => 'min-h-8 px-3 text-sm',
    };

    $classes = "{$base} {$variantClasses} {$sizeClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
