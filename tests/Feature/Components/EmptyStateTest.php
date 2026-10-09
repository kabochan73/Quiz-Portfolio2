<?php

it('空の状態は見出し・説明・ボタンを表示する', function () {
    $this->blade(<<<'BLADE'
        <x-ui.empty-state icon="document-text" title="まだ問題がありません" description="最初の問題を追加しましょう。">
            <x-slot:action><x-ui.button href="/sections/1/questions/create">+ 問題を追加</x-ui.button></x-slot:action>
        </x-ui.empty-state>
        BLADE)
        ->assertSee('まだ問題がありません')
        ->assertSee('最初の問題を追加しましょう。')
        ->assertSee('<a href="/sections/1/questions/create"', false);
});

it('空の状態の説明とボタンは省略できる', function () {
    $view = $this->blade('<x-ui.empty-state title="まだ挑戦していません" />');

    $view->assertSee('まだ挑戦していません');
    expect((string) $view)
        ->not->toContain('<p class="mt-1')
        ->not->toContain('<div class="mt-6">');
});

it('スケルトンは見た目だけの部品として読み上げから外し、大きさは呼び出し側で決める', function () {
    $this->blade('<x-ui.skeleton class="h-4 w-2/3" />')
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('animate-pulse', false)
        ->assertSee('h-4 w-2/3', false);
});
