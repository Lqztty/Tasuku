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
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | React 18, TypeScript 5.0 |
| 連携 | Inertia.js 2.0, Ziggy |
| スタイル | Tailwind CSS 3 |
| 状態管理 | Zustand (Immer) |
| テスト | Pest, PHPUnit, PHPStan |

詳細: `src/package.json`, `src/composer.json`

Docker実行・フォーマッター → `code-style.md`
DDD 3層アーキテクチャ → `backend/laravel-conventions.md`

### Frontend（Modular）

```
Pages/        # Inertia.jsページ
features/     # 機能モジュール
stores/       # Zustand状態管理
Components/   # 共有コンポーネント
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
- 明示的要求なしのコミット

✅ 必須:
- ファイル名指定でのステージング
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
