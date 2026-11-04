<?php

namespace App\Http\Resources;

use App\Domain\Project\Entities\ProjectSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * プロジェクト一覧の1行。
 *
 * @property-read ProjectSummary $resource
 */
class ProjectSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'role' => $this->resource->role->value,
            'task_count' => $this->resource->taskCount,
            'member_count' => $this->resource->memberCount,
        ];
    }
}
