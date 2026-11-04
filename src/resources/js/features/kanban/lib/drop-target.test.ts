import { describe, expect, it } from 'vite-plus/test';
import type { Task, TaskStatus } from '@/types/models';
import {
    isBelowCenter,
    isSamePlace,
    resolveDrop,
    taskAfterDrop,
} from './drop-target';
import { groupByStatus, moveLocally } from './move-locally';

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

/** todo: [1, 2, 3] / in_progress: [4] / review: [] / done: [] */
const board: Task[] = [
    task(1, 'todo', 0),
    task(2, 'todo', 1),
    task(3, 'todo', 2),
    task(4, 'in_progress', 0),
];
const byId = (id: number) => board.find((t) => t.id === id)!;
const overTask = (id: number) => ({ id, data: { current: undefined } });
const overColumn = (status: TaskStatus) => ({
    id: `column:${status}`,
    data: { current: { status } },
});

/** 実際に動かした後の、その列の並び */
function columnAfterMove(activeId: number, status: TaskStatus, index: number) {
    return groupByStatus(moveLocally(board, activeId, status, index))[
        status
    ].map((t) => t.id);
}

describe('resolveDrop', () => {
    it('別の列のカードの上半分に重ねると、その前に入る', () => {
        const target = resolveDrop(board, byId(1), overTask(4), false)!;

        expect(columnAfterMove(1, target.status, target.index)).toEqual([1, 4]);
    });

    it('別の列のカードの下半分に重ねると、その後ろに入る', () => {
        const target = resolveDrop(board, byId(1), overTask(4), true)!;

        expect(columnAfterMove(1, target.status, target.index)).toEqual([4, 1]);
    });

    it('同じ列で下のカードの下半分に重ねると、その後ろに入る', () => {
        const target = resolveDrop(board, byId(1), overTask(3), true)!;

        expect(columnAfterMove(1, target.status, target.index)).toEqual([
            2, 3, 1,
        ]);
    });

    it('同じ列で下のカードの上半分に重ねると、その前に入る', () => {
        const target = resolveDrop(board, byId(1), overTask(3), false)!;

        expect(columnAfterMove(1, target.status, target.index)).toEqual([
            2, 1, 3,
        ]);
    });

    it('同じ列で上のカードの上半分に重ねると、その前に入る', () => {
        const target = resolveDrop(board, byId(3), overTask(1), false)!;

        expect(columnAfterMove(3, target.status, target.index)).toEqual([
            3, 1, 2,
        ]);
    });

    it('列の空いている所に重ねると、その列の末尾に入る', () => {
        expect(
            resolveDrop(board, byId(1), overColumn('in_progress'), false),
        ).toEqual({ status: 'in_progress', index: 1 });
        expect(
            resolveDrop(board, byId(1), overColumn('review'), false),
        ).toEqual({
            status: 'review',
            index: 0,
        });
        expect(resolveDrop(board, byId(1), overColumn('todo'), false)).toEqual({
            status: 'todo',
            index: 2,
        });
    });

    it('自分自身に重ねたら今の場所', () => {
        const target = resolveDrop(board, byId(2), overTask(2), true)!;

        expect(isSamePlace(board, byId(2), target)).toBe(true);
    });

    it('すぐ隣に重ねても動かない場所なら「今の場所」と判定する', () => {
        // 2 を、1 の下半分 / 3 の上半分に重ねても [1, 2, 3] のまま
        expect(
            isSamePlace(
                board,
                byId(2),
                resolveDrop(board, byId(2), overTask(1), true)!,
            ),
        ).toBe(true);
        expect(
            isSamePlace(
                board,
                byId(2),
                resolveDrop(board, byId(2), overTask(3), false)!,
            ),
        ).toBe(true);
    });
});

