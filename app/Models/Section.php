<?php

namespace App\Models;

use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 「問題の入れ物」。必ず1つのカテゴリに属する(requirements.md 3.2)。
 * 回答はセクションの問題にまとめて行い、1回の API リクエストで採点するため、
 * 作れる問題数に上限(config('quiz.max_questions_per_section'))がある。
 */
#[Fillable(['category_id', 'name'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    /**
     * 問題数が上限に達しているか。「+ 問題を追加」の無効表示に使う。
     * 実際に問題を追加するときは、同時の追加で上限を超えないよう、
     * セクション行をロックしてから数え直す(architecture.md 4.1)。
     */
    public function isFull(): bool
    {
        return $this->questions()->count() >= config('quiz.max_questions_per_section');
    }
}
