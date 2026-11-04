<?php

namespace App\Domain\Project;

use App\Domain\Project\Entities\Project;
use App\Domain\Project\Entities\ProjectSummary;
use App\Domain\Shared\Exceptions\NotFoundException;

interface ProjectRepositoryInterface
{
    public function find(int $id): ?Project;

    /**
     * プロジェクトの行を排他ロック（SELECT ... FOR UPDATE）して取得する。
     *
     * 同じプロジェクトへの書き込みを1本ずつに並べるために使う。
     * トランザクションの中で呼ぶこと（呼ぶ側の UseCase が責任を持つ）。
     *
     * @throws NotFoundException
     */
    public function lockForUpdate(int $id): Project;

    public function create(string $name, ?string $description): Project;

    public function save(Project $project): void;

    public function delete(int $id): void;

    /**
     * 指定したユーザーが参加しているプロジェクトの一覧（更新日の新しい順）。
     *
     * @return list<ProjectSummary>
     */
    public function listSummariesForUser(int $userId): array;
}
