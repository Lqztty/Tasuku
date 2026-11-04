import { useDroppable } from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { Plus } from 'lucide-react';
import { Fragment } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Member, Task, TaskStatus } from '@/types/models';
import { TASK_STATUS_LABELS } from '../lib/constants';
import { DropPlaceholder } from './drop-placeholder';
import { TaskCard } from './task-card';

/**
 * ドラッグ中、この列が置き先のときに出す枠の情報。
 * beforeTaskId のカードの直前に枠を出す（null なら列の末尾）。
 */
export type DropPreview = {
    beforeTaskId: number | null;
    height: number;
};

type KanbanColumnProps = {
    status: TaskStatus;
    /** 表示するタスク（絞り込み後） */
    tasks: Task[];
    membersById: ReadonlyMap<number, Member>;
    isDragDisabled: boolean;
    /** この列が置き先でなければ null */
    dropPreview: DropPreview | null;
    onCreate: (status: TaskStatus) => void;
    onEdit: (task: Task) => void;
};

/** 列そのもの（空いている所）へのドロップを、タスクへのドロップと区別するための ID */
export function columnDropId(status: TaskStatus): string {
    return `column:${status}`;
}

export function KanbanColumn({
    status,
    tasks,
    membersById,
    isDragDisabled,
    dropPreview,
    onCreate,
    onEdit,
}: KanbanColumnProps) {
    const { setNodeRef } = useDroppable({
        id: columnDropId(status),
        data: { status },
    });

    // 置き先のカードが絞り込みで隠れているときは、列の末尾に枠を出す
    const placeholderAtEnd =
        dropPreview !== null &&
        (dropPreview.beforeTaskId === null ||
            !tasks.some((task) => task.id === dropPreview.beforeTaskId));

    return (
        <section
            aria-label={TASK_STATUS_LABELS[status]}
            className={cn(
                'flex w-72 shrink-0 flex-col rounded-xl bg-muted/50 ring-2 ring-transparent transition-shadow duration-150 md:w-auto md:min-w-0 md:flex-1',
                dropPreview && 'ring-primary/40',
            )}
        >
            <header className="flex items-center gap-2 px-3 pt-3 pb-2">
                <h2 className="text-sm font-semibold">
                    {TASK_STATUS_LABELS[status]}
                </h2>
                <span className="text-xs text-muted-foreground">
                    {tasks.length}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="ml-auto size-7"
                    aria-label={`「${TASK_STATUS_LABELS[status]}」にタスクを追加`}
                    onClick={() => onCreate(status)}
                >
                    <Plus />
                </Button>
            </header>

            <SortableContext
                items={tasks.map((task) => task.id)}
                strategy={verticalListSortingStrategy}
            >
                <div
                    ref={setNodeRef}
                    className="flex min-h-24 flex-1 flex-col gap-2 rounded-b-xl px-2 pb-2"
                >
                    {tasks.map((task) => (
                        <Fragment key={task.id}>
                            {dropPreview?.beforeTaskId === task.id && (
                                <DropPlaceholder height={dropPreview.height} />
                            )}
                            <TaskCard
                                task={task}
                                assignee={
                                    task.assignee_id === null
                                        ? undefined
                                        : membersById.get(task.assignee_id)
                                }
                                onEdit={onEdit}
                                isDragDisabled={isDragDisabled}
                            />
                        </Fragment>
                    ))}
                    {placeholderAtEnd && (
                        <DropPlaceholder height={dropPreview.height} />
                    )}
                    {tasks.length === 0 && !dropPreview && (
                        <p className="px-1 py-6 text-center text-xs text-muted-foreground">
                            タスクはありません
                        </p>
                    )}
                </div>
            </SortableContext>
        </section>
    );
}
