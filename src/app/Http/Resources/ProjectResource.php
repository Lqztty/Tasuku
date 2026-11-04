<?php

namespace App\Http\Resources;

use App\Domain\Project\Entities\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ボード画面のプロジェクト。board_version はクライアントが移動のときに送り返す。
 *
 * @property-read Project $resource
 */
class ProjectResource extends JsonResource
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
            'board_version' => $this->resource->boardVersion,
        ];
    }
}
