<?php

namespace App\Infrastructure\Project;

use App\Domain\Project\Entities\Project;
use App\Domain\Project\Entities\ProjectSummary;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Models\KanbanProject;

final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function find(int $id): ?Project
    {
        $model = KanbanProject::query()->find($id);

        return $model === null ? null : $this->toEntity($model);
    }

    public function lockForUpdate(int $id): Project
    {
        $model = KanbanProject::query()->lockForUpdate()->find($id);

        if ($model === null) {
            throw new NotFoundException('プロジェクトが見つかりません。');
        }

        return $this->toEntity($model);
    }

    public function create(string $name, ?string $description): Project
    {
        $model = KanbanProject::query()->create([
            'name' => $name,
            'description' => $description,
            'board_version' => 0,
        ]);

        return $this->toEntity($model);
    }

    public function save(Project $project): void
    {
        KanbanProject::query()->whereKey($project->id)->update([
            'name' => $project->name,
            'description' => $project->description,
            'board_version' => $project->boardVersion,
            'updated_at' => now(),
        ]);
    }

    public function delete(int $id): void
    {
        KanbanProject::query()->whereKey($id)->delete();
    }

    public function listSummariesForUser(int $userId): array
    {
        return array_values(KanbanProject::query()
            ->join('kanban_project_user as membership', 'membership.project_id', '=', 'kanban_projects.id')
            ->where('membership.user_id', $userId)
            ->select('kanban_projects.*', 'membership.role as membership_role')
            ->withCount(['tasks', 'members'])
            ->orderByDesc('kanban_projects.updated_at')
            ->orderByDesc('kanban_projects.id')
            ->get()
            ->map(fn (KanbanProject $model): ProjectSummary => new ProjectSummary(
                id: $model->id,
                name: $model->name,
                description: $model->description,
                role: ProjectRole::from((string) $model->getAttribute('membership_role')),
                taskCount: (int) $model->getAttribute('tasks_count'),
                memberCount: (int) $model->getAttribute('members_count'),
            ))
            ->all());
    }

    private function toEntity(KanbanProject $model): Project
    {
        return new Project(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            boardVersion: $model->board_version,
        );
    }
}
