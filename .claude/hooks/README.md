# 共有AIハーネス（file-size警告 ＋ 共通処理切り出しゲート）

file-size-guard と component-duplication-check の2つのフックを、**ロジック1本化・configで差分吸収**する形にしたもの。
構成の違うプロジェクトへも `harness.config.json` の書き換えだけで移植できる（文中の「DA」は移植元の devarchitect-ai、「生徒」は移植先プロジェクトを指す）。

残り3本（dark-mode-guard / native-select-guard / php-unresolved-class-check）は config を読まない。概要は `../SETUP.md` を参照。

## 構成ファイル

| ファイル | 役割 |
|---|---|
| `harness-config.mjs` | 共有ローダ。`harness.config.json`読込＋`globToRegExp()`＋repo-relativeパス正規化 |
| `file-size-guard.mjs` | PostToolUse(Edit\|Write)。行数上限を警告/ブロック |
| `component-duplication-check.mjs` | PreToolUse(Write)。共通部品の再実装を検知しブロック |
| `harness-baseline.mjs` | grandfatherベースライン生成器（1回実行） |
| `../harness.config.json` | **各プロジェクトが自作する唯一の差分点** |
| `../harness-baseline.json` | 生成物。手で編集しない |

**不変条件**: 全フックは外部依存ゼロ（node組込のみ、`child_process`不使用）、stdin JSON入力、異常時は`exit(0)`でセッションを壊さない。**生徒は`.mjs`をforkしない**。config不在時は各フックのハードコード既定（DAのLaravel+React挙動）で安全に動く。

## 移植手順（生徒プロジェクト）

1. `harness-config.mjs` / `file-size-guard.mjs` / `component-duplication-check.mjs` / `harness-baseline.mjs` を `.claude/hooks/` へコピー。
2. `.claude/harness.config.json` を自プロジェクト構成に合わせて作成（下記スキーマ）。
3. `node .claude/hooks/harness-baseline.mjs` を1回実行し、既存巨大ファイルを凍結。
4. `settings.json` の `hooks` に配線を加算:
   ```jsonc
   "PreToolUse":  [{ "matcher": "Write",       "hooks": [{ "type": "command", "command": "node \"${CLAUDE_PROJECT_DIR}/.claude/hooks/component-duplication-check.mjs\"", "timeout": 10000 }] }],
   "PostToolUse": [{ "matcher": "Edit|Write",  "hooks": [{ "type": "command", "command": "node \"${CLAUDE_PROJECT_DIR}/.claude/hooks/file-size-guard.mjs\"",           "timeout": 5000  }] }]
   ```
5. まず `file-size-guard` 単独で1〜2日観測（既存巨大ファイルへの編集が止まらない／新規800超のみブロック）。問題なければ `component-duplication` を有効化。

## config スキーマ

```jsonc
{
  "fileSizeGuard": {
    "hardLimit": 800,          // 超過で block（grandfather対象は警告へ降格）
    "softLimit": 600,          // 超過で additionalContext 警告
    "roots":       ["/src/app/", "/src/resources/js/"],   // このいずれかをパスに含むファイルのみ対象
    "extensions":  [".php", ".ts", ".tsx", ".js", ".jsx"],
    "ignoreGlobs": ["**/vendor/**", "**/public/build/**", "**/*.g.dart"],  // 生成物・vendor除外
    "grandfatherExisting": true,                 // baseline凍結ファイルを block→警告へ降格
    "baselineFile": ".claude/harness-baseline.json",
    "sizeExemptMarker": "@size-exempt:",         // 新規でも「同一行に理由」付きで通過
    "splitHints": "<プロジェクト別の分割指針文>"
  },
  "componentDuplication": {
    "jsRoot":      "/src/resources/js",          // 検査対象ルート
    "sharedDirs":  ["Components", "hooks", "utils"],  // 共通置き場（再帰walk）
    "widgetVocab": ["modal", "table", "button"], // この語に一致した新規のみブロック（誤爆防止）
    "stopwords":   ["view", "page", "mock"],     // 突合に使わない汎用語
    "reuseMarker": "@reuse-checked:",            // 独自実装を正当化するマーカー
    "reuseRequireReason": true,                  // マーカー後（同一行）に理由必須
    "reuseDocsRef": "CLAUDE.md / atomic-design.md"  // ブロック時の参照先案内（"" で非表示。未指定=DA既定）
  }
}
```

- 未指定キーは各フックの既定（DA値）にフォールバックする。`widgetVocab`/`stopwords`はドメイン語に総入替してよい。
- `roots` は前後スラッシュ込みの部分文字列（例 `/src/app/`）。意図せぬ部分一致を避けるため区切り込みで指定する。

## glob 仕様（`ignoreGlobs`）

`**`=任意階層（`/`跨ぎ・0階層可）/ `*`=`/`以外の任意 / `?`=`/`以外の1文字。完全一致（`^...$`）で判定。
保証サポート例: `**/public/build/**`, `**/*.generated.ts`, `**/*.g.dart`, `**/*.freezed.dart`, `**/vendor/**`, `**/*.d.ts`。

## grandfather 運用

- `harness-baseline.json` は**採用時点で `hardLimit` を超えるファイル集合**を凍結（`{ files: { "<relPath>": <行数> } }`）。
- `grandfatherExisting:true` のとき、凍結ファイルへの編集は block ではなく警告へ降格。凍結時より肥大化した場合は行数増を明示（青天井防止）。
- **凍結外の新規ファイル/新たに800超へ育ったファイルは通常どおり block**（採用時点凍結の意味論）。
- ファイルを分割/削除して集合が変わったら `harness-baseline.mjs` を**再実行**して更新（手動運用。移管ステップ完了時やship時を推奨）。

## パス正規化契約

`harness-baseline.json` のキーは **repo-relative POSIX**（`src/resources/js/...`）。フックは絶対→相対を同一 `harness-config.mjs` の `toRelPosix()` で変換し、区切りを `/` に正規化して突き合わせる。
