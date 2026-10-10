<?php

use App\Support\CostCalculator;

function sonnetCalculator(mixed $webSearchPrice = '0.01'): CostCalculator
{
    // .env の値は文字列で来るので、テストも文字列で渡す
    return new CostCalculator(
        pricing: ['claude-sonnet-5-5' => ['input_per_mtok' => '2', 'output_per_mtok' => '10']],
        webSearchPerRequest: $webSearchPrice,
    );
}

it('入力・出力のトークンと検索回数から推定コストを求める', function () {
    // 6816 × $2/100万 + 754 × $10/100万 + 2 × $0.01 = 0.013632 + 0.00754 + 0.02
    expect(sonnetCalculator()->estimate('claude-sonnet-5-5', 6816, 754, 2))
        ->toEqualWithDelta(0.041172, 0.0000001);
});

it('検索していなければ、トークンの分だけを求める', function () {
    expect(sonnetCalculator()->estimate('claude-sonnet-5-5', 1_000_000, 0, 0))->toEqual(2.0)
        ->and(sonnetCalculator()->estimate('claude-sonnet-5-5', 0, 1_000_000, null))->toEqual(10.0);
});

it('単価が設定されていないモデル(フェイクや自動切り替え先など)は null にする', function () {
    expect(sonnetCalculator()->estimate('fake', 100, 100, 0))->toBeNull()
        ->and(sonnetCalculator()->estimate('claude-opus-5-5', 100, 100, 0))->toBeNull();
});

it('単価が空文字・未設定(.env の KEY= の形)なら null にする', function (mixed $price) {
    $calculator = new CostCalculator(['claude-sonnet-5-5' => ['input_per_mtok' => $price, 'output_per_mtok' => '10']], '0.01');

    expect($calculator->estimate('claude-sonnet-5-5', 100, 100, 0))->toBeNull();
})->with([
    '空文字' => [''],
    'null' => [null],
    '数値でない' => ['two'],
]);

it('検索したのに検索の単価が分からなければ null にし、検索していなければ求める', function () {
    $calculator = sonnetCalculator(webSearchPrice: null);

    expect($calculator->estimate('claude-sonnet-5-5', 1_000_000, 0, 1))->toBeNull()
        ->and($calculator->estimate('claude-sonnet-5-5', 1_000_000, 0, 0))->toEqual(2.0);
});

it('モデル名かトークン数が記録されていなければ null にする', function () {
    expect(sonnetCalculator()->estimate(null, 100, 100, 0))->toBeNull()
        ->and(sonnetCalculator()->estimate('claude-sonnet-5-5', null, null, null))->toBeNull();
});
