<?php

namespace App\Domain\Task;

use App\Domain\Task\Entities\AssignedTask;
use App\Domain\Task\Entities\Task;

/**
 * @phpstan-import-type Columns from TaskOrdering
 */
interface TaskRepositoryInterface
{
    /**
     * 呼ぶたびに結果が変わりうる（ロック待ちの間に他の人が削除することがある）ため impure とする。
     *
     * @phpstan-impure
     */
    public function find(int $id): ?Task;

    /**
     * プロジェクトのタスクを、列の順（未着手 → 完了）・position の順で返す。
     *
     * @return list<Task>
     */
    public function listByProject(int $projectId): array;

    /**
     * 指定したユーザーが担当しているタスクを、期限の近い順（期限なしは最後）で返す。
     *
     * @return list<AssignedTask>
     */
    public function listAssignedTo(int $userId): array;

    /**
     * id が null なら追加し、そうでなければ更新する。保存後のタスク（id 付き）を返す。
     */
    public function save(Task $task): Task;

    public function delete(int $id): void;

    /**
     * 並び（TaskOrdering の Columns）どおりに、各タスクの status と position を書き込む。
     *
     * @param  Columns  $columns
     */
    public function savePositions(int $projectId, array $columns): void;

    /**
     * プロジェクト内で、指定したユーザーが担当しているタスクの担当者を外す。
     */
    public function unassignUserInProject(int $projectId, int $userId): void;
}
