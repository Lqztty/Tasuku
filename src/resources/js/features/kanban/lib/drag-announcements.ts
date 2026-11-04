import type {
    Active,
    Announcements,
    Over,
    ScreenReaderInstructions,
} from '@dnd-kit/core';
import type { Task } from '@/types/models';
import { TASK_STATUS_LABELS } from './constants';
import {
    isBelowCenter,
    isSamePlace,
    resolveDrop,
    taskAfterDrop,
} from './drop-target';
import type { DropTarget } from './drop-target';

/**
 * キーボードとスクリーンリーダーで操作する人向けの説明（dnd-kit の既定は英語のため）。
 */
export const SCREEN_READER_INSTRUCTIONS: ScreenReaderInstructions = {
    draggable:
        'Space で持ち上げ、矢印キーで動かし、もう一度 Space で置きます。Escape で取り消します。Enter で編集します。',
};

/**
 * ドラッグ中の状況を読み上げる文言。置き先は画面の点線の枠と同じ規則で求める。
 *
 * 位置は「N 番目」ではなく、見えているカードを基準に「『○○』の前」「最後」と言う。
 * 絞り込み中は隠れたタスクも数えてしまい、画面の見た目と番号が食い違うため。
 */
export function createAnnouncements(
    tasks: readonly Task[],
    isVisible: (task: Task) => boolean,
): Announcements {
    const find = (id: unknown) => tasks.find((task) => task.id === id);

    const describe = (active: Task, target: DropTarget): string => {
        const column = `「${TASK_STATUS_LABELS[target.status]}」`;
        const after = taskAfterDrop(tasks, active, target, isVisible);

        return after ? `${column}の「${after.title}」の前` : `${column}の最後`;
    };

    const resolve = (active: Active, over: Over | null) => {
        const task = find(active.id);
        const target =
            task && over
                ? resolveDrop(tasks, task, over, isBelowCenter(active, over))
                : null;

        return task && target ? { task, target } : null;
    };

    return {
        onDragStart({ active }) {
            const task = find(active.id);

            return task
                ? `「${task.title}」を持ち上げました。矢印キーで動かし、Space で置きます。`
                : undefined;
        },
        onDragOver({ active, over }) {
            const result = resolve(active, over);

            if (!result) {
                return '置き先がありません。';
            }

            return isSamePlace(tasks, result.task, result.target)
                ? '元の場所です。'
                : `${describe(result.task, result.target)}に置きます。`;
        },
        onDragEnd({ active, over }) {
            const result = resolve(active, over);

            if (!result || isSamePlace(tasks, result.task, result.target)) {
                return '元の場所に戻しました。';
            }

            return `「${result.task.title}」を${describe(result.task, result.target)}に置きました。`;
        },
        onDragCancel({ active }) {
            const task = find(active.id);

            return task
                ? `移動を取り消しました。「${task.title}」は元の場所のままです。`
                : '移動を取り消しました。';
        },
    };
}
