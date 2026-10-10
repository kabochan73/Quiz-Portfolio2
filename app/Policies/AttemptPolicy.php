<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * 挑戦(採点結果・履歴)を見られるのは、その挑戦をしたユーザーだけ(architecture.md 6章)。
 * QuestionPolicy と同じく、他人の挑戦には 404 を返して存在を知らせない。
 */
class AttemptPolicy
{
    public function view(User $user, Attempt $attempt): Response
    {
        return $user->id === $attempt->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * 再採点(architecture.md 4.6)。自分の挑戦なら受け付ける。
     * 「失敗のときだけ再採点する」という状態の確認は、権限ではなく処理の条件なのでコントローラで行う。
     */
    public function regrade(User $user, Attempt $attempt): Response
    {
        return $this->view($user, $attempt);
    }
}
