import {
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    closestCorners,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent, DragStartEvent } from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';
import { useRef, useState } from 'react';
import type { Member, Task, TaskStatus } from '@/types/models';
import { TASK_STATUSES } from '../lib/constants';
import { groupByStatus } from '../lib/move-locally';
import { KanbanColumn } from './kanban-column';
import { TaskCardBody } from './task-card';

type KanbanBoardProps = {
    /** ボードの全タスク（位置の計算に使う） */
    tasks: Task[];
    /** 表示するタスク（絞り込み後） */
    visibleTasks: Task[];
    membersById: ReadonlyMap<number, Member>;
    /** 移動の送信中は次のドラッグを受け付けない（自分の操作同士の競合を防ぐ） */
    isMoving: boolean;
    onMove: (taskId: number, toStatus: TaskStatus, toIndex: number) => void;
    onCreate: (status: TaskStatus) => void;
    onEdit: (task: Task) => void;
};

/**
 * ドロップ先から「どの列の何番目へ移すか」を求める。
 *
 * 位置は絞り込み後ではなく、全タスクの並びでの位置にする（サーバーの position と一致させるため）。
 * - タスクの上に落としたら、そのタスクの位置（同じ列なら入れ替わり、別の列ならその前に入る）
 * - 列の空いている所に落としたら、その列の末尾
 */
function resolveDrop(
    tasks: Task[],
    active: Task,
    over: NonNullable<DragEndEvent['over']>,
): { status: TaskStatus; index: number } | null {
    const columns = groupByStatus(tasks);
    const overTask = tasks.find((task) => task.id === over.id);

    if (overTask) {
        return {
            status: overTask.status,
            index: columns[overTask.status].findIndex(
                (t) => t.id === overTask.id,
            ),
        };
    }

    const status = over.data.current?.status as TaskStatus | undefined;

    if (!status) {
        return null;
    }

    const length = columns[status].length;

    return { status, index: status === active.status ? length - 1 : length };
}

export function KanbanBoard({
    tasks,
    visibleTasks,
    membersById,
    isMoving,
    onMove,
    onCreate,
    onEdit,
}: KanbanBoardProps) {
    const [activeId, setActiveId] = useState<number | null>(null);
    // ドラッグを離した瞬間にブラウザが click も発生させるため、直後のクリックは編集として扱わない
    const lastDragEndAtRef = useRef(0);

    const sensors = useSensors(
        // 5px 動かすまではクリック扱いにする（カードを押して編集を開けるように）
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        // Enter はカードの編集に使うので、持ち上げと置くのは Space だけにする
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
            keyboardCodes: {
                start: ['Space'],
                cancel: ['Escape'],
                end: ['Space'],
            },
        }),
    );

    const visibleColumns = groupByStatus(visibleTasks);
    const activeTask = tasks.find((task) => task.id === activeId);

    const handleEdit = (task: Task) => {
        if (Date.now() - lastDragEndAtRef.current < 250) {
            return;
        }

        onEdit(task);
    };

    const handleDragStart = (event: DragStartEvent) => {
        setActiveId(Number(event.active.id));
    };

    const handleDragEnd = (event: DragEndEvent) => {
        setActiveId(null);
        lastDragEndAtRef.current = Date.now();

        const active = tasks.find((task) => task.id === event.active.id);

        if (!active || !event.over) {
            return;
        }

        const target = resolveDrop(tasks, active, event.over);

        if (
            !target ||
            (target.status === active.status &&
                target.index === active.position)
        ) {
            return;
        }

        onMove(active.id, target.status, target.index);
    };

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCorners}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
            onDragCancel={() => {
                setActiveId(null);
                lastDragEndAtRef.current = Date.now();
            }}
        >
            <div className="flex flex-1 gap-3 overflow-x-auto pb-2">
                {TASK_STATUSES.map((status) => (
                    <KanbanColumn
                        key={status}
                        status={status}
                        tasks={visibleColumns[status]}
                        membersById={membersById}
                        isDragDisabled={isMoving}
                        onCreate={onCreate}
                        onEdit={handleEdit}
                    />
                ))}
            </div>

            <DragOverlay>
                {activeTask && (
                    <div className="rounded-lg border bg-card p-3 shadow-md">
                        <TaskCardBody
                            task={activeTask}
                            assignee={
                                activeTask.assignee_id === null
                                    ? undefined
                                    : membersById.get(activeTask.assignee_id)
                            }
                        />
                    </div>
                )}
            </DragOverlay>
        </DndContext>
    );
}
