<?php

$breadcrumbs = [
    ['label' => 'カテゴリ', 'href' => '/categories'],
    ['label' => '基本情報', 'href' => '/categories/1'],
    ['label' => 'ネットワーク'],
];

it('パンくずの途中の項目はリンクにし、最後の項目は現在地として表示する', function () use ($breadcrumbs) {
    $view = $this->blade('<x-ui.breadcrumb :items="$items" />', ['items' => $breadcrumbs]);

    $view->assertSee('<a href="/categories"', false)
        ->assertSee('<a href="/categories/1"', false)
        ->assertSee('aria-current="page"', false);

    expect((string) $view)->toMatch('#aria-current="page"[^>]*>ネットワーク</span>#');
});

it('スマホ用の「…」は1つ上の階層へのリンクになる', function () use ($breadcrumbs) {
    expect((string) $this->blade('<x-ui.breadcrumb :items="$items" />', ['items' => $breadcrumbs]))
        ->toMatch('#<a href="/categories/1" aria-label="基本情報に戻る"#');
});

it('項目が1つだけなら「…」は出さない', function () {
    $this->blade('<x-ui.breadcrumb :items="$items" />', ['items' => [['label' => 'カテゴリ']]])
        ->assertSee('カテゴリ')
        ->assertDontSee('…');
});

it('レイアウトに breadcrumbs を渡すとパンくずを表示し、渡さなければ表示しない', function () use ($breadcrumbs) {
    $this->blade('<x-layouts.app :breadcrumbs="$items">本文</x-layouts.app>', ['items' => $breadcrumbs])
        ->assertSee('aria-label="パンくずリスト"', false);

    $this->blade('<x-layouts.app>本文</x-layouts.app>')
        ->assertDontSee('aria-label="パンくずリスト"', false);
});

it('ページ見出しは見出し・補足情報・操作ボタンを表示する', function () {
    $this->blade(<<<'BLADE'
        <x-ui.page-header title="ネットワーク">
            <x-slot:meta>8 / 10問</x-slot:meta>
            <x-slot:actions><x-ui.button>+ 問題を追加</x-ui.button></x-slot:actions>
        </x-ui.page-header>
        BLADE)
        ->assertSee('<h1', false)
        ->assertSee('ネットワーク')
        ->assertSee('8 / 10問')
        ->assertSee('+ 問題を追加');
});

it('補足情報と操作ボタンは省略できる', function () {
    $view = $this->blade('<x-ui.page-header title="カテゴリ" />');

    $view->assertSee('カテゴリ');
    expect((string) $view)->not->toContain('<p class="mt-1')
        ->not->toContain('flex flex-wrap items-center gap-2');
});
