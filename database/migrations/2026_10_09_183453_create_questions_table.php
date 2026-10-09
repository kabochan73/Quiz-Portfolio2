<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 問題は必ず1つのセクションに属する(requirements.md 3.2、architecture.md 2.3)。
     * セクションを削除したら問題も消えるよう、section_id は cascade にする。
     *
     * 「1セクション10問まで」の上限は、アプリ側でセクション行をロックしてから件数を数えて守る
     * (architecture.md 4.1)。DB では行数の制約は設けない。
     * 本文の文字数の上限(2000文字)もアプリ側のバリデーションで守るので、ここでは text にしている。
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            // 現状ログインできるのは管理者1名だけだが、権限の確認(QuestionPolicy)に使う
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // セクション詳細での問題一覧用にインデックスを付ける
            $table->foreignId('section_id')->index()->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
