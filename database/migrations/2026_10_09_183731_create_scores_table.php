<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 採点結果(Score)は回答と 1:1(architecture.md 2.6)。answer_id を UNIQUE にして 1:1 を守る。
     * フィードバックは「良い点 / 改善点 / 改善例」の3つに分けて持つ(requirements.md 3.3)。
     *
     * 点数は 0〜100。アプリ側でも範囲内に収めるが、Laravel のマイグレーションには
     * CHECK 制約を自由に書く方法がないため、SQL を直接実行して DB でも範囲外の値を弾く。
     */
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->text('good_points');
            $table->text('improvements');
            $table->text('example');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE scores ADD CONSTRAINT scores_score_between_0_and_100 CHECK (score BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
