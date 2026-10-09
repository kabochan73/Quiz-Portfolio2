<?php

it('タイトルは「ページ名 | Quiz」の形になる', function () {
    $this->blade('<x-layouts.app title="カテゴリ">本文</x-layouts.app>')
        ->assertSee('<title>カテゴリ | Quiz</title>', false)
        ->assertSee('本文');
});

it('タイトルを渡さなければアプリ名だけになる', function () {
    $this->blade('<x-layouts.app>本文</x-layouts.app>')
        ->assertSee('<title>Quiz</title>', false);
});

it('本文の最大幅は既定で 768px、narrow なら 640px になる', function () {
    $this->blade('<x-layouts.app>本文</x-layouts.app>')
        ->assertSee('max-w-3xl', false)
        ->assertDontSee('max-w-2xl', false);

    $this->blade('<x-layouts.app narrow>本文</x-layouts.app>')
        ->assertSee('max-w-2xl', false)
        ->assertDontSee('max-w-3xl', false);
});

it('サイドバーは現在地のカテゴリ・セクションに aria-current を付けて強調する', function () {
    $categories = [
        ['name' => '基本情報', 'href' => '/categories/1', 'current' => false, 'sections' => [
            ['name' => 'ネットワーク', 'href' => '/sections/1', 'current' => true],
            ['name' => 'データベース', 'href' => '/sections/2', 'current' => false],
        ]],
    ];

    $view = $this->blade('<x-layout.sidebar :categories="$categories" />', ['categories' => $categories]);

    $view->assertSee('基本情報')->assertSee('データベース');
    expect((string) $view)
        ->toMatch('#href="/sections/1"\s+aria-current="page"#')
        ->not->toMatch('#href="/sections/2"\s+aria-current#')
        ->not->toMatch('#href="/categories/1"\s+aria-current#');
});

it('現在地がなく open も指定されていないカテゴリは閉じた状態で始まる', function () {
    $categories = [
        ['name' => '英語', 'href' => '/categories/2', 'current' => false, 'sections' => [
            ['name' => '英文法', 'href' => '/sections/3', 'current' => false],
        ]],
    ];

    $this->blade('<x-layout.sidebar :categories="$categories" />', ['categories' => $categories])
        ->assertSee('x-data="{ open: false }"', false);
});

it('カテゴリが0件なら案内文を表示する', function () {
    $this->blade('<x-layout.sidebar />')
        ->assertSee('まだカテゴリがありません');
});
