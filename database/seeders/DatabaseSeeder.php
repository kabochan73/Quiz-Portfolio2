<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 管理者は常に作る。サンプルデータは画面の確認用なので、ローカル環境でだけ入れる。
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        if (app()->environment('local')) {
            $this->call(SampleDataSeeder::class);
        }
    }
}
