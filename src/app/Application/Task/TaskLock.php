<?php

namespace App\Application\Task;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\Exceptions\NotFoundException;
use App\Domain\Task\TaskRepositoryInterface;

/**
 * 既存のタスクを書き換える前の共通手順。トランザクションの中で呼ぶこと。
 *
 * 1. タスクを探して、属するプロジェクトを知る
 * 2. プロジェクトの行をロックする（同じプロジェクトへの書き込みはここで1本ずつに並ぶ）
 * 3. タスクを読み直す（ロックを待っている間に、他の人が削除・移動しているかもしれないため）
 */
final class TaskLock
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @throws NotFoundException
     */
    public function acquire(int $taskId, int $userId): LockedTask
    {
        $before = $this->tasks->find($taskId) ?? throw $this->notFound();
        $project = $this->projects->lockForUpdate($before->projectId);
        $task = $this->tasks->find($taskId) ?? throw $this->notFound();

        return new LockedTask(
            project: $project,
            task: $task,
            role: $this->members->findRole($project->id, $userId),
        );
    }

    private function notFound(): NotFoundException
    {
        return new NotFoundException('タスクが見つかりません。');
    }
}
