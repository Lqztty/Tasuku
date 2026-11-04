<?php

namespace App\Models;

use Database\Factories\KanbanProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * かんばんのプロジェクト（Eloquent）。
 *
 * Infrastructure 層の Repository からだけ使う。UseCase や Controller から直接触らない。
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $board_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'board_version'])]
class KanbanProject extends Model
{
    /** @use HasFactory<KanbanProjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'board_version' => 'integer',
        ];
    }

    /**
     * @return HasMany<KanbanProjectMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(KanbanProjectMember::class, 'project_id');
    }

    /**
     * @return HasMany<KanbanTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(KanbanTask::class, 'project_id');
    }
}
