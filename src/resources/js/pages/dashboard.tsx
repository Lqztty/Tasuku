import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    DueDateLabel,
    PriorityDot,
} from '@/features/kanban/components/task-meta';
import { TASK_STATUS_LABELS } from '@/features/kanban/lib/constants';
import { dashboard } from '@/routes';
import {
    index as projectsIndex,
    show as projectsShow,
} from '@/routes/projects';
import type { AssignedTask } from '@/types/models';

type Props = {
    tasks: AssignedTask[];
};

/**
 * マイタスク: 自分が担当しているタスクを、プロジェクトをまたいで期限の近い順に並べる。
 */
export default function Dashboard({ tasks }: Props) {
    return (
        <>
            <Head title="マイタスク" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="マイタスク"
                    description="自分が担当しているタスク（期限の近い順）"
                />

                {tasks.length === 0 ? (
                    <div className="flex flex-col items-start gap-3 rounded-xl border bg-card p-6 shadow-xs">
                        <p className="text-sm text-muted-foreground">
                            担当しているタスクはありません。
                        </p>
                        <Button variant="outline" asChild>
                            <Link href={projectsIndex()}>
                                プロジェクトを見る
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="divide-y divide-border overflow-hidden rounded-xl border bg-card shadow-xs">
                        {tasks.map((task) => (
                            <li key={task.id}>
                                <Link
                                    href={projectsShow(task.project_id)}
                                    className="flex items-center gap-4 px-4 py-3 transition-colors duration-150 hover:bg-accent"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">
                                            {task.title}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {task.project_name} ・{' '}
                                            {TASK_STATUS_LABELS[task.status]}
                                        </p>
                                    </div>
                                    <PriorityDot priority={task.priority} />
                                    <span className="w-16 text-right">
                                        <DueDateLabel task={task} />
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'マイタスク',
            href: dashboard(),
        },
    ],
};
