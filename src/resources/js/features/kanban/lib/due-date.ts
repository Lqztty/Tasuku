import type { Task } from '@/types/models';

/**
 * 今日の日付（端末のタイムゾーン）を YYYY-MM-DD で返す。
 */
export function todayString(now: Date = new Date()): string {
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

/**
 * 期限切れか。完了したタスクと期限のないタスクは期限切れにしない。
 * YYYY-MM-DD の文字列はそのまま大小比較できる。
 */
export function isOverdue(
    task: Pick<Task, 'due_date' | 'status'>,
    today: string = todayString(),
): boolean {
    return (
        task.status !== 'done' &&
        task.due_date !== null &&
        task.due_date < today
    );
}

/**
 * 期限を「11/24」のような短い形にする。年が今年と違うときだけ年を付ける。
 */
export function formatDueDate(
    dueDate: string,
    today: string = todayString(),
): string {
    const [year, month, day] = dueDate.split('-');
    const short = `${Number(month)}/${Number(day)}`;

    return year === today.slice(0, 4) ? short : `${year}/${short}`;
}
