import { Head, router, usePage } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import { Button } from '@/components/ui/button';
import { KanbanBoard } from '@/features/kanban/components/kanban-board';
import {
    EMPTY_FILTER,
    TaskFilterBar,
    applyTaskFilter,
} from '@/features/kanban/components/task-filter-bar';
import type { TaskFilter } from '@/features/kanban/components/task-filter-bar';
import { TaskFormDialog } from '@/features/kanban/components/task-form-dialog';
import type { TaskDialogTarget } from '@/features/kanban/components/task-form-dialog';
import { moveLocally } from '@/features/kanban/lib/move-locally';
import { MemberManageDialog } from '@/features/project/components/member-manage-dialog';
import { ProjectDeleteDialog } from '@/features/project/components/project-delete-dialog';
import { ProjectFormDialog } from '@/features/project/components/project-form-dialog';
import { visitErrorToast } from '@/lib/visit-error-toast';
import {
    index as projectsIndex,
    show as projectsShow,
} from '@/routes/projects';
import type {
    BoardPermissions,
    Member,
    Project,
    ProjectRole,
    Task,
    TaskStatus,
} from '@/types/models';

type Props = {
    project: Project;
    role: ProjectRole;
    members: Member[];
    tasks: Task[];
    permissions: BoardPermissions;
};

/**
 * かんばんボード。
 *
 * 状態の持ち場所（react-conventions.md）:
 * - タスクと board_version: Inertia の props（サーバーの状態が正）
 * - 移動の楽観的更新とロールバック: Inertia 3 の router.optimistic()
 * - 絞り込み・ダイアログ: この画面の useState
 */
export default function ProjectShow({
    project,
    members,
    tasks,
    permissions,
}: Props) {
    const { auth } = usePage().props;
    const [filter, setFilter] = useState<TaskFilter>(EMPTY_FILTER);
    const [dialogTarget, setDialogTarget] = useState<TaskDialogTarget | null>(
        null,
    );
    const [dialogKey, setDialogKey] = useState(0);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isMoving, setIsMoving] = useState(false);

    const membersById = new Map(
        members.map((member) => [member.user_id, member]),
    );
    const visibleTasks = applyTaskFilter(tasks, filter);

    const openDialog = (target: TaskDialogTarget) => {
        setDialogKey((key) => key + 1); // 開くたびに入力を初期化する
        setDialogTarget(target);
        setIsDialogOpen(true);
    };

    const canDelete = (task: Task) =>
        permissions.delete_any_task ||
        (task.created_by !== null && task.created_by === auth.user.id);

    /**
     * 移動。結果ごとの画面の状態:
     * - 成功: サーバーから返った tasks と board_version で置き換わる
     * - 競合: サーバーは何も変えずに最新の状態を返し、トースト（flash）で知らせる
     * - 422 / 403 / 500 / 通信エラー: Inertia が楽観的な状態を元に戻す。ここでトーストを出す
     */
    const handleMove = (
        taskId: number,
        toStatus: TaskStatus,
        toIndex: number,
    ) => {
        router
            .optimistic<Props>((props) => ({
                tasks: moveLocally(props.tasks, taskId, toStatus, toIndex),
            }))
            .patch(
                TaskController.move.url(taskId),
                {
                    status: toStatus,
                    position: toIndex,
                    expected_version: project.board_version,
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onStart: () => setIsMoving(true),
                    onFinish: () => setIsMoving(false),
                    ...visitErrorToast('移動'),
                },
            );
    };

    return (
        <>
            <Head title={project.name} />
            <div className="flex h-full min-w-0 flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start gap-3">
                    <div className="min-w-0 flex-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {project.name}
                        </h1>
                        {project.description && (
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {project.description}
                            </p>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <MemberManageDialog
                            projectId={project.id}
                            members={members}
                            canManage={permissions.manage_project}
                        />
                        {permissions.manage_project && (
                            <>
                                <ProjectFormDialog
                                    project={project}
                                    trigger={
                                        <Button
                                            variant="ghost"
                                            aria-label="プロジェクトを編集"
                                        >
                                            <Pencil />
                                        </Button>
                                    }
                                />
                                <ProjectDeleteDialog project={project} />
                            </>
                        )}
                    </div>
                </div>

                <TaskFilterBar
                    filter={filter}
                    members={members}
                    onChange={setFilter}
                />

                <KanbanBoard
                    tasks={tasks}
                    visibleTasks={visibleTasks}
                    membersById={membersById}
                    isMoving={isMoving}
                    onMove={handleMove}
                    onCreate={(status) =>
                        openDialog({ kind: 'create', status })
                    }
                    onEdit={(task) => openDialog({ kind: 'edit', task })}
                />
            </div>

            <TaskFormDialog
                key={dialogKey}
                target={dialogTarget}
                isOpen={isDialogOpen}
                projectId={project.id}
                members={members}
                canDelete={canDelete}
                onClose={() => setIsDialogOpen(false)}
            />
        </>
    );
}

ProjectShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'プロジェクト', href: projectsIndex() },
        { title: props.project.name, href: projectsShow(props.project.id) },
    ],
});
