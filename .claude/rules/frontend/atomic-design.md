---
paths:
  - "src/resources/js/Components/**/*.tsx"
  - "src/resources/js/Components/**/*.ts"
  - "src/resources/js/features/**/components/**/*.tsx"
---

# Atomic Design 規約

> **参照**:
> - React基本規約 → `react-conventions.md`
> - 命名規則 → `naming-conventions.md`

## 階層構造

| 層 | 責務 | 依存可能 | 配置場所 |
|---|---|---|---|
| **atoms** | 最小単位のUI（ボタン、入力等） | なし | `Components/atoms/` |
| **molecules** | atomsの組み合わせ | atoms | `Components/molecules/` |
| **organisms** | ドメイン固有の複合コンポーネント | atoms, molecules, stores, hooks | `Components/organisms/` |
| **templates** | ページレイアウト骨格 | atoms, molecules | `Components/templates/` または `Layouts/` |
| **pages** | 実際のページ（Inertia.js） | すべて | `Pages/` |

**注**: `features/` 内のコンポーネントはフィーチャー固有UIとして扱い、Atomic Designの階層には含めない。

## 依存関係ルール（厳守）

```
Pages → templates → organisms → molecules → atoms
```

| ルール | 説明 |
|---|---|
| **上位→下位のみ** | 下位が上位に依存してはいけない |
| **飛び越し依存OK** | organisms → atoms 直接使用可 |
| **同階層依存は限定的** | 同ディレクトリ内のみ許可 |

### ✅ 許可

```tsx
// organisms → molecules/atoms
import { ConfirmDialog } from '@/Components/molecules/dialog/ConfirmDialog';
import { Heading } from '@/Components/atoms/text/Heading';
```

### ❌ 禁止

```tsx
// atoms → molecules（逆依存）
import { ConfirmDialog } from '@/Components/molecules/dialog/ConfirmDialog';  // 禁止！
```

## 各階層の特徴

### atoms（原子）

- これ以上分解できない最小単位
- Tailwind CSS直接スタイリング、lucide-react使用
- 状態を持たない（またはローカル状態のみ）
- 例: `button/`, `icon/`, `input/`, `text/`, `badge/`

### molecules（分子）

- 複数atomsを組み合わせた機能単位
- @headlessui/react活用
- 再利用可能、ドメインに依存しない
- 例: `dialog/`, `form/`, `card/`, `select/`

### organisms（有機体）

- ドメイン固有のビジネスロジックを含む
- stores/hooksに依存可能
- ドメイン別サブディレクトリ
- 例: `project/`, `user/`, `dashboard/`

### templates（テンプレート）

- ページ全体のレイアウト構造
- コンテンツは受け取り、配置のみ担当
- ドメインロジックを持たない

## features/ vs Components/

| 条件 | 配置先 |
|---|---|
| 2つ以上の機能で使用 | `Components/` |
| 1つの機能でのみ使用 | `features/{feature}/components/` |
| 迷った場合 | `Components/` に配置（共通化優先） |

## エクスポート規則

- **named export**（default exportではなく）
- 1ファイル1コンポーネント原則
- **例外**: Pages/Layoutsはdefault export

```tsx
// ✅ Components
export const MyComponent = () => { ... };

// ✅ Pages/Layouts
export default function ProjectIndex() { ... }
```

## 🔍 コンポーネント作成前の必須チェック（最重要）

新規コンポーネント作成**前に必ず**以下を実行（このチェックを飛ばすことは**禁止**）

### Step 1: 既存コンポーネント検索

以下の順序で検索（Glob/Grep使用）：

1. `src/resources/js/Components/atoms/`
2. `src/resources/js/Components/molecules/`
3. `src/resources/js/Components/organisms/`
4. `src/resources/js/features/*/components/`

### Step 2: 再利用判断

| 既存との類似度 | アクション |
|---|---|
| **80%以上** | 既存を拡張（props/バリアント追加） |
| **50〜80%** | 共通部分を`Components/`に抽出 |
| **50%未満** | 新規作成可（配置判断フローに従う） |

### Step 3: 配置判断フロー

```
Q1: ドメイン固有のビジネスロジック・データに依存？
  YES → organisms → Q3へ
  NO → Q2へ

Q2: 複数atomsを組み合わせている？
  YES → molecules → Q3へ
  NO → atoms → Q3へ

Q3: 2つ以上の機能で使用する？
  YES → Components/{atoms|molecules|organisms}/
  NO → features/{feature}/components/
```

## 📋 配置判断例

| コンポーネント | 層 | 配置先 | 理由 |
|---|---|---|---|
| IconButton（Button + Icon） | atoms | `Components/atoms/` | 単純な装飾の組み合わせ |
| FormField（Label + Input + Error） | molecules | `Components/molecules/` | 複数atomsの機能的組み合わせ |
| ConfirmDialog | molecules | `Components/molecules/` | 確認ダイアログの共通パターン |
| ProjectCard | organisms | `Components/organisms/` | ドメインデータに依存、複数ページで使用 |
| ProjectStatusBadge | - | `features/project/components/` | 1機能でのみ使用 |

## ❌ NG例（絶対禁止）

| NG行為 | なぜダメか | 正しいやり方 |
|---|---|---|
| features/内でモーダルオーバーレイ直書き | 全モーダルで重複 | 共通のモーダル基底部品（`Components/molecules/modal/` に1つだけ置く）を使う |
| 既存の大きなUI（チャットパネル等）をコピーして2つ目を作成 | 数百行単位で重複 | 共通の organisms に切り出す |
| atoms内で同じボタンのバリアント複数作成 | variantsで対応すべき | 既存Buttonにvariant prop追加 |

## 昇格条件

2機能以上で同パターン使用 + ドメインロジック分離可 → `Components/`に昇格

## 自己修復ルール

コンポーネント実装後に以下を再確認する:

1. features/ 内に置いたが、他機能でも使えそうなら `Components/` へ昇格する
2. 既存共通コンポーネントの props 追加で済むなら新規作成をやめる
3. 名前違いの重複UIを見つけたら、既存に寄せて命名も統一する
4. 共通化を後回しにせず、そのタスク内で修復する

## UIスタック

Tailwind CSS v3 / lucide-react / @headlessui/react（shadcn/ui未使用）
