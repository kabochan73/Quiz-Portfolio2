<?php

use Illuminate\Support\Js;

// トーストの文言は @js で JSON として Alpine に渡す。比較も同じ形に変換して行う
function toastPayload(array $toasts): string
{
    return (string) Js::from($toasts);
}

it('セッションに toast があると、レイアウトがその内容をトーストとして渡す', function () {
    session()->now('toast', ['type' => 'success', 'message' => 'カテゴリを作成しました']);

    $this->blade('<x-layouts.app>本文</x-layouts.app>')
        ->assertSee('toastStack('.toastPayload([['type' => 'success', 'message' => 'カテゴリを作成しました']]).')', false);
});

it('セッションに toast がなければ、空の状態でトーストの表示場所だけを置く', function () {
    $this->blade('<x-layouts.app>本文</x-layouts.app>')
        ->assertSee('toastStack([])', false)
        // 画面の中から $dispatch('toast', ...) で出せるよう、イベントは常に待ち受ける
        ->assertSee('@toast.window="add($event.detail)"', false);
});

it('文言は HTML として埋め込まず、引用符などもエスケープして渡す', function () {
    session()->now('toast', ['type' => 'error', 'message' => '<script>alert("x")</script>']);

    $this->blade('<x-layouts.app>本文</x-layouts.app>')
        ->assertDontSee('<script>alert', false)
        ->assertDontSee('alert("x")', false);
});
