<?php

namespace App\Application\Project;

use App\Domain\Project\Entities\ProjectSummary;
use App\Domain\Project\ProjectRepositoryInterface;

/**
 * 自分が参加しているプロジェクトの一覧を取得する。
 */
final class GetProjectListUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
    ) {}

    /**
     * @return list<ProjectSummary>
     */
    public function execute(int $userId): array
    {
        return $this->projects->listSummariesForUser($userId);
    }
}
