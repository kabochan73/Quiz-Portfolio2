<?php

function renderConfirmModal(array $props = []): string
{
    $view = test()->blade(
        '<x-ui.confirm-modal name="delete-category" title="カテゴリを削除しますか?" :action="$action" :method="$method">'
        .'問題24件もまとめて削除されます。'
        .'</x-ui.confirm-modal>',
        array_merge(['action' => '/categories/1', 'method' => 'DELETE'], $props),
    );

    return (string) $view;
}

it('確認モーダルは action へ DELETE で送信するフォームを持つ', function () {
    $html = renderConfirmModal();

    expect($html)
        ->toContain('method="POST" action="/categories/1"')
        ->toContain('name="_method" value="DELETE"')
        ->toContain('name="_token"')
        ->toContain('カテゴリを削除しますか?')
        ->toContain('問題24件もまとめて削除されます。');
});

it('送信するメソッドは変更できる', function () {
    expect(renderConfirmModal(['method' => 'PUT']))->toContain('name="_method" value="PUT"');
});

it('開いたときのフォーカスは「キャンセル」に置く', function () {
    // autofocus の付いたボタンが「キャンセル」であることを確かめる
    expect(renderConfirmModal())->toMatch('#<button type="button"[^>]*autofocus[^>]*>キャンセル</button>#s');
});

it('名前が一致する open-modal イベントでだけ開く', function () {
    expect(renderConfirmModal())->toContain('if ($event.detail === \'delete-category\') open = true');
});

it('ドロップダウンの項目は href があればリンク、なければボタンになり、danger は赤字になる', function () {
    $this->blade('<x-ui.dropdown-item href="/categories/1/edit">編集</x-ui.dropdown-item>')
        ->assertSee('<a href="/categories/1/edit"', false)
        ->assertSee('text-zinc-700', false);

    $this->blade('<x-ui.dropdown-item danger>削除</x-ui.dropdown-item>')
        ->assertSee('<button type="button"', false)
        ->assertSee('text-rose-600', false);
});

it('ドロップダウンの「…」ボタンには操作の名前を読み上げ用に付ける', function () {
    $this->blade('<x-ui.dropdown label="カテゴリの操作"><x-ui.dropdown-item>編集</x-ui.dropdown-item></x-ui.dropdown>')
        ->assertSee('aria-label="カテゴリの操作"', false)
        ->assertSee(':aria-expanded="open.toString()"', false);
});
