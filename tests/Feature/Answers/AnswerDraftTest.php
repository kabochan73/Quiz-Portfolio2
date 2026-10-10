<?php

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Js;

// 下書きの保存・復元そのものはブラウザの JavaScript(resources/js/answer-form.js)で動くので、
// ここではフォームと結果画面に正しい設定が渡っていることを確かめる。

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    $this->section = Section::factory()->create();
    $this->questions = Question::factory()->count(2)->for($this->section)->for($this->admin)->create();
});

it('回答フォームに、セクションと種別を含む下書きのキーと、問題 ID の一覧を渡す', function () {
    $expected = (string) Js::from([
        'storageKey' => "quiz:draft:section:{$this->section->id}:all",
        'questionIds' => $this->questions->pluck('id')->map(fn ($id) => (string) $id)->all(),
        'hasOldInput' => false,
    ]);

    $this->get(route('answers.create', $this->section))
        ->assertSee('answerForm('.$expected.')', false);
});

it('回答欄には、どの問題の回答かを示す問題 ID を付ける', function () {
    $html = $this->get(route('answers.create', $this->section))->getContent();

    foreach ($this->questions as $question) {
        expect($html)->toContain('data-question-id="'.$question->id.'"');
    }
});

it('進捗表示・回答済みの印・保存の表示・下書きの破棄ボタンを出す', function () {
    $this->get(route('answers.create', $this->section))
        ->assertSee('/ 2 問 回答済み')
        ->assertSee("isAnswered('{$this->questions[0]->id}')", false)
        ->assertSee('✓ 下書きを保存しました')
        ->assertSee('下書きを破棄');
});

it('送信エラーで戻ってきたときは、下書きで上書きしない設定にする', function () {
    $expected = (string) Js::from([
        'storageKey' => "quiz:draft:section:{$this->section->id}:all",
        'questionIds' => $this->questions->pluck('id')->map(fn ($id) => (string) $id)->all(),
        'hasOldInput' => true,
    ]);

    // 送信時の入力(old)がセッションに残っている状態で開く
    $this->withSession(['_old_input' => [
        'grading_level' => 'hard',
        'answers' => [['question_id' => $this->questions[0]->id, 'body' => '送信した回答']],
    ]])
        ->get(route('answers.create', $this->section))
        ->assertSee('answerForm('.$expected.')', false)
        ->assertSee('送信した回答');
});

it('結果画面を開くと、その挑戦のセクションと種別の下書きを消す', function (AttemptMode $mode) {
    $attempt = Attempt::factory()->completed()->for($this->section)->for($this->admin)->create(['mode' => $mode]);

    $key = "quiz:draft:section:{$this->section->id}:{$mode->value}";

    $this->get(route('history.show', [$this->section, $attempt]))
        ->assertSee('localStorage.removeItem('.Js::from($key).')', false);
})->with(AttemptMode::cases());
