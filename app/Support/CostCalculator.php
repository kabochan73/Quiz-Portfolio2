<?php

namespace App\Support;

/**
 * 採点1回分の Claude API の推定コスト(ドル)を求める(architecture.md 5章)。
 *
 * 推定コスト = 入力トークン × 入力単価 + 出力トークン × 出力単価 + 検索回数 × 検索単価
 * (トークンの単価は 100万トークンあたり、検索の単価は1回あたり)
 *
 * 単価はコードに書かず config/quiz.php(.env)から受け取る。料金が変わっても設定だけ直せばよいように。
 * 単価が分からないとき(未設定・空文字・設定のないモデル)は、誤った金額を出さないよう null を返し、
 * 画面ではトークン数だけを表示する。
 */
class CostCalculator
{
    /**
     * @param  array<string, array{input_per_mtok?: mixed, output_per_mtok?: mixed}>  $pricing  モデル名ごとの単価
     */
    public function __construct(
        private readonly array $pricing,
        private readonly mixed $webSearchPerRequest,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('quiz.pricing', []), config('quiz.web_search_per_request'));
    }

    public function estimate(?string $model, ?int $inputTokens, ?int $outputTokens, ?int $webSearchRequests): ?float
    {
        if ($model === null || $inputTokens === null || $outputTokens === null) {
            return null;
        }

        $inputPrice = $this->price($this->pricing[$model]['input_per_mtok'] ?? null);
        $outputPrice = $this->price($this->pricing[$model]['output_per_mtok'] ?? null);

        if ($inputPrice === null || $outputPrice === null) {
            return null;
        }

        $cost = ($inputTokens * $inputPrice + $outputTokens * $outputPrice) / 1_000_000;

        // 検索をしていなければ、検索の単価が未設定でも計算できる
        $searches = $webSearchRequests ?? 0;

        if ($searches > 0) {
            $searchPrice = $this->price($this->webSearchPerRequest);

            if ($searchPrice === null) {
                return null;
            }

            $cost += $searches * $searchPrice;
        }

        return $cost;
    }

    /**
     * .env の値は文字列で来る。空文字(KEY= の形)や数値でない値は「未設定」として扱う。
     */
    private function price(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
