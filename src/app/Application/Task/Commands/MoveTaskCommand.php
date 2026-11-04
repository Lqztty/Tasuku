<?php

namespace App\Application\Task\Commands;

/**
 * expectedVersion は、クライアントが画面を表示したときのボードの版（board_version）。
 */
final readonly class MoveTaskCommand
{
    public function __construct(
        public int $userId,
        public int $taskId,
        public string $status,
        public int $position,
        public int $expectedVersion,
    ) {}
}
