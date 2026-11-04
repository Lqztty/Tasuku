<?php

namespace App\Infrastructure\Shared;

use App\Domain\Shared\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        return DB::transaction(fn () => $callback());
    }
}
