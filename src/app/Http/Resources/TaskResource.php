<?php

namespace App\Http\Resources;

use App\Domain\Task\Entities\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * タスク。担当者の名前は、画面側でメンバー一覧から引く（N+1 を避けるため、ここでは ID だけ返す）。
 *
 * @property-read Task $resource
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::fromTask($this->resource);
    }

    /**
     * AssignedTaskResource からも同じ形で使う。
     *
     * @return array<string, mixed>
     */
    public static function fromTask(Task $task): array
    {
        return [
            'id' => $task->id,
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
    }
}
