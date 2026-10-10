<?php

namespace App\Services\Grading;

use OutOfBoundsException;

/**
 * 採点1回分(1つの挑戦)の結果。回答の順番(position)ごとの採点結果と、
 * 採点に使ったモデル名・Claude API の利用量(architecture.md 5章)を持つ。
 * 利用量は、フェイクの採点などで分からない場合は null。
 */
final readonly class GradingResult
{
    /**
     * @param  array<int, Grade>  $grades  回答の position をキーにした採点結果
     */
    public function __construct(
        public array $grades,
        public ?string $model = null,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $webSearchRequests = null,
    ) {}

    /**
     * 指定した順番の回答の採点結果。見つからないのは採点サービスの不具合なので、例外にして気づけるようにする。
     */
    public function gradeFor(int $position): Grade
    {
        return $this->grades[$position]
            ?? throw new OutOfBoundsException("{$position}番目の回答の採点結果がありません。");
    }
}
