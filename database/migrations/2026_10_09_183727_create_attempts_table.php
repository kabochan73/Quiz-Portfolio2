<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 挑戦(Attempt)は「セクションの問題にまとめて回答した1回分」(requirements.md 3.3、architecture.md 2.4)。
     * この1行の下に回答(answers)がぶら下がる。採点の状態と、Claude API の利用量もここに持つ。
     *
     * 状態・種別・採点レベルは、Laravel の enum() で作る。PostgreSQL では「文字列 + CHECK 制約」になり、
     * 専用の enum 型を使わないので、あとから値を足すマイグレーションが簡単になる。
     * 許可する値は PHP の Enum から組み立て、Enum と DB の値がずれないようにしている。
     */
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('grading_level', array_column(GradingLevel::cases(), 'value'));
            $table->enum('mode', array_column(AttemptMode::cases(), 'value'));
            $table->enum('status', array_column(AttemptStatus::cases(), 'value'))->default(AttemptStatus::Pending->value);

            // 採点が終わってから入る値(architecture.md 4.3 / 5章)
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('web_search_requests')->nullable();
            // 失敗理由はログ用途。画面には出さない
            $table->text('error_message')->nullable();
            $table->timestamp('graded_at')->nullable();

            $table->timestamps();

            // 履歴一覧で、セクションごとの挑戦を新しい順に並べるため(screens.md 2.11)
            $table->index(['section_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
