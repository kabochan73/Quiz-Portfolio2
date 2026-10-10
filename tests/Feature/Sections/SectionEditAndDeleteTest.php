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
 * 問題2問・挑戦3回(回答と採点つき)を持つセクションを作る。
 */
function sectionWithContents(): Section
{
    $section = Section::factory()->create(['name' => 'ネットワーク']);
    $questions = Question::factory()->count(2)->for($section)->create();

    foreach (range(1, 3) as $i) {
        $attempt = Attempt::factory()->completed()->for($section)->create();
        $answer = Answer::factory()->for($attempt)->for($questions->first())->create();
        Score::factory()->for($answer)->create();
    }

    return $section;
}

it('詳細の「…」メニューに編集と削除がある', function () {
    $section = Section::factory()->create();

    $this->get(route('sections.show', $section))
        ->assertSee('aria-label="セクションの操作"', false)
        ->assertSee('href="'.route('sections.edit', $section).'"', false)
        ->assertSee("\$dispatch('open-modal', 'delete-section')", false);
});

it('編集画面には今の名前が入り、所属カテゴリを選べる', function () {
    $basic = Category::factory()->create(['name' => '基本情報']);
    Category::factory()->create(['name' => '応用情報']);
    $section = Section::factory()->for($basic)->create(['name' => 'ネットワーク']);

    $html = $this->get(route('sections.edit', $section))
        ->assertOk()
        ->assertSee('value="ネットワーク"', false)
        ->assertSee('別のカテゴリに移すと、問題と履歴も一緒に移ります。')
        ->getContent();

    expect($html)->toMatch('#<option value="'.$basic->id.'"\s+selected\s*>基本情報#')
        ->toContain('応用情報');
});

it('名前を変えて保存すると詳細へ戻り、トーストで知らせる', function () {
    $section = Section::factory()->create(['name' => 'ネットワーク']);

    $this->put(route('sections.update', $section), ['name' => 'ネットワーク基礎', 'category_id' => $section->category_id])
        ->assertRedirect(route('sections.show', $section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'セクションを保存しました']);

    expect($section->fresh()->name)->toBe('ネットワーク基礎');
});

it('別のカテゴリに移すと、問題と履歴も一緒に移る', function () {
    $section = sectionWithContents();
    $other = Category::factory()->create();

    $this->put(route('sections.update', $section), ['name' => $section->name, 'category_id' => $other->id]);

    $section->refresh();
    expect($section->category_id)->toBe($other->id)
        ->and($other->questions()->count())->toBe(2)
        ->and($other->attempts()->count())->toBe(3);
});

it('編集では、カテゴリが未選択・存在しないカテゴリなら保存できない', function (?int $categoryId, string $message) {
    $section = Section::factory()->create();
    $original = $section->category_id;

    $this->put(route('sections.update', $section), ['name' => '変更', 'category_id' => $categoryId])
        ->assertSessionHasErrors(['category_id' => $message]);

    expect($section->fresh()->category_id)->toBe($original);
})->with([
    '未選択' => [null, 'カテゴリを選んでください。'],
    '存在しない' => [999, '選択されたカテゴリは存在しません。'],
]);

it('作成のときは、カテゴリを送っても無視して URL のカテゴリに作る', function () {
    $category = Category::factory()->create();
    $other = Category::factory()->create();

    $this->post(route('categories.sections.store', $category), ['name' => 'ネットワーク', 'category_id' => $other->id]);

    expect(Section::sole()->category_id)->toBe($category->id);
});

it('削除の確認モーダルに、一緒に消える問題と履歴の件数を出す', function () {
    $section = sectionWithContents();

    $this->get(route('sections.show', $section))
        ->assertSee('セクションを削除しますか?')
        ->assertSee('「ネットワーク」と、その中の問題2件・履歴3件もまとめて削除されます。');
});

it('削除すると問題と履歴も消え、カテゴリ詳細へ戻ってトーストで知らせる', function () {
    $section = sectionWithContents();
    $category = $section->category;

    $this->delete(route('sections.destroy', $section))
        ->assertRedirect(route('categories.show', $category))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'セクションを削除しました']);

    expect(Section::count())->toBe(0)
        ->and(Question::count())->toBe(0)
        ->and(Attempt::count())->toBe(0)
        ->and(Answer::count())->toBe(0)
        ->and(Score::count())->toBe(0)
        ->and($category->fresh())->not->toBeNull();
});

it('ログインしていなければ、編集も削除もできない', function () {
    auth()->logout();
    $section = Section::factory()->create(['name' => 'ネットワーク']);

    $this->get(route('sections.edit', $section))->assertRedirect('/login');
    $this->put(route('sections.update', $section), ['name' => '変更', 'category_id' => $section->category_id])->assertRedirect('/login');
    $this->delete(route('sections.destroy', $section))->assertRedirect('/login');

    expect($section->fresh()->name)->toBe('ネットワーク');
});
