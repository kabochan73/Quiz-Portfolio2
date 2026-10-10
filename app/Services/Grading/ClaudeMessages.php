<?php

namespace App\Services\Grading;

use Anthropic\Beta\Messages\BetaMessage;

/**
 * Claude の Messages API を1回呼ぶ、という処理だけを表す小さなインターフェース。
 *
 * ClaudeGradingService から実際の通信を切り離し、テストでは用意した応答を返す偽物に差し替えられるようにする
 * (CLAUDE.md: テストで実際の Claude API を呼ばない)。本物は AnthropicClaudeMessages。
 */
interface ClaudeMessages
{
    /**
     * @param  array<string, mixed>  $params  Anthropic PHP SDK の beta messages create に渡す名前付き引数(camelCase)
     * @param  float  $timeout  この1回の呼び出しの制限時間(秒)
     */
    public function create(array $params, float $timeout): BetaMessage;
}
