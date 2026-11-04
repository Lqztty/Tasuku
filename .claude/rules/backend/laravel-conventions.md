# Laravel規約（圧縮版）

## DDD 3層（厳守）

```
Domain/{Feature}/          → Entity, ValueObject, ドメインサービス, RepositoryInterface, 例外
Application/{Feature}/     → {Action}{Feature}UseCase.php（execute()必須）, Commands/{Action}{Feature}Command.php
Infrastructure/{Feature}/  → Eloquent{Feature}Repository.php
```

依存方向: Domain <- Application <- Infrastructure

## 層ごとの責務

| 層 | 責務 | やらないこと |
|---|---|---|
| Domain | Entity（`readonly`。変更は PHP 8.5 の `clone($entity, [...])` で新しいインスタンスを返す）、enum、権限や並び順の計算などの純粋なドメインサービス、Repository の Interface、ドメイン例外 | DB や Laravel への依存 |
| Application | 1 UseCase = 1 トランザクション。`TransactionManagerInterface::run()` で境界を張り、ロック → 権限の判定 → ドメインの計算 → 保存 の順に組み立てる | Eloquent やファサードの直接使用 |
| Infrastructure | Model と Entity の変換、`lockForUpdate()`、一括更新。`LaravelTransactionManager`（`DB::transaction()` を包む） | トランザクションの開始、権限の判定、業務ルール |
| Http | Controller（Request → Command → UseCase → Inertia の応答）、Request（バリデーション）、Resource（Entity を配列にするだけ） | 業務ロジック、DB アクセス |

## 必須パターン

- Controller: UseCase呼び出しのみ。ビジネスロジック禁止
- UseCase: `execute()`統一。トランザクションは `TransactionManagerInterface` 経由。Repository経由のみ
- UseCase の引数はプリミティブ型。多いときはプリミティブだけを持つ `readonly` の Command DTO にまとめる（Request → Command の変換は Controller）
- Repository: Domain層でInterface定義、Infrastructure層でEloquent実装。`RepositoryServiceProvider`でDI
- Repository のロック付きメソッド（`lockForUpdate` など）はトランザクション内で呼ぶ前提。呼ぶ側の UseCase が責任を持つ
- 同じ集約への書き込みは、親の行（例: `kanban_projects`）を先にロックして直列化する。ロックの順番は常に「親 → 子」
- ドメイン例外（`ForbiddenException` / `NotFoundException` / `ConflictException`）の HTTP への変換は `bootstrap/app.php` に集める
- トーストはスターターキットの仕組みを使う: `Inertia::flash('toast', ['type' => 'success'|'info'|'warning'|'error', 'message' => '...'])`

## 禁止事項

- UseCase内でEloquent直接使用（`Model::find()`等）、`DB::` ファサードの直接使用
- Resource内でDBアクセス（N+1原因）
- `app()`サービスロケータ（ServiceProvider内は例外）
- `DB::table()`原則禁止（複雑サブクエリは例外）
- Controller内ビジネスロジック
- Docker外コマンド実行
- `env()` を config 以外で呼ぶこと（`config/*.php` を経由する）

## Request

1Controller=1Request。`routeIs()`でルート別バリデーション分岐。エラーメッセージと属性名は日本語で書く。
