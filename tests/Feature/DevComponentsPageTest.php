<?php

// routes/web.php: 見本ページはローカル環境でだけ登録する。
// テストは APP_ENV=testing で動くので、本番と同じく存在しない扱いになることを確認できる。
it('ローカル環境以外ではコンポーネント見本ページが表示されない', function () {
    $this->get('/dev/components')->assertNotFound();
});