describe('taskAfterDrop（点線の枠を出す位置）', () => {
    /**
     * 枠は「置いた後にすぐ下に来るタスク」の直前に出す。
     * 実際に moveLocally で動かした結果と一致することを確かめる。
     */
    const cases: Array<
        [string, number, Parameters<typeof resolveDrop>[2], boolean]
    > = [
        ['同じ列で下へ', 1, overTask(3), true],
        ['同じ列で上へ', 3, overTask(1), false],
        ['別の列のカードの前へ', 2, overTask(4), false],
        ['別の列のカードの後ろへ', 2, overTask(4), true],
        ['別の列の末尾へ', 2, overColumn('in_progress'), false],
        ['空の列へ', 2, overColumn('done'), false],
    ];

    it.each(cases)('%s', (_, activeId, over, isBelow) => {
        const active = byId(activeId);
        const target = resolveDrop(board, active, over, isBelow)!;
        const after = columnAfterMove(activeId, target.status, target.index);

        expect(taskAfterDrop(board, active, target)?.id ?? null).toBe(
            after[after.indexOf(activeId) + 1] ?? null,
        );
    });
});

describe('isBelowCenter', () => {
    const rect = (top: number) => ({
        top,
        height: 60,
        left: 0,
        width: 200,
        right: 200,
        bottom: top + 60,
    });
    const active = (initialTop: number, draggedTop: number) => ({
        rect: {
            current: {
                initial: rect(initialTop),
                translated: rect(draggedTop),
            },
        },
    });
    const over = (top: number) => ({ rect: rect(top) });

    it('マウス: 中心が下なら後ろ、上なら前', () => {
        expect(isBelowCenter(active(0, 130), over(100))).toBe(true);
        expect(isBelowCenter(active(0, 70), over(100))).toBe(false);
    });

    it('キーボード（中心がぴったり重なる）: 元の位置より下のカードなら後ろ、上なら前', () => {
        expect(isBelowCenter(active(0, 100), over(100))).toBe(true);
        expect(isBelowCenter(active(200, 100), over(100))).toBe(false);
    });

    it('キーボードで ↓ を押し続けると、列の最後まで動かせる', () => {
        // [1, 2, 3] の 1 を、2 → 3 と重ねていく（中心がそろう = 後ろに入る）
        const first = resolveDrop(board, byId(1), overTask(2), true)!;
        const last = resolveDrop(board, byId(1), overTask(3), true)!;

        expect(isSamePlace(board, byId(1), first)).toBe(false);
        expect(columnAfterMove(1, first.status, first.index)).toEqual([
            2, 1, 3,
        ]);
        expect(columnAfterMove(1, last.status, last.index)).toEqual([2, 3, 1]);
    });
});

describe('絞り込み中の枠の位置', () => {
    it('すぐ下のタスクが隠れていたら、次に見えているタスクの前に枠を出す', () => {
        // todo [1, 2, 3] で 2 を隠す。4 を 1 の下半分に重ねると、1 と 2 の間に入る
        const target = resolveDrop(board, byId(4), overTask(1), true)!;
        const isVisible = (t: Task) => t.id !== 2;

        expect(taskAfterDrop(board, byId(4), target, isVisible)?.id).toBe(3);
    });

    it('後ろに見えているタスクが無ければ、列の最後に枠を出す', () => {
        const target = resolveDrop(board, byId(4), overTask(2), true)!;

        expect(
            taskAfterDrop(board, byId(4), target, (t) => t.id !== 3),
        ).toBeNull();
    });
});

describe('position が連番でないデータ', () => {
    it('並び順から今の位置を求めるので、動かした操作が「元の場所」と誤判定されない', () => {
        // A(id 1) と B(id 2) が同じ position 0。並びは id 順で [1, 2]
        const data = [task(1, 'todo', 0), task(2, 'todo', 0)];
        const b = data[1];
        const target = resolveDrop(data, b, overTask(1), false)!;

        expect(target).toEqual({ status: 'todo', index: 0 });
        expect(isSamePlace(data, b, target)).toBe(false);
        expect(
            isSamePlace(data, b, resolveDrop(data, b, overTask(2), false)!),
        ).toBe(true);
    });
});
