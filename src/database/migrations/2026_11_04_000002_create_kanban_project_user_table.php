<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * プロジェクトのメンバー（中間テーブル）。
     *
     * role は owner / member。オーナーは1プロジェクトに必ず1人で、
     * その保証はアプリ側（作成時に同じトランザクションで追加・オーナーは外せない）で行う。
     */
    public function up(): void
    {
        Schema::create('kanban_project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('kanban_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_project_user');
    }
};
