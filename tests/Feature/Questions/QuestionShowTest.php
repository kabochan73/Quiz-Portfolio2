<?php

use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('問題詳細に、問題文の全文を改行を保ったまま表示する', function () {
    $body = "TCPとUDPの違いを説明してください。\n\n用途の例も挙げてください。".str_repeat('長い文章', 20);
    $question = Question::factory()->for($this->admin)->create(['body' => $body]);

    $main = Str::between($this->get(route('questions.show', $question))->assertOk()->getContent(), '<main', '</main>');

    expect($main)->toContain('whitespace-pre-wrap')
        ->toContain(e($body));
});

it('見出しとパンくずは、セクションの中で何問目かを「問題 N」で表す', function () {
    $section = Section::factory()->create(['name' => 'ネットワーク']);
    Question::factory()->for($this->admin)->for($section)->create();
    $second = Question::factory()->for($this->admin)->for($section)->create();

    $this->get(route('questions.show', $second))
        ->assertSee('<title>問題 2 | Quiz</title>', false)
        ->assertSeeInOrder(['パンくずリスト', 'カテゴリ', $section->category->name, 'ネットワーク', '問題 2']);
});

it('セクション詳細の問題の行は、問題詳細へのリンクになる', function () {
    $question = Question::factory()->for($this->admin)->create();

    $this->get(route('sections.show', $question->section))
        ->assertSee('href="'.route('questions.show', $question).'"', false);
});

it('問題詳細では、サイドバーで問題が属するセクションを強調する', function () {
    $question = Question::factory()->for($this->admin)->create();

    expect($this->get(route('questions.show', $question))->getContent())
        ->toMatch('#href="'.preg_quote(route('sections.show', $question->section), '#').'"\s+aria-current="page"#');
});

it('他人の問題は、存在を知らせないよう 404 にする', function () {
    $othersQuestion = Question::factory()->create(); // 別のユーザーの問題

    $this->get(route('questions.show', $othersQuestion))
        ->assertNotFound()
        ->assertDontSee($othersQuestion->body);
});

it('存在しない問題は 404 になる', function () {
    $this->get('/questions/999')->assertNotFound();
});

it('ログインしていなければ開けない', function () {
    $question = Question::factory()->for($this->admin)->create();
    auth()->logout();

    $this->get(route('questions.show', $question))->assertRedirect('/login');
});
