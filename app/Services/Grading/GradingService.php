<?php

namespace App\Services\Grading;

use App\Enums\GradingLevel;
use App\Models\Answer;
use Illuminate\Support\Collection;

/**
 * 採点サービスの決まった形(CLAUDE.md: 外部 API を呼ぶ処理はインターフェースの後ろに置く)。
 *
 * 呼び出す側(採点 Job)は、本物の Claude API(ClaudeGradingService)とフェイク(FakeGradingService)の
 * どちらが来ても同じように使える。どちらを使うかは .env の GRADING_DRIVER で切り替える(AppServiceProvider)。
 */
interface GradingService
{
    /**
     * 1つの挑戦の回答をまとめて採点する(requirements.md 3.3: 1回のリクエストで一括採点)。
     *
     * @param  Collection<int, Answer>  $answers  採点する回答。問題(question)を読み込み、position の順に並べておく
     * @return GradingResult 渡した回答すべての position について採点結果を持つ
     */
    public function grade(Collection $answers, GradingLevel $level): GradingResult;
}
