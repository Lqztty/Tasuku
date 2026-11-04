---
paths:
  - "src/resources/js/**/*.tsx"
  - "src/resources/js/**/*.ts"
---

# TypeScript (React) 命名規則

> **参照**:
> - Atomic Design配置ルール → `atomic-design.md`
> - React基本規約 → `react-conventions.md`

## 基本ルール

| 対象 | 規則 | 例 |
|---|---|---|
| **変数・関数** | **lowerCamelCase** | `projectId`, `getProjects`, `handleSubmit` |
| クラス・型・IF | PascalCase | `Project`, `ProjectState`, `ProjectRepositoryInterface` |
| 定数 | UPPER_SNAKE_CASE | `MAX_RETRY_COUNT`, `API_BASE_URL` |
| コンポーネント | PascalCase | `ProjectCard`, `ProjectList`, `AppLayout` |
| カスタムフック | use + PascalCase | `useProjectStore`, `useProjectForm` |
| ファイル名（コンポーネント） | PascalCase | `ProjectCard.tsx`, `AppLayout.tsx` |
| ファイル名（その他） | kebab-case | `use-debounce.ts`, `format-date.ts` |
| ディレクトリ | kebab-case | `project/`, `custom-hooks/` |

## Laravel ↔ React 変数名

Laravelから`snake_case`で渡されるデータは、**React側でもそのまま`snake_case`使用可**。

```tsx
// ✅ Laravelからのpropsはsnake_case
interface Props {
  projects: {
    user_id: number;       // snake_case OK
    created_at: string;
    updated_at: string;
  }[];
}

export default function ProjectIndex({ projects }: Props) {
  // React内部の新規変数はlowerCamelCase
  const [isLoading, setIsLoading] = useState(false);
  const selectedUserId = projects[0]?.user_id;  // props参照はsnake_case
}
```

## コンポーネント命名

### ファイル名 = コンポーネント名

```tsx
// ✅ 正しい
// ProjectCard.tsx
export const ProjectCard = () => { ... };

// ✅ Pages/Layoutsはdefault export
// Pages/Project/Index.tsx
export default function ProjectIndex() { ... }

// ❌ 避ける: 不一致
// project-card.tsx
export const ProjectCard = () => { ... };  // NG
```

### Props型

```tsx
// パターン1: Props（シンプル）
interface Props {
  project: Project;
  onEdit: () => void;
}

// パターン2: ComponentNameProps（明示的）
interface ProjectCardProps {
  project: Project;
  onEdit: () => void;
}
```

## カスタムフック

```tsx
// ✅ use + 機能名（PascalCase）
export const useProjectForm = () => { ... };
export const useDebounce = (value: string, delay: number) => { ... };

// ✅ Zustandセレクター
export const useProjects = () => useProjectStore((state) => state.projects);
```

## Zustand ストア

```typescript
// stores/project/index.ts

// ✅ ストア: use + Feature + Store
export const useProjectStore = create<ProjectState>()(/* ... */);

// ✅ セレクター: use + データ名
export const useProjects = () => useProjectStore((state) => state.projects);
export const useProjectLoading = () => useProjectStore((state) => state.loading);

// ✅ アクション: 動詞で開始
interface ProjectState {
  projects: Project[];
  setProjects: (projects: Project[]) => void;
  addProject: (project: Project) => void;
  updateProject: (id: number, updates: Partial<Project>) => void;
  removeProject: (id: number) => void;
}
```

## イベントハンドラー

### handle + 動作

```tsx
const ProjectCard = () => {
  const handleClick = () => { /* ... */ };
  const handleSubmit = () => { /* ... */ };
  const handleChange = (e: ChangeEvent<HTMLInputElement>) => { /* ... */ };
  const handleDelete = () => { /* ... */ };

  return <button onClick={handleClick}>Click</button>;
};
```

### Props経由: on + 動作

```tsx
interface ProjectCardProps {
  onEdit?: (project: Project) => void;
  onClick?: () => void;
  onDelete?: (id: number) => void;
}
```

## 状態管理

### useState

```tsx
// ✅ [値, set + 値名]
const [loading, setLoading] = useState(false);
const [projects, setProjects] = useState<Project[]>([]);

// ✅ boolean: is/has/should で開始
const [isLoading, setIsLoading] = useState(false);
const [hasError, setHasError] = useState(false);
const [shouldRefresh, setShouldRefresh] = useState(false);
```

### useRef

```tsx
// ✅ Ref で終わる
const inputRef = useRef<HTMLInputElement>(null);
const containerRef = useRef<HTMLDivElement>(null);
const timerRef = useRef<NodeJS.Timeout>();
```

## 定数

### グローバル定数

```typescript
// utils/constants.ts

// ✅ UPPER_SNAKE_CASE
export const MAX_FILE_SIZE = 5 * 1024 * 1024;
export const API_BASE_URL = '/api';
export const DEFAULT_PAGE_SIZE = 20;

// ✅ オブジェクト形式
export const ROUTES = {
  HOME: '/',
  PROJECTS: '/projects',
  SETTINGS: '/settings',
} as const;
```

## 型定義

### インターフェース vs Type

```typescript
// ✅ インターフェース（オブジェクト構造）
interface Project {
  id: number;
  name: string;
}

// ✅ Type（ユニオン、エイリアス）
type ProjectStatus = 'draft' | 'active' | 'archived';
type ID = string | number;
```

### ジェネリック型

```typescript
// ✅ T, U, V または意味のある名前
interface ApiResponse<T> {
  data: T;
  message: string;
}

interface ListProps<TItem> {
  items: TItem[];
  renderItem: (item: TItem) => ReactNode;
}
```

## ディレクトリ構造

```
features/
├── project/              # kebab-case
│   ├── components/
│   │   ├── ProjectCard.tsx       # PascalCase
│   │   └── ProjectForm.tsx
│   ├── hooks/
│   │   └── useProjectForm.ts     # use + PascalCase
│   └── types.ts

stores/
├── project/
│   └── index.ts          # useProjectStore, useProjects
└── index.ts              # 全ストア再エクスポート

Components/
├── atoms/
│   └── button/
│       └── PrimaryButton.tsx
├── molecules/
│   └── form-field/
│       └── FormField.tsx
└── organisms/
    └── project/
        └── ProjectCard.tsx
```

## チェックリスト

- [ ] 変数・関数はlowerCamelCase
- [ ] コンポーネント・型はPascalCase
- [ ] 定数はUPPER_SNAKE_CASE
- [ ] カスタムフックはuse開始
- [ ] イベントハンドラーはhandle開始
- [ ] PropsコールバックはOn開始
- [ ] Boolean値はis/has/should開始
- [ ] ファイル名とコンポーネント名一致
- [ ] Laravelからのpropsはsnake_case許容
- [ ] React内部新規変数はlowerCamelCase
