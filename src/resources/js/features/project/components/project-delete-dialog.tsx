import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { visitErrorToast } from '@/lib/visit-error-toast';
import type { Project } from '@/types/models';

/**
 * プロジェクトの削除（オーナーだけ）。タスクとメンバーもすべて消えるので確認を挟む。
 */
export function ProjectDeleteDialog({
    project,
}: {
    project: Pick<Project, 'id' | 'name'>;
}) {
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = () => {
        router.delete(ProjectController.destroy.url(project.id), {
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            ...visitErrorToast('プロジェクトの削除'),
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" aria-label="プロジェクトを削除">
                    <Trash2 />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>「{project.name}」を削除しますか？</DialogTitle>
                <DialogDescription>
                    タスクとメンバーの情報もすべて削除され、元に戻せません。
                </DialogDescription>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            キャンセル
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={isDeleting}
                        onClick={handleDelete}
                    >
                        削除する
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
