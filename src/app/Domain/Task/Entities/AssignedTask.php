<?php

namespace App\Domain\Task\Entities;

/**
 * マイタスク画面の1行（タスクと、それが属するプロジェクトの名前）。
 */
final readonly class AssignedTask
{
    public function __construct(
        public Task $task,
        public string $projectName,
    ) {}
}
