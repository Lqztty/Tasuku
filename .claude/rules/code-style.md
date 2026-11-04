---
paths:
  - "src/app/**/*.php"
  - "src/config/**/*.php"
  - "src/routes/**/*.php"
  - "src/database/**/*.php"
  - "src/tests/**/*.php"
  - "src/resources/js/**/*.ts"
  - "src/resources/js/**/*.tsx"
---

# コードスタイル規約

手動でフォーマットせず、必ず以下のツールを使用すること。コマンドはすべて Docker 内で実行する。

## フォーマッター & Linter 実行コマンド

| 言語 | 目的 | Docker実行コマンド |
| ---- | ---- | ------------------ |
| PHP | 整形（Pint） | `docker compose exec app composer lint` |
| PHP | 整形の確認だけ | `docker compose exec app composer lint:check` |
| PHP | 静的解析（Larastan / PHPStan level 7） | `docker compose exec app composer types:check` |
| PHP | テスト（PHPUnit 12） | `docker compose exec app php artisan test` |
| PHP | MySQL 結合テスト | `docker compose exec app php artisan test --configuration=phpunit.integration.xml` |
| TypeScript | Lint + 整形の確認（Vite+ = oxlint + oxfmt） | `docker compose exec node npm run check` |
| TypeScript | Lint + 整形の自動修正 | `docker compose exec node npm run check:fix` |
| TypeScript | 型チェック | `docker compose exec node npm run types:check` |
| TypeScript | ビルド | `docker compose exec node npm run build` |

> **Larastan（PHPStan）について**: `use` 漏れによる「Class not found」や未定義メソッド呼び出しなど、
> 実行するまで気づけない致命的バグを静的に検出する。PHPファイルを変更したら
> `composer types:check` を実行し、0 errors を確認してからコミットすること。
> （AI編集時は `.claude/hooks/php-unresolved-class-check.mjs` が編集直後に import 漏れを即警告する。）

ESLint と Prettier は使っていない。Lint と整形の設定は `vite.config.ts` の `lint` / `fmt` にある。

### 設定ファイル

| ツール | 設定ファイル |
| ------ | ------------ |
| Laravel Pint | `src/pint.json` |
| Larastan | `src/phpstan.neon` |
| Vite+（lint / fmt） | `src/vite.config.ts` |
| TypeScript | `src/tsconfig.json` |
| PHPUnit | `src/phpunit.xml`（SQLite インメモリ）、`src/phpunit.integration.xml`（MySQL） |

## 命名規則

**詳細は以下のドキュメントを参照:**

- **PHP（Laravel）**: `.claude/rules/backend/naming-conventions.md`
- **TypeScript（React）**: `.claude/rules/frontend/naming-conventions.md`

### クイックリファレンス

| 対象 | PHP（Laravel） | TypeScript（React） |
| ---- | -------------- | ------------------- |
| 変数 | `snake_case` | `lowerCamelCase` |
| メソッド/関数 | `camelCase` | `lowerCamelCase` |
| クラス/型/IF | `PascalCase` | `PascalCase` |
| 定数 | `UPPER_SNAKE_CASE` | `UPPER_SNAKE_CASE` |
| コンポーネント | - | `PascalCase` |
| カスタムフック | - | `use` + `PascalCase` |
| ファイル名 | `PascalCase.php` | `kebab-case.tsx` / `kebab-case.ts`（スターターキットに合わせる） |
| DBテーブル | `snake_case`（複数形） | - |
| DBカラム | `snake_case` | - |

## コメント規約

### PHP

```php
// 単一行コメント: 短い説明

/**
 * 複数行コメント: 詳細な説明
 *
 * 新しいプロジェクトを作成し、リポジトリに保存する
 *
 * @param CreateProjectCommand $command プロジェクト作成の入力
 * @return Project 作成されたプロジェクト
 * @throws ForbiddenException 権限がない場合
 */
```

### TypeScript

```typescript
// 単一行コメント: 短い説明

/**
 * 複数行コメント: 詳細な説明
 *
 * タスクを別の列へ移動したあとの並びを計算する
 * @param tasks - 現在のタスク一覧
 */
```

**コメントの原則:**

- 「何を」ではなく「なぜ」を説明すること
- 1年目のエンジニアが理解できる日本語で記述
- 複雑なロジックには必ずコメントを追加
- TODOコメントには担当者と期日を記載
  ```typescript
  // TODO: [担当者名] 2026-12-01 までにキャッシュ機構を追加
  ```

## インポート順序

### TypeScript

並び順は Vite+（oxfmt）に任せる。手で並べ替えない。目安は次のとおり。

```typescript
// 1. 外部ライブラリ（React、@inertiajs/react など）
import { router } from '@inertiajs/react';
import { useState } from 'react';

// 2. 内部モジュール（絶対パス @/）
import { Button } from '@/components/ui/button';

// 3. 型定義
import type { Task } from '@/types/models';

// 4. 相対パス
import { moveLocally } from './lib/move-locally';
```

### PHP

```php
<?php

// 1. 名前空間宣言
namespace App\Application\Project;

// 2. 外部依存
use Illuminate\Support\Facades\Log;

// 3. ドメイン層
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\Entities\Project;

// 4. 現在の層
use App\Application\Project\Commands\CreateProjectCommand;
```

## コミット前チェック（必須）

```bash
docker compose exec app composer lint
docker compose exec app composer types:check
docker compose exec app php artisan test
docker compose exec node npm run check
docker compose exec node npm run types:check
```

`npm run build` はブラウザでの動作確認が済んでから実行する（`work-rules.md`）。

## 注意事項

- **手動フォーマット禁止**: 必ずツールを使用すること
- **コミット前に実行**: フォーマットされていないコードはコミットしないこと
- **Docker環境**: すべてのコマンドはDockerコンテナ内で実行すること
