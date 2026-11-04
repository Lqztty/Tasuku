# Laravel規約（圧縮版）

## DDD 3層（厳守）

```
Domain/{Feature}/          → Interface, Entity, RepositoryInterface
Application/{Feature}/     → {Action}UseCase.php（execute()必須）
Infrastructure/{Feature}/  → Eloquent{Feature}Repository.php
```

依存方向: Domain <- Application <- Infrastructure

## 必須パターン

- Controller: UseCase呼び出しのみ。ビジネスロジック禁止
- UseCase: `execute()`統一。`DB::transaction()`でトランザクション管理。Repository経由のみ
- Repository: Domain層でInterface定義、Infrastructure層でEloquent実装。ServiceProviderでDI

## 禁止事項

- UseCase内でEloquent直接使用（`Model::find()`等）
- Resource内でDBアクセス（N+1原因）
- `app()`サービスロケータ（ServiceProvider内は例外）
- `DB::table()`原則禁止（複雑サブクエリは例外）
- Controller内ビジネスロジック
- Docker外コマンド実行

## Request

1Controller=1Request。`routeIs()`でルート別バリデーション分岐。
