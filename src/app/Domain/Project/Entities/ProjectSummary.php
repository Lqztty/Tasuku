<?php

namespace App\Domain\Project\Entities;

use App\Domain\Project\ValueObjects\ProjectRole;

/**
 * プロジェクト一覧の1行（読み取り専用の集計値を含む）。
 */
final readonly class ProjectSummary
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ProjectRole $role,
        public int $taskCount,
        public int $memberCount,
    ) {}
}
