<?php

namespace App\Services\Grading;

use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Client;

/**
 * 公式の Anthropic PHP SDK で、実際に Claude の Messages API を呼ぶ。
 *
 * 拒否時の自動切り替え(fallbacks)を使うため、beta の messages を使う(betas の指定はこちらにしかない)。
 * SDK 自体の再試行は 0 回にしている。再試行は採点 Job が待ち時間を空けて行うので、
 * ここでも再試行すると1回の Job の制限時間(240秒)を超えやすくなるため。
 */
class AnthropicClaudeMessages implements ClaudeMessages
{
    public function __construct(private readonly Client $client) {}

    public function create(array $params, float $timeout): BetaMessage
    {
        return $this->client->beta->messages->create(
            ...$params,
            requestOptions: ['timeout' => $timeout, 'maxRetries' => 0],
        );
    }
}
