<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Support\Facades\Hash;

// 本番環境の db:seed は確認を求めて止まるため、本番を想定したテストは実際のデプロイと同じく --force で実行する
function seedInProduction(string $class): void
{
    app()['env'] = 'production';
    test()->artisan('db:seed', ['--class' => $class, '--force' => true]);
}

beforeEach(function () {
    config(['quiz.admin' => ['email' => 'owner@example.com', 'password' => 'a-strong-password-123']]);
});

it('.env の値で管理者を作り、パスワードはハッシュ化して保存する', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::sole();
    expect($admin->email)->toBe('owner@example.com')
        ->and($admin->password)->not->toBe('a-strong-password-123')
        ->and(Hash::check('a-strong-password-123', $admin->password))->toBeTrue();
});

it('何度実行しても管理者は1人のままで、パスワードは新しい値に更新される', function () {
    $this->seed(AdminUserSeeder::class);
    config(['quiz.admin.password' => 'another-strong-password']);
    $this->seed(AdminUserSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Hash::check('another-strong-password', User::sole()->password))->toBeTrue();
});

it('管理者のメールアドレスかパスワードが未設定ならエラーにする', function () {
    config(['quiz.admin.password' => null]);

    $this->seed(AdminUserSeeder::class);
})->throws(RuntimeException::class, 'ADMIN_EMAIL と ADMIN_PASSWORD');

it('本番環境で弱いパスワードのままならエラーにする', function (string $password) {
    config(['quiz.admin.password' => $password]);

    seedInProduction(AdminUserSeeder::class);
})->with([
    '開発用の値' => ['password'],
    '12文字未満' => ['short-pass1'],
])->throws(RuntimeException::class, '本番環境では');

it('本番環境では管理者だけを作り、サンプルデータは入れない', function () {
    seedInProduction(DatabaseSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Category::count())->toBe(0);
});

it('サンプルデータには、苦手問題・苦手モード・失敗・空のセクションが含まれる', function () {
    $this->seed(AdminUserSeeder::class);
    $this->seed(SampleDataSeeder::class);

    expect(Category::count())->toBe(3)
        ->and(Section::doesntHave('questions')->count())->toBe(1)
        ->and(Attempt::where('mode', AttemptMode::Weak)->count())->toBe(1)
        ->and(Attempt::where('status', AttemptStatus::Failed)->count())->toBe(1)
        ->and(Attempt::where('status', AttemptStatus::Pending)->count())->toBe(1)
        // 60点未満の採点結果がある(苦手問題の表示を確かめられる)
        ->and(Attempt::whereHas('answers.score', fn ($query) => $query->where('score', '<', 60))->exists())->toBeTrue();
});
