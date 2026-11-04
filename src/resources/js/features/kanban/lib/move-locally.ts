import type { Task, TaskStatus } from '@/types/models';
import { TASK_STATUSES } from './constants';

export type Columns = Record<TaskStatus, Task[]>;

/**
 * タスクを列ごとに分け、position 順に並べる。
 */
export function groupByStatus(tasks: readonly Task[]): Columns {
    const columns = Object.fromEntries(
        TASK_STATUSES.map((status) => [status, [] as Task[]]),
    ) as Columns;

    for (const task of tasks) {
        columns[task.status].push(task);
    }

    for (const status of TASK_STATUSES) {
        columns[status].sort((a, b) => a.position - b.position || a.id - b.id);
    }

    return columns;
}

/**
 * 楽観的更新用に、タスクを指定した列の指定した位置へ移した後の一覧を返す（元の配列は変えない）。
 *
 * サーバーの App\Domain\Task\TaskOrdering::move と同じ規則で計算する:
 * 1. タスクを今の列から取り除いて詰める
 * 2. 移動先の列の index の位置に差し込む（範囲外なら先頭か末尾に寄せる）
 * 3. 影響した列の position を 0 からの連番に振り直す
 */
export function moveLocally(
    tasks: readonly Task[],
    taskId: number,
    toStatus: TaskStatus,
    toIndex: number,
): Task[] {
    const moving = tasks.find((task) => task.id === taskId);

    if (!moving) {
        return [...tasks];
    }

    const columns = groupByStatus(tasks.filter((task) => task.id !== taskId));
    const target = columns[toStatus];
    const at = Math.max(0, Math.min(toIndex, target.length));
    const moved: Task = { ...moving, status: toStatus };

    const reordered: Columns = {
        ...columns,
        [toStatus]: [...target.slice(0, at), moved, ...target.slice(at)],
    };

    return TASK_STATUSES.flatMap((status) =>
        reordered[status].map((task, position) =>
            task.position === position && task.status === status
                ? task
                : { ...task, status, position },
        ),
    );
}
