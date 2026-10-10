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

it('カテゴリ詳細に「+ セクションを追加」と、セクション詳細へのリンクがある', function () {
    $category = Category::factory()->create();
    $section = Section::factory()->for($category)->create();

    $this->get(route('categories.show', $category))
        ->assertSee('href="'.route('categories.sections.create', $category).'"', false)
        ->assertSee('href="'.route('sections.show', $section).'"', false);
});

it('セクションが0件のカテゴリでは、空の状態からセクションを追加できる', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.show', $category))
        ->assertSee('まだセクションがありません')
        ->assertSee('href="'.route('categories.sections.create', $category).'"', false)
        ->assertDontSee('+ セクションを追加');
});

it('セクション作成画面を、所属カテゴリが分かる形で表示する', function () {
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->get(route('categories.sections.create', $category))
        ->assertOk()
        ->assertSee('<title>セクションを追加 | Quiz</title>', false)
        ->assertSee('カテゴリ: 基本情報');
});

it('セクションを作成すると、そのカテゴリに属し、詳細へ移ってトーストで知らせる', function () {
    $category = Category::factory()->create();

    $response = $this->post(route('categories.sections.store', $category), ['name' => 'ネットワーク']);

    $section = Section::sole();
    expect($section->name)->toBe('ネットワーク')
        ->and($section->category_id)->toBe($category->id);
    $response->assertRedirect(route('sections.show', $section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'セクションを作成しました']);
});

it('セクション名が空・101文字以上なら作成できず、「セクション名」としてエラーを伝える', function (string $name, string $message) {
    $category = Category::factory()->create();

    $this->post(route('categories.sections.store', $category), ['name' => $name])
        ->assertSessionHasErrors(['name' => $message]);

    expect(Section::count())->toBe(0);
})->with([
    '空' => ['', 'セクション名を入力してください。'],
    '101文字' => [str_repeat('あ', 101), 'セクション名は100文字以内で入力してください。'],
]);

it('存在しないカテゴリにはセクションを作れない', function () {
    $this->post('/categories/999/sections', ['name' => 'ネットワーク'])->assertNotFound();
});

it('セクション詳細に、問題を作成順に番号と抜粋で表示し、問題数と上限を出す', function () {
    $category = Category::factory()->create(['name' => '基本情報']);
    $section = Section::factory()->for($category)->create(['name' => 'ネットワーク']);
    Question::factory()->for($section)->create(['body' => 'TCPとUDPの違いを説明してください。']);
    Question::factory()->for($section)->create(['body' => str_repeat('長い問題文', 20)]);

    $html = $this->get(route('sections.show', $section))
        ->assertOk()
        ->assertSee('<title>ネットワーク | Quiz</title>', false)
        ->assertSee('2 / 10問')
        ->getContent();

    $main = Str::between($html, '<main', '</main>');
    expect($main)->toContain('TCPとUDPの違いを説明してください。')
        // 長い問題文は40文字で切って「…」を付ける
        ->toContain(mb_substr(str_repeat('長い問題文', 20), 0, 40).'…');
});

it('セクション詳細の問題の行に、最新の点数のバッジを出し、未採点の問題は「—」にする', function () {
    $section = Section::factory()->create();
    $graded = Question::factory()->for($section)->create();
    Question::factory()->for($section)->create(); // 未採点
    $attempt = Attempt::factory()->completed()->for($section)->create();
    $answer = Answer::factory()->for($attempt)->for($graded)->create();
    Score::factory()->for($answer)->create(['score' => 45]);

    $main = Str::between($this->get(route('sections.show', $section))->getContent(), '<main', '</main>');

    expect($main)->toContain('aria-label="45点"')
        ->toContain('bg-rose-50')       // 60点未満は赤
        ->toContain('aria-label="未採点"');
});

it('セクション詳細のパンくずは カテゴリ › カテゴリ名 › セクション名 になる', function () {
    $category = Category::factory()->create(['name' => '基本情報']);
    $section = Section::factory()->for($category)->create(['name' => 'ネットワーク']);

    $this->get(route('sections.show', $section))
        ->assertSee('<a href="'.route('categories.show', $category).'"', false)
        ->assertSeeInOrder(['パンくずリスト', 'カテゴリ', '基本情報', 'ネットワーク']);
});

it('問題が0問のセクションでは空の状態を表示する', function () {
    $section = Section::factory()->create();

    $this->get(route('sections.show', $section))
        ->assertSee('まだ問題がありません')
        ->assertSee('0 / 10問');
});

it('セクション詳細では、サイドバーのそのセクションを強調し、カテゴリを開く', function () {
    $section = Section::factory()->create();

    $html = $this->get(route('sections.show', $section))->getContent();

    expect($html)->toMatch('#href="'.preg_quote(route('sections.show', $section), '#').'"\s+aria-current="page"#')
        ->and(substr_count($html, 'x-data="{ open: true }"'))->toBe(2); // PC 用とドロワー用
});

it('存在しないセクションは 404 になる', function () {
    $this->get('/sections/999')->assertNotFound();
});

it('ログインしていなければ、作成も詳細も使えない', function () {
    auth()->logout();
    $section = Section::factory()->create();

    $this->get(route('categories.sections.create', $section->category))->assertRedirect('/login');
    $this->post(route('categories.sections.store', $section->category), ['name' => '追加'])->assertRedirect('/login');
    $this->get(route('sections.show', $section))->assertRedirect('/login');

    expect(Section::count())->toBe(1);
});
