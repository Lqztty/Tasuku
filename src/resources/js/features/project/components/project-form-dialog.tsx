import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Project } from '@/types/models';

type ProjectFormDialogProps = {
    /** 渡せば編集、渡さなければ作成 */
    project?: Pick<Project, 'id' | 'name' | 'description'>;
    trigger: ReactNode;
};

/**
 * プロジェクトの作成・編集ダイアログ。
 */
export function ProjectFormDialog({
    project,
    trigger,
}: ProjectFormDialogProps) {
    const [isOpen, setIsOpen] = useState(false);
    const isEdit = project !== undefined;
    const action = isEdit
        ? ProjectController.update.form(project.id)
        : ProjectController.store.form();

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {isEdit ? 'プロジェクトを編集' : '新しいプロジェクト'}
                </DialogTitle>
                <DialogDescription>
                    {isEdit
                        ? '名前と説明を変更します。'
                        : '作成したあなたがオーナーになります。'}
                </DialogDescription>

                <Form
                    {...action}
                    options={{ preserveScroll: true, preserveState: true }}
                    onSuccess={() => setIsOpen(false)}
                    resetOnSuccess={!isEdit}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="project-name">
                                    プロジェクト名
                                </Label>
                                <Input
                                    id="project-name"
                                    name="name"
                                    defaultValue={project?.name ?? ''}
                                    maxLength={100}
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="project-description">
                                    説明（任意）
                                </Label>
                                <Textarea
                                    id="project-description"
                                    name="description"
                                    defaultValue={project?.description ?? ''}
                                    maxLength={2000}
                                    rows={3}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        キャンセル
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {isEdit ? '保存' : '作成'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
