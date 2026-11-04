<?php

namespace App\Application\Project;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\ForbiddenException;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Domain\Task\TaskRepositoryInterface;

/**
 * ボード画面のデータ（プロジェクト・メンバー・タスク・権限）を取得する。メンバーだけが見られる。
 */
final class GetProjectBoardUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function execute(int $userId, int $projectId): ProjectBoard
    {
        $project = $this->projects->find($projectId) ?? throw new NotFoundException('プロジェクトが見つかりません。');

        $role = $this->members->findRole($project->id, $userId);
        ProjectPermission::ensureCanView($role);
        /** @var ProjectRole $role ensureCanView を通ったので null ではない */

        return new ProjectBoard(
            project: $project,
            role: $role,
            members: $this->members->listByProject($project->id),
            tasks: $this->tasks->listByProject($project->id),
            canManageProject: ProjectPermission::canManageProject($role),
            // 「作成者が null（退会済み）のタスクも削除できる」= すべて削除できる
            canDeleteAnyTask: ProjectPermission::canDeleteTask($role, $userId, null),
        );
    }
}
