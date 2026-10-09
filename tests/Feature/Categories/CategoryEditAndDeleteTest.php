<?php

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * セクション2つ・問題3問・挑戦2回(回答と採点つき)を持つカテゴリを作る。
 */
function categoryWithContents(): Category
{
    $category = Category::factory()->create(['name' => '基本情報']);
    $network = Section::factory()->for($category)->create();
    Section::factory()->for($category)->create();
    $questions = Question::factory()->count(3)->for($network)->create();

    foreach (range(1, 2) as $i) {
        $attempt = Attempt::factory()->completed()->for($network)->create();
        $answer = Answer::factory()->for($attempt)->for($questions->first())->create();
        Score::factory()->for($answer)->create();
    }

    return $category;
}

it('詳細の「…」メニューに編集と削除がある', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.show', $category))
        ->assertSee('aria-label="カテゴリの操作"', false)
        ->assertSee('href="'.route('categories.edit', $category).'"', false)
        ->assertSee("\$dispatch('open-modal', 'delete-category')", false);
});

it('編集画面には今の名前が入っている', function () {
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->get(route('categories.edit', $category))
        ->assertOk()
        ->assertSee('<title>カテゴリを編集 | Quiz</title>', false)
        ->assertSee('value="基本情報"', false)
        ->assertSee('保存する');
});

it('名前を変えて保存すると詳細へ戻り、トーストで知らせる', function () {
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->put(route('categories.update', $category), ['name' => '基本情報技術者'])
        ->assertRedirect(route('categories.show', $category))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'カテゴリを保存しました']);

    expect($category->fresh()->name)->toBe('基本情報技術者');
});

it('編集でも、カテゴリ名が空なら保存できない', function () {
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->from(route('categories.edit', $category))
        ->put(route('categories.update', $category), ['name' => ''])
        ->assertRedirect(route('categories.edit', $category))
        ->assertSessionHasErrors(['name' => 'カテゴリ名を入力してください。']);

    expect($category->fresh()->name)->toBe('基本情報');
});

it('削除の確認モーダルに、一緒に消えるセクション・問題・履歴の件数を出す', function () {
    $category = categoryWithContents();

    $this->get(route('categories.show', $category))
        ->assertSee('カテゴリを削除しますか?')
        ->assertSee('「基本情報」と、その中のセクション2件・問題3件・履歴2件もまとめて削除されます。');
});

it('削除すると配下のデータもまとめて消え、一覧へ戻ってトーストで知らせる', function () {
    $category = categoryWithContents();
    $other = Category::factory()->create();

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'カテゴリを削除しました']);

    expect(Category::pluck('id')->all())->toBe([$other->id])
        ->and(Section::count())->toBe(0)
        ->and(Question::count())->toBe(0)
        ->and(Attempt::count())->toBe(0)
        ->and(Answer::count())->toBe(0)
        ->and(Score::count())->toBe(0);
});

it('ログインしていなければ、編集も削除もできない', function () {
    auth()->logout();
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->get(route('categories.edit', $category))->assertRedirect('/login');
    $this->put(route('categories.update', $category), ['name' => '変更'])->assertRedirect('/login');
    $this->delete(route('categories.destroy', $category))->assertRedirect('/login');

    expect($category->fresh()->name)->toBe('基本情報');
});
