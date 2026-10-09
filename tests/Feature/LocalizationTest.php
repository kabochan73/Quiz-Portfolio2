<?php

use Illuminate\Support\Facades\Validator;

/**
 * 1つの項目を1つのルールでチェックし、最初のエラーメッセージを返す。
 */
function firstValidationError(array $data, array $rules): ?string
{
    return Validator::make($data, $rules)->errors()->first();
}

it('アプリの言語は日本語になっている', function () {
    expect(app()->getLocale())->toBe('ja');
});

it('よく使う入力チェックのメッセージは日本語で、項目名も日本語になる', function (array $data, array $rules, string $expected) {
    expect(firstValidationError($data, $rules))->toBe($expected);
})->with([
    '必須' => [['email' => ''], ['email' => 'required'], 'メールアドレスを入力してください。'],
    '形式' => [['email' => 'abc'], ['email' => 'email'], 'メールアドレスは正しい形式で入力してください。'],
    '最大文字数' => [['body' => str_repeat('あ', 2001)], ['body' => 'max:2000'], '本文は2000文字以内で入力してください。'],
    '選択肢' => [['mode' => 'random'], ['mode' => 'in:all,weak'], '選択された種別は正しくありません。'],
]);

it('選択式の項目は「選んでください」というメッセージになる', function () {
    expect(firstValidationError([], ['grading_level' => 'required']))->toBe('採点レベルを選んでください。');
});

it('配列形式の項目(回答)も日本語の項目名になる', function () {
    expect(firstValidationError(['answers' => [['body' => '']]], ['answers.*.body' => 'required']))
        ->toBe('回答を入力してください。');
});

it('ログイン失敗と試行回数の制限のメッセージは日本語になる', function () {
    expect(__('auth.failed'))->toBe('メールアドレスまたはパスワードが正しくありません。')
        ->and(__('auth.throttle', ['seconds' => 45]))->toBe('ログインの試行回数が多すぎます。45秒後にもう一度お試しください。');
});
