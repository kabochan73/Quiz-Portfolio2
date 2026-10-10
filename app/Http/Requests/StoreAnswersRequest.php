<?php

namespace App\Http\Requests;

use App\Enums\AttemptMode;
use App\Enums\GradingLevel;
use App\Models\Section;
use App\Queries\WeakQuestions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * 回答の送信(requirements.md 3.3、architecture.md 4.2)。セクションの問題にまとめて回答する。
 *
 * 入力欄ごとのチェックに加えて、「送られてきた問題の組み合わせが、種別に応じた今の対象の問題と一致すること」を確かめる。
 * - 全問(all): セクションの全問 / 苦手(weak): 送信した時点の苦手問題
 * ほかのセクションの問題が混ざる・問題が足りない・苦手でない問題が混ざる、のどれも弾く
 * (フォームを開いている間に問題の追加・削除や、別の採点で苦手問題が変わった場合もここで気づける)。
 */
class StoreAnswersRequest extends FormRequest
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
            'grading_level' => ['required', Rule::enum(GradingLevel::class)],
            'mode' => ['required', Rule::enum(AttemptMode::class)],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            // architecture.md 2.5: 回答は 5000 文字まで
            'answers.*.body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // 入力欄ごとのチェックで失敗していれば、組み合わせのチェックはしない(メッセージが重ならないように)
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Section $section */
                $section = $this->route('section');

                $expected = AttemptMode::from($this->input('mode')) === AttemptMode::Weak
                    ? app(WeakQuestions::class)->ids($section)
                    : $section->questions()->orderBy('id')->pluck('id')->all();
                $submitted = collect($this->input('answers'))->pluck('question_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($expected !== $submitted) {
                    $validator->errors()->add('answers', '問題の内容が変わっています。ページを再読み込みして、もう一度回答してください。');
                }
            },
        ];
    }
}
