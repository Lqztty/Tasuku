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

手動でフォーマットせず、必ず以下のツールを使用すること。

## フォーマッター & Linter 実行コマンド

| 言語       | ツール       | Docker実行コマンド                                 |
| ---------- | ------------ | -------------------------------------------------- |
| PHP        | Laravel Pint | `docker compose exec app ./vendor/bin/pint`        |
| PHP        | Larastan（静的解析） | `docker compose exec app composer analyse` |
| TypeScript | Prettier     | `docker compose exec node npm run format`          |
| TypeScript | ESLint       | `docker compose exec node npm run lint`            |
| TypeScript | ESLint修正   | `docker compose exec node npm run lint:fix`        |

> **Larastan（PHPStan）について**: `use` 漏れによる「Class not found」や未定義メソッド呼び出しなど、
> 実行するまで気づけない致命的バグを静的に検出する。既存の指摘は `src/phpstan-baseline.neon` に
> 退避済みで、**新規コードのみ** level 5 で検査される。PHPファイルを変更したら
> `docker compose exec app composer analyse` を実行し、0 errors を確認してからコミットすること。
> （AI編集時は `.claude/hooks/php-unresolved-class-check.mjs` が編集直後に import 漏れを即警告する。）

### 設定ファイル

| ツール       | 設定ファイル                  |
| ------------ | ----------------------------- |
| Laravel Pint | `src/pint.json`               |
| Larastan     | `src/phpstan.neon` / `src/phpstan-baseline.neon` |
| Prettier     | `src/.prettierrc`             |
| Prettier     | `src/.prettierignore`         |
| ESLint       | `src/eslint.config.js`        |

## 命名規則

**詳細は以下のドキュメントを参照:**

- **PHP（Laravel）**: `.claude/rules/backend/naming-conventions.md`
- **TypeScript（React）**: `.claude/rules/frontend/naming-conventions.md`

### クイックリファレンス

| 対象               | PHP（Laravel）     | TypeScript（React）    |
| ------------------ | ------------------ | ---------------------- |
| 変数               | `snake_case`       | `lowerCamelCase`       |
| メソッド/関数      | `camelCase`        | `lowerCamelCase`       |
| クラス/型/IF       | `PascalCase`       | `PascalCase`           |
| 定数               | `UPPER_SNAKE_CASE` | `UPPER_SNAKE_CASE`     |
| コンポーネント     | -                  | `PascalCase`           |
| カスタムフック     | -                  | `use` + `PascalCase`   |
| ファイル名（コンポ） | -                  | `PascalCase.tsx`       |
| ファイル名（その他） | -                  | `kebab-case.ts`        |
| DBテーブル         | `snake_case`（複数形） | -                      |
| DBカラム           | `snake_case`       | -                      |

## コメント規約

### PHP

```php
// 単一行コメント: 短い説明

/**
 * 複数行コメント: 詳細な説明
 *
 * 新しいプロジェクトを作成し、リポジトリに保存する
 *
 * @param CreateProjectRequest $request プロジェクト作成リクエスト
 * @return Project 作成されたプロジェクト
 * @throws ValidationException バリデーションエラーの場合
 */
```

### TypeScript

```typescript
// 単一行コメント: 短い説明

/**
 * 複数行コメント: 詳細な説明
 *
 * プロジェクト一覧を取得して状態を更新する
 * @param filters - フィルター条件（オプション）
 */
```

**コメントの原則:**

- 「何を」ではなく「なぜ」を説明すること
- 1年目のエンジニアが理解できる日本語で記述
- 複雑なロジックには必ずコメントを追加
- TODOコメントには担当者と期日を記載
  ```typescript
  // TODO: [担当者名] 2026-02-01 までにキャッシュ機構を追加
  ```

## インポート順序

### TypeScript

```typescript
// 1. React関連
import { useState, useEffect } from 'react';

// 2. 外部ライブラリ
import { router } from '@inertiajs/react';
import { create } from 'zustand';

// 3. 内部モジュール（絶対パス @/）
import { useProjectStore } from '@/stores';
import { ProjectCard } from '@/Components/ProjectCard';

// 4. 型定義
import type { Project } from '@/types';

// 5. 相対パス
import { helper } from './helper';
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
use App\Application\Project\Dto\CreateProjectRequest;
```

## コミット前チェック（必須）

コミット前には必ず以下を実行すること：

```bash
# PHP（Laravel Pint）
docker compose exec app ./vendor/bin/pint

# PHP（Larastan 静的解析 / Class not found・未定義メソッド等の検出）
docker compose exec app composer analyse

# TypeScript（Prettier + ESLint）
docker compose exec node npm run format
docker compose exec node npm run lint

# TypeScriptビルドチェック
docker compose exec node npm run build
```

## VSCode 推奨設定

保存時に自動フォーマットを有効化：

```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode"
}
```

## 注意事項

- **手動フォーマット禁止**: 必ずツールを使用すること
- **コミット前に実行**: フォーマットされていないコードはコミットしないこと
- **Docker環境**: すべてのコマンドはDockerコンテナ内で実行すること
