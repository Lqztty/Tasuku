<?php

namespace App\Application\ProjectMember;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Domain\Shared\TransactionManagerInterface;

/**
 * オーナーを別のメンバーに引き継ぐ（オーナーだけ）。元のオーナーはメンバーとして残る。
 *
 * 2つのロールの書き換えを同じトランザクションで行い、「オーナーは1プロジェクトに必ず1人」を保つ。
 * プロジェクト行を先にロックするので、同時に引き継ごうとしても2回目は
 * 自分がすでにメンバーになっていて権限エラーになる。
 * タスクの並びや担当者は変わらないので、ボードの版は進めない。
 */
final class TransferProjectOwnershipUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
    ) {}

    public function execute(int $userId, int $projectId, int $newOwnerId): void
    {
        $this->transaction->run(function () use ($userId, $projectId, $newOwnerId): void {
            $project = $this->projects->lockForUpdate($projectId);
            ProjectPermission::ensureCanManageProject($this->members->findRole($project->id, $userId));

            $targetRole = $this->members->findRole($project->id, $newOwnerId)
                ?? throw new NotFoundException('メンバーが見つかりません。');

            if ($targetRole === ProjectRole::Owner) {
                throw new InvalidInputException('member', 'すでにオーナーです。');
            }

            $this->members->changeRole($project->id, $userId, ProjectRole::Member);
            $this->members->changeRole($project->id, $newOwnerId, ProjectRole::Owner);
        });
    }
}
