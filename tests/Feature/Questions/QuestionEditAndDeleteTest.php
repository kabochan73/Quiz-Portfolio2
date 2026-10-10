<?php

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    // 境界を確かめやすいよう、上限を2問に下げる
    config(['quiz.max_questions_per_section' => 2]);

    $this->category = Category::factory()->create(['name' => '基本情報']);
    $this->section = Section::factory()->for($this->category)->create(['name' => 'ネットワーク']);
    $this->question = Question::factory()->for($this->admin)->for($this->section)->create(['body' => '元の問題文']);
});

function fullSection(Category $category, User $user): Section
{
    $section = Section::factory()->for($category)->create(['name' => '満杯のセクション']);
    Question::factory()->count(2)->for($user)->for($section)->create();

    return $section;
}

it('問題詳細の「…」メニューに編集と削除がある', function () {
    $this->get(route('questions.show', $this->question))
        ->assertSee('aria-label="問題の操作"', false)
        ->assertSee('href="'.route('questions.edit', $this->question).'"', false)
        ->assertSee("\$dispatch('open-modal', 'delete-question')", false);
});

it('編集画面には今の問題文が入り、セクションはカテゴリ名と問題数付きで選べる', function () {
    $html = $this->get(route('questions.edit', $this->question))
        ->assertOk()
        ->assertSee('元の問題文')
        ->assertSee('上限の2問に達しているセクションには移せません。')
        ->getContent();

    // 括弧が正規表現の記号として扱われないよう、表示名は preg_quote で文字どおりに比べる
    expect($html)->toMatch('#<option value="'.$this->section->id.'"\s+selected\s*>'.preg_quote('基本情報 / ネットワーク(1問)', '#').'#');
});

it('上限に達しているセクションは選べず、今いるセクションは満杯でも選べる', function () {
    $full = fullSection($this->category, $this->admin);
    Question::factory()->for($this->admin)->for($this->section)->create(); // 今いるセクションも満杯にする

    $html = $this->get(route('questions.edit', $this->question))->getContent();

    expect($html)->toMatch('#<option value="'.$full->id.'"\s+disabled>#')
        ->not->toMatch('#<option value="'.$this->section->id.'"[^>]*disabled#');
});

it('問題文を変えて保存すると問題詳細へ戻り、トーストで知らせる', function () {
    $this->put(route('questions.update', $this->question), ['body' => '新しい問題文', 'section_id' => $this->section->id])
        ->assertRedirect(route('questions.show', $this->question))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => '問題を保存しました']);

    expect($this->question->fresh()->body)->toBe('新しい問題文');
});

it('別のセクションに移せる', function () {
    $other = Section::factory()->create();

    $this->put(route('questions.update', $this->question), ['body' => '元の問題文', 'section_id' => $other->id])
        ->assertSessionHasNoErrors();

    expect($this->question->fresh()->section_id)->toBe($other->id);
});

it('上限に達しているセクションには移せず、セクションの欄にエラーを出す', function () {
    $full = fullSection($this->category, $this->admin);

    $this->put(route('questions.update', $this->question), ['body' => '元の問題文', 'section_id' => $full->id])
        ->assertSessionHasErrors(['section_id' => '1セクションに登録できる問題は2問までです。']);

    expect($this->question->fresh()->section_id)->toBe($this->section->id);
});

it('同じセクションのままなら、満杯でも問題文を保存できる', function () {
    Question::factory()->for($this->admin)->for($this->section)->create(); // 今いるセクションを満杯にする

    $this->put(route('questions.update', $this->question), ['body' => '直した問題文', 'section_id' => $this->section->id])
        ->assertSessionHasNoErrors();

    expect($this->question->fresh()->body)->toBe('直した問題文');
});

it('編集では、セクションが未選択・存在しないセクションなら保存できない', function (?int $sectionId, string $message) {
    $this->put(route('questions.update', $this->question), ['body' => '元の問題文', 'section_id' => $sectionId])
        ->assertSessionHasErrors(['section_id' => $message]);
})->with([
    '未選択' => [null, 'セクションを選んでください。'],
    '存在しない' => [999, '選択されたセクションは存在しません。'],
]);

it('削除の確認モーダルに、一緒に消える回答の履歴の件数を出す', function () {
    Answer::factory()->count(3)->for($this->question)->create();

    $this->get(route('questions.show', $this->question))
        ->assertSee('問題を削除しますか?')
        ->assertSee('この問題と、回答の履歴3件も削除されます。');
});

it('削除すると回答と採点結果も消え、セクション詳細へ戻ってトーストで知らせる', function () {
    $answer = Answer::factory()->for($this->question)->create();
    Score::factory()->for($answer)->create();

    $this->delete(route('questions.destroy', $this->question))
        ->assertRedirect(route('sections.show', $this->section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => '問題を削除しました']);

    expect(Question::count())->toBe(0)
        ->and(Answer::count())->toBe(0)
        ->and(Score::count())->toBe(0);
});

it('削除で回答が0件になった挑戦は消し、ほかの問題の回答が残る挑戦は残す', function () {
    $otherQuestion = Question::factory()->for($this->admin)->for($this->section)->create();

    // この問題だけに回答した挑戦(削除すると空になる)
    $onlyThis = Attempt::factory()->for($this->section)->create();
    Answer::factory()->for($onlyThis)->for($this->question)->create();

    // 2問に回答した挑戦(もう1問の回答が残る)
    $both = Attempt::factory()->for($this->section)->create();
    Answer::factory()->for($both)->for($this->question)->create(['position' => 0]);
    Answer::factory()->for($both)->for($otherQuestion)->create(['position' => 1]);

    $this->delete(route('questions.destroy', $this->question));

    expect(Attempt::pluck('id')->all())->toBe([$both->id])
        ->and($both->answers()->count())->toBe(1);
});

it('他人の問題は、編集・保存・削除のどれも 404 にする', function () {
    $others = Question::factory()->create(['body' => '他人の問題']);

    $this->get(route('questions.edit', $others))->assertNotFound();
    $this->put(route('questions.update', $others), ['body' => '書き換え', 'section_id' => $others->section_id])->assertNotFound();
    $this->delete(route('questions.destroy', $others))->assertNotFound();

    expect($others->fresh()->body)->toBe('他人の問題');
});

it('ログインしていなければ、編集も削除もできない', function () {
    auth()->logout();

    $this->get(route('questions.edit', $this->question))->assertRedirect('/login');
    $this->put(route('questions.update', $this->question), ['body' => '変更', 'section_id' => $this->section->id])->assertRedirect('/login');
    $this->delete(route('questions.destroy', $this->question))->assertRedirect('/login');

    expect($this->question->fresh()->body)->toBe('元の問題文');
});
