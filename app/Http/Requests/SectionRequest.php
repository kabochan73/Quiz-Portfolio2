<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * セクションの作成(requirements.md 3.2、screens.md 2.5)。入力はセクション名だけで、
 * 所属カテゴリは URL で決まる。文字数の上限は sections.name の varchar(100) に合わせる。
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
