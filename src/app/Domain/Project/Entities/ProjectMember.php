<?php

namespace App\Domain\Project\Entities;

use App\Domain\Project\ValueObjects\ProjectRole;

/**
 * プロジェクトのメンバー（表示に使う名前とメールアドレスを含む）。
 */
final readonly class ProjectMember
{
    public function __construct(
        public int $projectId,
        public int $userId,
        public string $name,
        public string $email,
        public ProjectRole $role,
    ) {}

    public function isOwner(): bool
    {
        return $this->role === ProjectRole::Owner;
    }
}
