import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Member, Task, TaskPriority, TaskStatus } from '@/types/models';
import {
    TASK_PRIORITIES,
    TASK_PRIORITY_LABELS,
    TASK_STATUSES,
    TASK_STATUS_LABELS,
    UNASSIGNED,
} from '../lib/constants';

export type TaskDialogTarget =
    | { kind: 'create'; status: TaskStatus }
    | { kind: 'edit'; task: Task };

type TaskFormDialogProps = {
    target: TaskDialogTarget | null;
    projectId: number;
    members: Member[];
    canDelete: (task: Task) => boolean;
    onClose: () => void;
};

type TaskFormData = {
    title: string;
    description: string;
    status: TaskStatus;
    priority: TaskPriority;
    /** Select の値なので文字列。担当者なしは UNASSIGNED */
    assignee_id: string;
    /** <input type="date"> の値なので YYYY-MM-DD か空文字 */
    due_date: string;
};

/**
 * タスクの作成・編集ダイアログ。開くたびに親が key を変えて、入力を初期化する。
 */
export function TaskFormDialog({
    target,
    projectId,
    members,
    canDelete,
    onClose,
}: TaskFormDialogProps) {
    const task = target?.kind === 'edit' ? target.task : null;
    const [isConfirmingDelete, setIsConfirmingDelete] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const form = useForm<TaskFormData>({
        title: task?.title ?? '',
        description: task?.description ?? '',
        status:
            task?.status ??
            (target?.kind === 'create' ? target.status : 'todo'),
        priority: task?.priority ?? 'medium',
        assignee_id: task?.assignee_id ? String(task.assignee_id) : UNASSIGNED,
        due_date: task?.due_date ?? '',
    });

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        // 画面用の値を、サーバーが受け取る形（null / 数値）に直してから送る
        form.transform((data) => ({
            ...data,
            description:
                data.description.trim() === '' ? null : data.description,
            assignee_id:
                data.assignee_id === UNASSIGNED
                    ? null
                    : Number(data.assignee_id),
            due_date: data.due_date === '' ? null : data.due_date,
        }));

        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: onClose,
        };

        if (task) {
            form.put(TaskController.update.url(task.id), options);
        } else {
            form.post(TaskController.store.url(projectId), options);
        }
    };

    const handleDelete = () => {
        if (!task) {
            return;
        }

        router.delete(TaskController.destroy.url(task.id), {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: onClose,
        });
    };

    return (
        <Dialog
            open={target !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogTitle>
                    {task ? 'タスクを編集' : '新しいタスク'}
                </DialogTitle>
                <DialogDescription className="sr-only">
                    タイトル、説明、ステータス、優先度、担当者、期限を入力します。
                </DialogDescription>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="task-title">タイトル</Label>
                        <Input
                            id="task-title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            maxLength={255}
                            required
                            autoFocus
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="task-description">説明（任意）</Label>
                        <Textarea
                            id="task-description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            maxLength={5000}
                            rows={3}
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="task-status">ステータス</Label>
                            <Select
                                value={form.data.status}
                                onValueChange={(value) =>
                                    form.setData('status', value as TaskStatus)
                                }
                            >
                                <SelectTrigger
                                    id="task-status"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {TASK_STATUSES.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {TASK_STATUS_LABELS[status]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.status} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="task-priority">優先度</Label>
                            <Select
                                value={form.data.priority}
                                onValueChange={(value) =>
                                    form.setData(
                                        'priority',
                                        value as TaskPriority,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="task-priority"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {TASK_PRIORITIES.map((priority) => (
                                        <SelectItem
                                            key={priority}
                                            value={priority}
                                        >
                                            {TASK_PRIORITY_LABELS[priority]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.priority} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="task-assignee">担当者</Label>
                            <Select
                                value={form.data.assignee_id}
                                onValueChange={(value) =>
                                    form.setData('assignee_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="task-assignee"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={UNASSIGNED}>
                                        なし
                                    </SelectItem>
                                    {members.map((member) => (
                                        <SelectItem
                                            key={member.user_id}
                                            value={String(member.user_id)}
                                        >
                                            {member.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.assignee_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="task-due-date">期限（任意）</Label>
                            <Input
                                id="task-due-date"
                                type="date"
                                value={form.data.due_date}
                                onChange={(e) =>
                                    form.setData('due_date', e.target.value)
                                }
                            />
                            <InputError message={form.errors.due_date} />
                        </div>
                    </div>

                    <DialogFooter className="gap-2 sm:justify-between">
                        {task && canDelete(task) ? (
                            isConfirmingDelete ? (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    disabled={isDeleting}
                                    onClick={handleDelete}
                                >
                                    本当に削除する
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="text-destructive"
                                    onClick={() => setIsConfirmingDelete(true)}
                                >
                                    削除
                                </Button>
                            )
                        ) : (
                            <span />
                        )}
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={onClose}
                            >
                                キャンセル
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {task ? '保存' : '追加'}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
