<?php

namespace App\Application\User;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Shared\Exceptions\InvalidInputException;

/**
 * 退会（アカウント削除）してよいかを確かめる。
 *
 * オーナーのまま退会すると、メンバー行の cascade 削除で「オーナーのいないプロジェクト」が
 * 生まれてしまうため、オーナーになっているプロジェクトが残っていれば止める。
 */
final class EnsureUserCanBeDeletedUseCase
{
    public function __construct(
        private readonly ProjectMemberRepositoryInterface $members,
    ) {}

    /**
     * @throws InvalidInputException
     */
    public function execute(int $userId): void
    {
        if ($this->members->isOwnerOfAnyProject($userId)) {
            // 退会ダイアログがエラーを表示する欄が password だけなので、その欄に出す
            throw new InvalidInputException(
                'password',
                'オーナーになっているプロジェクトがあるため退会できません。先にプロジェクトを削除してください。',
            );
        }
    }
}
