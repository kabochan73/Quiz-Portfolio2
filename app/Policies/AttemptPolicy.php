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
}
