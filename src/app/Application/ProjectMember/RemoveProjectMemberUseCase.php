<?php

namespace App\Application\ProjectMember;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Task\TaskRepositoryInterface;

/**
 * メンバーをプロジェクトから外す（オーナーだけ。オーナー自身は外せない）。
 *
 * 外した人が担当していたタスクの担当者を null に戻す処理と、メンバー行の削除を
 * 同じトランザクションで行い、「メンバーでない担当者」が残らないようにする。
 */
final class RemoveProjectMemberUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function execute(int $userId, int $projectId, int $targetUserId): void
    {
        $this->transaction->run(function () use ($userId, $projectId, $targetUserId): void {
            $project = $this->projects->lockForUpdate($projectId);
            ProjectPermission::ensureCanManageProject($this->members->findRole($project->id, $userId));

            $targetRole = $this->members->findRole($project->id, $targetUserId)
                ?? throw new NotFoundException('メンバーが見つかりません。');

            if ($targetRole === ProjectRole::Owner) {
                throw new InvalidInputException('member', 'オーナーはプロジェクトから外せません。');
            }

            $this->tasks->unassignUserInProject($project->id, $targetUserId);
            $this->members->remove($project->id, $targetUserId);

            // 担当者が変わったのでボードの版を進める（古い画面からの移動を競合として扱う）
            $this->projects->save($project->bumpBoardVersion());
        });
    }
}
