<?php

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('カテゴリ一覧に、カテゴリごとのセクション数と問題数を表示する', function () {
    $category = Category::factory()->create(['name' => '基本情報']);
    $network = Section::factory()->for($category)->create();
    Section::factory()->for($category)->create();
    Question::factory()->count(3)->for($network)->create();

    $this->get('/categories')
        ->assertOk()
        ->assertSee('基本情報')
        ->assertSee('2セクション・3問')
        ->assertSee('+ カテゴリを作成');
});

it('カテゴリのカードに最終挑戦日を出し、挑戦していなければそう伝える', function () {
    $tried = Category::factory()->create(['name' => '基本情報']);
    $section = Section::factory()->for($tried)->create();
    Attempt::factory()->for($section)->create(['created_at' => '2026-10-03 21:00:00']);
    Attempt::factory()->failed()->for($section)->create(['created_at' => '2026-10-07 20:00:00']); // 失敗も挑戦した日に含める
    Category::factory()->create(['name' => '英語']);

    $this->get('/categories')
        ->assertSeeInOrder(['基本情報', '最終挑戦 10/7', '英語', 'まだ挑戦していません']);
});

it('カテゴリ一覧の問い合わせの回数は、カテゴリが増えても変わらない', function () {
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/categories');

        return count(DB::getQueryLog());
    };

    Category::factory()->count(2)->has(Section::factory()->has(Attempt::factory()))->create();
    $few = $count();
    Category::factory()->count(5)->has(Section::factory()->has(Attempt::factory()))->create();

    expect($count())->toBe($few);
});

it('カテゴリは作成した順に並ぶ', function () {
    Category::factory()->create(['name' => '最初のカテゴリ']);
    Category::factory()->create(['name' => '次のカテゴリ']);

    $this->get('/categories')->assertSeeInOrder(['最初のカテゴリ', '次のカテゴリ']);
});

it('カテゴリが0件なら、空の状態で作成を案内する', function () {
    $this->get('/categories')
        ->assertOk()
        ->assertSee('まだカテゴリがありません')
        ->assertDontSee('+ カテゴリを作成');
});

it('カテゴリ作成画面を表示できる', function () {
    $this->get('/categories/create')
        ->assertOk()
        ->assertSee('<title>カテゴリを作成 | Quiz</title>', false)
        ->assertSee('作成する');
});

it('カテゴリを作成するとその詳細へ移り、トーストで知らせる', function () {
    $response = $this->post('/categories', ['name' => '英語']);

    $category = Category::sole();
    expect($category->name)->toBe('英語');
    $response->assertRedirect(route('categories.show', $category))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'カテゴリを作成しました']);
});

it('カテゴリのカードは詳細へのリンクになる', function () {
    $category = Category::factory()->create();

    $this->get('/categories')->assertSee('href="'.route('categories.show', $category).'"', false);
});

it('カテゴリ名が空・101文字以上なら作成できず、「カテゴリ名」としてエラーを伝える', function (?string $name, string $message) {
    $this->from('/categories/create')
        ->post('/categories', ['name' => $name])
        ->assertRedirect('/categories/create')
        ->assertSessionHasErrors(['name' => $message]);

    expect(Category::count())->toBe(0);
})->with([
    '空' => ['', 'カテゴリ名を入力してください。'],
    '101文字' => [str_repeat('あ', 101), 'カテゴリ名は100文字以内で入力してください。'],
]);

it('ログインしていなければ、一覧も作成もできない', function () {
    auth()->logout();

    $this->get('/categories')->assertRedirect('/login');
    $this->get('/categories/create')->assertRedirect('/login');
    $this->post('/categories', ['name' => '英語'])->assertRedirect('/login');

    expect(Category::count())->toBe(0);
});
