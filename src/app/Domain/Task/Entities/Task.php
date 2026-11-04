<?php

namespace App\Domain\Task\Entities;

use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;
use DateTimeImmutable;

/**
 * タスク。
 *
 * 不変（readonly）。値を変えるときは PHP 8.5 の clone with で新しいインスタンスを返す。
 * id が null のものは、まだ保存していない新しいタスク。
 */
final readonly class Task
{
    public function __construct(
        public ?int $id,
        public int $projectId,
        public string $title,
        public ?string $description,
        public TaskStatus $status,
        public TaskPriority $priority,
        public ?int $assigneeId,
        public ?DateTimeImmutable $dueDate,
        public int $position,
        public ?int $createdBy,
    ) {}

    /**
     * まだ保存していない新しいタスクを作る。
     */
    public static function draft(
        int $projectId,
        string $title,
        ?string $description,
        TaskStatus $status,
        TaskPriority $priority,
        ?int $assigneeId,
        ?DateTimeImmutable $dueDate,
        int $position,
        int $createdBy,
    ): self {
        return new self(
            id: null,
            projectId: $projectId,
            title: $title,
            description: $description,
            status: $status,
            priority: $priority,
            assigneeId: $assigneeId,
            dueDate: $dueDate,
            position: $position,
            createdBy: $createdBy,
        );
    }

    /**
     * 並び（ステータスと位置）以外の内容を変える。
     */
    public function withDetails(
        string $title,
        ?string $description,
        TaskPriority $priority,
        ?int $assigneeId,
        ?DateTimeImmutable $dueDate,
    ): self {
        return clone ($this, [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'assigneeId' => $assigneeId,
            'dueDate' => $dueDate,
        ]);
    }

    public function withId(int $id): self
    {
        return clone ($this, ['id' => $id]);
    }
}
