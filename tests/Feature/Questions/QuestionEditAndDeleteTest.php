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

    $this->category = Category::factory()->create(['name' => '基本情報']);
    $this->section = Section::factory()->for($this->category)->create(['name' => 'ネットワーク']);
    $this->question = Question::factory()->for($this->admin)->for($this->section)->create(['body' => '元の問題文']);
});

it('問題詳細の「…」メニューに編集と削除がある', function () {
    $this->get(route('questions.show', $this->question))
        ->assertSee('aria-label="問題の操作"', false)
        ->assertSee('href="'.route('questions.edit', $this->question).'"', false)
        ->assertSee("\$dispatch('open-modal', 'delete-question')", false);
});

it('編集画面には今の問題文が入り、セクションを選ぶ欄はない', function () {
    $this->get(route('questions.edit', $this->question))
        ->assertOk()
        ->assertSee('元の問題文')
        ->assertDontSee('name="section_id"', false);
});

it('問題文を変えて保存すると問題詳細へ戻り、トーストで知らせる', function () {
    $this->put(route('questions.update', $this->question), ['body' => '新しい問題文'])
        ->assertRedirect(route('questions.show', $this->question))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => '問題を保存しました']);

    expect($this->question->fresh()->body)->toBe('新しい問題文');
});

it('編集でも、問題文が空なら保存できない', function () {
    $this->put(route('questions.update', $this->question), ['body' => ''])
        ->assertSessionHasErrors(['body' => '問題文を入力してください。']);

    expect($this->question->fresh()->body)->toBe('元の問題文');
});

it('所属セクションは変更できず、セクションを送っても無視する', function () {
    $other = Section::factory()->create();

    $this->put(route('questions.update', $this->question), ['body' => '元の問題文', 'section_id' => $other->id])
        ->assertSessionHasNoErrors();

    expect($this->question->fresh()->section_id)->toBe($this->section->id);
});

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
    $this->put(route('questions.update', $others), ['body' => '書き換え'])->assertNotFound();
    $this->delete(route('questions.destroy', $others))->assertNotFound();

    expect($others->fresh()->body)->toBe('他人の問題');
});

it('ログインしていなければ、編集も削除もできない', function () {
    auth()->logout();

    $this->get(route('questions.edit', $this->question))->assertRedirect('/login');
    $this->put(route('questions.update', $this->question), ['body' => '変更'])->assertRedirect('/login');
    $this->delete(route('questions.destroy', $this->question))->assertRedirect('/login');

    expect($this->question->fresh()->body)->toBe('元の問題文');
});
