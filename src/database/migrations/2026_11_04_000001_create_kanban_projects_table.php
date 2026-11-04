<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * かんばんのプロジェクト。
     *
     * board_version はボード（タスクの並びや内容）が変わるたびに +1 する。
     * クライアントが見ていた版と比べ、古い画面からの移動を競合として検出するために使う。
     */
    public function up(): void
    {
        Schema::create('kanban_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('board_version')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_projects');
    }
};
