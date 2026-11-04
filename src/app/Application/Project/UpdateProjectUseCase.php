<?php

namespace App\Application\Project;

use App\Application\Project\Commands\UpdateProjectCommand;
use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectPermission;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Shared\TransactionManagerInterface;

/**
 * プロジェクトの名前と説明を変更する（オーナーだけ）。
 */
final class UpdateProjectUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
    ) {}

    public function execute(UpdateProjectCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $project = $this->projects->lockForUpdate($command->projectId);
            ProjectPermission::ensureCanManageProject(
                $this->members->findRole($project->id, $command->userId),
            );

            $this->projects->save($project->rename($command->name, $command->description));
        });
    }
}
