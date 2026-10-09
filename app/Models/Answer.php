<?php

namespace App\Models;

use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 1つの問題に対する1回分の回答(architecture.md 2.5)。
 * 同じ問題に何度でも挑戦できるので、1つの問題に複数の回答がある。
 * どの挑戦の何問目かを attempt_id と position で持つ。
 */
#[Fillable(['question_id', 'user_id', 'attempt_id', 'position', 'body'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * 採点結果。採点が終わるまでは null。
     */
    public function score(): HasOne
    {
        return $this->hasOne(Score::class);
    }
}
