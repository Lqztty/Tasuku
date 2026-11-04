import {
    DndContext,
    DragOverlay,
    KeyboardSensor,
    MeasuringStrategy,
    PointerSensor,
    closestCorners,
    pointerWithin,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type {
    CollisionDetection,
    DragEndEvent,
    DragOverEvent,
    DragStartEvent,
} from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';
import { useRef, useState } from 'react';
import type { Member, Task, TaskStatus } from '@/types/models';
import { TASK_STATUSES } from '../lib/constants';
import {
    SCREEN_READER_INSTRUCTIONS,
    createAnnouncements,
} from '../lib/drag-announcements';
import {
    isBelowCenter,
    isSamePlace,
    resolveDrop,
    taskAfterDrop,
} from '../lib/drop-target';
import type { DropTarget } from '../lib/drop-target';
import { groupByStatus } from '../lib/move-locally';
import { KanbanColumn } from './kanban-column';
import type { DropPreview } from './kanban-column';
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

/** 高さが取れなかったときの点線の枠の高さ（カード1枚ぶんの目安） */
const FALLBACK_CARD_HEIGHT = 72;

/**
 * 重なりの判定。マウスの下にカードがあればそのカード、無ければ列（空いている所）。
 * キーボード操作ではマウスの位置が無いので、近い角で判定する。
 */
const collisionDetection: CollisionDetection = (args) => {
    const within = pointerWithin(args);
    const card = within.find((collision) => typeof collision.id === 'number');

    if (card) {
        return [card];
    }

    return within.length > 0 ? within : closestCorners(args);
};

/**
 * 当たり判定には、ドラッグを始めた時点の位置を使う。
 * 点線の枠を差し込むとカードがずれるが、それで置き先が変わって枠が動き、
 * またずれる……というちらつきが起きないようにするため。
 */
const MEASURING = {
    droppable: { strategy: MeasuringStrategy.BeforeDragging },
};

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
    const [dropTarget, setDropTarget] = useState<DropTarget | null>(null);
    const [activeHeight, setActiveHeight] = useState(FALLBACK_CARD_HEIGHT);
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

    // 置き先の列にだけ、点線の枠を出す位置を渡す（今の場所と同じなら出さない）
    const preview: (DropPreview & { status: TaskStatus }) | null =
        activeTask && dropTarget && !isSamePlace(activeTask, dropTarget)
            ? {
                  status: dropTarget.status,
                  beforeTaskId: taskAfterDrop(tasks, activeTask, dropTarget),
                  height: activeHeight,
              }
            : null;

    const finishDrag = () => {
        setActiveId(null);
        setDropTarget(null);
        lastDragEndAtRef.current = Date.now();
    };

    const handleEdit = (task: Task) => {
        if (Date.now() - lastDragEndAtRef.current < 250) {
            return;
        }

        onEdit(task);
    };

    const handleDragStart = (event: DragStartEvent) => {
        setActiveId(Number(event.active.id));
        setActiveHeight(
            event.active.rect.current.initial?.height ?? FALLBACK_CARD_HEIGHT,
        );
    };

    const handleDragOver = (event: DragOverEvent) => {
        const active = tasks.find((task) => task.id === event.active.id);

        setDropTarget(
            active && event.over
                ? resolveDrop(
                      tasks,
                      active,
                      event.over,
                      isBelowCenter(event.active, event.over),
                  )
                : null,
        );
    };

    const handleDragEnd = (event: DragEndEvent) => {
        finishDrag();

        const active = tasks.find((task) => task.id === event.active.id);

        if (!active || !event.over) {
            return;
        }

        const target = resolveDrop(
            tasks,
            active,
            event.over,
            isBelowCenter(event.active, event.over),
        );

        if (!target || isSamePlace(active, target)) {
            return;
        }

        onMove(active.id, target.status, target.index);
    };

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={collisionDetection}
            measuring={MEASURING}
            accessibility={{
                announcements: createAnnouncements(tasks),
                screenReaderInstructions: SCREEN_READER_INSTRUCTIONS,
            }}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDragEnd={handleDragEnd}
            onDragCancel={finishDrag}
        >
            <div className="flex flex-1 gap-3 overflow-x-auto pb-2">
                {TASK_STATUSES.map((status) => (
                    <KanbanColumn
                        key={status}
                        status={status}
                        tasks={visibleColumns[status]}
                        membersById={membersById}
                        isDragDisabled={isMoving}
                        dropPreview={
                            preview?.status === status ? preview : null
                        }
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
