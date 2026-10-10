<?php

use App\Enums\AttemptStatus;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('採点の状態を JSON で返す', function (AttemptStatus $status) {
    $attempt = Attempt::factory()->for($this->admin)->create(['status' => $status]);

    $this->getJson(route('attempts.status', $attempt))
        ->assertOk()
        ->assertExactJson(['status' => $status->value]);
})->with(AttemptStatus::cases());

it('他人の挑戦の状態は 404 にする', function () {
    $attempt = Attempt::factory()->create();

    $this->getJson(route('attempts.status', $attempt))->assertNotFound();
});

it('ログインしていなければ状態を確かめられない', function () {
    $attempt = Attempt::factory()->for($this->admin)->create();
    auth()->logout();

    $this->getJson(route('attempts.status', $attempt))->assertUnauthorized();
});

it('採点中の結果画面では、状態の確認(ポーリング)を始め、問題の数だけスケルトンを並べる', function () {
    $attempt = Attempt::factory()->for($this->admin)->create(['status' => AttemptStatus::Grading]);
    Answer::factory()->count(3)->for($attempt)->sequence(fn ($sequence) => ['position' => $sequence->index])->create();

    $main = Str::between($this->get(route('history.show', [$attempt->section_id, $attempt]))->getContent(), '<main', '</main>');

    expect($main)->toContain('attemptPoller(')
        ->toContain(str_replace('/', '\/', route('attempts.status', $attempt)))
        ->toContain('AIが3問の回答を確認しています。')
        ->toContain('時間がかかっています')
        ->and(substr_count($main, 'h-7 w-12 rounded-full'))->toBe(3);
});

it('採点が終わった結果画面では、状態の確認をしない', function (AttemptStatus $status) {
    $attempt = Attempt::factory()->for($this->admin)->create(['status' => $status]);

    $this->get(route('history.show', [$attempt->section_id, $attempt]))
        ->assertDontSee('attemptPoller(', false);
})->with([AttemptStatus::Completed, AttemptStatus::Failed]);
