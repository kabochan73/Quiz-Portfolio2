<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * カテゴリの作成(requirements.md 3.2、screens.md 2.3)。入力はカテゴリ名だけ。
 * 文字数の上限は categories.name の varchar(100) に合わせる。
 */
class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ログインできるのは管理者だけなので、ルートの auth ミドルウェアで十分
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * 共通の項目名「名前」ではなく、「カテゴリ名」と表示する。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'カテゴリ名',
        ];
    }
}
