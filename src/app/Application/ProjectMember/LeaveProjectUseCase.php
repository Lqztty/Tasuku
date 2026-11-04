<?php

namespace App\Application\ProjectMember;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\ForbiddenException;
use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Task\TaskRepositoryInterface;

/**
 * 自分からプロジェクトを抜ける（メンバーだけ。オーナーは先に引き継ぐ）。
 *
 * 担当していたタスクの担当者を null に戻す処理とメンバー行の削除を同じトランザクションで行う
 * （RemoveProjectMemberUseCase と同じ）。プロジェクト行を先にロックするので、
 * 同時に届いたオーナーの引き継ぎとは直列になり、「抜けた人がオーナーになる」ことは起きない。
 */
final class LeaveProjectUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @throws NotFoundException プロジェクトが無いとき
     * @throws ForbiddenException メンバーでないとき
     * @throws InvalidInputException オーナーのとき
     */
    public function execute(int $userId, int $projectId): void
    {
        $this->transaction->run(function () use ($userId, $projectId): void {
            $project = $this->projects->lockForUpdate($projectId);
            $role = $this->members->findRole($project->id, $userId);
            ProjectPermission::ensureCanView($role);

            // オーナーが抜けるとオーナーのいないプロジェクトになるため、先に引き継いでもらう
            if ($role === ProjectRole::Owner) {
                throw new InvalidInputException('member', 'オーナーはプロジェクトから抜けられません。先にほかのメンバーへオーナーを引き継いでください。');
            }

            $this->tasks->unassignUserInProject($project->id, $userId);
            $this->members->remove($project->id, $userId);

            // 担当者が変わったのでボードの版を進める（古い画面からの移動を競合として扱う）
            $this->projects->save($project->bumpBoardVersion());
        });
    }
}
