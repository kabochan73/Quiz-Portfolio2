<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 自由記述の問題。タイトルは持たず、本文だけを持つ(requirements.md 3.2)。
 * 一覧ではタイトルの代わりに本文の先頭を表示する(excerpt)。
 */
#[Fillable(['user_id', 'section_id', 'body'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * 一覧で表示する本文の抜粋(screens.md 2.6: 先頭40文字)。
     * 改行や連続した空白は1つの空白に詰め、長い場合は末尾を「…」にする。
     *
     * Str::limit() は文字数ではなく表示幅で数える(全角1文字を2と数える)ため、日本語だと半分の
     * 20文字で切れてしまう。仕様どおり文字数で切るよう、mb_strlen / mb_substr で数える。
     */
    public function excerpt(int $length = 40): string
    {
        $text = Str::squish($this->body);

        return mb_strlen($text) > $length ? mb_substr($text, 0, $length).'…' : $text;
    }
}
