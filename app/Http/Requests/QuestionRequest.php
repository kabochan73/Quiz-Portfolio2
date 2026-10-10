<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 問題の作成・編集(requirements.md 3.2、screens.md 2.7)。
 * - 作成: 入力は問題文だけ。セクションは URL で決まる
 * - 編集: 問題文に加えて、所属セクションを変更できる
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
        $rules = [
            // architecture.md 2.3: 本文は 2000 文字まで
            'body' => ['required', 'string', 'max:2000'],
        ];

        // 所属セクションの変更は編集のときだけ受け付ける。作成のときは送られてきても無視される
        if ($this->routeIs('questions.update')) {
            $rules['section_id'] = ['required', 'integer', 'exists:sections,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '問題文',
            'section_id' => 'セクション',
        ];
    }
}
