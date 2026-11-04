<?php

namespace App\Domain\Shared;

/**
 * トランザクションの境界を張る。
 *
 * UseCase が「1 UseCase = 1 トランザクション」を守るために使う。
 * Laravel の DB ファサードに直接依存しないよう、Interface として Domain に置く。
 */
interface TransactionManagerInterface
{
    /**
     * コールバックを1つのトランザクションの中で実行し、戻り値をそのまま返す。
     * 例外が投げられたらロールバックし、例外はそのまま呼び出し元へ伝える。
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
