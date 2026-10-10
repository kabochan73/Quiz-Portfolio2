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
    $this->section = Section::factory()->create(['name' => 'ネットワーク']);
});

/**
 * 指定した点数で採点済みの挑戦を作る。null を渡すと、その問題は未採点にする。
 *
 * @param  array<int, int|null>  $scores
 */
function gradedAttempt(Section $section, User $user, array $scores, array $attributes = []): Attempt
{
    $attempt = Attempt::factory()->completed()->for($section)->for($user)->create($attributes);

    foreach ($scores as $position => $score) {
        $question = Question::factory()->for($section)->for($user)->create(['body' => "問題文{$position}"]);
        $answer = Answer::factory()->for($attempt)->for($question)->create(['position' => $position, 'body' => "回答{$position}"]);

        if ($score !== null) {
            Score::factory()->for($answer)->create([
                'score' => $score,
                'good_points' => "良い点{$position}",
                'improvements' => "改善点{$position}",
                'example' => "改善例{$position}",
            ]);
        }
    }

    return $attempt;
}

function resultUrl(Attempt $attempt): string
{
    return route('history.show', [$attempt->section_id, $attempt]);
}

it('完了した結果に、平均点と点数帯ごとの問題数、種別・採点レベルを表示する', function () {
    $attempt = gradedAttempt($this->section, $this->admin, [85, 45, 72], ['grading_level' => GradingLevel::Hard, 'mode' => AttemptMode::All]);

    $this->get(resultUrl($attempt))
        ->assertOk()
        ->assertSee('<title>採点結果 | Quiz</title>', false)
        ->assertSee('67.3')                 // (85 + 45 + 72) / 3
        ->assertSeeInOrder(['80点以上', '1問', '60点未満', '1問'])
        ->assertSee('全問')
        ->assertSee('厳しい')
        ->assertSee('href="'.route('answers.create', $this->section).'"', false);
});

it('問題ごとに、問題文・回答・点数・良い点・改善点・改善例を回答した順に表示する', function () {
    $attempt = gradedAttempt($this->section, $this->admin, [85, 45]);

    $main = Str::between($this->get(resultUrl($attempt))->getContent(), '<main', '</main>');

    expect($main)->toContain('aria-label="85点"')
        ->and($main)->toContain('aria-label="45点"');
    $this->get(resultUrl($attempt))->assertSeeInOrder([
        '問題 1', '問題文0', '回答0', '良い点0', '改善点0', '改善例0',
        '問題 2', '問題文1', '回答1', '良い点1', '改善点1', '改善例1',
    ]);
});

it('最初は苦手の基準点(60点)未満の問題だけを開いておく', function () {
    $attempt = gradedAttempt($this->section, $this->admin, [85, 45, 60, 59]);

    $html = $this->get(resultUrl($attempt))->getContent();

    // 開閉の初期状態を、問題の順に取り出す
    preg_match_all('#x-data="\{ open: (true|false) \}"#', Str::between($html, '<main', '</main>'), $matches);
    expect($matches[1])->toBe(['false', 'true', 'false', 'true']);
});

it('採点中の挑戦では、採点中であることを伝える', function (AttemptStatus $status) {
    $attempt = Attempt::factory()->for($this->section)->for($this->admin)->create(['status' => $status]);

    $this->get(resultUrl($attempt))
        ->assertOk()
        ->assertSee('採点しています…')
        ->assertDontSee('平均点');
})->with([AttemptStatus::Pending, AttemptStatus::Grading]);

it('失敗した挑戦では、採点できなかったことを伝える', function () {
    $attempt = Attempt::factory()->failed()->for($this->section)->for($this->admin)->create();

    $this->get(resultUrl($attempt))
        ->assertOk()
        ->assertSee('採点できませんでした')
        ->assertDontSee('平均点');
});

it('URL のセクションと挑戦が食い違っていれば 404 にする', function () {
    $attempt = gradedAttempt($this->section, $this->admin, [80]);
    $otherSection = Section::factory()->create();

    $this->get(route('history.show', [$otherSection, $attempt]))->assertNotFound();
});

it('他人の挑戦は、存在を知らせないよう 404 にする', function () {
    $attempt = gradedAttempt($this->section, User::factory()->create(), [80]);

    $this->get(resultUrl($attempt))->assertNotFound();
});

it('回答を送ると結果画面へ移り、再読み込みしても挑戦は増えない(PRG)', function () {
    $questions = Question::factory()->count(2)->for($this->section)->for($this->admin)->create();
    $payload = [
        'grading_level' => 'normal', 'mode' => 'all',
        'answers' => $questions->values()->map(fn ($q, $i) => ['question_id' => $q->id, 'body' => "回答{$i}"])->all(),
    ];

    $response = $this->post(route('answers.store', $this->section), $payload);
    $attempt = Attempt::sole();
    $response->assertRedirect(resultUrl($attempt));

    // 結果画面を何度開いても(再読み込み)、GET なので回答は再送信されない
    $this->get(resultUrl($attempt))->assertOk();
    $this->get(resultUrl($attempt))->assertOk();

    expect(Attempt::count())->toBe(1);
});

it('完了した結果の一番下に、使用量と推定コストを表示する', function () {
    config([
        'quiz.pricing.claude-sonnet-5-5' => ['input_per_mtok' => '2', 'output_per_mtok' => '10'],
        'quiz.web_search_per_request' => '0.01',
    ]);
    $attempt = gradedAttempt($this->section, $this->admin, [80], [
        'model' => 'claude-sonnet-5-5', 'input_tokens' => 6816, 'output_tokens' => 754, 'web_search_requests' => 2,
    ]);

    $this->get(resultUrl($attempt))
        ->assertSee('使用量: 入力 6,816 / 出力 754 トークン・検索 2回')
        ->assertSee('・約 $0.041');
});

it('単価が分からないモデルでは、コストは出さずに使用量だけを表示する', function () {
    $attempt = gradedAttempt($this->section, $this->admin, [80], [
        'model' => 'claude-opus-5-5', 'input_tokens' => 1000, 'output_tokens' => 200, 'web_search_requests' => 0,
    ]);

    $this->get(resultUrl($attempt))
        ->assertSee('使用量: 入力 1,000 / 出力 200 トークン・検索 0回')
        ->assertDontSee('・約 $');
});

it('利用量が記録されていない採点(フェイクなど)では、使用量の行を出さない', function () {
    $attempt = gradedAttempt($this->section, $this->admin, [80], [
        'model' => 'fake', 'input_tokens' => null, 'output_tokens' => null, 'web_search_requests' => null,
    ]);

    $this->get(resultUrl($attempt))->assertDontSee('使用量:');
});

it('ログインしていなければ開けない', function () {
    $attempt = gradedAttempt($this->section, $this->admin, [80]);
    auth()->logout();

    $this->get(resultUrl($attempt))->assertRedirect('/login');
});
