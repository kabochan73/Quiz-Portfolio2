{{--
    点数バッジ(design-guide.md 2.3 / 7.4)
    - 80〜100: 緑 / 60〜79: 黄 / 0〜59(苦手): 赤 / 未採点(null): グレーで「—」
    - 赤との区切りは苦手判定の基準点(config('quiz.weak_threshold'))と同じ値を使う。
      基準点を変えたときに、見た目の「赤」と「苦手問題」がずれないようにするため
    - 80 の区切りは見た目だけのものなので、ここに定数として持つ
    - 色だけで意味を伝えないよう、数字を必ず表示し、読み上げ用に「〇点」を付ける

    例: <x-ui.score-badge :score="$score?->score" size="lg" />
--}}
@props([
    'score' => null,
    'size' => 'md',
])

@php
    $highThreshold = 80;
    $weakThreshold = config('quiz.weak_threshold');

    $colorClasses = match (true) {
        $score === null => 'bg-zinc-100 text-zinc-500',
        $score >= $highThreshold => 'bg-emerald-50 text-emerald-700',
        $score >= $weakThreshold => 'bg-amber-50 text-amber-700',
        default => 'bg-rose-50 text-rose-700',
    };

    $sizeClasses = match ($size) {
        'md' => 'min-w-10 px-2.5 py-0.5 text-sm',
        'lg' => 'min-w-12 px-3 py-1 text-base',
    };
@endphp

<span
    aria-label="{{ $score === null ? '未採点' : "{$score}点" }}"
    {{ $attributes->class("inline-flex items-center justify-center rounded-full font-medium tabular-nums {$colorClasses} {$sizeClasses}") }}
>{{ $score ?? '—' }}</span>
