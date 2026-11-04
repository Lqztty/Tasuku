<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    /**
     * メールアドレス（大文字・小文字を区別しない）からユーザー ID を探す。見つからなければ null。
     */
    public function findIdByEmail(string $email): ?int;
}
