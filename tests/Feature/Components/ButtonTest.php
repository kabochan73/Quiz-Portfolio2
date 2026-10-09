<?php

use Illuminate\View\ViewException;

it('href がなければ button 要素として出力し、type の既定値は button になる', function () {
    $this->blade('<x-ui.button>保存する</x-ui.button>')
        ->assertSee('<button type="button"', false)
        ->assertSee('保存する')
        ->assertDontSee('<a ', false);
});

it('type を指定すると、その type の button になる', function () {
    $this->blade('<x-ui.button type="submit">作成する</x-ui.button>')
        ->assertSee('<button type="submit"', false);
});

it('href を渡すと a 要素として出力する', function () {
    $this->blade('<x-ui.button href="/categories">戻る</x-ui.button>')
        ->assertSee('<a href="/categories"', false)
        ->assertDontSee('<button', false);
});

it('variant ごとに対応する色のクラスが付く', function (string $variant, string $expectedClass) {
    $this->blade('<x-ui.button :variant="$variant">ボタン</x-ui.button>', ['variant' => $variant])
        ->assertSee($expectedClass, false);
})->with([
    'primary' => ['primary', 'bg-brand-600'],
    'secondary' => ['secondary', 'border-zinc-300'],
    'ghost' => ['ghost', 'hover:bg-zinc-100'],
    'danger' => ['danger', 'bg-rose-600'],
]);

it('size が sm なら低いボタンになる', function () {
    $this->blade('<x-ui.button size="sm">編集</x-ui.button>')
        ->assertSee('min-h-8', false)
        ->assertDontSee('min-h-11', false);
});

it('呼び出し側で渡した属性やクラスも引き継がれる', function () {
    $this->blade('<x-ui.button class="w-full" disabled>送信中…</x-ui.button>')
        ->assertSee('w-full', false)
        ->assertSee('disabled', false);
});

// match の UnhandledMatchError は、Blade の描画中に ViewException に包まれて投げられる
it('想定外の variant を渡すとエラーになる', function () {
    $this->blade('<x-ui.button variant="unknown">ボタン</x-ui.button>');
})->throws(ViewException::class, "Unhandled match case 'unknown'");
