<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// モデルと Factory は次の作業(implementation-plan.md 3-2)で作るので、ここでは DB に直接データを入れて
// テーブルの制約そのもの(外部キー・cascade)を確かめる。

function insertUser(): int
{
    return DB::table('users')->insertGetId([
        'name' => '管理者',
        'email' => 'admin@example.com',
        'password' => 'dummy',
    ]);
}

function insertCatalog(): array
{
    $userId = insertUser();
    $categoryId = DB::table('categories')->insertGetId(['name' => '基本情報']);
    $sectionId = DB::table('sections')->insertGetId(['category_id' => $categoryId, 'name' => 'ネットワーク']);
    $questionId = DB::table('questions')->insertGetId([
        'user_id' => $userId,
        'section_id' => $sectionId,
        'body' => 'TCPとUDPの違いを説明してください。',
    ]);

    return compact('userId', 'categoryId', 'sectionId', 'questionId');
}

it('カテゴリを削除すると、配下のセクションと問題もまとめて削除される', function () {
    ['categoryId' => $categoryId] = insertCatalog();

    DB::table('categories')->where('id', $categoryId)->delete();

    expect(DB::table('sections')->count())->toBe(0)
        ->and(DB::table('questions')->count())->toBe(0);
});

it('セクションを削除すると、その問題も削除されるが、カテゴリは残る', function () {
    ['sectionId' => $sectionId] = insertCatalog();

    DB::table('sections')->where('id', $sectionId)->delete();

    expect(DB::table('questions')->count())->toBe(0)
        ->and(DB::table('categories')->count())->toBe(1);
});

it('存在しないセクションを指定した問題は作れない', function () {
    $userId = insertUser();

    DB::table('questions')->insert(['user_id' => $userId, 'section_id' => 999, 'body' => '問題文']);
})->throws(QueryException::class);

it('存在しないカテゴリを指定したセクションは作れない', function () {
    DB::table('sections')->insert(['category_id' => 999, 'name' => 'ネットワーク']);
})->throws(QueryException::class);

// PostgreSQL の varchar(100) はバイト数ではなく文字数で数えるので、日本語でも100文字まで入る
it('カテゴリ名は101文字以上を保存できない', function () {
    DB::table('categories')->insert(['name' => str_repeat('あ', 101)]);
})->throws(QueryException::class);
