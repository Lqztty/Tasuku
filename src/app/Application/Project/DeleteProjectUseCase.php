<?php

namespace App\Application\Project;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\TransactionManagerInterface;

/**
 * プロジェクトを削除する（オーナーだけ）。メンバーとタスクは外部キーの cascade で消える。
 */
final class DeleteProjectUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
    ) {}

    public function execute(int $userId, int $projectId): void
    {
        $this->transaction->run(function () use ($userId, $projectId): void {
            $project = $this->projects->lockForUpdate($projectId);
            ProjectPermission::ensureCanManageProject($this->members->findRole($project->id, $userId));

            $this->projects->delete($project->id);
        });
    }
}
