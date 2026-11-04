<?php

namespace Tests\Unit\Domain;

use App\Domain\Project\Entities\Project;
use App\Domain\Shared\Exceptions\ConflictException;
use App\Domain\Task\Entities\Task;
use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * readonly な Entity が clone with で「新しいインスタンス」を返し、元を変えないことを確かめる。
 */
class EntityTest extends TestCase
{
    public function test_プロジェクトの版を進めると新しいインスタンスになり元は変わらない(): void
    {
        $project = new Project(1, 'A', null, 3);

        $bumped = $project->bumpBoardVersion();

        $this->assertSame(4, $bumped->boardVersion);
        $this->assertSame(3, $project->boardVersion);
        $this->assertNotSame($project, $bumped);
    }

    public function test_プロジェクト名の変更は版を変えない(): void
    {
        $renamed = (new Project(1, 'A', null, 3))->rename('B', '説明');

        $this->assertSame('B', $renamed->name);
        $this->assertSame('説明', $renamed->description);
        $this->assertSame(3, $renamed->boardVersion);
    }

    public function test_版が違えば競合になる(): void
    {
        $this->expectException(ConflictException::class);

        (new Project(1, 'A', null, 3))->ensureBoardVersion(2);
    }

    public function test_版が同じなら競合にならない(): void
    {
        (new Project(1, 'A', null, 3))->ensureBoardVersion(3);

        $this->addToAssertionCount(1);
    }

    public function test_タスクの内容を変えても並びと作成者は変わらない(): void
    {
        $task = new Task(5, 1, '旧', null, TaskStatus::Review, TaskPriority::Low, null, null, 2, 9);
        $due = new DateTimeImmutable('2026-12-01');

        $changed = $task->withDetails('新', '説明', TaskPriority::High, 7, $due);

        $this->assertSame('新', $changed->title);
        $this->assertSame(TaskPriority::High, $changed->priority);
        $this->assertSame(7, $changed->assigneeId);
        $this->assertSame($due, $changed->dueDate);
        $this->assertSame(TaskStatus::Review, $changed->status);
        $this->assertSame(2, $changed->position);
        $this->assertSame(9, $changed->createdBy);
        $this->assertSame('旧', $task->title);
    }

    public function test_下書きのタスクは_id_が_null(): void
    {
        $draft = Task::draft(1, 'T', null, TaskStatus::Todo, TaskPriority::Medium, null, null, 0, 9);

        $this->assertNull($draft->id);
        $this->assertSame(42, $draft->withId(42)->id);
    }
}
