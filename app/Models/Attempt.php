<?php

namespace App\Models;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use Database\Factories\AttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * 挑戦。「セクションの問題にまとめて回答した1回分」(requirements.md 3.3)。
 * 履歴は問題単位ではなく、この挑戦単位で一覧・詳細を表示する(requirements.md 3.4)。
 * 採点の状態と、採点にかかった Claude API の利用量もここに持つ(architecture.md 2.4)。
 */
#[Fillable([
    'section_id', 'user_id', 'grading_level', 'mode', 'status',
    'model', 'input_tokens', 'output_tokens', 'web_search_requests', 'error_message', 'graded_at',
])]
class Attempt extends Model
{
    /** @use HasFactory<AttemptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'grading_level' => GradingLevel::class,
            'mode' => AttemptMode::class,
            'status' => AttemptStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'web_search_requests' => 'integer',
            'graded_at' => 'datetime',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 回答した順(position)に並べる。結果画面は回答フォームと同じ順で表示するため。
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('position');
    }

    /**
     * 回答を通した採点結果。履歴一覧で、平均点を一覧の問い合わせの中でまとめて求める(withAvg)のに使う。
     */
    public function scores(): HasManyThrough
    {
        return $this->hasManyThrough(Score::class, Answer::class);
    }

    /**
     * 採点済みの回答の平均点(小数第1位まで)。結果画面と履歴一覧に表示する。
     * 採点結果がない回答は計算から除き、1つもなければ null を返す。
     * 回答と採点結果は呼び出し側で読み込んでおく(一覧で問い合わせが増えないよう、load('answers.score') を使う)。
     */
    public function averageScore(): ?float
    {
        $scores = $this->answers
            ->pluck('score.score')
            ->filter(fn (?int $score) => $score !== null);

        if ($scores->isEmpty()) {
            return null;
        }

        return round($scores->avg(), 1);
    }
}
