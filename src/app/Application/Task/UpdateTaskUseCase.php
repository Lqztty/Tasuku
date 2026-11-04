<?php

namespace App\Application\Task;

use App\Application\Task\Commands\UpdateTaskCommand;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Task\TaskOrdering;
use App\Domain\Task\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;

/**
 * タスクの内容を変更する（メンバーなら誰でも）。
 *
 * ステータスを変えた場合は、新しい列の末尾へ移し、移動元と移動先の列の position を振り直す。
 */
final class UpdateTaskUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskLock $taskLock,
        private readonly AssigneeGuard $assigneeGuard,
    ) {}

    public function execute(UpdateTaskCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $locked = $this->taskLock->acquire($command->taskId, $command->userId);
            ProjectPermission::ensureCanEditTasks($locked->role);
            $this->assigneeGuard->ensureMember($locked->project->id, $command->assigneeId);

            $task = $this->tasks->save($locked->task->withDetails(
                title: $command->title,
                description: $command->description,
                priority: TaskPriority::from($command->priority),
                assigneeId: $command->assigneeId,
                dueDate: DueDate::parse($command->dueDate),
            ));

            $newStatus = TaskStatus::from($command->status);
            if ($newStatus !== $task->status && $task->id !== null) {
                $columns = TaskOrdering::columnsFrom($this->tasks->listByProject($locked->project->id));
                $this->tasks->savePositions(
                    $locked->project->id,
                    TaskOrdering::move($columns, $task->id, $newStatus, PHP_INT_MAX),
                );
            }

            $this->projects->save($locked->project->bumpBoardVersion());
        });
    }
}
