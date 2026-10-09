<?php

namespace App\Enums;

/**
 * 挑戦(Attempt)の種別(requirements.md 3.3)。
 * セクションの全問に回答したのか、苦手問題だけに再挑戦したのかを記録する。
 */
enum AttemptMode: string
{
    case All = 'all';
    case Weak = 'weak';

    /**
     * 画面表示用の日本語ラベル。
     */
    public function label(): string
    {
        return match ($this) {
            self::All => '全問',
            self::Weak => '苦手',
        };
    }
}
