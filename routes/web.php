<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// アプリの起点はログイン画面(requirements.md 6章)
Route::redirect('/', '/login');

// ログインしていないときだけ開ける。ログイン済みならカテゴリ一覧へ移す(bootstrap/app.php の redirectUsersTo)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// ログインが必要なページ。会員登録はないので、ここに入れるのは管理者だけ
Route::middleware('auth')->group(function () {
    // リンクを踏まされただけでログアウトさせられないよう、POST だけを受け付ける
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ログイン後の着地点。本物のカテゴリ一覧は implementation-plan.md 4-1 で作る
    Route::view('/categories', 'categories.index')->name('categories.index');
});

// 共通コンポーネントの見本ページ(implementation-plan.md 2-6)。
// 見た目の確認・調整用なので、ローカル環境でだけ登録する(本番やテストでは 404)。
if (app()->environment('local')) {
    Route::view('/dev/components', 'dev.components')->name('dev.components');
}
