---
paths:
  - "src/resources/js/**/*.tsx"
  - "src/resources/js/**/*.ts"
---

# TypeScript (React) 命名規則

> **参照**:
> - コンポーネント配置ルール → `atomic-design.md`
> - React基本規約 → `react-conventions.md`

## 基本ルール

| 対象 | 規則 | 例 |
|---|---|---|
| **変数・関数** | **lowerCamelCase** | `projectId`, `moveLocally`, `handleSubmit` |
| 型・インターフェース | PascalCase | `Project`, `TaskStatus`, `TaskCardProps` |
| 定数 | UPPER_SNAKE_CASE | `TASK_STATUSES`, `MAX_TITLE_LENGTH` |
| コンポーネント | PascalCase | `TaskCard`, `KanbanBoard` |
| カスタムフック | use + PascalCase | `useBoardFilter` |
| **ファイル名** | **kebab-case**（スターターキットに合わせる） | `task-card.tsx`, `use-board-filter.ts`, `move-locally.ts` |
| ディレクトリ | kebab-case | `features/kanban/`, `pages/projects/` |

## Laravel ↔ React 変数名

Laravelから`snake_case`で渡されるデータは、**React側でもそのまま`snake_case`使用可**。

```tsx
type Props = {
    tasks: { assignee_id: number | null; due_date: string | null }[];
};

export default function ProjectShow({ tasks }: Props) {
    // React内部の新規変数はlowerCamelCase
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const firstAssigneeId = tasks[0]?.assignee_id; // props参照はsnake_case
}
```

## コンポーネント命名

### ファイル名は kebab-case、コンポーネント名は PascalCase

```tsx
// ✅ features/kanban/components/task-card.tsx
export function TaskCard({ task }: TaskCardProps) { ... }

// ✅ pages/projects/index.tsx
export default function ProjectIndex({ projects }: Props) { ... }

// ❌ 避ける: ファイル名を PascalCase にする（スターターキットと揃わない）
// features/kanban/components/TaskCard.tsx
```

### Props型

```tsx
type TaskCardProps = {
    task: Task;
    onEdit: (task: Task) => void;
};
```

スターターキットに合わせ、Props は `type` で書く（`interface` でも誤りではない）。

## イベントハンドラー

- コンポーネント内: `handle` + 動作（`handleDragEnd`, `handleSubmit`）
- Props 経由: `on` + 動作（`onEdit`, `onDelete`, `onMove`）

## 状態

```tsx
// ✅ [値, set + 値名]
const [tasks, setTasks] = useState<Task[]>([]);

// ✅ boolean: is/has/should で開始
const [isMoving, setIsMoving] = useState(false);
const [hasError, setHasError] = useState(false);

// ✅ useRef は Ref で終わる
const inputRef = useRef<HTMLInputElement>(null);
```

## 定数

```typescript
// features/kanban/lib/constants.ts
export const TASK_STATUSES = ['todo', 'in_progress', 'review', 'done'] as const;

export const TASK_STATUS_LABELS: Record<TaskStatus, string> = {
    todo: '未着手',
    in_progress: '進行中',
    review: 'レビュー',
    done: '完了',
};
```

## チェックリスト

- [ ] 変数・関数はlowerCamelCase
- [ ] コンポーネント・型はPascalCase
- [ ] 定数はUPPER_SNAKE_CASE
- [ ] カスタムフックはuse開始
- [ ] イベントハンドラーはhandle開始、Propsコールバックはon開始
- [ ] Boolean値はis/has/should開始
- [ ] ファイル名・ディレクトリ名は kebab-case
- [ ] Laravelからのpropsはsnake_case許容、React内部の新規変数はlowerCamelCase
