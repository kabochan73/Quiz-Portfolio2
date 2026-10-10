<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * 問題を扱えるのは、その問題を作ったユーザーだけ(architecture.md 6章)。
 *
 * ログインできるのは管理者1人なので現実には他人の問題はないが、問題は user_id を持つので、
 * 将来の複数ユーザー化に備えて Policy として正式に確かめておく。
 * 他人の問題には 403 ではなく 404 を返し、「存在するが見られない」ことを相手に知らせない。
 */
class QuestionPolicy
{
    public function view(User $user, Question $question): Response
    {
        return $user->id === $question->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
