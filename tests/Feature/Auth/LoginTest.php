<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'admin@example.com', 'password' => 'correct-password']);
});

it('トップページはログイン画面へリダイレクトする', function () {
    $this->get('/')->assertRedirect('/login');
});

it('ログイン画面を表示できる', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<title>ログイン | Quiz</title>', false)
        ->assertSee('ログイン状態を保持する');
});

it('正しいメールアドレスとパスワードでログインすると、カテゴリ一覧へ移動する', function () {
    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'correct-password'])
        ->assertRedirect(route('categories.index'));

    $this->assertAuthenticatedAs($this->admin);
});

it('パスワードが違うとログインできず、どちらが違うかは伝えずにフォームへ戻す', function () {
    $this->from('/login')
        ->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong-password'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['login' => 'メールアドレスまたはパスワードが正しくありません。'])
        // 入力したメールアドレスは入れ直さなくて済むよう残す。パスワードは残さない
        ->assertSessionHasInput('email', 'admin@example.com')
        ->assertSessionMissing('_old_input.password');

    $this->assertGuest();
});

it('メールアドレスが空だと、入力欄のエラーとして日本語で伝える', function () {
    $this->post('/login', ['email' => '', 'password' => 'correct-password'])
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください。']);
});

it('1分間に6回失敗すると、正しいパスワードでもしばらくログインできない', function () {
    foreach (range(1, 6) as $i) {
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong-password']);
    }

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'correct-password'])
        ->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))->toMatch('/^ログインの試行回数が多すぎます。\d+秒後にもう一度お試しください。$/');
    $this->assertGuest();
});

it('ログイン済みでログイン画面を開くと、カテゴリ一覧へ移す', function () {
    $this->actingAs($this->admin)->get('/login')->assertRedirect(route('categories.index'));
});

it('ログインしていなければ、カテゴリ一覧からログイン画面へ戻される', function () {
    $this->get('/categories')->assertRedirect('/login');
});

it('ログインしていない状態で開こうとしたページへ、ログイン後に戻る', function () {
    $this->get('/categories');

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'correct-password'])
        ->assertRedirect('/categories');
});
