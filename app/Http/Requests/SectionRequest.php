<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * セクションの作成・編集(requirements.md 3.2、screens.md 2.5)。
 * - 作成: 入力はセクション名だけ。所属カテゴリは URL で決まる
 * - 編集: セクション名に加えて、所属カテゴリを変更できる
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
        $rules = [
            'name' => ['required', 'string', 'max:100'],
        ];

        // 所属カテゴリの変更は編集のときだけ受け付ける。
        // 作成のときはルールに含めないので、送られてきても validated() に入らず無視される
        if ($this->routeIs('sections.update')) {
            $rules['category_id'] = ['required', 'integer', 'exists:categories,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'セクション名',
            'category_id' => 'カテゴリ',
        ];
    }
}
