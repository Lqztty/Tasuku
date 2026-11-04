<?php

namespace App\Application\Task;

use App\Domain\Project\Entities\Project;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Task\Entities\Task;

/**
 * プロジェクトをロックしたあとに読み直したタスクと、操作するユーザーのロール。
 */
final readonly class LockedTask
{
    public function __construct(
        public Project $project,
        public Task $task,
        public ?ProjectRole $role,
    ) {}
}
