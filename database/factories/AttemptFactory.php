<?php

namespace Database\Factories;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Attempt;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * 既定は「全問・普通・採点待ち」。採点済みや失敗は completed() / failed() で作る。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'user_id' => User::factory(),
            'grading_level' => GradingLevel::Normal,
            'mode' => AttemptMode::All,
            'status' => AttemptStatus::Pending,
        ];
    }

    /**
     * 採点が完了した挑戦。採点時に保存する値(モデル名・利用量・完了日時)も入れる。
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => AttemptStatus::Completed,
            'model' => 'claude-sonnet-5-5',
            'input_tokens' => fake()->numberBetween(2000, 15000),
            'output_tokens' => fake()->numberBetween(500, 4000),
            'web_search_requests' => fake()->numberBetween(0, 3),
            'graded_at' => now(),
        ]);
    }

    /**
     * リトライの上限まで採点に失敗した挑戦。
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => AttemptStatus::Failed,
            'error_message' => 'Claude API の呼び出しに失敗しました。',
        ]);
    }

    /**
     * 苦手問題だけに再挑戦した挑戦。
     */
    public function weak(): static
    {
        return $this->state(fn () => ['mode' => AttemptMode::Weak]);
    }
}
