<?php

use App\Models\Category;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('サイドバーに全カテゴリとセクションを作成順に表示する', function () {
    $basic = Category::factory()->create(['name' => '基本情報']);
    Section::factory()->for($basic)->create(['name' => 'ネットワーク']);
    Section::factory()->for($basic)->create(['name' => 'データベース']);
    Category::factory()->create(['name' => '英語']);

    $this->get('/categories')
        ->assertSee('aria-label="カテゴリとセクション"', false)
        ->assertSeeInOrder(['基本情報', 'ネットワーク', 'データベース', '英語'])
        ->assertSee('href="'.route('categories.show', $basic).'"', false)
        ->assertDontSee('まだカテゴリがありません');
});

it('カテゴリ詳細を開いているときは、そのカテゴリを強調して開き、ほかは閉じておく', function () {
    $basic = Category::factory()->create(['name' => '基本情報']);
    Section::factory()->for($basic)->create();
    $english = Category::factory()->create(['name' => '英語']);
    Section::factory()->for($english)->create();

    $html = $this->get(route('categories.show', $basic))->getContent();

    // 基本情報だけが開いた状態で始まり、リンクに aria-current が付く
    expect(substr_count($html, 'x-data="{ open: true }"'))->toBe(2) // PC 用とドロワー用で2回出力される
        ->and($html)->toMatch('#href="'.preg_quote(route('categories.show', $basic), '#').'"\s+aria-current="page"#')
        ->and($html)->not->toMatch('#href="'.preg_quote(route('categories.show', $english), '#').'"\s+aria-current#');
});

it('カテゴリ詳細以外のページでは、どのカテゴリも強調しない', function () {
    Category::factory()->create();

    // パンくずの現在地にも aria-current が付くので、リンク(サイドバー)に付いていないかで確かめる
    $html = $this->get('/categories')->getContent();

    expect($html)->not->toMatch('#<a href="[^"]*"\s+aria-current#')
        ->and($html)->not->toContain('x-data="{ open: true }"');
});

it('セクション詳細がまだないので、セクションはリンクにせず文字だけで表示する', function () {
    Section::factory()->create(['name' => 'ネットワーク']);

    $html = $this->get('/categories')->getContent();

    expect($html)->toMatch('#<span class="truncate">ネットワーク</span>#')
        ->and($html)->not->toMatch('#<a href="[^"]*/sections/#');
});

it('カテゴリが0件なら、サイドバーに案内文を表示する', function () {
    $this->get('/categories')->assertSee('まだカテゴリがありません');
});

it('サイドバーのための問い合わせは、カテゴリが増えても増えない', function () {
    Category::factory()->count(5)->has(Section::factory()->count(3))->create();

    DB::enableQueryLog();
    $this->get('/categories');
    $withFive = count(DB::getQueryLog());

    Category::factory()->count(5)->has(Section::factory()->count(3))->create();

    DB::flushQueryLog();
    $this->get('/categories');
    $withTen = count(DB::getQueryLog());

    expect($withTen)->toBe($withFive);
});
