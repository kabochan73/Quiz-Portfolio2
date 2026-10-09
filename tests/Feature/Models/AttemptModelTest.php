<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Score;

/**
 * 1つの挑戦に、指定した点数の回答を順に付ける。null を渡した位置は未採点の回答にする。
 *
 * @param  array<int, int|null>  $scores
 */
function attemptWithScores(array $scores): Attempt
{
    $attempt = Attempt::factory()->completed()->create();

    foreach ($scores as $position => $score) {
        $answer = Answer::factory()->for($attempt)->create(['position' => $position]);
        if ($score !== null) {
            Score::factory()->for($answer)->create(['score' => $score]);
        }
    }

    return $attempt->load('answers.score');
}

it('状態・種別・採点レベルは Enum として読み出せる', function () {
    $attempt = Attempt::factory()->weak()->create(['grading_level' => GradingLevel::Hard])->fresh();

    expect($attempt->status)->toBe(AttemptStatus::Pending)
        ->and($attempt->mode)->toBe(AttemptMode::Weak)
        ->and($attempt->grading_level)->toBe(GradingLevel::Hard);
});

it('採点済みの挑戦には、完了日時と利用量が入る', function () {
    $attempt = Attempt::factory()->completed()->create()->fresh();

    expect($attempt->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->graded_at)->not->toBeNull()
        ->and($attempt->input_tokens)->toBeInt()
        ->and($attempt->model)->toBe('claude-sonnet-5-5');
});

it('失敗した挑戦には、失敗理由が入る', function () {
    $attempt = Attempt::factory()->failed()->create()->fresh();

    expect($attempt->status)->toBe(AttemptStatus::Failed)
        ->and($attempt->error_message)->not->toBeEmpty();
});

it('挑戦の回答は position の順に並ぶ', function () {
    $attempt = Attempt::factory()->create();
    foreach ([2, 0, 1] as $position) {
        Answer::factory()->for($attempt)->create(['position' => $position]);
    }

    expect($attempt->answers->pluck('position')->all())->toBe([0, 1, 2]);
});

it('回答から、問題・挑戦・採点結果をたどれる', function () {
    $score = Score::factory()->create(['score' => 72]);
    $answer = $score->answer;

    expect($answer->score->score)->toBe(72)
        ->and($answer->question)->not->toBeNull()
        ->and($answer->attempt->answers->pluck('id')->all())->toBe([$answer->id]);
});

it('平均点は採点済みの回答から求め、小数第1位まで丸める', function () {
    expect(attemptWithScores([80, 45, 72])->averageScore())->toBe(65.7);
});

it('平均点の計算では、未採点の回答を除く', function () {
    expect(attemptWithScores([80, null, 60])->averageScore())->toBe(70.0);
});

it('採点済みの回答が1つもなければ、平均点は null', function () {
    expect(attemptWithScores([null, null])->averageScore())->toBeNull();
});
