{{--
    前回比(design-guide.md 7.4、requirements.md 3.4)。点数バッジの右に小さく添える。
    - 上がった: ▲16(緑)/ 下がった: ▼8(赤)/ 同じ: ±0(グレー)/ 前回なし: 「初回」(グレー)
    - 記号は見た目用。読み上げソフトには「前回より16点アップ」のような文言を渡す

    例: <x-ui.score-delta :current="$answer->score->score" :previous="$previousScore" />
--}}
@props([
    'current',
    'previous' => null,
])

@php
    $delta = $previous === null ? null : $current - $previous;

    [$text, $label, $colorClass] = match (true) {
        $delta === null => ['初回', '初回の挑戦', 'text-zinc-500'],
        $delta > 0 => ["▲{$delta}", "前回より{$delta}点アップ", 'text-emerald-600'],
        $delta < 0 => ['▼'.abs($delta), '前回より'.abs($delta).'点ダウン', 'text-rose-600'],
        default => ['±0', '前回と同じ点数', 'text-zinc-500'],
    };
@endphp

<span {{ $attributes->class("text-xs font-medium tabular-nums {$colorClass}") }}>
    <span aria-hidden="true">{{ $text }}</span>
    <span class="sr-only">{{ $label }}</span>
</span>
