# 学習メモリ

セッション間で学んだ教訓。最初から入っている項目は devarchitect-ai で得たもののうち、
同じ Laravel + React 構成なら通用するもの。このプロジェクトで得た教訓は該当する節に追記する。

---

## 環境・プロジェクト固有

### Docker コンテナの使い分け

| コマンド | 実行場所 |
|---------|---------|
| npm / Node.js | `docker compose exec node` |
| PHP / Laravel | `docker compose exec app` |

### Inertia.js vs axios API呼び出し

| 用途 | 使うもの |
|------|---------|
| ページ遷移・フォーム送信 | `router.post()` (Inertia) |
| JSON APIコール | `axios.post()` |

`router.post()`でJSON APIを呼ぶと"All Inertia requests must receive a valid Inertia response"エラーになる。

### Vite+ が外から見えなくなる（rebase 後）

| 症状 | 原因 | 対処 |
|------|------|------|
| ブラウザから 5173 番に繋がらない。ログに「Network: use --host to expose」 | rebase などで作業ツリーが一時的に古いコミットになり、Docker 用の `server.host` が無い `vite.config.ts` で Vite が再起動した | `docker compose restart node` |

### shadcn CLI で部品を足すとき（2026-11 時点）

- `src/pnpm-workspace.yaml` があると CLI が pnpm を使おうとして失敗する。実行中だけ退避する:
  `docker compose exec node sh -c 'mv pnpm-workspace.yaml /tmp/ && trap "mv /tmp/pnpm-workspace.yaml ." EXIT; npx shadcn@latest add <部品> --yes'`
- 最新の CLI は無関係の npm パッケージ `cn` を依存に足し、`import { cn } from "cn"` で生成することがある。
  依存の追加は取り消し、import を `@/lib/utils` に直す

### Laravel のバリデーションメッセージを日本語にするとき

`Rule::enum()` のメッセージは翻訳ファイル（`validation.enum`）からしか取らず、FormRequest の `messages()` が効かない。
Request の中だけで日本語にしたいときは `Rule::in(array_column(Enum::cases(), 'value'))` を使う。

---

## デバッグ・診断パターン

### CSS問題の誤診断（whitespace-pre-wrap事件）

| 症状 | 誤った推測 | 真の原因 |
|------|-----------|---------|
| リストの行間が広い | margin/leadingの問題 | `whitespace-pre-wrap`がHTML改行を保持 |

CSS問題で仮説が2回失敗したら: whitespace, display, position, overflowをチェック。

### フォームボタンのtype属性（モーダル勝手に閉じる事件）

form内のbuttonはデフォルトで`type="submit"`。アクションボタンには`type="button"`を明示。

---

## React パターン

### Zustand無限ループ対策

```typescript
// ❌ オブジェクト分割代入（毎回新しいオブジェクト参照で無限ループ）
const { a, b } = useStore();

// ✅ 個別セレクター
const a = useStore(s => s.a);
const b = useStore(s => s.b);
```

### stale closure対策（useEffect内のコールバック）

```typescript
const onDragEndRef = useRef(onDragEnd);
onDragEndRef.current = onDragEnd;

useEffect(() => {
    const handler = () => onDragEndRef.current?.(...args);
    window.addEventListener('mouseup', handler);
    return () => window.removeEventListener('mouseup', handler);
}, []);
```

### useEffect依存配列最適化

イベントハンドラ内でのみ使う値はuseRefで保持し、依存配列から除外。

### 楽観的更新パターン

```typescript
const original = { ...currentState }; // 元の状態を保存
setState(newState);                    // 先にUI更新
try {
    await api.update();
} catch (error) {
    setState(original);                // 失敗時ロールバック
    showErrorToast(error);             // ユーザー通知を忘れない
}
```

---

## Laravel / DDD パターン

### N+1対策: relationLoaded()

```php
if (!$project->relationLoaded('customer')) {
    $project->load('customer');
}
```

### UseCase層でフレームワーク非依存

```php
// ❌ UseCase内
throw new \Exception('エラー');
$url = url('/vault/share/' . $token);

// ✅
throw new \DomainException('エラー');
return $token; // URLはController層で生成
```

### UseCase引数はプリミティブ型

```php
// ❌ Eloquent Modelを受け取る
public function execute(User $admin): void

// ✅ プリミティブ型で受け取る
public function execute(int $adminUserId, bool $isAdmin): void
```

### APIレスポンス形式

```php
return response()->json([
    'success' => true,
    'data' => ['id' => $id, 'created_at' => $createdAt->format('c')]
]);
```

---

## 機能パリティチェック

新画面/機能を作る前の必須チェック:
- 既存の同等機能（別画面・別デバイス版）を調査したか
- 使用している型は既存の共通型か（features/xxx/types.ts に重複した独自型を作らない）
- どのstoreアクションを使っているか
- 副機能（他機能との連携など）はあるか

最小実装 → 動作確認 → 段階的拡張の順で進める。

---

## 更新履歴

| 日付 | 内容 |
|------|------|
| 2026-10-08 | devarchitect-ai の学習メモリから汎用項目を移植 |
