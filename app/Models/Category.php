<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * 分類だけを表すモデル。問題は直接持たず、必ずセクションを通して問題につながる
 * (Category → Section → Question の3階層、requirements.md 3.2)。
 */
#[Fillable(['name'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * セクションを通した、このカテゴリの全問題。一覧での問題数の集計や、削除時の件数表示に使う。
     */
    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, Section::class);
    }

    /**
     * セクションを通した、このカテゴリの全挑戦(履歴)。削除時の件数表示に使う。
     */
    public function attempts(): HasManyThrough
    {
        return $this->hasManyThrough(Attempt::class, Section::class);
    }
}
