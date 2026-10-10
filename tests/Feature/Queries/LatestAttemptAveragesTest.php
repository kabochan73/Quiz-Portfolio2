<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Score;
use App\Models\Section;
use App\Queries\LatestAttemptAverages;
use Illuminate\Support\Facades\DB;

/**
 * 指定した点数の回答を持つ挑戦を、$daysAgo 日前に作る。
 *
 * @param  array<int, int>  $scores
 */
function averagedAttempt(Section $section, array $scores, int $daysAgo, AttemptStatus $status = AttemptStatus::Completed, AttemptMode $mode = AttemptMode::All): Attempt
{
    $attempt = Attempt::factory()->for($section)->create(['status' => $status, 'mode' => $mode, 'created_at' => now()->subDays($daysAgo)]);

    foreach ($scores as $position => $score) {
        $answer = Answer::factory()->for($attempt)->create(['position' => $position]);
        Score::factory()->for($answer)->create(['score' => $score]);
    }

    return $attempt;
}

it('セクションごとに、直近の全問の採点済みの挑戦の平均点を小数第1位まで返す', function () {
    $network = Section::factory()->create();
    $database = Section::factory()->create();
    averagedAttempt($network, [40, 40], 9);
    averagedAttempt($network, [85, 45, 72], 2);   // 直近: 67.3
    averagedAttempt($database, [90, 80], 5);

    expect((new LatestAttemptAverages)->forSections([$network->id, $database->id]))
        ->toBe([$network->id => 67.3, $database->id => 85.0]);
});

it('苦手モード・採点中・失敗の挑戦は除き、その前の全問の採点済みの挑戦を使う', function () {
    $section = Section::factory()->create();
    averagedAttempt($section, [70, 80], 9);
    averagedAttempt($section, [30], 5, mode: AttemptMode::Weak);
    averagedAttempt($section, [], 3, AttemptStatus::Failed);
    averagedAttempt($section, [], 1, AttemptStatus::Grading);

    expect((new LatestAttemptAverages)->forSections([$section->id]))->toBe([$section->id => 75.0]);
});

it('全問で採点済みの挑戦がないセクションは結果に含めない', function () {
    $section = Section::factory()->create();
    averagedAttempt($section, [30], 1, mode: AttemptMode::Weak);

    expect((new LatestAttemptAverages)->forSections([$section->id, Section::factory()->create()->id]))->toBe([]);
});

it('セクションが空なら問い合わせず、いくつあっても問い合わせは1回だけ', function () {
    DB::enableQueryLog();
    expect((new LatestAttemptAverages)->forSections([]))->toBe([])
        ->and(DB::getQueryLog())->toBe([]);

    $sections = Section::factory()->count(5)->create();
    $sections->each(fn (Section $section) => averagedAttempt($section, [60, 80], 1));

    DB::flushQueryLog();
    expect((new LatestAttemptAverages)->forSections($sections->pluck('id')))->toHaveCount(5)
        ->and(DB::getQueryLog())->toHaveCount(1);
});
