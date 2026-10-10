<?php

use App\Enums\AttemptStatus;
use App\Jobs\GradeAttempt;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

/**
 * 回答2問を持つ、失敗した挑戦を作る。
 */
function failedAttempt(User $user): Attempt
{
    $attempt = Attempt::factory()->failed()->for($user)->create();

    foreach ([0, 1] as $position) {
        $question = Question::factory()->for($attempt->section)->for($user)->create(['body' => "問題文{$position}"]);
        Answer::factory()->for($attempt)->for($question)->create(['position' => $position, 'body' => "保存済みの回答{$position}"]);
    }

    return $attempt;
}

it('失敗の画面に、再採点ボタンと保存済みの回答を出し、失敗の理由は出さない', function () {
    $attempt = failedAttempt($this->admin);

    $main = Str::between($this->get(route('history.show', [$attempt->section_id, $attempt]))->getContent(), '<main', '</main>');

    expect($main)->toContain('採点できませんでした')
        ->toContain('action="'.route('attempts.regrade', $attempt).'"')
        ->toContain('再採点する')
        ->toContain('あなたの回答(2問)')
        ->toContain('保存済みの回答0')
        ->toContain('保存済みの回答1')
        ->not->toContain($attempt->error_message)
        // 回答のカードは閉じた状態で並べる
        ->and(substr_count($main, 'x-data="{ open: false }"'))->toBe(2);
});

it('再採点すると採点待ちに戻して失敗の理由を消し、採点 Job を投入して結果画面へ戻る', function () {
    Queue::fake();
    $attempt = failedAttempt($this->admin);

    $this->post(route('attempts.regrade', $attempt))
        ->assertRedirect(route('history.show', [$attempt->section_id, $attempt]));

    $attempt->refresh();
    expect($attempt->status)->toBe(AttemptStatus::Pending)
        ->and($attempt->error_message)->toBeNull();
    Queue::assertPushed(GradeAttempt::class, fn (GradeAttempt $job) => $job->attempt->is($attempt));
});

it('再採点すると、worker の採点(テストではその場で実行)で完了まで進む', function () {
    $attempt = failedAttempt($this->admin);

    $this->post(route('attempts.regrade', $attempt));

    $attempt->refresh();
    expect($attempt->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->answers()->has('score')->count())->toBe(2);
});

it('失敗していない挑戦は、再採点しても何も変えない', function (AttemptStatus $status) {
    Queue::fake();
    $attempt = Attempt::factory()->for($this->admin)->create(['status' => $status]);

    $this->post(route('attempts.regrade', $attempt))
        ->assertRedirect(route('history.show', [$attempt->section_id, $attempt]));

    expect($attempt->fresh()->status)->toBe($status);
    Queue::assertNothingPushed();
})->with([AttemptStatus::Pending, AttemptStatus::Grading, AttemptStatus::Completed]);

it('他人の挑戦は再採点できず、404 にする', function () {
    Queue::fake();
    $attempt = failedAttempt(User::factory()->create());

    $this->post(route('attempts.regrade', $attempt))->assertNotFound();

    expect($attempt->fresh()->status)->toBe(AttemptStatus::Failed);
    Queue::assertNothingPushed();
});

it('GET では再採点できない', function () {
    $attempt = failedAttempt($this->admin);

    $this->get('/attempts/'.$attempt->id.'/regrade')->assertStatus(405);
});

it('ログインしていなければ再採点できない', function () {
    $attempt = failedAttempt($this->admin);
    auth()->logout();

    $this->post(route('attempts.regrade', $attempt))->assertRedirect('/login');

    expect($attempt->fresh()->status)->toBe(AttemptStatus::Failed);
});
