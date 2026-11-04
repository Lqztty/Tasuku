---
paths:
  - "src/resources/js/components/**/*.tsx"
  - "src/resources/js/components/**/*.ts"
  - "src/resources/js/features/**/*.tsx"
  - "src/resources/js/features/**/*.ts"
---

# コンポーネント配置規約

> **参照**:
> - React基本規約 → `react-conventions.md`
> - 命名規則 → `naming-conventions.md`
>
> 以前は Atomic Design（atoms / molecules / organisms）で分けていたが、
> shadcn/ui を土台にするスターターキットに合わせて、次の4層に置き換えた。

## 階層構造

| 層 | 責務 | 依存してよいもの | 配置場所 |
|---|---|---|---|
| **ui** | shadcn/ui の基本部品（Button、Dialog、Select、Badge など） | なし（Radix UI、`lib/utils`） | `components/ui/` |
| **共通部品** | 2つ以上の機能で使う、アプリ共通の部品 | ui、`hooks/`、`lib/` | `components/` |
| **機能部品** | 1つの機能だけで使う部品と、その機能のロジック | ui、共通部品、`hooks/`、`lib/` | `features/{feature}/components/`、`features/{feature}/lib/` |
| **ページ** | Inertia のページ（ルートと1:1） | すべて | `pages/` |
| レイアウト | 画面の骨格 | ui、共通部品 | `layouts/` |

## 依存関係ルール（厳守）

```
pages → features → components → components/ui
```

| ルール | 説明 |
|---|---|
| **上位→下位のみ** | 下位が上位に依存してはいけない（`components/` から `features/` を import しない） |
| **機能同士は依存しない** | `features/kanban` から `features/project` を import しない。両方で使うなら `components/` へ昇格する |
| **ui は手で直接編集しない** | 足りない見た目は、呼ぶ側の `className` か、`components/` のラッパーで足す。部品の追加は shadcn CLI（`npx shadcn@latest add ...`）で行う |

## features/ vs components/

| 条件 | 配置先 |
|---|---|
| 2つ以上の機能で使用 | `components/` |
| 1つの機能でのみ使用 | `features/{feature}/components/` |
| 迷った場合 | `features/` に置き、2つ目の利用者が出た時点で昇格する |

## エクスポート規則

- 新しく作る部品は **named export**、1ファイル1コンポーネント
- **例外**: `pages/` と `layouts/` は default export（Inertia が読み込むため）
- スターターキットの既存部品（default export のものがある）は、そのままにする

```tsx
// ✅ features / components
export function TaskCard({ task }: TaskCardProps) { ... }

// ✅ pages
export default function ProjectShow({ project, tasks }: Props) { ... }
```

## 🔍 コンポーネント作成前の必須チェック（最重要）

新規コンポーネント作成**前に必ず**以下を実行（このチェックを飛ばすことは**禁止**）

### Step 1: 既存コンポーネント検索

以下の順序で検索（Glob/Grep使用）：

1. `src/resources/js/components/ui/`
2. `src/resources/js/components/`
3. `src/resources/js/features/*/components/`

### Step 2: 再利用判断

| 既存との類似度 | アクション |
|---|---|
| **80%以上** | 既存を拡張（props/バリアント追加） |
| **50〜80%** | 共通部分を`components/`に抽出 |
| **50%未満** | 新規作成可 |

shadcn/ui にある部品（Dialog、Select、DropdownMenu、Badge、Card、Sheet、Tooltip、Sonner など）は、自前で作らずに必ずそれを使う。

## ❌ NG例（絶対禁止）

| NG行為 | なぜダメか | 正しいやり方 |
|---|---|---|
| モーダルのオーバーレイを直書き | 全モーダルで重複し、アクセシビリティも崩れる | `components/ui/dialog` を使う |
| 素の `<select>` で動的リストを描画 | 検索できず、ダークモードで崩れる | `components/ui/select`（固定の数択）を使う |
| 既存の大きなUIをコピーして2つ目を作成 | 数百行単位で重複 | `components/` に切り出す |
| 自前のトースト表示 | 仕組みが二重になる | サーバーから `Inertia::flash('toast', ...)`、クライアントは sonner の `toast()` |

## 自己修復ルール

コンポーネント実装後に以下を再確認する:

1. features/ 内に置いたが、他機能でも使えそうなら `components/` へ昇格する
2. 既存部品の props 追加で済むなら新規作成をやめる
3. 名前違いの重複UIを見つけたら、既存に寄せて命名も統一する
4. 共通化を後回しにせず、そのタスク内で修復する

## UIスタック

Tailwind CSS 4 / shadcn/ui（Radix UI）/ lucide-react / sonner（トースト）/ dnd-kit（ドラッグ&ドロップ）
