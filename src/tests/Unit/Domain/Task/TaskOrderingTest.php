<?php

namespace Tests\Unit\Domain\Task;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\TaskOrdering;
use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskOrderingTest extends TestCase
{
    /**
     * @return array<string, list<int>>
     */
    private function board(): array
    {
        return [
            'todo' => [1, 2, 3],
            'in_progress' => [4, 5],
            'review' => [],
            'done' => [6],
        ];
    }

    public function test_同じ列の中で下へ移動できる(): void
    {
        $result = TaskOrdering::move($this->board(), 1, TaskStatus::Todo, 2);

        $this->assertSame([2, 3, 1], $result['todo']);
    }

    public function test_同じ列の中で上へ移動できる(): void
    {
        $result = TaskOrdering::move($this->board(), 3, TaskStatus::Todo, 0);

        $this->assertSame([3, 1, 2], $result['todo']);
    }

    public function test_別の列の途中へ移動すると移動元は詰まり移動先に差し込まれる(): void
    {
        $result = TaskOrdering::move($this->board(), 2, TaskStatus::InProgress, 1);

        $this->assertSame([1, 3], $result['todo']);
        $this->assertSame([4, 2, 5], $result['in_progress']);
        $this->assertSame([6], $result['done']);
    }

    public function test_空の列へ移動できる(): void
    {
        $result = TaskOrdering::move($this->board(), 5, TaskStatus::Review, 0);

        $this->assertSame([4], $result['in_progress']);
        $this->assertSame([5], $result['review']);
    }

    /**
     * @return array<string, array{int, list<int>}>
     */
    public static function outOfRangeIndexes(): array
    {
        return [
            '末尾より後ろは末尾' => [99, [6, 1]],
            '負の値は先頭' => [-3, [1, 6]],
        ];
    }

    /**
     * @param  list<int>  $expected
     */
    #[DataProvider('outOfRangeIndexes')]
    public function test_範囲外の位置は端に寄せる(int $index, array $expected): void
    {
        $result = TaskOrdering::move($this->board(), 1, TaskStatus::Done, $index);

        $this->assertSame($expected, $result['done']);
    }

    public function test_移動しても元の配列は変わらない(): void
    {
        $board = $this->board();

        TaskOrdering::move($board, 1, TaskStatus::Done, 0);

        $this->assertSame($this->board(), $board);
    }

    public function test_削除すると残りが詰まる(): void
    {
        $result = TaskOrdering::remove($this->board(), 2);

        $this->assertSame([1, 3], $result['todo']);
    }

    public function test_タスク一覧から列ごとの並びを作り空の列も含める(): void
    {
        $tasks = [
            $this->task(10, TaskStatus::Done, 0),
            $this->task(11, TaskStatus::Todo, 1),
            $this->task(12, TaskStatus::Todo, 0),
        ];

        $this->assertSame(
            ['todo' => [12, 11], 'in_progress' => [], 'review' => [], 'done' => [10]],
            TaskOrdering::columnsFrom($tasks),
        );
    }

    private function task(int $id, TaskStatus $status, int $position): Task
    {
        return new Task($id, 1, "task {$id}", null, $status, TaskPriority::Medium, null, null, $position, null);
    }
}
