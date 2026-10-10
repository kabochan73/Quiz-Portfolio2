<?php

use App\Enums\AttemptMode;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Models\User;
use App\Queries\WeakQuestions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    $this->section = Section::factory()->create();
    // 4問: 59点(苦手)・60点・85点・未採点
    $this->questions = Question::factory()->count(4)->for($this->section)->for($this->admin)->create();
    $attempt = Attempt::factory()->completed()->for($this->section)->for($this->admin)->create(['created_at' => now()->subDay()]);
    foreach ([59, 60, 85] as $i => $score) {
        $answer = Answer::factory()->for($attempt)->for($this->questions[$i])->create(['position' => $i, 'created_at' => now()->subDay()]);
        Score::factory()->for($answer)->create(['score' => $score]);
    }
});

function weakPayload(iterable $questions): array
{
    return [
        'grading_level' => 'normal',
        'mode' => 'weak',
        'answers' => collect($questions)->values()->map(fn (Question $q) => ['question_id' => $q->id, 'body' => '再挑戦の回答'])->all(),
    ];
}

it('苦手問題は、最新の点数が基準点(60点)未満の問題だけで、未採点の問題は含めない', function () {
    expect(app(WeakQuestions::class)->ids($this->section))->toBe([$this->questions[0]->id]);
});

it('苦手の基準点は設定に従う', function () {
    config(['quiz.weak_threshold' => 61]);

    expect(app(WeakQuestions::class)->ids($this->section))->toBe([$this->questions[0]->id, $this->questions[1]->id]);
});

it('セクション詳細と結果画面に「苦手だけ再挑戦(N問)」を出す', function () {
    $url = route('answers.create', [$this->section, 'mode' => 'weak']);

    $this->get(route('sections.show', $this->section))
        ->assertSee('苦手だけ再挑戦(1問)')
        ->assertSee('href="'.e($url).'"', false);

    $this->get(route('history.show', [$this->section, Attempt::first()]))
        ->assertSee('苦手だけ再挑戦(1問)');
});

it('苦手な問題がなければ、ボタンを無効にして理由を添える', function () {
    config(['quiz.weak_threshold' => 50]);

    $main = Str::between($this->get(route('sections.show', $this->section))->getContent(), '<main', '</main>');

    expect($main)->toContain('苦手な問題はありません')
        ->not->toContain('mode=weak');
});

it('苦手モードの回答フォームには、苦手な問題だけを並べ、種別と下書きのキーも苦手用にする', function () {
    $main = Str::between($this->get(route('answers.create', [$this->section, 'mode' => 'weak']))->getContent(), '<main', '</main>');

    expect($main)->toContain('苦手な問題に再挑戦')
        ->toContain('前回60点未満だった1問です')
        ->toContain('name="mode" value="weak"')
        ->toContain($this->questions[0]->body)
        ->not->toContain('data-question-id="'.$this->questions[1]->id.'"')
        ->toContain("quiz:draft:section:{$this->section->id}:weak");
});

it('苦手な問題がないのに苦手モードのフォームを開くと、セクション詳細へ戻して理由を伝える', function () {
    config(['quiz.weak_threshold' => 50]);

    $this->get(route('answers.create', [$this->section, 'mode' => 'weak']))
        ->assertRedirect(route('sections.show', $this->section))
        ->assertSessionHas('toast', ['type' => 'error', 'message' => '苦手な問題はありません']);
});

it('苦手問題に回答すると、種別を「苦手」として保存する', function () {
    Queue::fake();

    $this->post(route('answers.store', $this->section), weakPayload([$this->questions[0]]))
        ->assertSessionHasNoErrors();

    $attempt = Attempt::latest('id')->first();
    expect($attempt->mode)->toBe(AttemptMode::Weak)
        ->and($attempt->answers->pluck('question_id')->all())->toBe([$this->questions[0]->id]);
});

it('苦手モードで、苦手でない問題を混ぜたり全問を送ったりすると保存しない', function () {
    $this->post(route('answers.store', $this->section), weakPayload([$this->questions[0], $this->questions[2]]))
        ->assertSessionHasErrors('answers');

    $this->post(route('answers.store', $this->section), weakPayload($this->questions))
        ->assertSessionHasErrors('answers');

    expect(Attempt::count())->toBe(1);
});

it('種別が未指定・想定外の値なら保存しない', function (?string $mode) {
    $payload = array_merge(weakPayload([$this->questions[0]]), ['mode' => $mode]);

    $this->post(route('answers.store', $this->section), $payload)->assertSessionHasErrors('mode');
})->with([null, 'random']);

it('URL の種別が想定外の値なら、全問のフォームとして開く', function () {
    $this->get(route('answers.create', [$this->section, 'mode' => 'random']))
        ->assertSee('全問に回答する')
        ->assertSee('name="mode" value="all"', false);
});
