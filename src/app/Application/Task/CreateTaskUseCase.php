<?php

namespace App\Application\Task;

use App\Application\Task\Commands\CreateTaskCommand;
use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\TaskOrdering;
use App\Domain\Task\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;

/**
 * タスクを作成し、指定した列の末尾に置く（メンバーなら誰でも）。
 */
final class CreateTaskUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly TaskRepositoryInterface $tasks,
        private readonly AssigneeGuard $assigneeGuard,
    ) {}

    public function execute(CreateTaskCommand $command): Task
    {
        return $this->transaction->run(function () use ($command): Task {
            $project = $this->projects->lockForUpdate($command->projectId);
            ProjectPermission::ensureCanEditTasks($this->members->findRole($project->id, $command->userId));
            $this->assigneeGuard->ensureMember($project->id, $command->assigneeId);

            $status = TaskStatus::from($command->status);
            $columns = TaskOrdering::columnsFrom($this->tasks->listByProject($project->id));

            $task = $this->tasks->save(Task::draft(
                projectId: $project->id,
                title: $command->title,
                description: $command->description,
                status: $status,
                priority: TaskPriority::from($command->priority),
                assigneeId: $command->assigneeId,
                dueDate: DueDate::parse($command->dueDate),
                position: count($columns[$status->value]),
                createdBy: $command->userId,
            ));

            $this->projects->save($project->bumpBoardVersion());

            return $task;
        });
    }
}
