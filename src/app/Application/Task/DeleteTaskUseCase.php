<?php

namespace App\Application\Task;

use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Task\TaskOrdering;
use App\Domain\Task\TaskRepositoryInterface;

/**
 * タスクを削除し、残ったタスクの position を詰める。
 *
 * オーナーはすべて、メンバーは自分が作成したタスクだけ削除できる。
 */
final class DeleteTaskUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskLock $taskLock,
    ) {}

    public function execute(int $userId, int $taskId): void
    {
        $this->transaction->run(function () use ($userId, $taskId): void {
            $locked = $this->taskLock->acquire($taskId, $userId);
            ProjectPermission::ensureCanDeleteTask($locked->role, $userId, $locked->task->createdBy);

            $columns = TaskOrdering::columnsFrom($this->tasks->listByProject($locked->project->id));
            $this->tasks->delete($taskId);
            $this->tasks->savePositions($locked->project->id, TaskOrdering::remove($columns, $taskId));

            $this->projects->save($locked->project->bumpBoardVersion());
        });
    }
}
