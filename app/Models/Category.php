<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
