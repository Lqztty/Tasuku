<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * かんばんのタスク。
     *
     * position は列（status）ごとに 0 からの連番。書き込みのたびにアプリ側で振り直す。
     * 担当者と作成者は、ユーザーが削除されてもタスクを残すため null に戻す。
     */
    public function up(): void
    {
        Schema::create('kanban_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('kanban_projects')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 20);
            $table->string('priority', 10);
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('position');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_tasks');
    }
};
