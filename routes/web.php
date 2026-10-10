<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SectionController;
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

    // カテゴリ。一覧はログイン後の着地点
    Route::resource('categories', CategoryController::class);

    // セクションは必ずカテゴリに属するので、作成だけカテゴリ配下の URL にし(/categories/{category}/sections/create)、
    // 詳細などはセクションの ID だけで決まる短い URL にする(/sections/{section})。shallow はこの形を作る指定。
    // セクションの一覧はカテゴリ詳細が兼ねるので、index は作らない
    Route::resource('categories.sections', SectionController::class)
        ->shallow()
        ->except(['index']);

    // 問題もセクションと同じ形の URL にする(/sections/{section}/questions/create、/questions/{question})
    // 問題の一覧はセクション詳細が兼ねるので、index は作らない
    Route::resource('sections.questions', QuestionController::class)
        ->shallow()
        ->except(['index']);
});

// 共通コンポーネントの見本ページ(implementation-plan.md 2-6)。
// 見た目の確認・調整用なので、ローカル環境でだけ登録する(本番やテストでは 404)。
if (app()->environment('local')) {
    Route::view('/dev/components', 'dev.components')->name('dev.components');
}
