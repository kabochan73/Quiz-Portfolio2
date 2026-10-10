<?php

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Str;

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

it('セクションの行に、直近の全問の挑戦の平均点と最終挑戦日を出す', function () {
    $category = Category::factory()->create();
    $network = Section::factory()->for($category)->create(['name' => 'ネットワーク']);
    Section::factory()->for($category)->create(['name' => 'データベース']);
    $attempt = Attempt::factory()->completed()->for($network)->create(['created_at' => '2026-10-07 20:00:00']);
    foreach ([85, 45, 72] as $position => $score) {
        $answer = Answer::factory()->for($attempt)->create(['position' => $position]);
        Score::factory()->for($answer)->create(['score' => $score]);
    }

    $main = Str::between($this->get(route('categories.show', $category))->getContent(), '<main', '</main>');

    expect($main)->toMatch('#ネットワーク.*aria-label="67.3点".*最終 10/7.*データベース.*aria-label="未採点".*未挑戦#s');
});

it('ほかのカテゴリのセクションは表示しない', function () {
    $category = Category::factory()->create();
    Section::factory()->create(['name' => '別カテゴリのセクション']);

    // サイドバーには全カテゴリのセクションが出るので、本文(main)の中だけで確かめる
    $html = $this->get(route('categories.show', $category))->getContent();
    $main = Str::between($html, '<main', '</main>');

    expect($main)->not->toContain('別カテゴリのセクション');
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
