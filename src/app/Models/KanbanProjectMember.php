<?php

namespace App\Models;

use Database\Factories\KanbanProjectMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * プロジェクトのメンバー（中間テーブル kanban_project_user の行）。
 *
 * Infrastructure 層の Repository からだけ使う。
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('kanban_project_user')]
#[Fillable(['project_id', 'user_id', 'role'])]
class KanbanProjectMember extends Model
{
    /** @use HasFactory<KanbanProjectMemberFactory> */
    use HasFactory;

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
