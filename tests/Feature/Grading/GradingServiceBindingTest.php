<?php

use App\Services\Grading\ClaudeGradingService;
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
])->throws(InvalidArgumentException::class, 'には対応していません');

it('claude なら本物の採点サービスを使う(作るだけで、API は呼ばない)', function () {
    config(['services.grading.driver' => 'claude', 'services.anthropic.api_key' => 'sk-ant-test-dummy']);

    expect(app(GradingService::class))->toBeInstanceOf(ClaudeGradingService::class);
});

it('claude なのに API キーがなければ、使おうとしたときに分かりやすいエラーにする', function () {
    config(['services.grading.driver' => 'claude', 'services.anthropic.api_key' => null]);

    app(GradingService::class);
})->throws(InvalidArgumentException::class, '.env に ANTHROPIC_API_KEY を設定してください');
