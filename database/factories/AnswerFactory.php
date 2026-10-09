<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * 指定しなければ、問題・ユーザー・挑戦も一緒に作る。
     * 問題と挑戦を同じセクションにそろえたいテストでは、for() で両方を明示する。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'user_id' => User::factory(),
            'attempt_id' => Attempt::factory(),
            'position' => 0,
            'body' => fake()->randomElement([
                'TCPはコネクション型で信頼性が高く、UDPはコネクションレスで高速です。',
                'DNSリゾルバがルートサーバーから順に問い合わせて、IPアドレスを得ます。',
                '公開鍵で共通鍵を安全に共有し、その共通鍵で通信を暗号化します。',
            ]),
        ];
    }
}
