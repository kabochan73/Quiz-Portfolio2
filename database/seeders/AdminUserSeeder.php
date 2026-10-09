<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * 唯一のログインユーザーである管理者を作る(requirements.md 3.1)。
 *
 * - メールアドレスとパスワードは .env(config('quiz.admin'))から読む。リポジトリは公開する前提なので、
 *   コードには書かない
 * - 本番環境で、パスワードが短い・開発用の値のままなら、エラーで止める
 *   (弱いパスワードのまま公開されるのを防ぐ)
 * - 同じメールアドレスの管理者がいれば、作り直さずにパスワードを更新する(何度実行しても1人のまま)
 */
class AdminUserSeeder extends Seeder
{
    // 本番で許可するパスワードの最低文字数
    private const MIN_PRODUCTION_PASSWORD_LENGTH = 12;

    public function run(): void
    {
        $email = config('quiz.admin.email');
        $password = config('quiz.admin.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('ADMIN_EMAIL と ADMIN_PASSWORD を .env に設定してください。');
        }

        if (app()->isProduction() && $this->isWeakPassword($password)) {
            throw new RuntimeException(sprintf(
                '本番環境では、ADMIN_PASSWORD に開発用ではない %d 文字以上のパスワードを設定してください。',
                self::MIN_PRODUCTION_PASSWORD_LENGTH,
            ));
        }

        // password は User モデルの hashed キャストでハッシュ化されて保存される
        User::updateOrCreate(
            ['email' => $email],
            ['name' => '管理者', 'password' => $password],
        );
    }

    private function isWeakPassword(string $password): bool
    {
        return $password === 'password' || mb_strlen($password) < self::MIN_PRODUCTION_PASSWORD_LENGTH;
    }
}
