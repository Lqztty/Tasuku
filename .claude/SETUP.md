# このテンプレートの使い方

devarchitect-ai の設計ルールと hook を、同じ Laravel + React 構成の新規プロジェクトで使うための一式。

## 前提にしている構成

- Laravel 本体はリポジトリ直下の `src/` にある（`src/app`、`src/resources/js`、`src/database`）
- Docker Compose のサービス名は PHP が `app`、Node が `node`
- Node.js がホストに入っている（hook は `node` で動く）

`src/` を使わない構成にする場合は、手順4の調整が必要。

## 手順

1. このフォルダの中身（`CLAUDE.md` と `.claude/`）を、新規プロジェクトのルートへコピーする。
   - 既に `CLAUDE.md` や `.claude/settings.json` がある場合は上書きせず、内容を手で合わせる。
2. `CLAUDE.md` の「セレクト/オートコンプリート系の正典」の表を、そのプロジェクトの共通部品に合わせる。
3. 既存コードがあるプロジェクトに入れる場合だけ、800行超のファイルを凍結する。
   ```bash
   node .claude/hooks/harness-baseline.mjs
   ```
   新規プロジェクトなら不要（`harness-baseline.json` が無くても hook は動く）。
4. フォルダ構成が違う場合は次を直す。
   - `.claude/harness.config.json` の `roots`・`jsRoot`・`sharedDirs`（値の意味は `.claude/hooks/README.md`）
   - `.claude/hooks/php-unresolved-class-check.mjs` の `APP_ROOT`（`src/app` 固定）
   - `.claude/rules/` 各ファイル先頭の `paths:`（`src/...` 固定）
5. Claude Code を起動し直す（hook と rules は起動時に読み込まれる）。

## 入っているもの

| 場所 | 内容 |
| ---- | ---- |
| `CLAUDE.md` | ファイルサイズ規約、共通処理再利用ゲート、ダーク/ライト対応、スキル起動、サブエージェント、自己修復 |
| `.claude/rules/general/` | AI行動制約、実装前・中・後の流れ |
| `.claude/rules/work-rules.md` | 作業ルール、小規模変更の定義（定義はここ1か所） |
| `.claude/rules/backend/` | DDD 3層、PHP・DB命名、DB構造の確認方法 |
| `.claude/rules/frontend/` | Atomic Design、React規約、TypeScript命名 |
| `.claude/rules/ui.md` | デザイン原則（iOSライク / Apple HIG準拠） |
| `.claude/rules/code-style.md` | フォーマッター、Linter、コメント、インポート順序 |
| `.claude/hooks/` | 自動検査5本と設定ローダ |
| `.claude/harness.config.json` | hook の設定（プロジェクトごとの差分はここだけ） |
| `.claude/settings.json` | hook の配線と Docker コマンドの許可 |
| `.claude/memories/learnings.md` | 同じ構成なら通用する教訓（以後はそのプロジェクトの教訓を追記） |

## hook が検査すること

| hook | タイミング | 検査内容 | 通過用マーカー |
| ---- | ---------- | -------- | -------------- |
| `component-duplication-check` | Write 前 | 共通部品と重複しそうな新規ファイル | `// @reuse-checked: <理由>` |
| `file-size-guard` | Edit/Write 後 | 800行超（600行超で警告） | `@size-exempt: <理由>` |
| `dark-mode-guard` | Edit/Write 後 | ニュートラル色の `dark:` 漏れ | `/* dark-mode-checked */` |
| `native-select-guard` | Edit/Write 後 | `.map(` で選択肢を作る素の `<select>` | `@native-select-ok: <理由>` |
| `php-unresolved-class-check` | Edit/Write 後 | PHP の `use` 漏れ | — |

## 入れていないもの（必要なら元プロジェクトから個別に持ってくる）

- pm-workflow プラグイン、ピカチュウ関連、`compressed/`（S式圧縮）
- `/plan`・`/track`・`/mockup` コマンド（Nimbalyst 用の書式のため）
- gstack のスキル一覧（gstack を入れた環境でだけ `CLAUDE.md` に追記する）
- テーブル一覧、ドメイン用語、各ディレクトリの `CLAUDE.md`（プロジェクトごとに書く）
- 共通部品のコード本体（`AutocompleteSingle` など）

## 外部ツールが前提の記述

ルール内の次の記述は、該当ツールを入れていない環境では読み飛ばしてよい。

- `/tdd`、`/test-coverage`、`/session-carryover`、MCP Serena
