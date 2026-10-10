<?php

use App\Models\Question;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('セクション詳細に「+ 問題を追加」がある', function () {
    $section = Section::factory()->create();
    Question::factory()->for($section)->create();

    $this->get(route('sections.show', $section))
        ->assertSee('href="'.route('sections.questions.create', $section).'"', false)
        ->assertSee('+ 問題を追加');
});

it('問題が0問のセクションでは、空の状態から問題を追加できる', function () {
    $section = Section::factory()->create();

    $this->get(route('sections.show', $section))
        ->assertSee('まだ問題がありません')
        ->assertSee('href="'.route('sections.questions.create', $section).'"', false);
});

it('問題作成画面を、追加先のセクションが分かる形で表示する', function () {
    $section = Section::factory()->create(['name' => 'ネットワーク']);

    $this->get(route('sections.questions.create', $section))
        ->assertOk()
        ->assertSee('<title>問題を追加 | Quiz</title>', false)
        ->assertSee('セクション: ネットワーク')
        ->assertSee('/ 2000');
});

it('問題を作成すると、ログイン中のユーザーの問題としてセクションに追加し、セクション詳細へ戻る', function () {
    $section = Section::factory()->create();

    $this->post(route('sections.questions.store', $section), ['body' => 'TCPとUDPの違いを説明してください。'])
        ->assertRedirect(route('sections.show', $section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => '問題を作成しました']);

    $question = Question::sole();
    expect($question->body)->toBe('TCPとUDPの違いを説明してください。')
        ->and($question->section_id)->toBe($section->id)
        ->and($question->user_id)->toBe($this->admin->id);
});

it('問題文が空・2001文字以上なら作成できず、「問題文」としてエラーを伝える', function (string $body, string $message) {
    $section = Section::factory()->create();

    $this->post(route('sections.questions.store', $section), ['body' => $body])
        ->assertSessionHasErrors(['body' => $message]);

    expect(Question::count())->toBe(0);
})->with([
    '空' => ['', '問題文を入力してください。'],
    '2001文字' => [str_repeat('あ', 2001), '問題文は2000文字以内で入力してください。'],
]);

it('問題文はちょうど2000文字なら作成できる', function () {
    $section = Section::factory()->create();

    $this->post(route('sections.questions.store', $section), ['body' => str_repeat('あ', 2000)])
        ->assertSessionHasNoErrors();

    expect(Question::count())->toBe(1);
});

describe('1セクションあたりの問題数の上限', function () {
    beforeEach(function () {
        // 境界を確かめやすいよう、上限を2問に下げる
        config(['quiz.max_questions_per_section' => 2]);
        $this->section = Section::factory()->create();
    });

    it('上限の1問手前までは作成できる', function () {
        Question::factory()->for($this->section)->create();

        $this->post(route('sections.questions.store', $this->section), ['body' => '2問目'])
            ->assertSessionHasNoErrors();

        expect($this->section->questions()->count())->toBe(2);
    });

    it('上限に達したセクションには作成できず、理由を伝える', function () {
        Question::factory()->count(2)->for($this->section)->create();

        $this->post(route('sections.questions.store', $this->section), ['body' => '3問目'])
            ->assertSessionHasErrors(['body' => '1セクションに登録できる問題は2問までです。']);

        expect($this->section->questions()->count())->toBe(2);
    });

    it('上限に達したセクションでは、追加ボタンを無効にして理由を添える', function () {
        Question::factory()->count(2)->for($this->section)->create();

        $this->get(route('sections.show', $this->section))
            ->assertSee('上限の2問に達しています')
            ->assertDontSee('href="'.route('sections.questions.create', $this->section).'"', false);
    });

    it('上限に達したセクションの作成画面を直接開くと、セクション詳細へ戻して理由を伝える', function () {
        Question::factory()->count(2)->for($this->section)->create();

        $this->get(route('sections.questions.create', $this->section))
            ->assertRedirect(route('sections.show', $this->section))
            ->assertSessionHas('toast', ['type' => 'error', 'message' => '1セクションに登録できる問題は2問までです']);
    });

    it('ほかのセクションの問題は数に含めない', function () {
        Question::factory()->count(2)->create();

        $this->post(route('sections.questions.store', $this->section), ['body' => '1問目'])
            ->assertSessionHasNoErrors();
    });
});

it('ログインしていなければ、問題を作成できない', function () {
    auth()->logout();
    $section = Section::factory()->create();

    $this->get(route('sections.questions.create', $section))->assertRedirect('/login');
    $this->post(route('sections.questions.store', $section), ['body' => '問題文'])->assertRedirect('/login');

    expect(Question::count())->toBe(0);
});
