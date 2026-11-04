<?php

namespace App\Application\User;

use App\Domain\Project\ProjectMemberRepositoryInterface;
use App\Domain\Project\ValueObjects\ProjectRole;
use App\Domain\Shared\Exceptions\InvalidInputException;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\User\UserRepositoryInterface;

/**
 * 退会（アカウント削除）。オーナーになっているプロジェクトが残っていれば止める。
 *
 * オーナーのまま退会すると、メンバー行の cascade 削除で「オーナーのいないプロジェクト」が生まれる。
 * 確認と削除の間にオーナーを引き継がれても同じことが起きるため、本人のメンバー行をロックしてから確認し、
 * 同じトランザクションで削除する。引き継ぎ側（TransferProjectOwnershipUseCase）も相手の行をロックして読むので、
 * どちらが先に届いても、後から来た方は先の結果を見て止まる。
 *
 * このトランザクションはプロジェクト行をロックしない。子の行だけを取り、プロジェクト行を待つことがないので、
 * 「親 → 子」の順でロックする他の UseCase とのあいだにデッドロックの輪はできない。
 */
final class DeleteUserUseCase
{
    public function __construct(
        private readonly TransactionManagerInterface $transaction,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @throws InvalidInputException
     */
    public function execute(int $userId): void
    {
        $this->transaction->run(function () use ($userId): void {
            $roles = $this->members->findRolesByUserForUpdate($userId);

            if (in_array(ProjectRole::Owner, $roles, true)) {
                // 退会ダイアログがエラーを表示する欄が password だけなので、その欄に出す
                throw new InvalidInputException(
                    'password',
                    'オーナーになっているプロジェクトがあるため退会できません。先にオーナーを他のメンバーに引き継ぐか、プロジェクトを削除してください。',
                );
            }

            $this->users->delete($userId);
        });
    }
}
