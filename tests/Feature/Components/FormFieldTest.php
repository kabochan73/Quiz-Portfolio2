<?php

it('ラベルと入力欄が for / id で関連付けられる', function () {
    $this->blade('<x-ui.input name="name" label="カテゴリ名" />')
        ->assertSee('<label for="field-name"', false)
        ->assertSee('id="field-name"', false)
        ->assertSee('カテゴリ名');
});

it('配列形式の name でも、id とエラーのキーがドット形式に変換される', function () {
    $this->withViewErrors(['answers.0.body' => '回答を入力してください。'])
        ->blade('<x-ui.textarea name="answers[0][body]" label="回答" />')
        ->assertSee('id="field-answers-0-body"', false)
        ->assertSee('回答を入力してください。');
});

it('バリデーションエラーで戻ってきたときは、old() の値で入力欄を復元する', function () {
    // old() はリクエストに紐づいたセッションから読むので、実際のリクエストと同じ状態を作る
    request()->setLaravelSession(session()->driver());
    session()->flashInput(['name' => '入力し直した名前']);

    $this->blade('<x-ui.input name="name" label="カテゴリ名" value="元の名前" />')
        ->assertSee('value="入力し直した名前"', false)
        ->assertDontSee('元の名前');
});

it('old() がなければ value の値を表示する', function () {
    $this->blade('<x-ui.input name="name" label="カテゴリ名" value="元の名前" />')
        ->assertSee('value="元の名前"', false);
});

it('エラーがあるとメッセージを表示し、入力欄を赤枠にして aria 属性を付ける', function () {
    $this->withViewErrors(['name' => 'カテゴリ名を入力してください。'])
        ->blade('<x-ui.input name="name" label="カテゴリ名" hint="補足文" />')
        ->assertSee('カテゴリ名を入力してください。')
        ->assertSee('border-rose-500', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="field-name-error"', false)
        // エラーがあるときは補足文の代わりにエラーを出す
        ->assertDontSee('補足文');
});

it('エラーがなければ補足文を表示し、aria-invalid は付けない', function () {
    $this->blade('<x-ui.input name="name" label="カテゴリ名" hint="補足文" />')
        ->assertSee('補足文')
        ->assertDontSee('aria-invalid', false);
});

it('textarea は maxlength を渡したときだけ文字数を表示する', function () {
    $this->blade('<x-ui.textarea name="body" label="問題文" :maxlength="2000" value="あいう" />')
        ->assertSee('/ 2000')
        ->assertSee('x-data="{ count: 3 }"', false);

    $this->blade('<x-ui.textarea name="body" label="問題文" />')
        ->assertDontSee('x-data', false);
});

it('textarea には HTML の maxlength 属性を付けない(貼り付けた文章が切り捨てられないように)', function () {
    $this->blade('<x-ui.textarea name="body" label="問題文" :maxlength="2000" />')
        ->assertDontSee('maxlength=', false);
});

it('select は value に一致する選択肢を選択済みにし、disabled の選択肢は選べなくする', function () {
    $view = $this->blade(
        '<x-ui.select name="section_id" label="セクション" :options="$options" :value="2" :disabled="[3]" />',
        ['options' => [1 => 'ネットワーク', 2 => 'データベース', 3 => 'セキュリティ']],
    );

    // @selected / @disabled が出力しない側には空白が残るので、空白の数は問わずに確かめる
    expect((string) $view)
        ->toMatch('/<option value="2"\s+selected\s*>データベース/')
        ->toMatch('/<option value="3"\s+disabled>セキュリティ/')
        ->not->toMatch('/<option value="1"[^>]*(selected|disabled)/');
});
