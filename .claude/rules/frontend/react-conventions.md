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

## ディレクトリ構造

```
src/resources/js/
├── Pages/           # Inertia.jsページ（ルートと1:1対応）
├── features/        # 機能別モジュール
│   └── {feature}/
│       ├── components/
│       ├── hooks/
│       └── types.ts
├── stores/          # Zustand状態管理
├── Components/      # 共通コンポーネント（Atomic Design）
├── Layouts/         # レイアウトコンポーネント
├── hooks/           # 共通カスタムフック
├── types/           # 共通TypeScript型定義
└── utils/           # ユーティリティ関数
```

## Inertia.js連携パターン

### ページコンポーネント基本構造

```tsx
// Pages/Project/Index.tsx
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Project } from '@/types/models';

interface Props {
  projects: Project[];  // Laravelから渡される初期データ
}

export default function ProjectIndex({ projects }: Props) {
  return (
    <AppLayout>
      <Head title="プロジェクト一覧" />
      <div className="container mx-auto py-8">
        {/* ... */}
      </div>
    </AppLayout>
  );
}
```

### Inertia.js + Ziggyルーター

```tsx
import { router } from '@inertiajs/react';

// GET
router.get(route('projects.show', { project: 1 }));

// POST
router.post(route('projects.store'), { name: '名前', description: '説明' });

// PUT
router.put(route('projects.update', { project: 1 }), data);

// DELETE
router.delete(route('projects.destroy', { project: 1 }));
```

## 状態管理（Zustand + Immer）

### ストアパターン概要

```typescript
// stores/project/index.ts
import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';

interface ProjectState {
  projects: Project[];
  loading: boolean;
  // Actions
  setProjects: (projects: Project[]) => void;
  addProject: (project: Project) => void;
  updateProject: (id: number, updates: Partial<Project>) => void;
  removeProject: (id: number) => void;
}

export const useProjectStore = create<ProjectState>()(
  immer((set) => ({
    projects: [],
    loading: false,
    setProjects: (projects) => set({ projects }),
    addProject: (project) => set((state) => {
      state.projects.push(project);  // Immerがイミュータブルに変換
    }),
    updateProject: (id, updates) => set((state) => {
      const index = state.projects.findIndex(p => p.id === id);
      if (index !== -1) Object.assign(state.projects[index], updates);
    }),
    removeProject: (id) => set((state) => {
      state.projects = state.projects.filter(p => p.id !== id);
    }),
  }))
);

// セレクター（推奨）
export const useProjects = () => useProjectStore((state) => state.projects);
export const useProjectLoading = () => useProjectStore((state) => state.loading);
```

## イミュータビリティ（必須）

### ❌ 禁止（ミューテーション）

```typescript
state.items.push(item);  // 通常のReactコンポーネント内で禁止
user.name = name;  // オブジェクトの直接変更
```

### ✅ 正しい（イミュータブル）

```typescript
// スプレッド構文
setItems([...items, newItem]);
const updated = { ...user, name };

// Zustand + Immer（ドラフト状態のみOK）
set((state) => {
  state.items.push(item);  // Immerが自動変換
});
```

## 型定義パターン

### Laravel ↔ React型対応

| Laravel側 | React側 |
|-----------|---------|
| `ProjectResource` | `types/models.ts: Project` |
| `UserResource` | `types/models.ts: User` |

### 型定義例

```typescript
// types/models.ts
export interface Project {
  id: number;
  name: string;
  description: string;
  created_at: string;  // Laravelからsnake_case
  updated_at: string;
}

// types/index.d.ts
export interface PageProps<T = {}> {
  auth: { user: User };
  flash?: { success?: string; error?: string };
  errors?: Record<string, string>;
} & T;
```

## エクスポート規則

- **Pages/Layouts**: default export
- **Components/features**: named export（詳細は`atomic-design.md`参照）

```tsx
// Pages/Project/Index.tsx
export default function ProjectIndex() { ... }

// Components/atoms/button/PrimaryButton.tsx
export const PrimaryButton = () => { ... };
```
