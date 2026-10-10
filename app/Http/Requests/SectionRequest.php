<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * セクションの作成・編集(requirements.md 3.2、screens.md 2.5)。入力はセクション名だけ。
 * 所属カテゴリは作成時の URL で決まり、あとから変更はできない(2026-10-10 決定)。
 * category_id はルールに含めないので、送られてきても validated() に入らず無視される。
 * 文字数の上限は sections.name の varchar(100) に合わせる。
 */
class SectionRequest extends FormRequest
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
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'セクション名',
        ];
    }
}
