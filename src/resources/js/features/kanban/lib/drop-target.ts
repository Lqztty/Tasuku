import type { Active, Over } from '@dnd-kit/core';
import type { Task, TaskStatus } from '@/types/models';
import { groupByStatus } from './move-locally';

/** 中心の差がこれ以下なら「同じ高さ」とみなす（px） */
const SAME_HEIGHT_TOLERANCE = 1;

/**
 * 重ねているカードの「後ろ」に入れるか（false なら「前」）。
 *
 * - マウス: ドラッグ中のカードの中心が、重ねているカードの中心より下なら後ろ
 * - 中心が同じ高さのとき（キーボード操作では、重ねたカードにぴったり重なる）:
 *   元の位置より下にあるカードに重ねたら後ろ、上なら前。
 *   こうしないと、矢印キーの↓で隣のカードに重ねても「元の場所」になり、列の最後にも置けない
 */
export function isBelowCenter(
    active: Pick<Active, 'rect'>,
    over: Pick<Over, 'rect'> | null,
): boolean {
    const dragged = active.rect.current.translated;

    if (!dragged || !over) {
        return false;
    }

    const diff =
        dragged.top +
        dragged.height / 2 -
        (over.rect.top + over.rect.height / 2);

    if (Math.abs(diff) > SAME_HEIGHT_TOLERANCE) {
        return diff > 0;
    }

    const initial = active.rect.current.initial;

    return initial !== null && over.rect.top > initial.top;
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
 * 今の列の中で、上から何番目か（0 から）。
 * position の値ではなく実際の並び順から求める（position が連番でなくても正しく判定するため）。
 */
function currentIndex(tasks: readonly Task[], active: Task): number {
    return groupByStatus(tasks)[active.status].findIndex(
        (task) => task.id === active.id,
    );
}

/**
 * ドラッグ中のカードが重なっている所から、置き先を求める。
 *
 * - カードに重ねたら、前か後ろ（isBelowCenter）
 * - 列の空いている所に重ねたら、その列の末尾（カードの無い列のときだけ起きる。
 *   カードのある列では、当たり判定がいちばん近いカードを選ぶ）
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
        return { status: active.status, index: currentIndex(tasks, active) };
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
export function isSamePlace(
    tasks: readonly Task[],
    active: Task,
    target: DropTarget,
): boolean {
    return (
        target.status === active.status &&
        target.index === currentIndex(tasks, active)
    );
}

/**
 * 置いた後に、すぐ下に来る「見えている」タスク（列の末尾なら null）。
 * 点線の枠はこのタスクの直前に出し、読み上げもこのタスクを基準に言う。
 *
 * 絞り込みで隠れているタスクは飛ばす。飛ばさないと、すぐ下のタスクが隠れているときに
 * 枠が列の末尾に出てしまい、実際に入る位置と食い違う。
 */
export function taskAfterDrop(
    tasks: readonly Task[],
    active: Task,
    target: DropTarget,
    isVisible: (task: Task) => boolean = () => true,
): Task | null {
    const others = groupByStatus(tasks)[target.status].filter(
        (task) => task.id !== active.id,
    );

    return others.slice(target.index).find(isVisible) ?? null;
}
