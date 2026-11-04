<?php

namespace Database\Seeders;

use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use App\Models\KanbanTask;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * 開発・動作確認用のデモデータ。
 *
 * ログイン: owner@example.com / password（ほかの2人も同じパスワード）
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $owner = User::factory()->create(['name' => '山田 太郎', 'email' => 'owner@example.com']);
        $hanako = User::factory()->create(['name' => '佐藤 花子', 'email' => 'hanako@example.com']);
        $jiro = User::factory()->create(['name' => '鈴木 次郎', 'email' => 'jiro@example.com']);

        $project = KanbanProject::factory()
            ->ownedBy($owner)
            ->withMember($hanako)
            ->withMember($jiro)
            ->create([
                'name' => 'Tasuku 開発',
                'description' => '社内向けタスク管理システムの開発プロジェクト',
            ]);

        // [タイトル, ステータス, 優先度, 担当者, 期限（今日からの日数。null は期限なし）]
        $tasks = [
            ['要件定義書のレビュー', TaskStatus::Done, TaskPriority::High, $owner, -5],
            ['DB 設計', TaskStatus::Done, TaskPriority::High, $hanako, -3],
            ['Docker 環境の構築', TaskStatus::Done, TaskPriority::Medium, $jiro, -2],
            ['ログイン画面の確認', TaskStatus::Review, TaskPriority::Medium, $hanako, 1],
            ['かんばんボードの実装', TaskStatus::InProgress, TaskPriority::High, $owner, 3],
            ['権限チェックのテスト', TaskStatus::InProgress, TaskPriority::Medium, $jiro, -1],
            ['マイタスク画面の実装', TaskStatus::Todo, TaskPriority::Medium, $hanako, 7],
            ['絞り込み機能の実装', TaskStatus::Todo, TaskPriority::Low, null, 10],
            ['ダークモードの表示確認', TaskStatus::Todo, TaskPriority::Low, $jiro, null],
            ['リリース手順書の作成', TaskStatus::Todo, TaskPriority::Medium, null, null],
        ];

        // position は列（status）ごとに 0 からの連番にする
        $positions = [];

        foreach ($tasks as [$title, $status, $priority, $assignee, $dueInDays]) {
            $position = $positions[$status->value] ?? 0;
            $positions[$status->value] = $position + 1;

            KanbanTask::factory()->create([
                'project_id' => $project->id,
                'title' => $title,
                'description' => null,
                'status' => $status->value,
                'priority' => $priority->value,
                'assignee_id' => $assignee?->id,
                'due_date' => $dueInDays === null ? null : now()->addDays($dueInDays)->toDateString(),
                'position' => $position,
                'created_by' => $owner->id,
            ]);
        }
    }
}
