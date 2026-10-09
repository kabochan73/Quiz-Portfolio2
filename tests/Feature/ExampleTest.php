<?php

it('トップページが表示される', function () {
    $this->get('/')->assertOk();
});
