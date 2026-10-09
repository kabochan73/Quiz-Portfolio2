<?php

use App\Models\User;
use Illuminate\Support\Js;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('ログアウトするとログイン画面に戻り、トーストで知らせる', function () {
    $this->actingAs($this->admin)
        ->post('/logout')
        ->assertRedirect('/login')
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'ログアウトしました']);

    $this->assertGuest();
});

it('ログアウト後のログイン画面に「ログアウトしました」のトーストが出る', function () {
    $this->actingAs($this->admin)->post('/logout');

    // リダイレクト先(ログイン画面)を開くと、トーストの内容が Alpine に渡される
    $this->get('/login')
        ->assertSee((string) Js::from([['type' => 'success', 'message' => 'ログアウトしました']]), false);
});

it('ログアウト後はログインが必要なページを開けない', function () {
    $this->actingAs($this->admin)->post('/logout');

    $this->get('/categories')->assertRedirect('/login');
});

it('GET ではログアウトできない(リンクを踏まされただけでログアウトさせられないように)', function () {
    $this->actingAs($this->admin)
        ->get('/logout')
        ->assertStatus(405);

    $this->assertAuthenticatedAs($this->admin);
});

it('ログイン中はサイドバーにログアウトボタンを表示する', function () {
    $this->actingAs($this->admin)
        ->get('/categories')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('ログアウト');
});

it('ログインしていなければサイドバーにログアウトボタンを出さない', function () {
    $this->blade('<x-layout.sidebar />')
        ->assertDontSee('ログアウト');
});
