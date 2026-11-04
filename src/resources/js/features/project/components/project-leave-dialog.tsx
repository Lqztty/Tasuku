import { router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { useState } from 'react';
import ProjectMemberController from '@/actions/App/Http/Controllers/ProjectMemberController';
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
 * 自分からプロジェクトを抜ける（オーナー以外）。抜けるとボードを見られなくなるので確認を挟む。
 */
export function ProjectLeaveDialog({
    project,
}: {
    project: Pick<Project, 'id' | 'name'>;
}) {
    const [isLeaving, setIsLeaving] = useState(false);

    const handleLeave = () => {
        router.delete(ProjectMemberController.leave.url(project.id), {
            onStart: () => setIsLeaving(true),
            onFinish: () => setIsLeaving(false),
            ...visitErrorToast('プロジェクトから抜けることが'),
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" aria-label="プロジェクトから抜ける">
                    <LogOut />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>「{project.name}」から抜けますか？</DialogTitle>
                <DialogDescription>
                    担当しているタスクは担当者なしに戻ります。もう一度参加するには、オーナーに追加してもらう必要があります。
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
                        disabled={isLeaving}
                        onClick={handleLeave}
                    >
                        抜ける
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
