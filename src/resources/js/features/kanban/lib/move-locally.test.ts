import { describe, expect, it } from 'vite-plus/test';
import type { Task, TaskStatus } from '@/types/models';
import { groupByStatus, moveLocally } from './move-locally';

/**
 * サーバーの tests/Unit/Domain/Task/TaskOrderingTest.php と同じケースで、
 * 楽観的更新の結果がサーバーの並べ替えと一致することを確かめる。
 */

function task(id: number, status: TaskStatus, position: number): Task {
    return {
        id,
        project_id: 1,
        title: `task ${id}`,
        description: null,
        status,
        priority: 'medium',
        assignee_id: null,
        due_date: null,
        position,
        created_by: null,
    };
}

/** todo: [1, 2, 3] / in_progress: [4, 5] / review: [] / done: [6] */
const board: Task[] = [
    task(1, 'todo', 0),
    task(2, 'todo', 1),
    task(3, 'todo', 2),
    task(4, 'in_progress', 0),
    task(5, 'in_progress', 1),
    task(6, 'done', 0),
];

function ids(tasks: Task[]): Record<TaskStatus, number[]> {
    const columns = groupByStatus(tasks);

    return {
        todo: columns.todo.map((t) => t.id),
        in_progress: columns.in_progress.map((t) => t.id),
        review: columns.review.map((t) => t.id),
        done: columns.done.map((t) => t.id),
    };
}

describe('moveLocally', () => {
    it('同じ列の中で下へ移動できる', () => {
        expect(ids(moveLocally(board, 1, 'todo', 2)).todo).toEqual([2, 3, 1]);
    });

    it('同じ列の中で上へ移動できる', () => {
        expect(ids(moveLocally(board, 3, 'todo', 0)).todo).toEqual([3, 1, 2]);
    });

    it('別の列の途中へ移動すると移動元は詰まり移動先に差し込まれる', () => {
        const result = ids(moveLocally(board, 2, 'in_progress', 1));

        expect(result.todo).toEqual([1, 3]);
        expect(result.in_progress).toEqual([4, 2, 5]);
        expect(result.done).toEqual([6]);
    });

    it('空の列へ移動できる', () => {
        const result = ids(moveLocally(board, 5, 'review', 0));

        expect(result.in_progress).toEqual([4]);
        expect(result.review).toEqual([5]);
    });

    it('範囲外の位置は端に寄せる', () => {
        expect(ids(moveLocally(board, 1, 'done', 99)).done).toEqual([6, 1]);
        expect(ids(moveLocally(board, 1, 'done', -3)).done).toEqual([1, 6]);
    });

    it('position を列ごとに 0 からの連番に振り直す', () => {
        const result = moveLocally(board, 2, 'in_progress', 1);
        const positions = (status: TaskStatus) =>
            result.filter((t) => t.status === status).map((t) => t.position);

        expect(positions('todo').sort((a, b) => a - b)).toEqual([0, 1]);
        expect(positions('in_progress').sort((a, b) => a - b)).toEqual([
            0, 1, 2,
        ]);
    });

    it('元の配列とタスクは変えない', () => {
        const before = structuredClone(board);

        moveLocally(board, 1, 'done', 0);

        expect(board).toEqual(before);
    });

    it('存在しないタスクなら何も変えない', () => {
        expect(moveLocally(board, 999, 'done', 0)).toEqual(board);
    });
});
