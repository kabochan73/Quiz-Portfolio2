<?php

namespace App\Providers;

use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
