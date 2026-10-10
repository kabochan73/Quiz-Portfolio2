<?php

use App\Enums\AttemptStatus;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Queries\LatestScores;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->section = Section::factory()->create();
});

/**
 * 問題に、指定した日数前の回答を1件作る。$score が null なら未採点。
 */
function answerFor(Question $question, int $daysAgo, ?int $score, AttemptStatus $status = AttemptStatus::Completed): Answer
{
    $attempt = Attempt::factory()->for($question->section)->create(['status' => $status, 'created_at' => now()->subDays($daysAgo)]);
    $answer = Answer::factory()->for($attempt)->for($question)->create(['created_at' => now()->subDays($daysAgo)]);

    if ($score !== null) {
        Score::factory()->for($answer)->create(['score' => $score]);
    }

    return $answer;
}

it('問題ごとに、一番新しい回答の点数を返す', function () {
    $tcp = Question::factory()->for($this->section)->create();
    $dns = Question::factory()->for($this->section)->create();
    answerFor($tcp, 9, 40);
    answerFor($tcp, 5, 70);
    answerFor($tcp, 7, 55);
    answerFor($dns, 3, 90);

    expect((new LatestScores)->forQuestions([$tcp->id, $dns->id]))
        ->toBe([$tcp->id => 70, $dns->id => 90]);
});

it('採点中・失敗の挑戦の回答は数えず、その前の採点済みの点数を返す', function () {
    $question = Question::factory()->for($this->section)->create();
    answerFor($question, 9, 45);
    answerFor($question, 2, null, AttemptStatus::Failed);
    answerFor($question, 1, null, AttemptStatus::Grading);

    expect((new LatestScores)->forQuestions([$question->id]))->toBe([$question->id => 45]);
});

it('一度も採点されていない問題は結果に含めない', function () {
    $graded = Question::factory()->for($this->section)->create();
    $ungraded = Question::factory()->for($this->section)->create();
    answerFor($graded, 1, 80);

    expect((new LatestScores)->forQuestions([$graded->id, $ungraded->id]))->toBe([$graded->id => 80]);
});

it('指定した時点より前の、最新の点数を返す(前回の点数)', function () {
    $question = Question::factory()->for($this->section)->create();
    answerFor($question, 9, 40);
    answerFor($question, 5, 70);
    answerFor($question, 1, 95);

    expect((new LatestScores)->forQuestions([$question->id], now()->subDays(3)))->toBe([$question->id => 70])
        ->and((new LatestScores)->forQuestions([$question->id], now()->subDays(10)))->toBe([]);
});

it('問題が空なら、問い合わせずに空を返す', function () {
    DB::enableQueryLog();

    expect((new LatestScores)->forQuestions([]))->toBe([])
        ->and(DB::getQueryLog())->toBe([]);
});

it('問題がいくつあっても、問い合わせは1回だけ', function () {
    $questions = Question::factory()->count(10)->for($this->section)->create();
    $questions->each(function (Question $question) {
        answerFor($question, 3, 50);
        answerFor($question, 1, 80);
    });

    DB::enableQueryLog();
    $scores = (new LatestScores)->forQuestions($questions->pluck('id'));

    expect($scores)->toHaveCount(10)
        ->and(array_unique($scores))->toBe([$questions->first()->id => 80])
        ->and(DB::getQueryLog())->toHaveCount(1);
});
