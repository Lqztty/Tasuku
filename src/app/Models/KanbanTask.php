<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\KanbanTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * かんばんのタスク（Eloquent）。
 *
 * Infrastructure 層の Repository からだけ使う。
 * status / priority は文字列のまま持ち、Domain の enum への変換は Repository で行う。
 *
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property int|null $assignee_id
 * @property CarbonImmutable|null $due_date
 * @property int $position
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'project_id',
    'title',
    'description',
    'status',
    'priority',
    'assignee_id',
    'due_date',
    'position',
    'created_by',
])]
class KanbanTask extends Model
{
    /** @use HasFactory<KanbanTaskFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'due_date' => 'immutable_date',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KanbanProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(KanbanProject::class, 'project_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
