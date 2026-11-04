<?php

namespace App\Application\Task;

use App\Domain\Task\Entities\AssignedTask;
use App\Domain\Task\TaskRepositoryInterface;

/**
 * 自分が担当しているタスクを、プロジェクトをまたいで取得する（期限の近い順）。
 */
final class GetMyTaskListUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @return list<AssignedTask>
     */
    public function execute(int $userId): array
    {
        return $this->tasks->listAssignedTo($userId);
    }
}
