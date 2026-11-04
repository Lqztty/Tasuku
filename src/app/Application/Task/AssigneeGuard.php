<?php

namespace App\Application\Task;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Shared\Exceptions\InvalidInputException;

/**
 * 担当者に指定できるのは、そのプロジェクトのメンバー（オーナーを含む）だけ。
 */
final class AssigneeGuard
{
    public function __construct(
        private readonly ProjectMemberRepositoryInterface $members,
    ) {}

    /**
     * @throws InvalidInputException
     */
    public function ensureMember(int $projectId, ?int $assigneeId): void
    {
        if ($assigneeId === null) {
            return;
        }

        if ($this->members->findRole($projectId, $assigneeId) === null) {
            throw new InvalidInputException('assignee_id', '担当者はプロジェクトのメンバーから選んでください。');
        }
    }
}
