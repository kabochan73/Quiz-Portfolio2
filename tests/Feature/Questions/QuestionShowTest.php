<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Score;
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

/**
 * この問題への回答を、$daysAgo 日前の挑戦として作る。$score が null なら未採点。
 */
function recentAnswer(Question $question, int $daysAgo, ?int $score, array $attemptAttributes = []): Answer
{
    $at = now()->subDays($daysAgo);
    $attempt = Attempt::factory()->completed()->for($question->section)->for($question->user)
        ->create(array_merge(['created_at' => $at], $attemptAttributes));
    $answer = Answer::factory()->for($attempt)->for($question)->create(['created_at' => $at]);

    if ($score !== null) {
        Score::factory()->for($answer)->create(['score' => $score]);
    }

    return $answer;
}

it('最近の点数に、採点済みの直近5回分を新しい順に出し、各行から結果画面へ移れる', function () {
    $question = Question::factory()->for($this->admin)->create();
    foreach ([10, 20, 30, 40, 50, 60] as $i => $score) {
        recentAnswer($question, 10 - $i, $score); // 60点が一番新しい
    }
    $newest = Answer::orderByDesc('created_at')->first();

    $main = Str::between($this->get(route('questions.show', $question))->getContent(), '<main', '</main>');

    expect($main)->toMatch('#aria-label="60点".*aria-label="50点".*aria-label="40点".*aria-label="30点".*aria-label="20点"#s')
        ->not->toContain('aria-label="10点"')
        ->toContain('href="'.route('history.show', [$question->section_id, $newest->attempt_id]).'"');
});

it('最近の点数から、採点中・失敗の回答は除く', function () {
    $question = Question::factory()->for($this->admin)->create();
    recentAnswer($question, 3, 70);
    recentAnswer($question, 2, null, ['status' => AttemptStatus::Failed]);
    recentAnswer($question, 1, null, ['status' => AttemptStatus::Grading]);

    $main = Str::between($this->get(route('questions.show', $question))->getContent(), '<main', '</main>');

    expect(substr_count($main, '/history/'))->toBe(1)
        ->and($main)->toContain('aria-label="70点"');
});

it('最近の点数に、採点レベルと、苦手モードで回答したときの種別を出す', function () {
    $question = Question::factory()->for($this->admin)->create();
    recentAnswer($question, 2, 40, ['grading_level' => GradingLevel::Hard]);
    recentAnswer($question, 1, 70, ['mode' => AttemptMode::Weak]);

    $main = Str::between($this->get(route('questions.show', $question))->getContent(), '<main', '</main>');

    expect($main)->toMatch('#aria-label="70点".*普通.*苦手.*aria-label="40点".*厳しい#s');
});

it('採点された回答がなければ、そう伝える', function () {
    $question = Question::factory()->for($this->admin)->create();

    $this->get(route('questions.show', $question))->assertSee('まだ採点された回答がありません');
});
