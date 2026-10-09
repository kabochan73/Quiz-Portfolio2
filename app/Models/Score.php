<?php

namespace App\Models;

use Database\Factories\ScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 回答に 1:1 で付く採点結果(architecture.md 2.6)。
 * 点数(0〜100)と、「良い点 / 改善点 / 改善例」に分けたフィードバックを持つ(requirements.md 3.3)。
 * 範囲外の点数は DB の CHECK 制約でも弾かれる。
 */
#[Fillable(['answer_id', 'score', 'good_points', 'improvements', 'example'])]
class Score extends Model
{
    /** @use HasFactory<ScoreFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
