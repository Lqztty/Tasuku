---
paths:
  - "src/resources/js/**/*.tsx"
  - "src/resources/js/**/*.ts"
---

# デザイン原則（iOSライク / Apple HIG準拠）

> 本プロジェクトのUIデザイン哲学。新規UI作成時は必ず参照。

## 0. 大原則: Restraint（節度）

**「色は意味があるときだけ。1画面に1アクセント」**

- 情報の階層は「色の数」ではなく、**サイズ・ウェイト・グレーの濃淡・余白**で作る
- 迷ったら要素を減らす（Chanelの原則: 出かける前にアクセサリーを1つ外す）

## 1. カラー

| 役割 | ライト | ダーク |
|------|--------|--------|
| アクセント（1画面1色） | `blue-500` | `blue-400` |
| 背景（ベース） | `gray-50` / `white` | `#1C1C1E` / `#2C2C2E` |
| 面（カード・リスト） | `white` | `#2C2C2E` |
| 本文 | `gray-900` | `white` |
| 補助テキスト | `gray-500` | `gray-400` |
| 区切り線（hairline） | `black/5`〜`gray-200` | `white/10` |

- セマンティックカラーは**状態を伝えるときだけ**: 破壊=`red-500`、警告=`orange-500`、成功=`green-500`
- カード・行の**背景全体を意味色で塗らない**。色はドット・小バッジ・テキストで点として使う
- グラデーション・グロー・点滅アニメーションは原則禁止

## 2. タイポグラフィ

- 階層は3段まで: タイトル `text-[15px] font-semibold` / 本文 `text-sm` / 補助 `text-xs text-gray-500`
- **10px未満・全要素太字は禁止**。太字はタイトルと強調のみ
- 数値・日付など補助情報は regular ウェイト＋グレー

## 3. 形状と奥行き

- 角丸: カード・モーダル `rounded-2xl`、ボタン・入力 `rounded-lg`〜`rounded-xl`
- 影は1段だけ薄く: `shadow-sm`（ホバーで `shadow-md` まで）
- 境界は hairline（`border-black/5` / `dark:border-white/10`）
- モーダル背景は `bg-black/40 backdrop-blur-sm`（iOSのシート感）

## 4. インタラクション

- フィードバックは**小さく速く**: `active:scale-[0.98] transition duration-150`
- ホバーは背景色の変化（`hover:bg-gray-100` / `dark:hover:bg-white/5`）を基本とし、拡大・傾き・グローは使わない
- `prefers-reduced-motion` を必ず尊重

## 5. 構造（グループ化インセット）

- フォーム・詳細画面はiOSの設定アプリのように**セクション単位の角丸カード**でグループ化する
- 常時表示する情報は最小限に絞り、詳細はモーダル・ポップオーバーへ格納する
- フィルタ等の操作群は折りたたみ、**適用中の条件だけ**をチップで示す

## 適用ガイドライン

| 要素 | 原則 | 具体例 |
|------|------|--------|
| カード | 面は無彩色 | `bg-white dark:bg-[#2C2C2E] rounded-2xl shadow-sm border border-black/5 dark:border-white/10` |
| 状態表示 | 点で示す | カラードット `w-2 h-2 rounded-full` ＋ テキスト |
| ボタン(主) | アクセント1色 | `bg-blue-500 text-white rounded-lg px-4 py-2 font-semibold active:scale-[0.98]` |
| ボタン(副) | 無彩色 | `text-blue-500`（テキストボタン）または `bg-gray-100 dark:bg-white/10` |
| バッジ | 淡色チップ | `bg-{color}-500/15 text-{color}-600 dark:text-{color}-400 rounded-full px-2` |
| 区切り | hairline | `divide-y divide-black/5 dark:divide-white/10` |
| ダークモード | 必須併記 | すべてのクラスに `dark:` バリアント |
