<?php

namespace App\Enums;

/**
 * 挑戦(Attempt)の採点状態(requirements.md 3.3、architecture.md 4.3)。
 * 回答を送信すると pending で作られ、worker が採点を始めると grading、
 * 終われば completed、リトライ上限まで失敗すると failed になる。
 */
enum AttemptStatus: string
{
    case Pending = 'pending';
    case Grading = 'grading';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * 画面表示用の日本語ラベル。
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '待機中',
            self::Grading => '採点中',
            self::Completed => '完了',
            self::Failed => '失敗',
        };
    }

    /**
     * 採点がまだ終わっていないか。
     * 結果画面でポーリングを続けるか、履歴一覧で点数の代わりに状態を出すかの判断に使う。
     */
    public function isInProgress(): bool
    {
        return $this === self::Pending || $this === self::Grading;
    }
}
