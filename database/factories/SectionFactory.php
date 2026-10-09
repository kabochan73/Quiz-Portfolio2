<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 指定しなければ、カテゴリも一緒に作る
            'category_id' => Category::factory(),
            'name' => fake()->randomElement(['ネットワーク', 'セキュリティ', 'データベース', '英文法', 'ルーティング', 'Eloquent']),
        ];
    }
}
