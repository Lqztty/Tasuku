---
paths:
  - "src/resources/js/**/*.tsx"
  - "src/resources/js/**/*.ts"
---

# デザイン原則（節度 / Apple HIG の考え方を shadcn/ui のトークンで実現する）

> 本プロジェクトのUIデザイン哲学。新規UI作成時は必ず参照。
> 色・角丸・影は `resources/css/app.css` のトークン（CSS 変数）で指定する。
> トークンはライトとダークで自動的に切り替わるので、`dark:` を書き足す必要がない。

## 0. 大原則: Restraint（節度）

**「色は意味があるときだけ。1画面に1アクセント」**

- 情報の階層は「色の数」ではなく、**サイズ・ウェイト・グレーの濃淡・余白**で作る
- 迷ったら要素を減らす

## 1. カラー（トークンを使う）

| 役割 | クラス |
|------|--------|
| 背景（ベース） | `bg-background` / `text-foreground` |
| 面（カード・リスト・かんばんの列） | `bg-card` / `text-card-foreground`、列の地は `bg-muted/50` |
| 補助テキスト | `text-muted-foreground` |
| 区切り線（hairline） | `border-border`（`border` だけでも可）、`divide-border` |
| 主要な操作（1画面1つ） | `<Button>`（既定の variant = `bg-primary`） |
| 副次的な操作 | `<Button variant="outline" / "ghost" / "secondary">` |
| 破壊的な操作 | `<Button variant="destructive">`、テキストは `text-destructive` |

- `gray-*` / `white` / `black` などの生の無彩色は使わない。使うなら `dark:` を必ず併記する（`dark-mode-guard` hook が検査する）
- 状態を伝える色（期限切れ = 赤、優先度・高 = オレンジ など）は**点で使う**: ドット `size-2 rounded-full bg-red-500` ＋ テキスト。カード・行の**背景全体を意味色で塗らない**
- グラデーション・グロー・点滅アニメーションは原則禁止

## 2. タイポグラフィ

- 階層は3段まで: タイトル `text-[15px] font-semibold` / 本文 `text-sm` / 補助 `text-xs text-muted-foreground`
- **10px未満・全要素太字は禁止**。太字はタイトルと強調のみ
- 数値・日付など補助情報は regular ウェイト＋ `text-muted-foreground`

## 3. 形状と奥行き

- 角丸はトークン基準: カード・ダイアログは shadcn の既定（`rounded-xl`）、ボタン・入力も shadcn の既定に任せる
- 影は1段だけ薄く: `shadow-xs`〜`shadow-sm`（ドラッグ中のカードだけ `shadow-md` まで）
- ダイアログの背景（オーバーレイ）は `components/ui/dialog` の既定のまま使う

## 4. インタラクション

- フィードバックは**小さく速く**: `transition-colors duration-150`、押下時 `active:scale-[0.98]`
- ホバーは背景色の変化（`hover:bg-accent`）を基本とし、拡大・傾き・グローは使わない
- `prefers-reduced-motion` を必ず尊重（`motion-safe:` / `motion-reduce:` を使う）
- ドラッグ&ドロップは、キーボードでも操作できるようにする（dnd-kit の `KeyboardSensor`）

## 5. 構造（グループ化）

- フォーム・詳細はセクション単位のカードでまとめる
- 常時表示する情報は最小限に絞り、詳細はダイアログへ格納する
- フィルタ等の操作群は折りたたみ、**適用中の条件だけ**をチップ（`<Badge variant="secondary">`）で示す

## 適用ガイドライン

| 要素 | 原則 | 具体例 |
|------|------|--------|
| カード | 面は無彩色 | `<Card>` または `rounded-xl border bg-card shadow-xs` |
| 状態表示 | 点で示す | `<span className="size-2 rounded-full bg-red-500" />` ＋ テキスト |
| バッジ | 淡色チップ | `<Badge variant="secondary">`、意味色を付けるなら `bg-orange-500/15 text-orange-600 dark:text-orange-400` |
| 区切り | hairline | `divide-y divide-border` |
| ダークモード | トークンで自動対応 | 生の無彩色を使ったときだけ `dark:` を併記 |
