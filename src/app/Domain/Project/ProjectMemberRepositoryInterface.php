<?php

namespace App\Domain\Project;

use App\Domain\Project\Entities\ProjectMember;
use App\Domain\Project\ValueObjects\ProjectRole;

interface ProjectMemberRepositoryInterface
{
    /**
     * メンバーでなければ null を返す。
     */
    public function findRole(int $projectId, int $userId): ?ProjectRole;

    /**
     * オーナーを先頭に、名前順で返す。
     *
     * @return list<ProjectMember>
     */
    public function listByProject(int $projectId): array;

    public function add(int $projectId, int $userId, ProjectRole $role): void;

    public function remove(int $projectId, int $userId): void;

    /**
     * メンバーのロールを書き換える（オーナーの移譲に使う）。
     */
    public function changeRole(int $projectId, int $userId, ProjectRole $role): void;

    /**
     * いずれかのプロジェクトのオーナーかどうか（退会できるかの判定に使う）。
     */
    public function isOwnerOfAnyProject(int $userId): bool;
}
