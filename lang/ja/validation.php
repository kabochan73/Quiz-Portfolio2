<?php

/*
|--------------------------------------------------------------------------
| 入力チェックのメッセージ(日本語)
|--------------------------------------------------------------------------
|
| このアプリのフォームで使うルールに絞って訳している。訳していないルールは、
| fallback_locale(en)の Laravel 標準の英語メッセージになる。
| 文言は screens.md 4章に合わせ、丁寧語で「!」は使わない。
|
*/

return [

    'accepted' => ':attributeを承認してください。',
    'array' => ':attributeの形式が正しくありません。',
    'boolean' => ':attributeの値が正しくありません。',
    'confirmed' => ':attributeが確認用の値と一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは正しい日付を入力してください。',
    'distinct' => ':attributeに同じ値が含まれています。',
    'email' => ':attributeは正しい形式で入力してください。',
    'enum' => '選択された:attributeは正しくありません。',
    'exists' => '選択された:attributeは存在しません。',
    'in' => '選択された:attributeは正しくありません。',
    'integer' => ':attributeは整数で入力してください。',
    'max' => [
        'array' => ':attributeは:max個以下にしてください。',
        'file' => ':attributeは:max KB以下のファイルにしてください。',
        'numeric' => ':attributeは:max以下で入力してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'min' => [
        'array' => ':attributeは:min個以上にしてください。',
        'file' => ':attributeは:min KB以上のファイルにしてください。',
        'numeric' => ':attributeは:min以上で入力してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'numeric' => ':attributeは数値で入力してください。',
    'present' => ':attributeが送信されていません。',
    'prohibited' => ':attributeは入力できません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeを入力してください。',
    'required_array_keys' => ':attributeに必要な項目が含まれていません。',
    'size' => [
        'array' => ':attributeは:size個にしてください。',
        'file' => ':attributeは:size KBのファイルにしてください。',
        'numeric' => ':attributeは:sizeにしてください。',
        'string' => ':attributeは:size文字で入力してください。',
    ],
    'string' => ':attributeは文字列で入力してください。',
    'unique' => 'その:attributeはすでに使われています。',

    /*
    |--------------------------------------------------------------------------
    | 項目ごとのメッセージ
    |--------------------------------------------------------------------------
    |
    | 選択式の項目は「入力してください」より「選んでください」が自然なので、ここで上書きする。
    |
    */

    'custom' => [
        'grading_level' => [
            'required' => '採点レベルを選んでください。',
        ],
        'section_id' => [
            'required' => 'セクションを選んでください。',
        ],
        'category_id' => [
            'required' => 'カテゴリを選んでください。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 項目名
    |--------------------------------------------------------------------------
    |
    | メッセージ中の :attribute に入る日本語の項目名。
    | フォームごとに呼び分けたい場合(name を「カテゴリ名」「セクション名」など)は、
    | FormRequest の attributes() で上書きする。
    |
    */

    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
        'body' => '本文',
        'section_id' => 'セクション',
        'grading_level' => '採点レベル',
        'mode' => '種別',
        'answers' => '回答',
        'answers.*.question_id' => '問題',
        'answers.*.body' => '回答',
    ],

];
