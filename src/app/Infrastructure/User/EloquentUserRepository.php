<?php

namespace App\Infrastructure\User;

use App\Domain\User\UserRepositoryInterface;
use App\Models\User;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findIdByEmail(string $email): ?int
    {
        // MySQL の照合順序は大文字・小文字を区別しないが、SQLite（テスト）は区別するため、両方を小文字にそろえる
        $id = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->value('id');

        return $id === null ? null : (int) $id;
    }
}
