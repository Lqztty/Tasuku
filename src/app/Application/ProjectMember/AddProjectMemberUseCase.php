<?php

namespace App\Application\ProjectMember;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\User\UserRepositoryInterface;

/**
 * 登録済みのユーザーを、メールアドレスで探してメンバーに追加する（オーナーだけ）。
 */
final class AddProjectMemberUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function execute(int $userId, int $projectId, string $email): void
    {
        $this->transaction->run(function () use ($userId, $projectId, $email): void {
            $project = $this->projects->lockForUpdate($projectId);
            ProjectPermission::ensureCanManageProject($this->members->findRole($project->id, $userId));

            $newMemberId = $this->users->findIdByEmail($email)
                ?? throw new InvalidInputException('email', 'このメールアドレスのユーザーは登録されていません。');

            if ($this->members->findRole($project->id, $newMemberId) !== null) {
                throw new InvalidInputException('email', 'このユーザーはすでにメンバーです。');
            }

            $this->members->add($project->id, $newMemberId, ProjectRole::Member);
        });
    }
}
