<?php

use Illuminate\Support\Facades\Route;

it('存在しない URL では日本語の 404 ページを表示する', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('ページが見つかりません')
        ->assertSee('<title>ページが見つかりません | Quiz</title>', false);
});

it('権限がないときは 404 と同じ画面を出し、存在することを知らせない', function () {
    Route::get('/test-forbidden', fn () => abort(403));

    $this->get('/test-forbidden')
        ->assertForbidden()
        ->assertSee('ページが見つかりません')
        // 画面上のステータス表示も 404 にする(「403」は CSS などのファイル名に偶然含まれうるので、表示部分で確かめる)
        ->assertSee('tabular-nums">404</p>', false)
        ->assertDontSee('tabular-nums">403</p>', false);
});

it('CSRF トークンの期限切れでは、もう一度操作するよう案内する', function () {
    Route::get('/test-expired', fn () => abort(419));

    $this->get('/test-expired')
        ->assertStatus(419)
        ->assertSee('ページの有効期限が切れました');
});

it('サーバー内部のエラーでは、原因を出さずに案内だけを表示する', function () {
    // .env の APP_DEBUG=true のままだと、詳細なデバッグ画面が出てしまうため本番と同じ設定にする
    config(['app.debug' => false]);
    Route::get('/test-error', fn () => throw new RuntimeException('内部の詳細なエラー'));

    $this->get('/test-error')
        ->assertStatus(500)
        ->assertSee('エラーが発生しました')
        ->assertDontSee('内部の詳細なエラー');
});
