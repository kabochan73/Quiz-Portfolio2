{{--
    前回比(design-guide.md 7.4、requirements.md 3.4)。点数バッジの右に小さく添える。
    - 上がった: ▲16(緑)/ 下がった: ▼8(赤)/ 同じ: ±0(グレー)/ 前回なし: 「初回」(グレー)
    - 記号は見た目用。読み上げソフトには「前回より16点アップ」のような文言を渡す
    - 平均点のような小数も受け付け、差は小数第1位まで出す(整数になる差は 16 のように整数で出す)

    例: <x-ui.score-delta :current="$answer->score->score" :previous="$previousScore" />
--}}
@props([
    'current',
    'previous' => null,
])

@php
    // 小数の引き算の誤差(6.299999… など)が出ないよう、小数第1位で丸める
    $delta = $previous === null ? null : round($current - $previous, 1);
    // 6.3 はそのまま、16.0 は 16 と出す
    $amount = $delta === null ? null : rtrim(rtrim(number_format(abs($delta), 1), '0'), '.');

    [$text, $label, $colorClass] = match (true) {
        $delta === null => ['初回', '初回の挑戦', 'text-zinc-500'],
        $delta > 0 => ["▲{$amount}", "前回より{$amount}点アップ", 'text-emerald-600'],
        $delta < 0 => ["▼{$amount}", "前回より{$amount}点ダウン", 'text-rose-600'],
        default => ['±0', '前回と同じ点数', 'text-zinc-500'],
    };
@endphp

<span {{ $attributes->class("text-xs font-medium tabular-nums {$colorClass}") }}>
    <span aria-hidden="true">{{ $text }}</span>
    <span class="sr-only">{{ $label }}</span>
</span>
