import { useSortable } from '@dnd-kit/sortable';
import type { KeyboardEvent } from 'react';
import { cn } from '@/lib/utils';
import type { Member, Task } from '@/types/models';
import { AssigneeAvatar, DueDateLabel, PriorityDot } from './task-meta';

type TaskCardProps = {
    task: Task;
    assignee: Member | undefined;
    onEdit: (task: Task) => void;
    /** 移動の送信中など、ドラッグさせたくないとき */
    isDragDisabled?: boolean;
};

/**
 * ボードに並ぶタスクのカード。
 *
 * - マウス: クリックで編集、少し動かすとドラッグ（PointerSensor の distance）
 * - キーボード: Enter で編集、Space で持ち上げて矢印キーで移動し、Space で置く
 *
 * ドラッグ中もカード自体は動かさない（transform を使わない）。置き先は点線の枠
 * （DropPlaceholder）だけで示し、元の場所のカードは薄く残して「どこから」を示す。
 */
export function TaskCard({
    task,
    assignee,
    onEdit,
    isDragDisabled = false,
}: TaskCardProps) {
    const { attributes, listeners, setNodeRef, isDragging } = useSortable({
        id: task.id,
        data: { status: task.status },
        disabled: isDragDisabled,
    });

    const handleKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            onEdit(task);

            return;
        }

        listeners?.onKeyDown?.(event);
    };

    return (
        <div
            ref={setNodeRef}
            {...attributes}
            {...listeners}
            onKeyDown={handleKeyDown}
            onClick={() => onEdit(task)}
            aria-label={`${task.title}（Enter で編集、Space で移動）`}
            className={cn(
                'cursor-grab touch-none rounded-lg border bg-card p-3 text-left shadow-xs transition-colors duration-150 outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50',
                isDragging && 'border-dashed opacity-40',
                isDragDisabled && 'cursor-progress',
            )}
        >
            <TaskCardBody task={task} assignee={assignee} />
        </div>
    );
}

/**
 * カードの中身。ドラッグ中に指の下に表示する複製（DragOverlay）でも使う。
 */
export function TaskCardBody({
    task,
    assignee,
}: {
    task: Task;
    assignee: Member | undefined;
}) {
    return (
        <>
            <p className="text-sm leading-snug font-medium break-words">
                {task.title}
            </p>
            <div className="mt-2 flex items-center gap-3">
                <PriorityDot priority={task.priority} />
                <DueDateLabel task={task} />
                <span className="ml-auto">
                    <AssigneeAvatar member={assignee} />
                </span>
            </div>
        </>
    );
}
