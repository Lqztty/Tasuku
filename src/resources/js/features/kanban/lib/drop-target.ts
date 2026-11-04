import type { Active, Over } from '@dnd-kit/core';
import type { Task, TaskStatus } from '@/types/models';
import { groupByStatus } from './move-locally';

/**
 * ドラッグ中のカードの中心が、重ねているカードの中心より下にあるか。
 * 下なら「後ろに入る」、上なら「前に入る」と判断する。
 */
export function isBelowCenter(
    active: Pick<Active, 'rect'>,
    over: Pick<Over, 'rect'> | null,
): boolean {
    const dragged = active.rect.current.translated;

    if (!dragged || !over) {
        return false;
    }

    return (
        dragged.top + dragged.height / 2 > over.rect.top + over.rect.height / 2
    );
}

/**
 * どの列の何番目（0 から）へ移すか。
 *
 * index は「動かすカードを除いた、その列の並び」に差し込む位置。
 * サーバーの TaskOrdering::move（取り除いてから差し込む）と同じ意味なので、そのまま送れる。
 */
export type DropTarget = {
    status: TaskStatus;
    index: number;
};

/**
 * ドラッグ中のカードが重なっている所から、置き先を求める。
 *
 * - カードに重ねたら、そのカードの上半分なら前、下半分なら後ろ（isBelowCenter）
 * - 列の空いている所に重ねたら、その列の末尾
 *
 * 位置は絞り込み後ではなく、全タスクの並びでの位置にする（サーバーの position と一致させるため）。
 */
export function resolveDrop(
    tasks: readonly Task[],
    active: Task,
    over: Pick<Over, 'id' | 'data'>,
    isBelowCenter: boolean,
): DropTarget | null {
    const columns = groupByStatus(tasks);
    const othersIn = (status: TaskStatus) =>
        columns[status].filter((task) => task.id !== active.id);

    if (over.id === active.id) {
        return { status: active.status, index: active.position };
    }

    const overTask = tasks.find((task) => task.id === over.id);

    if (overTask) {
        const at = othersIn(overTask.status).findIndex(
            (task) => task.id === overTask.id,
        );

        return {
            status: overTask.status,
            index: isBelowCenter ? at + 1 : at,
        };
    }

    const status = over.data.current?.status as TaskStatus | undefined;

    return status ? { status, index: othersIn(status).length } : null;
}

/** 今の場所と同じ（動かしても何も変わらない）か */
export function isSamePlace(active: Task, target: DropTarget): boolean {
    return target.status === active.status && target.index === active.position;
}

/**
 * 置いた後に、すぐ下に来るタスクの ID（列の末尾なら null）。
 * 点線の枠をこのタスクの直前に出す。
 */
export function taskAfterDrop(
    tasks: readonly Task[],
    active: Task,
    target: DropTarget,
): number | null {
    const others = groupByStatus(tasks)[target.status].filter(
        (task) => task.id !== active.id,
    );

    return others[target.index]?.id ?? null;
}
