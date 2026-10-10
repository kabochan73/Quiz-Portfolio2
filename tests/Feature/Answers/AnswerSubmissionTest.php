<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);

    $this->section = Section::factory()->create(['name' => 'ネットワーク']);
    $this->questions = Question::factory()->count(3)->for($this->admin)->for($this->section)->create();
});

/**
 * セクションの全問への回答として送る内容を作る。$overrides で一部を差し替えられる。
 */
function answerPayload(iterable $questions, array $overrides = []): array
{
    $answers = collect($questions)->values()->map(fn (Question $question, int $i) => [
        'question_id' => $question->id,
        'body' => "問題{$i}への回答",
    ])->all();

    return array_replace_recursive(['grading_level' => 'normal', 'answers' => $answers], $overrides);
}

it('セクション詳細に「全問に回答する」ボタンを出す', function () {
    $this->get(route('sections.show', $this->section))
        ->assertSee('href="'.route('answers.create', $this->section).'"', false)
        ->assertSee('全問に回答する(3問)');
});

it('問題が0問のセクションには、回答ボタンを出さない', function () {
    $empty = Section::factory()->create();

    $this->get(route('sections.show', $empty))
        ->assertDontSee('href="'.route('answers.create', $empty).'"', false);
});

it('回答フォームに全問と回答欄を並べ、採点レベルの既定は「普通」にする', function () {
    $html = $this->get(route('answers.create', $this->section))
        ->assertOk()
        ->assertSee('<title>全問に回答する | Quiz</title>', false)
        ->assertSee('採点する(3問)')
        ->assertSeeInOrder(['問題 1', $this->questions[0]->body, '問題 2', '問題 3'])
        ->getContent();

    expect(substr_count($html, 'name="answers['))->toBe(6) // 問題ごとに question_id と body
        ->and($html)->toMatch('#value="normal"[^>]*checked#')
        ->and($html)->not->toMatch('#value="hard"[^>]*checked#')
        ->and($html)->toContain('優しい')->toContain('厳しい');
});

it('問題が0問のセクションの回答フォームは開かせず、理由を伝える', function () {
    $empty = Section::factory()->create();

    $this->get(route('answers.create', $empty))
        ->assertRedirect(route('sections.show', $empty))
        ->assertSessionHas('toast', ['type' => 'error', 'message' => 'このセクションにはまだ問題がありません']);
});

it('回答を送ると、採点待ちの挑戦と、問題ごとの回答を順番付きで保存する', function () {
    $this->post(route('answers.store', $this->section), answerPayload($this->questions, ['grading_level' => 'hard']))
        ->assertRedirect(route('sections.show', $this->section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => '回答を送信しました']);

    $attempt = Attempt::sole();
    expect($attempt->section_id)->toBe($this->section->id)
        ->and($attempt->user_id)->toBe($this->admin->id)
        ->and($attempt->status)->toBe(AttemptStatus::Pending)
        ->and($attempt->mode)->toBe(AttemptMode::All)
        ->and($attempt->grading_level)->toBe(GradingLevel::Hard)
        ->and($attempt->answers->pluck('position')->all())->toBe([0, 1, 2])
        ->and($attempt->answers->pluck('question_id')->all())->toBe($this->questions->pluck('id')->all())
        ->and($attempt->answers->first()->body)->toBe('問題0への回答');
});

it('回答が空・5001文字以上なら保存せず、「回答」としてエラーを伝える', function (string $body, string $message) {
    $this->post(route('answers.store', $this->section), answerPayload($this->questions, ['answers' => [1 => ['body' => $body]]]))
        ->assertSessionHasErrors(['answers.1.body' => $message]);

    expect(Attempt::count())->toBe(0)->and(Answer::count())->toBe(0);
})->with([
    '空' => ['', '回答を入力してください。'],
    '5001文字' => [str_repeat('あ', 5001), '回答は5000文字以内で入力してください。'],
]);

it('採点レベルが未選択・想定外の値なら保存しない', function (?string $level, string $message) {
    $this->post(route('answers.store', $this->section), answerPayload($this->questions, ['grading_level' => $level]))
        ->assertSessionHasErrors(['grading_level' => $message]);

    expect(Attempt::count())->toBe(0);
})->with([
    '未選択' => [null, '採点レベルを選んでください。'],
    '想定外' => ['very_hard', '選択された採点レベルは正しくありません。'],
]);

it('問題が1問足りないと保存しない', function () {
    $this->post(route('answers.store', $this->section), answerPayload($this->questions->take(2)))
        ->assertSessionHasErrors(['answers' => '問題の内容が変わっています。ページを再読み込みして、もう一度回答してください。']);

    expect(Attempt::count())->toBe(0)->and(Answer::count())->toBe(0);
});

it('ほかのセクションの問題が混ざっていると保存しない', function () {
    $mixed = $this->questions->take(2)->push(Question::factory()->create());

    $this->post(route('answers.store', $this->section), answerPayload($mixed))
        ->assertSessionHasErrors(['answers' => '問題の内容が変わっています。ページを再読み込みして、もう一度回答してください。']);

    expect(Attempt::count())->toBe(0)->and(Answer::count())->toBe(0);
});

it('途中で保存に失敗したら、挑戦も回答も残さない', function () {
    // 2問目の回答を保存しようとしたところで、わざと失敗させる
    Answer::creating(function (Answer $answer) {
        if ($answer->position === 1) {
            throw new RuntimeException('保存に失敗しました');
        }
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('answers.store', $this->section), answerPayload($this->questions)))
        ->toThrow(RuntimeException::class);

    expect(Attempt::count())->toBe(0)->and(Answer::count())->toBe(0);
});

it('ログインしていなければ、回答フォームも送信も使えない', function () {
    auth()->logout();

    $this->get(route('answers.create', $this->section))->assertRedirect('/login');
    $this->post(route('answers.store', $this->section), answerPayload($this->questions))->assertRedirect('/login');

    expect(Attempt::count())->toBe(0);
});
