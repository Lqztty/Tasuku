import { useDroppable } from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Member, Task, TaskStatus } from '@/types/models';
import { TASK_STATUS_LABELS } from '../lib/constants';
import { TaskCard } from './task-card';

type KanbanColumnProps = {
    status: TaskStatus;
    /** 表示するタスク（絞り込み後） */
    tasks: Task[];
    membersById: ReadonlyMap<number, Member>;
    isDragDisabled: boolean;
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
    onCreate,
    onEdit,
}: KanbanColumnProps) {
    const { setNodeRef, isOver } = useDroppable({
        id: columnDropId(status),
        data: { status },
    });

    return (
        <section
            aria-label={TASK_STATUS_LABELS[status]}
            className="flex w-72 shrink-0 flex-col rounded-xl bg-muted/50 md:w-auto md:min-w-0 md:flex-1"
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
                    className={cn(
                        'flex min-h-24 flex-1 flex-col gap-2 rounded-b-xl px-2 pb-2 transition-colors duration-150',
                        isOver && 'bg-accent/60',
                    )}
                >
                    {tasks.map((task) => (
                        <TaskCard
                            key={task.id}
                            task={task}
                            assignee={
                                task.assignee_id === null
                                    ? undefined
                                    : membersById.get(task.assignee_id)
                            }
                            onEdit={onEdit}
                            isDragDisabled={isDragDisabled}
                        />
                    ))}
                    {tasks.length === 0 && (
                        <p className="px-1 py-6 text-center text-xs text-muted-foreground">
                            タスクはありません
                        </p>
                    )}
                </div>
            </SortableContext>
        </section>
    );
}
