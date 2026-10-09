<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 回答(Answer)は、1つの問題に対する1回分の回答本文(architecture.md 2.5)。
     * 同じ問題に何度でも挑戦できるので、1つの問題に複数の回答がある。
     * どの挑戦の回答かを attempt_id で、その挑戦の中で何問目かを position で持つ。
     */
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            // 結果画面を回答した順に並べ、AI の採点結果(position 付き)と対応付けるのに使う
            $table->unsignedSmallInteger('position');
            // 文字数の上限(5000文字)はアプリ側のバリデーションで守る
            $table->text('body');
            $table->timestamps();

            // 同じ挑戦の中で問題の順番が重複しないようにする。attempt_id のインデックスも兼ねる
            $table->unique(['attempt_id', 'position']);
            // 問題ごとの最新の点数・前回の点数・古い回答の整理で、問題ごとに新しい順に並べるため(architecture.md 3章)
            $table->index(['question_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
