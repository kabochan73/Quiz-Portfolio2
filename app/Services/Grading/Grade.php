<?php

namespace App\Services\Grading;

/**
 * 1問分の採点結果(requirements.md 3.3)。点数と、「良い点 / 改善点 / 改善例」に分けたフィードバック。
 * 作ったあとに書き換えられないよう readonly にしている。
 */
final readonly class Grade
{
    public function __construct(
        public int $score,
        public string $goodPoints,
        public string $improvements,
        public string $example,
    ) {}
}
