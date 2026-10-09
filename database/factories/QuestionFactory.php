<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 指定しなければ、ユーザーとセクション(とそのカテゴリ)も一緒に作る
            'user_id' => User::factory(),
            'section_id' => Section::factory(),
            'body' => fake()->randomElement([
                'TCPとUDPの違いを説明してください。',
                'DNSによる名前解決の流れを説明してください。',
                'HTTPSで通信が暗号化される仕組みを説明してください。',
                'データベースの正規化の目的を説明してください。',
                'SQLインジェクションとその対策を説明してください。',
            ]),
        ];
    }
}
