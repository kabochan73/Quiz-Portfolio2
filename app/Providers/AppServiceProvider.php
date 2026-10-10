<?php

namespace App\Providers;

use App\Services\Grading\FakeGradingService;
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
                // 'claude' は implementation-plan.md 5-5 で ClaudeGradingService を作ってから追加する
                default => throw new InvalidArgumentException(
                    "GRADING_DRIVER の値「{$driver}」には対応していません。.env で fake を指定してください。"
                ),
            };
        });
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
