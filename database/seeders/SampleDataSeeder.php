<?php

namespace Database\Seeders;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * 画面を作り込むときの動作確認用のサンプルデータ(ローカル環境だけで入れる)。
 *
 * 点数をばらつかせ、苦手問題(60点未満)・前回比・採点待ち・失敗・空のセクションなど、
 * 画面のさまざまな表示を確かめられるようにしている。
 */
class SampleDataSeeder extends Seeder
{
    /**
     * カテゴリ => セクション => 問題文の一覧
     */
    private const CATALOG = [
        '基本情報' => [
            'ネットワーク' => [
                'TCPとUDPの違いを説明してください。',
                'DNSによる名前解決の流れを説明してください。',
                'HTTPSで通信が暗号化される仕組みを説明してください。',
                'IPアドレスとMACアドレスの役割の違いを説明してください。',
                'サブネットマスクの役割を説明してください。',
            ],
            'データベース' => [
                'データベースの正規化の目的を説明してください。',
                'トランザクションのACID特性を説明してください。',
                'インデックスを付けると検索が速くなる理由を説明してください。',
            ],
            'セキュリティ' => [
                'SQLインジェクションとその対策を説明してください。',
                'XSS(クロスサイトスクリプティング)とその対策を説明してください。',
            ],
        ],
        '英語' => [
            '英文法' => [
                '現在完了形と過去形の使い分けを説明してください。',
                '関係代名詞 which と that の使い分けを説明してください。',
            ],
        ],
        'Laravel' => [
            'ルーティング' => [
                'ルートモデルバインディングとは何か説明してください。',
                'ミドルウェアの役割を説明してください。',
            ],
            // 問題が0問のときの表示(空の状態)を確かめるためのセクション
            'Eloquent' => [],
        ],
    ];

    public function run(): void
    {
        $user = User::where('email', config('quiz.admin.email'))->firstOrFail();

        $sections = [];
        foreach (self::CATALOG as $categoryName => $sectionList) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($sectionList as $sectionName => $bodies) {
                $section = Section::create(['category_id' => $category->id, 'name' => $sectionName]);
                foreach ($bodies as $body) {
                    Question::create(['user_id' => $user->id, 'section_id' => $section->id, 'body' => $body]);
                }
                $sections[$sectionName] = $section;
            }
        }

        // ネットワーク: 点数が少しずつ上がっていく3回分 + 苦手問題だけの再挑戦 + 失敗 + 採点待ち
        $network = $sections['ネットワーク']->questions()->orderBy('id')->get();
        $this->createAttempt($user, $network, [55, 40, 70, 62, 48], daysAgo: 9);
        $this->createAttempt($user, $network, [68, 45, 78, 70, 52], daysAgo: 5, level: GradingLevel::Hard);
        $this->createAttempt($user, $network->only([$network[1]->id, $network[4]->id]), [58, 65], daysAgo: 2, mode: AttemptMode::Weak);
        $this->createAttempt($user, $network, null, daysAgo: 1, status: AttemptStatus::Failed);
        $this->createAttempt($user, $network, null, daysAgo: 0, status: AttemptStatus::Pending);

        // データベース: 高得点の1回分
        $database = $sections['データベース']->questions()->orderBy('id')->get();
        $this->createAttempt($user, $database, [85, 92, 74], daysAgo: 3, level: GradingLevel::Easy);
    }

    /**
     * 挑戦を1つ作り、問題ごとに回答を付ける。$scores を渡すと、その点数で採点済みにする。
     *
     * @param  Collection<int, Question>  $questions
     * @param  array<int, int>|null  $scores  $questions と同じ順の点数。null なら未採点
     */
    private function createAttempt(
        User $user,
        Collection $questions,
        ?array $scores,
        int $daysAgo,
        AttemptStatus $status = AttemptStatus::Completed,
        AttemptMode $mode = AttemptMode::All,
        GradingLevel $level = GradingLevel::Normal,
    ): void {
        $createdAt = now()->subDays($daysAgo)->setTime(20, 30);

        $attempt = Attempt::factory()
            ->when($status === AttemptStatus::Completed, fn ($factory) => $factory->completed())
            ->when($status === AttemptStatus::Failed, fn ($factory) => $factory->failed())
            ->create([
                'section_id' => $questions->first()->section_id,
                'user_id' => $user->id,
                'grading_level' => $level,
                'mode' => $mode,
                'status' => $status,
                'created_at' => $createdAt,
                'graded_at' => $status === AttemptStatus::Completed ? $createdAt->copy()->addMinute() : null,
            ]);

        foreach ($questions->values() as $position => $question) {
            $answer = $attempt->answers()->create([
                'question_id' => $question->id,
                'user_id' => $user->id,
                'position' => $position,
                'body' => '(サンプルの回答)'.mb_substr($question->body, 0, 10).'について、要点を説明します。',
                'created_at' => $createdAt,
            ]);

            if ($scores !== null) {
                $answer->score()->create([
                    'score' => $scores[$position],
                    'good_points' => '基本的な用語を正しく使えています。',
                    'improvements' => '具体例を1つ加えると、理解が伝わりやすくなります。',
                    'example' => '(改善例)'.mb_substr($question->body, 0, 10).'は、たとえば次のような場面で使われます。',
                ]);
            }
        }
    }
}
