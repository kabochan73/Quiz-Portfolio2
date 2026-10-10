<?php

use App\Services\Grading\FakeGradingService;
use App\Services\Grading\GradingService;

it('テストでは GRADING_DRIVER=fake なので、フェイクの採点サービスが使われる', function () {
    expect(app(GradingService::class))->toBeInstanceOf(FakeGradingService::class);
});

it('対応していない値や未設定なら、使おうとしたときにエラーにする', function (?string $driver) {
    config(['services.grading.driver' => $driver]);

    app(GradingService::class);
})->with([
    '未設定' => [null],
    '想定外の値' => ['openai'],
    // 本物の採点(ClaudeGradingService)は implementation-plan.md 5-5 で追加する
    'まだ作っていない claude' => ['claude'],
])->throws(InvalidArgumentException::class, 'には対応していません');
