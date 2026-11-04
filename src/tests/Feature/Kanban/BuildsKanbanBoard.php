<?php

namespace Tests\Feature\Kanban;

use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanProject;
use App\Models\KanbanTask;
use App\Models\User;

/**
 * かんばんの Feature テスト用の共通データ。
 *
 * - owner: プロジェクトのオーナー
 * - member: プロジェクトのメンバー
 * - outsider: どのプロジェクトにも入っていないユーザー
 */
trait BuildsKanbanBoard
{
    protected User $owner;

    protected User $member;

    protected User $outsider;

    protected KanbanProject $project;

    protected function setUpBoard(): void
    {
        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->outsider = User::factory()->create();
        $this->project = KanbanProject::factory()
            ->ownedBy($this->owner)
            ->withMember($this->member)
            ->create();
    }

    /**
     * 指定した列の末尾にタスクを作る（position は列ごとの連番になる）。
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function createTask(TaskStatus $status = TaskStatus::Todo, array $attributes = [], ?KanbanProject $project = null): KanbanTask
    {
        $project ??= $this->project;
        $position = KanbanTask::query()
            ->where('project_id', $project->id)
            ->where('status', $status->value)
            ->count();

        return KanbanTask::factory()->create([
            'project_id' => $project->id,
            'status' => $status->value,
            'position' => $position,
            'created_by' => $this->owner->id,
            ...$attributes,
        ]);
    }

    /**
     * 列ごとのタスク ID の並び（position 順）。position が 0 からの連番かも確かめる。
     *
     * @return list<int>
     */
    protected function column(TaskStatus $status, ?KanbanProject $project = null): array
    {
        $project ??= $this->project;
        $rows = KanbanTask::query()
            ->where('project_id', $project->id)
            ->where('status', $status->value)
            ->orderBy('position')
            ->get(['id', 'position']);

        $this->assertSame(
            range(0, max(0, $rows->count() - 1)),
            $rows->isEmpty() ? [0] : $rows->pluck('position')->all(),
            "{$status->value} 列の position が 0 からの連番になっていない",
        );

        return $rows->pluck('id')->all();
    }

    protected function boardVersion(?KanbanProject $project = null): int
    {
        return ($project ?? $this->project)->fresh()?->board_version ?? -1;
    }
}
