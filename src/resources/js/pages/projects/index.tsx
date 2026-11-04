import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ProjectFormDialog } from '@/features/project/components/project-form-dialog';
import {
    index as projectsIndex,
    show as projectsShow,
} from '@/routes/projects';
import type { ProjectSummary } from '@/types/models';

type Props = {
    projects: ProjectSummary[];
};

/**
 * プロジェクト一覧（自分が参加しているものだけ）。
 */
export default function ProjectIndex({ projects }: Props) {
    return (
        <>
            <Head title="プロジェクト" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="プロジェクト"
                        description="参加しているプロジェクト"
                    />
                    <ProjectFormDialog
                        trigger={
                            <Button>
                                <Plus />
                                新しいプロジェクト
                            </Button>
                        }
                    />
                </div>

                {projects.length === 0 ? (
                    <div className="rounded-xl border bg-card p-6 shadow-xs">
                        <p className="text-sm text-muted-foreground">
                            まだプロジェクトがありません。「新しいプロジェクト」から作成するか、オーナーにメンバーとして追加してもらってください。
                        </p>
                    </div>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {projects.map((project) => (
                            <li key={project.id}>
                                <Link
                                    href={projectsShow(project.id)}
                                    className="flex h-full flex-col gap-3 rounded-xl border bg-card p-4 shadow-xs transition-colors duration-150 hover:bg-accent active:scale-[0.98] motion-reduce:active:scale-100"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <h3 className="text-[15px] font-semibold">
                                            {project.name}
                                        </h3>
                                        {project.role === 'owner' && (
                                            <Badge variant="secondary">
                                                オーナー
                                            </Badge>
                                        )}
                                    </div>
                                    {project.description && (
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {project.description}
                                        </p>
                                    )}
                                    <p className="mt-auto text-xs text-muted-foreground">
                                        タスク {project.task_count} 件 ・
                                        メンバー {project.member_count} 人
                                    </p>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

ProjectIndex.layout = {
    breadcrumbs: [
        {
            title: 'プロジェクト',
            href: projectsIndex(),
        },
    ],
};
