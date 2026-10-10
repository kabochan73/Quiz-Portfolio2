<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * セクションに問題を置く処理と、1セクションあたりの問題数の上限チェック(requirements.md 3.2、architecture.md 4.1)。
 *
 * 回答はセクションの全問を1回の API リクエストで採点するため、問題数に上限がある。
 * 同時に追加されて上限を超えないよう、トランザクションの中で追加先のセクションの行をロックしてから数える。
 * 2つ目の処理は1つ目が終わるまでロックで待たされ、数え直したときに上限に気づく。
 *
 * 問題を増やすのは作成のときだけ(所属セクションの変更はできない)なので、作成で使う。
 */
class QuestionPlacement
{
    public function create(Section $section, User $user, string $body): Question
    {
        return DB::transaction(function () use ($section, $user, $body) {
            $this->lockAndEnsureCapacity($section, errorKey: 'body');

            return $section->questions()->create([
                'user_id' => $user->id,
                'body' => $body,
            ]);
        });
    }

    /**
     * 追加先のセクションの行をロックし、上限に達していればバリデーションエラーにする。
     * エラーは、画面上で原因の入力欄の下に出せるよう、呼び出し元が指定したキーに入れる。
     *
     * @throws ValidationException
     */
    private function lockAndEnsureCapacity(Section $section, string $errorKey): void
    {
        Section::query()->whereKey($section->id)->lockForUpdate()->first();

        $max = config('quiz.max_questions_per_section');

        if ($section->questions()->count() >= $max) {
            throw ValidationException::withMessages([
                $errorKey => "1セクションに登録できる問題は{$max}問までです。",
            ]);
        }
    }
}
