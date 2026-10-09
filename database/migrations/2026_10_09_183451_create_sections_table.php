<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * セクションは「問題の入れ物」で、必ず1つのカテゴリに属する(requirements.md 3.2、architecture.md 2.2)。
     * カテゴリを削除したら配下のセクションも消えるよう、category_id は cascade にする。
     */
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            // PostgreSQL は外部キーに自動でインデックスを付けないため、カテゴリ詳細でのセクション一覧用に明示する
            $table->foreignId('category_id')->index()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
