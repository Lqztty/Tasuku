<?php

namespace Tests\Integration;

use App\Domain\Project\ValueObjects\ProjectRole;
use App\Models\KanbanProjectMember;
use App\Models\KanbanTask;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * MySQL の外部キーと一意制約が、設計どおりに働くこと。
 */
class ConstraintTest extends IntegrationTestCase
{
    public function test_プロジェクトを削除するとメンバーとタスクも消える(): void
    {
        [, , $project] = $this->ownerMemberAndProject();
        KanbanTask::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseCount('kanban_project_user', 0);
        $this->assertDatabaseCount('kanban_tasks', 0);
    }

    public function test_ユーザーを削除してもタスクは残り担当者と作成者は_null_になる(): void
    {
        [$owner, $member, $project] = $this->ownerMemberAndProject();
        $task = KanbanTask::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $member->id,
            'created_by' => $member->id,
        ]);

        $member->delete();

        $this->assertDatabaseHas('kanban_tasks', ['id' => $task->id, 'assignee_id' => null, 'created_by' => null]);
        $this->assertDatabaseMissing('kanban_project_user', ['user_id' => $member->id]);
        $this->assertDatabaseHas('kanban_project_user', ['user_id' => $owner->id]);
    }

    public function test_同じユーザーを同じプロジェクトに二重に登録できない(): void
    {
        [, $member, $project] = $this->ownerMemberAndProject();

        $this->expectException(UniqueConstraintViolationException::class);

        KanbanProjectMember::query()->create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => ProjectRole::Member->value,
        ]);
    }
}
