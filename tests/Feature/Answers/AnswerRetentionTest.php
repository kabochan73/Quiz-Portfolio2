<?php

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Models\User;
use App\Services\AnswerRetentionService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // 境界を確かめやすいよう、残す件数を3件に下げる
    config(['quiz.answer_retention_limit' => 3]);

    $this->admin = User::factory()->create();
    $this->section = Section::factory()->create();
    $this->question = Question::factory()->for($this->section)->for($this->admin)->create();
});

/**
 * この問題だけに回答した挑戦を、古い順に $count 回作る(採点結果つき)。作った回答を古い順に返す。
 */
function pastAnswers(Section $section, Question $question, int $count): array
{
    $answers = [];

    foreach (range(1, $count) as $i) {
        $attempt = Attempt::factory()->completed()->for($section)->create(['created_at' => now()->subDays(100 - $i)]);
        $answer = Answer::factory()->for($attempt)->for($question)->create(['created_at' => now()->subDays(100 - $i)]);
        Score::factory()->for($answer)->create();
        $answers[] = $answer;
    }

    return $answers;
}

it('残す件数ちょうどまでは、何も消さない', function () {
    pastAnswers($this->section, $this->question, 3);

    app(AnswerRetentionService::class)->prune($this->section, [$this->question->id]);

    expect(Answer::count())->toBe(3)->and(Attempt::count())->toBe(3);
});

it('残す件数を超えたら、一番古い回答から消し、採点結果と空になった挑戦も消す', function () {
    $answers = pastAnswers($this->section, $this->question, 5);

    app(AnswerRetentionService::class)->prune($this->section, [$this->question->id]);

    // 新しい3件(3〜5回目)だけが残る
    expect(Answer::orderBy('created_at')->pluck('id')->all())->toBe([$answers[2]->id, $answers[3]->id, $answers[4]->id])
        ->and(Score::count())->toBe(3)
        ->and(Attempt::count())->toBe(3);
});

it('ほかの問題の回答は消さない', function () {
    $other = Question::factory()->for($this->section)->for($this->admin)->create();
    pastAnswers($this->section, $other, 5);
    pastAnswers($this->section, $this->question, 5);

    app(AnswerRetentionService::class)->prune($this->section, [$this->question->id]);

    expect($this->question->answers()->count())->toBe(3)
        ->and($other->answers()->count())->toBe(5);
});

it('一部の回答だけが消えた挑戦は残す', function () {
    $other = Question::factory()->for($this->section)->for($this->admin)->create();

    // 一番古い挑戦は、この問題ともう1問の両方に回答している
    $oldest = Attempt::factory()->completed()->for($this->section)->create(['created_at' => now()->subDays(200)]);
    Answer::factory()->for($oldest)->for($this->question)->create(['position' => 0, 'created_at' => now()->subDays(200)]);
    Answer::factory()->for($oldest)->for($other)->create(['position' => 1, 'created_at' => now()->subDays(200)]);
    pastAnswers($this->section, $this->question, 3);

    app(AnswerRetentionService::class)->prune($this->section, [$this->question->id]);

    // この問題の一番古い回答は消えるが、もう1問の回答が残るので挑戦は残る
    expect($oldest->fresh())->not->toBeNull()
        ->and($oldest->answers()->pluck('question_id')->all())->toBe([$other->id]);
});

it('採点中・失敗した挑戦の回答も件数に数える', function () {
    pastAnswers($this->section, $this->question, 2);
    $failed = Attempt::factory()->failed()->for($this->section)->create();
    Answer::factory()->for($failed)->for($this->question)->create();
    $grading = Attempt::factory()->for($this->section)->create(['status' => 'grading']);
    Answer::factory()->for($grading)->for($this->question)->create();

    app(AnswerRetentionService::class)->prune($this->section, [$this->question->id]);

    // 4件のうち、一番古い採点済みの1件だけが消える
    expect($this->question->answers()->count())->toBe(3)
        ->and($failed->fresh())->not->toBeNull()
        ->and($grading->fresh())->not->toBeNull();
});

it('回答を送ると古い回答を整理し、新しく送った回答は必ず残る', function () {
    Queue::fake();
    $this->actingAs($this->admin);
    pastAnswers($this->section, $this->question, 3);

    $this->post(route('answers.store', $this->section), [
        'grading_level' => 'normal',
        'answers' => [['question_id' => $this->question->id, 'body' => '今回の回答']],
    ])->assertSessionHasNoErrors();

    expect($this->question->answers()->count())->toBe(3)
        ->and($this->question->answers()->where('body', '今回の回答')->exists())->toBeTrue()
        ->and(Attempt::count())->toBe(3);
});
