<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 問題の作成(requirements.md 3.2、screens.md 2.7)。入力は問題文だけで、セクションは URL で決まる。
 * 1セクションあたりの問題数の上限は、ロックが必要なので App\Services\QuestionPlacement で確かめる。
 */
class QuestionRequest extends FormRequest
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
            // architecture.md 2.3: 本文は 2000 文字まで
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '問題文',
        ];
    }
}
