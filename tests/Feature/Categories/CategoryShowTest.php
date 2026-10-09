<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('カテゴリ詳細に、中のセクションを作成順に問題数と一緒に表示する', function () {
    $category = Category::factory()->create(['name' => '基本情報']);
    $network = Section::factory()->for($category)->create(['name' => 'ネットワーク']);
    Section::factory()->for($category)->create(['name' => 'データベース']);
    Question::factory()->count(3)->for($network)->create();

    $this->get(route('categories.show', $category))
        ->assertOk()
        ->assertSee('<title>基本情報 | Quiz</title>', false)
        ->assertSee('2セクション')
        ->assertSeeInOrder(['ネットワーク', '3問', 'データベース', '0問']);
});

it('ほかのカテゴリのセクションは表示しない', function () {
    $category = Category::factory()->create();
    Section::factory()->create(['name' => '別カテゴリのセクション']);

    $this->get(route('categories.show', $category))->assertDontSee('別カテゴリのセクション');
});

it('パンくずはカテゴリ一覧 › カテゴリ名 になる', function () {
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->get(route('categories.show', $category))
        ->assertSee('<a href="'.route('categories.index').'"', false)
        ->assertSeeInOrder(['パンくずリスト', 'カテゴリ', '基本情報']);
});

it('セクションが0件なら空の状態を表示する', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.show', $category))
        ->assertSee('まだセクションがありません')
        ->assertSee('0セクション');
});

it('存在しないカテゴリは 404 になる', function () {
    $this->get('/categories/999')->assertNotFound();
});

it('ログインしていなければ開けない', function () {
    auth()->logout();
    $category = Category::factory()->create();

    $this->get(route('categories.show', $category))->assertRedirect('/login');
});
