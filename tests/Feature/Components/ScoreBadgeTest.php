<?php

it('点数の境目に応じて色が切り替わる', function (?int $score, string $expectedClass) {
    $this->blade('<x-ui.score-badge :score="$score" />', ['score' => $score])
        ->assertSee($expectedClass, false);
})->with([
    '100点は緑' => [100, 'bg-emerald-50'],
    '80点は緑' => [80, 'bg-emerald-50'],
    '79点は黄' => [79, 'bg-amber-50'],
    '60点は黄' => [60, 'bg-amber-50'],
    '59点は赤' => [59, 'bg-rose-50'],
    '0点は赤' => [0, 'bg-rose-50'],
    '未採点はグレー' => [null, 'bg-zinc-100'],
]);

it('点数を表示し、読み上げ用に「〇点」を付ける', function () {
    $this->blade('<x-ui.score-badge :score="72" />')
        ->assertSee('aria-label="72点"', false)
        ->assertSee('>72</span>', false);
});

it('未採点なら「—」を表示する', function () {
    $this->blade('<x-ui.score-badge :score="null" />')
        ->assertSee('aria-label="未採点"', false)
        ->assertSee('—');
});

it('赤との区切りは苦手の基準点(config)に追従する', function () {
    config(['quiz.weak_threshold' => 70]);

    $this->blade('<x-ui.score-badge :score="65" />')
        ->assertSee('bg-rose-50', false);
});

it('前回比は増減に応じて記号・色・読み上げ用の文言が変わる', function (?int $previous, string $text, string $label, string $colorClass) {
    $this->blade('<x-ui.score-delta :current="70" :previous="$previous" />', ['previous' => $previous])
        ->assertSee($text)
        ->assertSee($label)
        ->assertSee($colorClass, false);
})->with([
    '上がった' => [54, '▲16', '前回より16点アップ', 'text-emerald-600'],
    '下がった' => [78, '▼8', '前回より8点ダウン', 'text-rose-600'],
    '同じ' => [70, '±0', '前回と同じ点数', 'text-zinc-500'],
    '前回なし' => [null, '初回', '初回の挑戦', 'text-zinc-500'],
]);
