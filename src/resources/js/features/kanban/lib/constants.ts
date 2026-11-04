import type { TaskPriority, TaskStatus } from '@/types/models';

/** かんばんの列。並び順は左から右（サーバーの TaskStatus::cases() と同じ順） */
export const TASK_STATUSES = [
    'todo',
    'in_progress',
    'review',
    'done',
] as const satisfies readonly TaskStatus[];

export const TASK_STATUS_LABELS: Record<TaskStatus, string> = {
    todo: '未着手',
    in_progress: '進行中',
    review: 'レビュー',
    done: '完了',
};

export const TASK_PRIORITIES = [
    'high',
    'medium',
    'low',
] as const satisfies readonly TaskPriority[];

export const TASK_PRIORITY_LABELS: Record<TaskPriority, string> = {
    high: '高',
    medium: '中',
    low: '低',
};

/**
 * 優先度は色の「点」で示す（カード全体は塗らない。ui.md の節度の原則）。
 * 中・低はアクセントを増やさないよう無彩色にする。
 */
export const TASK_PRIORITY_DOT_CLASSES: Record<TaskPriority, string> = {
    high: 'bg-orange-500',
    medium: 'bg-muted-foreground/60',
    low: 'bg-muted-foreground/25',
};

/** Select で「担当者なし」を表す値（Radix の Select は空文字を値にできないため） */
export const UNASSIGNED = 'none';
