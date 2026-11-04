---
paths:
  - src/resources/js/**/*.tsx
  - src/resources/js/**/*.ts
---

# React コーディング規約

> **参照**:
> - コンポーネント配置・エクスポート規則 → `atomic-design.md`
> - 命名規則 → `naming-conventions.md`

## 基本ルール

- **TypeScript必須**: `any`型は原則禁止。関数の引数と戻り値には必ず型を付ける
- **イミュータビリティ厳守**: 常に新しいオブジェクトを作成（NEVER mutate, ALWAYS create new）
- **React Compiler が有効**: `useMemo` / `useCallback` を手で書き足さない（必要なときだけ理由をコメントで残す）

## ディレクトリ構造

```
src/resources/js/
├── pages/           # Inertia.jsページ（ルートと1:1対応、default export）
├── layouts/         # レイアウト
├── features/        # 機能別モジュール
│   └── {feature}/
│       ├── components/
│       ├── lib/         # 純粋関数（並べ替えなど）
│       └── types.ts     # その機能だけの型（共通の型は types/ に置く）
├── components/      # アプリ共通の部品
│   └── ui/          # shadcn/ui（手で直接編集しない）
├── hooks/           # 共通カスタムフック
├── lib/             # ユーティリティ（cn など）
├── types/           # 共通TypeScript型定義（models.ts にサーバーの Resource と対になる型）
└── actions/ routes/ wayfinder/   # Wayfinder の生成物（編集しない）
```

## Inertia.js 連携パターン

### ページコンポーネント基本構造

```tsx
// pages/projects/show.tsx
import { Head } from '@inertiajs/react';
import { show } from '@/routes/projects';
import type { Project, Task } from '@/types/models';

type Props = {
    project: Project;
    tasks: Task[];
};

export default function ProjectShow({ project, tasks }: Props) {
    return (
        <>
            <Head title={project.name} />
            {/* ... */}
        </>
    );
}

// レイアウトとパンくずは、ページの layout プロパティで指定する（スターターキットの方式）
ProjectShow.layout = (props: Props) => ({
    breadcrumbs: [{ title: props.project.name, href: show(props.project.id) }],
});
```

### ルーティング（Wayfinder）

Ziggy の `route()` は使わない。URL は Wayfinder の生成物から取る。

```tsx
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import { router } from '@inertiajs/react';

// フォーム: <Form> コンポーネントに action と method を渡す
<Form {...ProjectController.store.form()}>{({ errors, processing }) => ...}</Form>

// 手続き的な送信
router.put(ProjectController.update.url(project.id), data);
router.delete(ProjectController.destroy.url(project.id));
```

### 楽観的更新（Inertia 3）

楽観的更新は Inertia 3 の `router.optimistic()` を使う。自前で状態を複製してロールバックしない。

```tsx
router
    .optimistic((props) => ({ tasks: moveLocally(props.tasks, taskId, status, index) }))
    .patch(TaskController.move.url(taskId), { status, position: index, expected_version }, {
        preserveScroll: true,
    });
```

- 422: Inertia が楽観的な状態を戻し、`errors` を残す
- それ以外の失敗: Inertia が元の状態に戻す
- 成功: サーバーから返った props で置き換わる（サーバーの状態が正）

### トースト

サーバーから `Inertia::flash('toast', [...])` を返すと、`hooks/use-flash-toast.ts` が sonner で表示する。クライアントだけで出すときは `import { toast } from 'sonner'`。

## 状態管理

| 状態 | 持つ場所 |
|---|---|
| サーバーのデータ | Inertia の props |
| 保存・楽観的更新・ロールバック | Inertia 3 |
| 画面の中だけの状態（絞り込み、モーダルの開閉） | `useState` |
| ドラッグ中の一時的な状態 | dnd-kit |

Zustand は入れていない。複数ページにまたがるクライアント状態が必要になったときだけ、相談してから入れる。

## イミュータビリティ（必須）

```typescript
// ❌ 禁止
items.push(item);
user.name = name;

// ✅ 正しい
setItems([...items, newItem]);
const updated = { ...user, name };
```

## 型定義パターン

| Laravel側 | React側 |
|-----------|---------|
| `ProjectResource` | `types/models.ts: Project` |
| `TaskResource` | `types/models.ts: Task` |

```typescript
// types/models.ts
export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done';

export type Task = {
    id: number;
    title: string;
    status: TaskStatus;
    due_date: string | null; // Laravel から snake_case のまま受け取る
};
```
