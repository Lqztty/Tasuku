<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    /**
     * メールアドレス（大文字・小文字を区別しない）からユーザー ID を探す。見つからなければ null。
     */
    public function findIdByEmail(string $email): ?int;

    /**
     * ユーザーを削除する。メンバー行は外部キーの cascade で消え、
     * タスクの担当者と作成者は nullOnDelete で null になる。
     */
    public function delete(int $userId): void;
}
