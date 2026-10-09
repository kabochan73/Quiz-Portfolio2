<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Score;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'answer_id' => Answer::factory(),
            'score' => fake()->numberBetween(0, 100),
            'good_points' => '要点を押さえて説明できています。',
            'improvements' => '具体例があるとより分かりやすくなります。',
            'example' => 'たとえば、動画配信のように多少の欠落より速さが大事な通信では UDP が使われます。',
        ];
    }
}
