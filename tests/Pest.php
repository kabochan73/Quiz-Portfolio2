<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature テストは画面・Job・DB を通した流れを確認するので、Laravel の TestCase を使い、
| テストごとに DB をまっさらな状態に戻す(RefreshDatabase)。
| Unit テストは DB や API に依存しない純粋なロジックだけを対象にするため、
| ここでは何も設定せず、素の PHPUnit の TestCase で動かす(implementation-plan.md 3.1)。
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
