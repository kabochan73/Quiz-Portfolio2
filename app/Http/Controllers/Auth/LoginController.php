<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        // セッションを破棄し、CSRF トークンも作り直す(ログアウト前のトークンを使い回させない)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('toast', ['type' => 'success', 'message' => 'ログアウトしました']);
    }
}
