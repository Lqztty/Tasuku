import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { Member, Task } from '@/types/models';
import {
    TASK_PRIORITY_DOT_CLASSES,
    TASK_PRIORITY_LABELS,
} from '../lib/constants';
import { formatDueDate, isOverdue } from '../lib/due-date';

/**
 * 優先度を色の点で示す（読み上げ用にラベルも付ける）。
 */
export function PriorityDot({ priority }: Pick<Task, 'priority'>) {
    return (
        <span
            className="inline-flex items-center gap-1 text-xs text-muted-foreground"
            title={`優先度: ${TASK_PRIORITY_LABELS[priority]}`}
        >
            <span
                aria-hidden
                className={cn(
                    'size-2 rounded-full',
                    TASK_PRIORITY_DOT_CLASSES[priority],
                )}
            />
            <span className="sr-only">優先度</span>
            {TASK_PRIORITY_LABELS[priority]}
        </span>
    );
}

/**
 * 期限。期限切れのときだけ赤い点を付ける（文字は塗らない）。
 */
export function DueDateLabel({
    task,
}: {
    task: Pick<Task, 'due_date' | 'status'>;
}) {
    if (task.due_date === null) {
        return null;
    }

    const overdue = isOverdue(task);

    return (
        <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
            {overdue && (
                <span aria-hidden className="size-2 rounded-full bg-red-500" />
            )}
            <time dateTime={task.due_date}>{formatDueDate(task.due_date)}</time>
            {overdue && <span className="sr-only">（期限切れ）</span>}
        </span>
    );
}

/**
 * 担当者のイニシャル。担当者がいなければ何も出さない。
 */
export function AssigneeAvatar({ member }: { member: Member | undefined }) {
    const getInitials = useInitials();

    if (!member) {
        return null;
    }

    return (
        <Avatar className="size-6" title={`担当: ${member.name}`}>
            <AvatarFallback className="text-[11px]">
                {getInitials(member.name)}
            </AvatarFallback>
        </Avatar>
    );
}
