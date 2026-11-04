<?php

namespace App\Infrastructure\Task;

use App\Domain\Task\Entities\AssignedTask;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;
use App\Models\KanbanTask;

final class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function find(int $id): ?Task
    {
        $model = KanbanTask::query()->find($id);

        return $model === null ? null : $this->toEntity($model);
    }

    public function listByProject(int $projectId): array
    {
        $tasks = KanbanTask::query()
            ->where('project_id', $projectId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (KanbanTask $model): Task => $this->toEntity($model))
            ->all();

        // 列の順（TaskStatus::cases() の順）に並べ直す。DB 方言（FIELD 関数など）に頼らない
        $statusOrder = array_flip(array_map(fn (TaskStatus $s): string => $s->value, TaskStatus::cases()));
        usort($tasks, fn (Task $a, Task $b): int => [$statusOrder[$a->status->value], $a->position, $a->id]
            <=> [$statusOrder[$b->status->value], $b->position, $b->id]);

        return $tasks;
    }

    public function listAssignedTo(int $userId): array
    {
        return array_values(KanbanTask::query()
            ->join('kanban_projects', 'kanban_projects.id', '=', 'kanban_tasks.project_id')
            ->where('kanban_tasks.assignee_id', $userId)
            ->select('kanban_tasks.*', 'kanban_projects.name as project_name')
            // 期限の近い順。期限なしは最後に回す
            ->orderByRaw('CASE WHEN kanban_tasks.due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('kanban_tasks.due_date')
            ->orderBy('kanban_tasks.id')
            ->get()
            ->map(fn (KanbanTask $model): AssignedTask => new AssignedTask(
                task: $this->toEntity($model),
                projectName: (string) $model->getAttribute('project_name'),
            ))
            ->all());
    }

    public function save(Task $task): Task
    {
        $attributes = [
            'project_id' => $task->projectId,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'assignee_id' => $task->assigneeId,
            'due_date' => $task->dueDate?->format('Y-m-d'),
            'position' => $task->position,
            'created_by' => $task->createdBy,
        ];

        if ($task->id === null) {
            $model = KanbanTask::query()->create($attributes);

            return $task->withId($model->id);
        }

        KanbanTask::query()->whereKey($task->id)->update([...$attributes, 'updated_at' => now()]);

        return $task;
    }

    public function delete(int $id): void
    {
        KanbanTask::query()->whereKey($id)->delete();
    }

    public function savePositions(int $projectId, array $columns): void
    {
        // 今の値と同じ行は書き込まない（不要な UPDATE と updated_at の変更を避ける）
        $current = KanbanTask::query()
            ->where('project_id', $projectId)
            ->get(['id', 'status', 'position'])
            ->keyBy('id');

        foreach ($columns as $status => $ids) {
            foreach ($ids as $position => $id) {
                $row = $current->get($id);
                if ($row === null || ($row->status === $status && $row->position === $position)) {
                    continue;
                }

                KanbanTask::query()
                    ->whereKey($id)
                    ->where('project_id', $projectId)
                    ->update(['status' => $status, 'position' => $position, 'updated_at' => now()]);
            }
        }
    }

    public function unassignUserInProject(int $projectId, int $userId): void
    {
        KanbanTask::query()
            ->where('project_id', $projectId)
            ->where('assignee_id', $userId)
            ->update(['assignee_id' => null, 'updated_at' => now()]);
    }

    private function toEntity(KanbanTask $model): Task
    {
        return new Task(
            id: $model->id,
            projectId: $model->project_id,
            title: $model->title,
            description: $model->description,
            status: TaskStatus::from($model->status),
            priority: TaskPriority::from($model->priority),
            assigneeId: $model->assignee_id,
            dueDate: $model->due_date,
            position: $model->position,
            createdBy: $model->created_by,
        );
    }
}
