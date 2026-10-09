<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// モデルと Factory は次の作業(implementation-plan.md 3-2)で作るので、ここでは DB に直接データを入れて
// テーブルの制約そのもの(CHECK・UNIQUE・cascade)を確かめる。

/**
 * ユーザー・カテゴリ・セクション・問題・挑戦・回答を1つずつ作り、それぞれの ID を返す。
 */
function insertAttemptFixture(): array
{
    $userId = DB::table('users')->insertGetId(['name' => '管理者', 'email' => 'admin@example.com', 'password' => 'dummy']);
    $categoryId = DB::table('categories')->insertGetId(['name' => '基本情報']);
    $sectionId = DB::table('sections')->insertGetId(['category_id' => $categoryId, 'name' => 'ネットワーク']);
    $questionId = DB::table('questions')->insertGetId(['user_id' => $userId, 'section_id' => $sectionId, 'body' => '問題文']);
    $attemptId = DB::table('attempts')->insertGetId([
        'section_id' => $sectionId, 'user_id' => $userId, 'grading_level' => 'normal', 'mode' => 'all',
    ]);
    $answerId = DB::table('answers')->insertGetId([
        'question_id' => $questionId, 'user_id' => $userId, 'attempt_id' => $attemptId, 'position' => 0, 'body' => '回答',
    ]);

    return compact('userId', 'sectionId', 'questionId', 'attemptId', 'answerId');
}

function scoreRow(int $answerId, int $score): array
{
    return ['answer_id' => $answerId, 'score' => $score, 'good_points' => '良い点', 'improvements' => '改善点', 'example' => '改善例'];
}

it('挑戦の状態は、指定しなければ pending で作られる', function () {
    ['attemptId' => $attemptId] = insertAttemptFixture();

    expect(DB::table('attempts')->find($attemptId)->status)->toBe('pending');
});

it('挑戦の状態・種別・採点レベルは、決まった値しか保存できない', function (string $column, string $value) {
    ['attemptId' => $attemptId] = insertAttemptFixture();

    DB::table('attempts')->where('id', $attemptId)->update([$column => $value]);
})->with([
    '状態' => ['status', 'done'],
    '種別' => ['mode', 'random'],
    '採点レベル' => ['grading_level', 'very_hard'],
])->throws(QueryException::class);

it('点数は 0〜100 の範囲なら保存できる', function (int $score) {
    ['answerId' => $answerId] = insertAttemptFixture();

    DB::table('scores')->insert(scoreRow($answerId, $score));

    expect(DB::table('scores')->value('score'))->toBe($score);
})->with([0, 100]);

it('点数が 0〜100 の範囲外だと保存できない', function (int $score) {
    ['answerId' => $answerId] = insertAttemptFixture();

    DB::table('scores')->insert(scoreRow($answerId, $score));
})->with([-1, 101])->throws(QueryException::class);

it('1つの回答に採点結果を2つは付けられない', function () {
    ['answerId' => $answerId] = insertAttemptFixture();

    DB::table('scores')->insert(scoreRow($answerId, 80));
    DB::table('scores')->insert(scoreRow($answerId, 60));
})->throws(QueryException::class);

it('同じ挑戦の中で、問題の順番(position)は重複できない', function () {
    ['userId' => $userId, 'questionId' => $questionId, 'attemptId' => $attemptId] = insertAttemptFixture();

    DB::table('answers')->insert([
        'question_id' => $questionId, 'user_id' => $userId, 'attempt_id' => $attemptId, 'position' => 0, 'body' => '別の回答',
    ]);
})->throws(QueryException::class);

it('セクションを削除すると、挑戦・回答・採点結果までまとめて削除される', function () {
    ['sectionId' => $sectionId, 'answerId' => $answerId] = insertAttemptFixture();
    DB::table('scores')->insert(scoreRow($answerId, 80));

    DB::table('sections')->where('id', $sectionId)->delete();

    expect(DB::table('attempts')->count())->toBe(0)
        ->and(DB::table('answers')->count())->toBe(0)
        ->and(DB::table('scores')->count())->toBe(0);
});

it('挑戦を削除すると、その回答と採点結果も削除されるが、問題は残る', function () {
    ['attemptId' => $attemptId, 'answerId' => $answerId] = insertAttemptFixture();
    DB::table('scores')->insert(scoreRow($answerId, 80));

    DB::table('attempts')->where('id', $attemptId)->delete();

    expect(DB::table('answers')->count())->toBe(0)
        ->and(DB::table('scores')->count())->toBe(0)
        ->and(DB::table('questions')->count())->toBe(1);
});
