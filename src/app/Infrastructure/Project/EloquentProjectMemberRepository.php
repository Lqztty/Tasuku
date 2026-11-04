<?php

namespace App\Infrastructure\Project;

use App\Domain\Project\Entities\ProjectMember;
use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Models\KanbanProjectMember;

final class EloquentProjectMemberRepository implements ProjectMemberRepositoryInterface
{
    public function findRole(int $projectId, int $userId): ?ProjectRole
    {
        $role = KanbanProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->value('role');

        return $role === null ? null : ProjectRole::from((string) $role);
    }

    public function findRoleForUpdate(int $projectId, int $userId): ?ProjectRole
    {
        $role = KanbanProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->value('role');

        return $role === null ? null : ProjectRole::from((string) $role);
    }

    public function listByProject(int $projectId): array
    {
        return array_values(KanbanProjectMember::query()
            ->join('users', 'users.id', '=', 'kanban_project_user.user_id')
            ->where('kanban_project_user.project_id', $projectId)
            ->select('kanban_project_user.*', 'users.name as user_name', 'users.email as user_email')
            // オーナーを先頭にする（role の値に依存せず、明示的に並べる）
            ->orderByRaw('CASE WHEN kanban_project_user.role = ? THEN 0 ELSE 1 END', [ProjectRole::Owner->value])
            ->orderBy('users.name')
            ->get()
            ->map(fn (KanbanProjectMember $model): ProjectMember => new ProjectMember(
                projectId: $model->project_id,
                userId: $model->user_id,
                name: (string) $model->getAttribute('user_name'),
                email: (string) $model->getAttribute('user_email'),
                role: ProjectRole::from($model->role),
            ))
            ->all());
    }

    public function add(int $projectId, int $userId, ProjectRole $role): void
    {
        KanbanProjectMember::query()->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role->value,
        ]);
    }

    public function remove(int $projectId, int $userId): void
    {
        KanbanProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function changeRole(int $projectId, int $userId, ProjectRole $role): void
    {
        KanbanProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->update(['role' => $role->value]);
    }

    public function findRolesByUserForUpdate(int $userId): array
    {
        return array_values(KanbanProjectMember::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->pluck('role')
            ->map(fn (mixed $role): ProjectRole => ProjectRole::from((string) $role))
            ->all());
    }
}
