---
paths:
  - "**/*"
---

# AI実行指示

> **参照**:
> - 行動制約 → `ai-behavior.md`
> - Backend詳細 → `backend/laravel-conventions.md`
> - Frontend詳細 → `frontend/react-conventions.md` / `frontend/atomic-design.md`
> - 作業ルール → `work-rules.md`

言語設定 → `CLAUDE.md`参照

## 必読ドキュメント

| ファイル | 重要度 | 内容 |
|---------|-------|------|
| `CLAUDE.md` | **必読** | アーキテクチャ、コマンド、パターン |
| `rules/work-rules.md` | **必読** | 作業ルール |
| `memories/learnings.md` | 参考 | 過去の学習パターン |

## 技術スタック

| カテゴリ | 技術 |
|---------|------|
| Backend | Laravel 13, PHP 8.5, Fortify（認証） |
| Frontend | React 19（React Compiler 有効）, TypeScript 5.9 |
| 連携 | Inertia.js 3, Wayfinder（ルートとアクションの型生成。Ziggy は使わない） |
| スタイル | Tailwind CSS 4, shadcn/ui（Radix UI）, lucide-react |
| ビルド・Lint・整形 | Vite+（`vp dev` / `vp build` / `vp check`。中身は oxlint と oxfmt） |
| 状態管理 | Inertia のページ props が正。楽観的更新は Inertia 3 の `router.optimistic()`。画面内だけの状態は `useState` |
| テスト | PHPUnit 12, Larastan（PHPStan level 7） |
| DB | MySQL 8.4（Docker）。Feature テストは SQLite インメモリ |

詳細: `src/package.json`, `src/composer.json`

- Zustand は入れていない。複数ページにまたがるクライアント状態が本当に必要になったときだけ、相談してから入れる
- Pest は入れていない（スターターキットの PHPUnit に合わせる）

Docker実行・フォーマッター → `code-style.md`
DDD 3層アーキテクチャ → `backend/laravel-conventions.md`

### Frontend（スターターキットの構成に合わせる）

```
pages/        # Inertia.jsページ（ルートと1:1）
layouts/      # レイアウト
components/   # アプリ共通の部品
components/ui # shadcn/ui の部品（手で直接編集しない）
features/     # 機能ごとのモジュール
hooks/        # 共通カスタムフック
lib/          # ユーティリティ
types/        # 共通の型
actions/ routes/ wayfinder/   # Wayfinder の生成物（編集しない・git 管理外）
```

**パスエイリアス**: `@/*` → `./resources/js/*`

**参照**: `frontend/react-conventions.md`

## Git制約

```
❌ 禁止:
- git config変更
- 破壊的コマンド (push --force, reset --hard)
- フック無視 (--no-verify)
- main/masterへのforce push
- main への直接コミット（初期コミットを除く）

✅ 必須:
- ファイル名指定でのステージング
- 小さな変更や追加でも、区切りごとにコミットしてプッシュし、コミット内容をユーザーに報告する（ユーザー指示 2026-11-04）
- 作業は feature ブランチで行い、区切りで PR を作る（リモート: https://github.com/Lqztty/Tasuku）
- 公開リポジトリなので `.env` や認証情報は絶対にコミットしない
```

## ワークフロー

### 実装前
1. 不明点は質問（推測禁止）
2. 中規模以上は計画（`docs/plans/{feature}.md`）またはスコープ要約
3. TDDは `/tdd`
4. スキル適合を確認し、強い一致があればスキルを先に起動
5. 係 (サブエージェント) に渡すかは `work-rules.md` の「誰が作業するか」で決める
6. 変更対象に入るドメインの `CLAUDE.md`（用語集があればそれも）を確認する
7. UI変更時は `Components/` 既存実装を検索してから着手する
8. 小規模判定を先に行う

### 実装中
1. DDD厳守
2. MCP Serena更新（導入している場合のみ）
3. 適切な層に配置
4. 基本はメインが通しで実装する
5. ファイルは必要な範囲だけ読む (読んだ中身は会話履歴に残り、以降の応答で毎回読み直される)

### 実装後
1. `/code-review`
2. `/test-coverage` (80%以上)
3. ドキュメント更新
4. 一時ファイル削除
5. 重要変更は Codex のセカンドオピニオンで回帰リスクを確認
6. 自己修復チェックを行う

## スキル優先ルール

```
強い一致:
- そのスキルを最初に起動

中程度一致:
- スキル適用で成果が明確に良くなるなら起動

弱い一致:
- 通常フローで対応
```

「ユーザーがスキル名を明示したか」ではなく、「依頼内容がそのスキルの専門領域か」で判断する。

## 委任判断ルール

`work-rules.md` の「誰が作業するか」に一本化した。

## 自己修復チェック

```
1. 用語: ユビキタス言語と一致しているか
2. 配置: 層とディレクトリが責務に合っているか
3. UI: 既存 Components/ を再利用または昇格したか
4. 命名: deprecated / ローカル方言が混ざっていないか
5. 差分: 「とりあえず追加」が残っていないか
```

## 小規模判定

定義は `work-rules.md`「小規模変更の定義」に一本化した。

## セキュリティ

```
❌ 禁止: 本番直接アクセス、認証情報平文、APIキー埋め込み
✅ 推奨: 環境変数、.env.example提供
```
