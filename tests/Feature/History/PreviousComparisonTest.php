<?php

use App\Enums\AttemptMode;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    $this->section = Section::factory()->create();
    $this->questions = Question::factory()->count(3)->for($this->section)->for($this->admin)->create();
});

/**
 * 問題の順に点数を付けた挑戦を、$daysAgo 日前に作る。null の位置の問題には回答しない。
 *
 * @param  array<int, int|null>  $scores
 */
function comparisonAttempt(Section $section, User $user, iterable $questions, array $scores, int $daysAgo, array $attributes = []): Attempt
{
    $at = now()->subDays($daysAgo);
    $attempt = Attempt::factory()->completed()->for($section)->for($user)->create(array_merge(['created_at' => $at], $attributes));

    $position = 0;
    foreach ($questions as $i => $question) {
        if (($scores[$i] ?? null) === null) {
            continue;
        }
        $answer = Answer::factory()->for($attempt)->for($question)->create(['position' => $position++, 'created_at' => $at]);
        Score::factory()->for($answer)->create(['score' => $scores[$i]]);
    }

    return $attempt;
}

function comparisonMain(Attempt $attempt): string
{
    return Str::between(test()->get(route('history.show', [$attempt->section_id, $attempt]))->getContent(), '<main', '</main>');
}

it('問題ごとに、前回の点数との差を出す(上がった・下がった・同じ・初回)', function () {
    comparisonAttempt($this->section, $this->admin, $this->questions, [60, 80, 50], 5);
    $newQuestion = Question::factory()->for($this->section)->for($this->admin)->create();
    $current = comparisonAttempt($this->section, $this->admin, [...$this->questions, $newQuestion], [76, 72, 50, 90], 1);

    expect(comparisonMain($current))
        ->toContain('前回より16点アップ')
        ->toContain('前回より8点ダウン')
        ->toContain('前回と同じ点数')
        ->toContain('初回の挑戦');
});

it('前回は、この挑戦より前の採点済みの回答だけから選ぶ', function () {
    comparisonAttempt($this->section, $this->admin, $this->questions, [40, null, null], 9);
    // 失敗した挑戦と、この挑戦より後の挑戦は「前回」にしない
    $failed = Attempt::factory()->failed()->for($this->section)->for($this->admin)->create(['created_at' => now()->subDays(3)]);
    Answer::factory()->for($failed)->for($this->questions[0])->create(['created_at' => now()->subDays(3)]);
    $current = comparisonAttempt($this->section, $this->admin, $this->questions, [70, null, null], 2);
    comparisonAttempt($this->section, $this->admin, $this->questions, [100, null, null], 1);

    expect(comparisonMain($current))->toContain('前回より30点アップ');
});

it('問題の前回比は、全問と苦手の種別をまたいでも同じ問題どうしで比べる', function () {
    comparisonAttempt($this->section, $this->admin, $this->questions, [null, 45, null], 5, ['mode' => AttemptMode::Weak]);
    $current = comparisonAttempt($this->section, $this->admin, $this->questions, [80, 75, 60], 1);

    expect(comparisonMain($current))->toContain('前回より30点アップ');
});

it('平均点は、同じ種別のひとつ前の挑戦の平均点と比べ、小数第1位まで出す', function () {
    comparisonAttempt($this->section, $this->admin, $this->questions, [60, 60, 60], 9);                                     // 全問: 平均 60
    comparisonAttempt($this->section, $this->admin, $this->questions, [10, null, null], 5, ['mode' => AttemptMode::Weak]);  // 苦手は対象外
    $current = comparisonAttempt($this->section, $this->admin, $this->questions, [85, 45, 72], 1);                          // 平均 67.3

    expect(comparisonMain($current))->toContain('67.3')->toContain('前回より7.3点アップ');
});

it('同じ種別の前回の挑戦がなければ、平均点は「初回」にする', function () {
    $current = comparisonAttempt($this->section, $this->admin, $this->questions, [80, 70, 60], 1);

    expect(substr_count(comparisonMain($current), '初回の挑戦'))->toBe(4); // 平均点 + 3問
});

it('前回比のための問い合わせの回数は、問題が増えても変わらない', function () {
    $count = function (Attempt $attempt) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('history.show', [$attempt->section_id, $attempt]));

        return count(DB::getQueryLog());
    };

    comparisonAttempt($this->section, $this->admin, $this->questions, [50, 50, 50], 5);
    $few = $count(comparisonAttempt($this->section, $this->admin, $this->questions, [60, 60, 60], 1));

    $more = Question::factory()->count(7)->for($this->section)->for($this->admin)->create();
    $all = $this->questions->concat($more);
    comparisonAttempt($this->section, $this->admin, $all, array_fill(0, 10, 50), 4);
    $many = $count(comparisonAttempt($this->section, $this->admin, $all, array_fill(0, 10, 70), 0));

    expect($many)->toBe($few);
});
