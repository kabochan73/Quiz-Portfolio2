<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 共通コンポーネントの見本ページ(implementation-plan.md 2-6)。
// 見た目の確認・調整用なので、ローカル環境でだけ登録する(本番やテストでは 404)。
if (app()->environment('local')) {
    Route::view('/dev/components', 'dev.components')->name('dev.components');
}
