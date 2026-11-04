import { Form, router } from '@inertiajs/react';
import { Crown, Users, X } from 'lucide-react';
import { useState } from 'react';
import ProjectMemberController from '@/actions/App/Http/Controllers/ProjectMemberController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { visitErrorToast } from '@/lib/visit-error-toast';
import type { Member } from '@/types/models';

type MemberManageDialogProps = {
    projectId: number;
    members: Member[];
    /** オーナーだけが追加・削除・オーナーの引き継ぎをできる */
    canManage: boolean;
};

/**
 * メンバーの一覧。オーナーには追加・削除・オーナーの引き継ぎの操作も出す（オーナー自身は外せない）。
 */
export function MemberManageDialog({
    projectId,
    members,
    canManage,
}: MemberManageDialogProps) {
    const [removingUserId, setRemovingUserId] = useState<number | null>(null);
    // 引き継ぎの確認欄を出している相手。null なら確認欄の代わりに追加フォームを出す
    const [transferTarget, setTransferTarget] = useState<Member | null>(null);
    const [isTransferring, setIsTransferring] = useState(false);
    const isBusy = removingUserId !== null || isTransferring;

    const handleRemove = (member: Member) => {
        router.delete(
            ProjectMemberController.destroy.url({
                project: projectId,
                user: member.user_id,
            }),
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setRemovingUserId(member.user_id),
                onFinish: () => setRemovingUserId(null),
                ...visitErrorToast('メンバーの削除'),
            },
        );
    };

    const handleTransfer = (member: Member) => {
        router.patch(
            ProjectMemberController.transferOwnership.url({
                project: projectId,
                user: member.user_id,
            }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setIsTransferring(true),
                onSuccess: () => setTransferTarget(null),
                onFinish: () => setIsTransferring(false),
                ...visitErrorToast('オーナーの引き継ぎ'),
            },
        );
    };

    return (
        <Dialog
            onOpenChange={(open) => {
                // 確認欄を出したまま閉じたら、次に開いたときは追加フォームに戻す
                if (!open) {
                    setTransferTarget(null);
                }
            }}
        >
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Users />
                    メンバー（{members.length}）
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>メンバー</DialogTitle>
                <DialogDescription>
                    {canManage
                        ? 'メンバーを外すと、その人が担当していたタスクは担当者なしになります。'
                        : 'メンバーの追加と削除はオーナーだけができます。'}
                </DialogDescription>

                <ul className="divide-y divide-border rounded-xl border">
                    {members.map((member) => (
                        <li
                            key={member.user_id}
                            className="flex items-center gap-3 px-3 py-2"
                        >
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">
                                    {member.name}
                                </p>
                                <p className="truncate text-xs text-muted-foreground">
                                    {member.email}
                                </p>
                            </div>
                            {member.role === 'owner' ? (
                                <Badge variant="secondary">オーナー</Badge>
                            ) : (
                                canManage && (
                                    <div className="flex gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`${member.name} にオーナーを引き継ぐ`}
                                            title="オーナーにする"
                                            disabled={isBusy}
                                            onClick={() =>
                                                setTransferTarget(member)
                                            }
                                        >
                                            <Crown />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`${member.name} を外す`}
                                            title="メンバーから外す"
                                            disabled={isBusy}
                                            onClick={() => handleRemove(member)}
                                        >
                                            <X />
                                        </Button>
                                    </div>
                                )
                            )}
                        </li>
                    ))}
                </ul>

                {canManage && transferTarget !== null && (
                    <div className="grid gap-3 rounded-xl border p-3">
                        <p className="text-sm font-medium">
                            {transferTarget.name}{' '}
                            さんにオーナーを引き継ぎますか？
                        </p>
                        <p className="text-sm text-muted-foreground">
                            引き継ぐと、あなたはメンバーになり、プロジェクトの編集・削除やメンバーの管理ができなくなります。
                        </p>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={isTransferring}
                                onClick={() => setTransferTarget(null)}
                            >
                                キャンセル
                            </Button>
                            <Button
                                type="button"
                                disabled={isTransferring}
                                onClick={() => handleTransfer(transferTarget)}
                            >
                                引き継ぐ
                            </Button>
                        </div>
                    </div>
                )}

                {canManage && transferTarget === null && (
                    <Form
                        {...ProjectMemberController.store.form(projectId)}
                        options={{ preserveScroll: true, preserveState: true }}
                        resetOnSuccess
                        className="grid gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Label htmlFor="member-email">
                                    メンバーを追加（登録済みのメールアドレス）
                                </Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="member-email"
                                        name="email"
                                        type="email"
                                        autoComplete="off"
                                        placeholder="name@example.com"
                                        required
                                    />
                                    <Button type="submit" disabled={processing}>
                                        追加
                                    </Button>
                                </div>
                                <InputError message={errors.email} />
                            </>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}
