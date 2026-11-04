import type {
    Active,
    Announcements,
    Over,
    ScreenReaderInstructions,
} from '@dnd-kit/core';
import type { Task } from '@/types/models';
import { TASK_STATUS_LABELS } from './constants';
import { isBelowCenter, isSamePlace, resolveDrop } from './drop-target';
import type { DropTarget } from './drop-target';

/**
 * キーボードとスクリーンリーダーで操作する人向けの説明（dnd-kit の既定は英語のため）。
 */
export const SCREEN_READER_INSTRUCTIONS: ScreenReaderInstructions = {
    draggable:
        'Space で持ち上げ、矢印キーで動かし、もう一度 Space で置きます。Escape で取り消します。Enter で編集します。',
};

function describe(target: DropTarget): string {
    return `「${TASK_STATUS_LABELS[target.status]}」の ${target.index + 1} 番目`;
}

/**
 * ドラッグ中の状況を読み上げる文言。置き先は画面の点線の枠と同じ規則（resolveDrop）で求める。
 */
export function createAnnouncements(tasks: readonly Task[]): Announcements {
    const find = (id: unknown) => tasks.find((task) => task.id === id);

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

            return isSamePlace(result.task, result.target)
                ? '元の場所です。'
                : `${describe(result.target)}に置きます。`;
        },
        onDragEnd({ active, over }) {
            const result = resolve(active, over);

            if (!result || isSamePlace(result.task, result.target)) {
                return '元の場所に戻しました。';
            }

            return `「${result.task.title}」を${describe(result.target)}に置きました。`;
        },
        onDragCancel({ active }) {
            const task = find(active.id);

            return task
                ? `移動を取り消しました。「${task.title}」は元の場所のままです。`
                : '移動を取り消しました。';
        },
    };
}
