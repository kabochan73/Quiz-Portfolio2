<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 管理者のログイン(requirements.md 3.1)。会員登録の機能はないので、ログインできるのは
 * Seeder で作った管理者だけ。
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // ログイン前のセッション ID を使い続けさせない(セッション固定攻撃の対策)
        $request->session()->regenerate();

        // ログインしていない状態で開こうとしたページがあれば、そこへ戻す
        return redirect()->intended(route('categories.index'));
    }
}
