/**
 * サーバーの Resource（app/Http/Resources）と対になる型。
 * Laravel から snake_case のまま受け取る。
 */

export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done';

export type TaskPriority = 'low' | 'medium' | 'high';

export type ProjectRole = 'owner' | 'member';

/** ProjectResource（ボード画面のプロジェクト） */
export type Project = {
    id: number;
    name: string;
    description: string | null;
    /** 移動のときに送り返す版。サーバーで違えば競合になる */
    board_version: number;
};

/** ProjectSummaryResource（プロジェクト一覧の1行） */
export type ProjectSummary = {
    id: number;
    name: string;
    description: string | null;
    role: ProjectRole;
    task_count: number;
    member_count: number;
};

/** MemberResource */
export type Member = {
    user_id: number;
    name: string;
    email: string;
    role: ProjectRole;
};

/** TaskResource */
export type Task = {
    id: number;
    project_id: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    assignee_id: number | null;
    /** YYYY-MM-DD */
    due_date: string | null;
    position: number;
    created_by: number | null;
};

/** AssignedTaskResource（マイタスクの1行） */
export type AssignedTask = Task & {
    project_name: string;
};

/** ボード画面でボタンを出し分けるための権限（判定の本体はサーバーの ProjectPermission） */
export type BoardPermissions = {
    manage_project: boolean;
    delete_any_task: boolean;
};
