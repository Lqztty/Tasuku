import { Form, router } from '@inertiajs/react';
import { Users, X } from 'lucide-react';
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
    /** オーナーだけが追加・削除できる */
    canManage: boolean;
};

/**
 * メンバーの一覧。オーナーには追加と削除の操作も出す（オーナー自身は外せない）。
 */
export function MemberManageDialog({
    projectId,
    members,
    canManage,
}: MemberManageDialogProps) {
    const [removingUserId, setRemovingUserId] = useState<number | null>(null);

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

    return (
        <Dialog>
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
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`${member.name} を外す`}
                                        disabled={removingUserId !== null}
                                        onClick={() => handleRemove(member)}
                                    >
                                        <X />
                                    </Button>
                                )
                            )}
                        </li>
                    ))}
                </ul>

                {canManage && (
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
