<?php

namespace App\Domain\Task;

use App\Domain\Task\Entities\Task;
use App\Domain\Task\ValueObjects\TaskStatus;

/**
 * かんばんの並び順の計算（純粋な関数だけ）。
 *
 * 並びは「列（ステータスの値）→ その列に上から並ぶタスク ID の配列」で表す。
 * 配列の添字がそのまま position（0 からの連番）になる。
 * フロントエンドの features/kanban/lib/move-locally.ts も同じ規則で計算すること。
 *
 * @phpstan-type Columns array<string, list<int>>
 */
final class TaskOrdering
{
    /**
     * タスクの一覧から、列ごとの並びを作る。タスクの無い列も空配列で必ず含める。
     *
     * @param  list<Task>  $tasks
     * @return Columns
     */
    public static function columnsFrom(array $tasks): array
    {
        $sorted = $tasks;
        usort($sorted, fn (Task $a, Task $b): int => [$a->position, $a->id] <=> [$b->position, $b->id]);

        $columns = array_fill_keys(array_map(fn (TaskStatus $s): string => $s->value, TaskStatus::cases()), []);
        foreach ($sorted as $task) {
            if ($task->id !== null) {
                $columns[$task->status->value][] = $task->id;
            }
        }

        return $columns;
    }

    /**
     * タスクを指定した列の指定した位置へ移す。位置が範囲外なら、列の先頭か末尾に寄せる。
     *
     * @param  Columns  $columns
     * @return Columns
     */
    public static function move(array $columns, int $taskId, TaskStatus $to, int $index): array
    {
        return $columns
            |> (fn (array $c): array => self::remove($c, $taskId))
            |> (fn (array $c): array => self::insert($c, $taskId, $to, $index));
    }

    /**
     * タスクを並びから取り除き、残りを詰める。
     *
     * @param  Columns  $columns
     * @return Columns
     */
    public static function remove(array $columns, int $taskId): array
    {
        return array_map(
            fn (array $ids): array => array_values(array_filter($ids, fn (int $id): bool => $id !== $taskId)),
            $columns,
        );
    }

    /**
     * タスクを指定した列の指定した位置へ差し込む。
     *
     * @param  Columns  $columns
     * @return Columns
     */
    public static function insert(array $columns, int $taskId, TaskStatus $to, int $index): array
    {
        $ids = $columns[$to->value] ?? [];
        $at = max(0, min($index, count($ids)));

        return [
            ...$columns,
            $to->value => [...array_slice($ids, 0, $at), $taskId, ...array_slice($ids, $at)],
        ];
    }
}
