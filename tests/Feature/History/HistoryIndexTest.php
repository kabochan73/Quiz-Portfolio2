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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
    $this->section = Section::factory()->create(['name' => 'ネットワーク']);
});

/**
 * 指定した点数の回答を持つ挑戦を作る。点数を渡さなければ未採点の回答を1件付ける。
 */
function historyAttempt(Section $section, User $user, array $scores, array $attributes = []): Attempt
{
    $attempt = Attempt::factory()->for($section)->for($user)->create($attributes);

    foreach ($scores ?: [null] as $position => $score) {
        $question = Question::factory()->for($section)->for($user)->create();
        $answer = Answer::factory()->for($attempt)->for($question)->create(['position' => $position]);
        if ($score !== null) {
            Score::factory()->for($answer)->create(['score' => $score]);
        }
    }

    return $attempt;
}

function historyMain(string $html): string
{
    return Str::between($html, '<main', '</main>');
}

it('セクション詳細に「履歴を見る」を出す', function () {
    Question::factory()->for($this->section)->for($this->admin)->create();

    $this->get(route('sections.show', $this->section))
        ->assertSee('href="'.route('history.index', $this->section).'"', false);
});

it('挑戦を新しい順に並べ、日時・種別・採点レベル・問題数・平均点を出す', function () {
    historyAttempt($this->section, $this->admin, [80, 60], ['status' => AttemptStatus::Completed, 'created_at' => now()->subDays(3)]);
    historyAttempt($this->section, $this->admin, [45], [
        'status' => AttemptStatus::Completed, 'mode' => AttemptMode::Weak, 'grading_level' => GradingLevel::Hard, 'created_at' => now()->subDay(),
    ]);

    $response = $this->get(route('history.index', $this->section))
        ->assertOk()
        ->assertSee('<title>履歴 | Quiz</title>', false)
        ->assertSee('ネットワーク・2件');

    $main = historyMain($response->getContent());
    expect($main)->toMatch('#苦手.*厳しい・1問.*aria-label="45点".*全問.*普通・2問.*aria-label="70点"#s');
});

it('採点が終わっていない挑戦は、平均点の代わりに状態を出す', function () {
    historyAttempt($this->section, $this->admin, [], ['status' => AttemptStatus::Grading]);
    historyAttempt($this->section, $this->admin, [], ['status' => AttemptStatus::Failed]);

    $main = historyMain($this->get(route('history.index', $this->section))->getContent());

    expect($main)->toContain('採点中')->toContain('失敗')->not->toContain('aria-label="未採点"');
});

it('各行は結果画面へのリンクになり、結果画面のパンくずから一覧に戻れる', function () {
    $attempt = historyAttempt($this->section, $this->admin, [80], ['status' => AttemptStatus::Completed]);

    $this->get(route('history.index', $this->section))
        ->assertSee('href="'.route('history.show', [$this->section, $attempt]).'"', false);

    $this->get(route('history.show', [$this->section, $attempt]))
        ->assertSee('<a href="'.route('history.index', $this->section).'"', false)
        ->assertSeeInOrder(['パンくずリスト', 'ネットワーク', '履歴']);
});

it('ほかのセクションの挑戦と、他人の挑戦は出さない', function () {
    historyAttempt(Section::factory()->create(), $this->admin, [80], ['status' => AttemptStatus::Completed]);
    historyAttempt($this->section, User::factory()->create(), [80], ['status' => AttemptStatus::Completed]);

    $this->get(route('history.index', $this->section))
        ->assertSee('ネットワーク・0件')
        ->assertSee('まだ挑戦していません');
});

it('20件ずつ表示し、21件目以降は「さらに古い履歴」の次のページに出す', function () {
    foreach (range(1, 21) as $i) {
        Attempt::factory()->completed()->for($this->section)->for($this->admin)->create(['created_at' => now()->subMinutes($i)]);
    }
    $oldest = Attempt::orderBy('created_at')->first();

    $first = $this->get(route('history.index', $this->section));
    $first->assertSee('さらに古い履歴')->assertDontSee('新しい履歴へ')->assertSee('ネットワーク・21件');
    expect(substr_count(historyMain($first->getContent()), '/history/'))->toBe(20);

    $this->get(route('history.index', [$this->section, 'page' => 2]))
        ->assertSee('href="'.route('history.show', [$this->section, $oldest]).'"', false)
        ->assertSee('新しい履歴へ')
        ->assertDontSee('さらに古い履歴');
});

it('一覧のための問い合わせの回数は、挑戦の件数が増えても変わらない', function () {
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('history.index', $this->section));

        return count(DB::getQueryLog());
    };

    foreach (range(1, 3) as $i) {
        historyAttempt($this->section, $this->admin, [80, 60], ['status' => AttemptStatus::Completed]);
    }
    $withThree = $count();

    foreach (range(1, 6) as $i) {
        historyAttempt($this->section, $this->admin, [80, 60], ['status' => AttemptStatus::Completed]);
    }

    expect($count())->toBe($withThree);
});

it('ログインしていなければ開けない', function () {
    auth()->logout();

    $this->get(route('history.index', $this->section))->assertRedirect('/login');
});
