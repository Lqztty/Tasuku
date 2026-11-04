# PHP命名規則（圧縮版）

## コード命名

変数:`snake_case` / クラス:`PascalCase` / メソッド:`camelCase` / 定数:`UPPER_SNAKE_CASE`

## DDD層別

- **Domain**: `{Feature}RepositoryInterface`, Entity=名詞, VO=名詞
- **Application**: `{Action}{Feature}UseCase` + `execute()`必須、入力 DTO は `{Action}{Feature}Command`
- **Infrastructure**: `Eloquent{Feature}Repository`
- **Http**: `{Feature}Controller`, `{Feature}Request`, `{Feature}Resource`

## メソッド動詞

create/find/get/update/delete/save / 真偽: is/has/can

## DB命名

- テーブル: `snake_case`複数形。機能ごとに接頭辞を決め、決めたらここに追記する
  - かんばん（プロジェクト・メンバー・タスク）: `kanban_`（例: `kanban_projects`、`kanban_tasks`）
  - Eloquent Model 名は接頭辞を含めた単数形（`KanbanProject`、`KanbanTask`）。Domain の Entity 名は接頭辞なし（`Project`、`Task`）
- カラム: `snake_case`。FK=`{model}_id`。真偽=`is_{adj}`。論理削除=`deleted_at`
- 中間テーブル: アルファベット順 `{model1}_{model2}`。接頭辞は先頭に付ける（例: `kanban_project_user`）
