<?php

namespace App\Application\Task;

use App\Application\Task\Commands\MoveTaskCommand;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\Exceptions\ConflictException;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Task\TaskOrdering;
use App\Domain\Task\TaskRepositoryInterface;
use App\Domain\Task\ValueObjects\TaskStatus;

/**
 * ドラッグ&ドロップでタスクを移動する（メンバーなら誰でも）。
 *
 * 手順:
 * 1. プロジェクトをロックする（同じボードへの書き込みはここで1本ずつに並ぶ）
 * 2. 権限を確かめる（メンバー以外には、競合ではなく 403 を返すため、版の比較より先に行う）
 * 3. クライアントが見ていた版と今の版を比べ、違えば何も変えずに競合として返す
 * 4. 並びを計算して書き込み、版を1つ進める
 */
final class MoveTaskUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskLock $taskLock,
    ) {}

    /**
     * @throws ConflictException
     */
    public function execute(MoveTaskCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $locked = $this->taskLock->acquire($command->taskId, $command->userId);
            ProjectPermission::ensureCanEditTasks($locked->role);
            $locked->project->ensureBoardVersion($command->expectedVersion);

            $columns = TaskOrdering::columnsFrom($this->tasks->listByProject($locked->project->id));
            $this->tasks->savePositions(
                $locked->project->id,
                TaskOrdering::move($columns, $command->taskId, TaskStatus::from($command->status), $command->position),
            );

            $this->projects->save($locked->project->bumpBoardVersion());
        });
    }
}
