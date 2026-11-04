import { ListFilter, X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Member, Task, TaskPriority } from '@/types/models';
import {
    TASK_PRIORITIES,
    TASK_PRIORITY_LABELS,
    UNASSIGNED,
} from '../lib/constants';
import { isOverdue } from '../lib/due-date';

/** 担当者の条件: null = 指定なし、UNASSIGNED = 担当者なし、数値 = その人 */
export type TaskFilter = {
    assignee: number | typeof UNASSIGNED | null;
    priority: TaskPriority | null;
    overdueOnly: boolean;
};

export const EMPTY_FILTER: TaskFilter = {
    assignee: null,
    priority: null,
    overdueOnly: false,
};

/** Select の「すべて」を表す値 */
const ALL = 'all';

export function applyTaskFilter(tasks: Task[], filter: TaskFilter): Task[] {
    return tasks.filter((task) => {
        if (filter.assignee === UNASSIGNED && task.assignee_id !== null) {
            return false;
        }

        if (
            typeof filter.assignee === 'number' &&
            task.assignee_id !== filter.assignee
        ) {
            return false;
        }

        if (filter.priority !== null && task.priority !== filter.priority) {
            return false;
        }

        return !filter.overdueOnly || isOverdue(task);
    });
}

type TaskFilterBarProps = {
    filter: TaskFilter;
    members: Member[];
    onChange: (filter: TaskFilter) => void;
};

/**
 * 絞り込み。操作群は普段は畳んでおき、適用中の条件だけをチップで示す（ui.md）。
 */
export function TaskFilterBar({
    filter,
    members,
    onChange,
}: TaskFilterBarProps) {
    const [isOpen, setIsOpen] = useState(false);

    const assigneeLabel =
        filter.assignee === UNASSIGNED
            ? '担当者なし'
            : members.find((m) => m.user_id === filter.assignee)?.name;

    const chips = [
        filter.assignee !== null && {
            key: 'assignee',
            label: `担当: ${assigneeLabel ?? '不明'}`,
            clear: () => onChange({ ...filter, assignee: null }),
        },
        filter.priority !== null && {
            key: 'priority',
            label: `優先度: ${TASK_PRIORITY_LABELS[filter.priority]}`,
            clear: () => onChange({ ...filter, priority: null }),
        },
        filter.overdueOnly && {
            key: 'overdue',
            label: '期限切れのみ',
            clear: () => onChange({ ...filter, overdueOnly: false }),
        },
    ].filter((chip) => chip !== false);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    aria-expanded={isOpen}
                    onClick={() => setIsOpen(!isOpen)}
                >
                    <ListFilter />
                    絞り込み
                </Button>
                {chips.map((chip) => (
                    <Badge
                        key={chip.key}
                        variant="secondary"
                        className="gap-1 pr-1"
                    >
                        {chip.label}
                        <button
                            type="button"
                            className="rounded-full p-0.5 hover:bg-background/60"
                            aria-label={`${chip.label} を解除`}
                            onClick={chip.clear}
                        >
                            <X className="size-3" />
                        </button>
                    </Badge>
                ))}
                {chips.length > 1 && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onChange(EMPTY_FILTER)}
                    >
                        すべて解除
                    </Button>
                )}
            </div>

            {isOpen && (
                <div className="flex flex-wrap items-end gap-4 rounded-xl border bg-card p-3 shadow-xs">
                    <div className="grid gap-1.5">
                        <Label htmlFor="filter-assignee" className="text-xs">
                            担当者
                        </Label>
                        <Select
                            value={
                                filter.assignee === null
                                    ? ALL
                                    : String(filter.assignee)
                            }
                            onValueChange={(value) =>
                                onChange({
                                    ...filter,
                                    assignee:
                                        value === ALL
                                            ? null
                                            : value === UNASSIGNED
                                              ? UNASSIGNED
                                              : Number(value),
                                })
                            }
                        >
                            <SelectTrigger
                                id="filter-assignee"
                                size="sm"
                                className="w-40"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>すべて</SelectItem>
                                <SelectItem value={UNASSIGNED}>
                                    担当者なし
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
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="filter-priority" className="text-xs">
                            優先度
                        </Label>
                        <Select
                            value={filter.priority ?? ALL}
                            onValueChange={(value) =>
                                onChange({
                                    ...filter,
                                    priority:
                                        value === ALL
                                            ? null
                                            : (value as TaskPriority),
                                })
                            }
                        >
                            <SelectTrigger
                                id="filter-priority"
                                size="sm"
                                className="w-28"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>すべて</SelectItem>
                                {TASK_PRIORITIES.map((priority) => (
                                    <SelectItem key={priority} value={priority}>
                                        {TASK_PRIORITY_LABELS[priority]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex h-8 items-center gap-2">
                        <Checkbox
                            id="filter-overdue"
                            checked={filter.overdueOnly}
                            onCheckedChange={(checked) =>
                                onChange({
                                    ...filter,
                                    overdueOnly: checked === true,
                                })
                            }
                        />
                        <Label
                            htmlFor="filter-overdue"
                            className="text-sm font-normal"
                        >
                            期限切れのみ
                        </Label>
                    </div>
                </div>
            )}
        </div>
    );
}
