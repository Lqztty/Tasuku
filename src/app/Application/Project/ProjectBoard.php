<?php

namespace App\Application\Project;

use App\Domain\Project\Entities\Project;
use App\Domain\Project\Entities\ProjectMember;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Task\Entities\Task;

/**
 * ボード画面に必要なものをまとめた読み取り結果。
 *
 * 画面でボタンを出し分けるための権限もここで計算しておく（判定の本体は ProjectPermission）。
 */
final readonly class ProjectBoard
{
    /**
     * @param  list<ProjectMember>  $members
     * @param  list<Task>  $tasks
     */
    public function __construct(
        public Project $project,
        public ProjectRole $role,
        public array $members,
        public array $tasks,
        public bool $canManageProject,
        public bool $canDeleteAnyTask,
        public bool $canLeaveProject,
    ) {}
}
