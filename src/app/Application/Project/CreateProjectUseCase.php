<?php

namespace App\Application\Project;

use App\Application\Project\Commands\CreateProjectCommand;
use App\Domain\Project\Entities\Project;
use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\TransactionManagerInterface;

/**
 * プロジェクトを作成し、作成した人をオーナーにする。
 *
 * 「オーナーは必ず1人」を守るため、プロジェクトの作成とオーナーの追加は同じトランザクションで行う。
 */
final class CreateProjectUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
    ) {}

    public function execute(CreateProjectCommand $command): Project
    {
        return $this->transaction->run(function () use ($command): Project {
            $project = $this->projects->create($command->name, $command->description);
            $this->members->add($project->id, $command->userId, ProjectRole::Owner);

            return $project;
        });
    }
}
