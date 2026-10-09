<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ログインフォームの入力チェックと認証(requirements.md 3.1、screens.md 2.1)。
 *
 * 総当たり対策として、同じ「メールアドレス + IP アドレス」での失敗を1分間に6回までに制限する。
 * v1 は throttle ミドルウェアで制限していたため、超えると英語の 429 画面になっていた。
 * ここでは制限をフォームのエラーとして扱い、何秒後に再試行できるかを日本語で表示する。
 */
class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 6;

    private const DECAY_SECONDS = 60;

    /**
     * 認証の失敗・制限のエラーを入れるキー。特定の入力欄の下ではなく、フォームの上に1つだけ表示する。
     */
    public const ERROR_KEY = 'login';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * メールアドレスとパスワードで認証する。失敗したら試行回数を数え、エラーにする。
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            // どちらが間違っているかは伝えない
            throw ValidationException::withMessages([self::ERROR_KEY => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            self::ERROR_KEY => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    /**
     * メールアドレスの大文字・小文字の違いで制限をすり抜けられないよう、小文字にそろえてから使う。
     */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
