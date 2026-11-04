<?php

namespace App\Http\Resources;

use App\Domain\Task\Entities\AssignedTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * マイタスク画面の1行（タスク + プロジェクト名）。
 *
 * @property-read AssignedTask $resource
 */
class AssignedTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...TaskResource::fromTask($this->resource->task),
            'project_name' => $this->resource->projectName,
        ];
    }
}
