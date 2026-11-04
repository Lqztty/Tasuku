# Tasuku（タスク）

社内向けのかんばん式タスク管理システム。PHP 8.5 / Laravel 13 / React 19 / Inertia 3 の検証を兼ねる。

- リポジトリ: https://github.com/Lqztty/Tasuku（公開）
- Laravel 本体は `src/`。ルートには Docker と `.claude/` だけを置く

## 言語

回答・コメント・コミットメッセージは日本語。コードの識別子は英語。

## 必読

| ファイル | 内容 |
|---|---|
| `.claude/rules/work-rules.md` | 作業ルール、小規模変更の定義 |
| `.claude/rules/general/ai-behavior.md` / `ai-instructions.md` | 行動制約、技術スタック、Git 運用 |
| `.claude/rules/backend/laravel-conventions.md` | DDD 3層と層ごとの責務 |
| `.claude/rules/frontend/atomic-design.md` / `react-conventions.md` | 部品の配置、Inertia 3 の使い方 |
| `.claude/rules/ui.md` | デザイン原則 |
| `.claude/memories/learnings.md` | 過去の教訓 |

## コマンド（すべて Docker 内で実行する）

```bash
docker compose up -d                       # app: http://localhost:8080 / vite: 5173 / MySQL: 33060
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test                                          # Unit + Feature（SQLite）
docker compose exec app php artisan test --configuration=phpunit.integration.xml  # MySQL 結合テスト
docker compose exec app composer lint          # Pint
docker compose exec app composer types:check   # Larastan
docker compose exec node npm run check         # Vite+（oxlint + oxfmt）
docker compose exec node npm run types:check   # tsc
```

ホストの 8000 番は別のコンテナが使っているため、app は 8080 番にしている（`APP_PORT` で変更可）。

## ディレクトリ構成

```
compose.yaml / docker/        開発環境（app と node は同じイメージ。node にも PHP が入っている）
src/app/Domain/               Entity、enum、ドメインサービス、Repository の Interface、例外
src/app/Application/          UseCase（execute()）、Command DTO
src/app/Infrastructure/       Eloquent の Repository、トランザクション
src/app/Http/                 Controller、Request、Resource
src/resources/js/pages/       Inertia のページ
src/resources/js/features/    機能ごとの部品とロジック（kanban、project）
src/resources/js/components/  共通部品（ui/ は shadcn）
```

## Git 運用

- 小さな変更でも、区切りごとにコミットしてプッシュし、コミット内容を報告する
- 作業は feature ブランチで行い、区切りで PR を作る。main に直接コミットしない
- `.env` や認証情報はコミットしない（公開リポジトリ）
- コミットの作者は `Lqztty <bxckwww@gmail.com>`（`git config --local` で設定済み）
- コミットメッセージに `Co-Authored-By: Claude ...`、PR 本文に「Generated with Claude Code」を付けない（GitHub の Contributors に Lqztty だけを表示するため）
- PR を出している最中に `main` を force push すると、GitHub がその PR を自動で閉じ、再オープンもできなくなる。履歴の書き換えは避ける

## ファイルサイズ規約

1ファイル 800 行まで（600 行で警告）。`file-size-guard` hook が検査する。超えそうなら、画面はセクション単位の部品に、Controller は UseCase に、ロジックは `lib/` の純粋関数に切り出す。

## 共通処理再利用ゲート

新しい部品や処理を書く前に、次の正典を確認する。`component-duplication-check` hook が、似た名前の共通部品があると作成を止める。

| 用途 | 正典 |
|---|---|
| ダイアログ・モーダル | `components/ui/dialog` |
| 固定の数択（ステータス、優先度、担当者） | `components/ui/select`（Radix。素の `<select>` は使わない） |
| ドロップダウンメニュー | `components/ui/dropdown-menu` |
| バッジ・チップ | `components/ui/badge` |
| トースト | サーバー: `Inertia::flash('toast', ['type' => ..., 'message' => ...])`、クライアント: `toast()`（sonner） |
| フォームのエラー表示 | `components/input-error` |
| 見出し | `components/heading` |
| クラス名の結合 | `lib/utils` の `cn()` |
| 楽観的更新 | Inertia 3 の `router.optimistic()`（自前で状態を複製しない） |
| URL | Wayfinder（`@/actions/...`、`@/routes/...`） |

検索できるオートコンプリート部品はまだ無い。件数の多い選択肢が必要になったら、先に `components/` に1つ作ってから使う。

## ダーク / ライト対応

色は `app.css` のトークン（`bg-card`、`text-muted-foreground`、`border-border` など）で指定する。生の無彩色（`gray-*`、`white`、`black`）を使うときは `dark:` を必ず併記する（`dark-mode-guard` hook が検査する）。

## サブエージェントと Codex

- 基本はメインが自分で調べて書く。係に渡すのは、広い検索、互いに依存しない並行作業、別の目のレビューだけ（`work-rules.md`）
- 迷う設計判断、DB 変更、セキュリティやデータ整合性に関わる変更では、Codex に批判的なレビューを頼んでよい（`.claude/skills/codex-collab`）

## 自己修復

用語のずれ、層の責務のずれ、部品の重複を見つけたら、そのタスクの中で直す。「後で直す」を残さない。
