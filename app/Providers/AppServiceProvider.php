<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\Grading\AnthropicClaudeMessages;
use App\Services\Grading\ClaudeGradingService;
use App\Services\Grading\FakeGradingService;
use App\Services\Grading\GradeResultParser;
use App\Services\Grading\GradingPrompt;
use App\Services\Grading\GradingService;
use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // GradingService を頼まれたら、.env の GRADING_DRIVER に応じた採点サービスを渡す。
        // 実際に使われるときに初めて選ぶので、採点しない画面では設定がなくてもエラーにならない
        $this->app->bind(GradingService::class, function () {
            $driver = config('services.grading.driver');

            return match ($driver) {
                'fake' => new FakeGradingService,
                'claude' => $this->makeClaudeGradingService(),
                default => throw new InvalidArgumentException(
                    "GRADING_DRIVER の値「{$driver}」には対応していません。.env で fake か claude を指定してください。"
                ),
            };
        });
    }

    /**
     * 本物の採点サービス。API キーがなければ、使おうとした時点で分かりやすいエラーにする
     * (キーなしで SDK を作ると、別の認証方法を探しに行って原因が分かりにくくなるため)。
     */
    private function makeClaudeGradingService(): ClaudeGradingService
    {
        $apiKey = config('services.anthropic.api_key');

        if (blank($apiKey)) {
            throw new InvalidArgumentException('GRADING_DRIVER=claude のときは、.env に ANTHROPIC_API_KEY を設定してください。');
        }

        return new ClaudeGradingService(
            messages: new AnthropicClaudeMessages(new Client(apiKey: $apiKey)),
            prompt: new GradingPrompt,
            parser: new GradeResultParser,
            model: config('services.anthropic.model'),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // アプリのレイアウトを表示するたびに、サイドバーのカテゴリ・セクション一覧を渡す
        View::composer('components.layouts.app', SidebarComposer::class);
    }
}
