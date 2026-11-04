<?php

namespace App\Application\Task\Commands;

/**
 * status / priority は enum の値（文字列）、dueDate は Y-m-d の文字列で受け取る。
 */
final readonly class CreateTaskCommand
{
    public function __construct(
        public int $userId,
        public int $projectId,
        public string $title,
        public ?string $description,
        public string $status,
        public string $priority,
        public ?int $assigneeId,
        public ?string $dueDate,
    ) {}
}
