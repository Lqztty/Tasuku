#!/usr/bin/env node
/**
 * 共通処理再利用ゲート (PreToolUse / Write)
 *
 * 共通部品の置き場（Components/, features/common/components/, hooks/, utils/）を
 * 自動スキャンしてレジストリを構築し、features/・Pages/ 配下への新規 .ts/.tsx 作成時に
 * 「既存の共通部品で実現できる可能性」を検知したら Write をブロックする。
 *
 * 検知方法:
 *   1. ファイル名の完全一致（既存共通部品と同名） → 無条件ブロック
 *   2. ファイル名トークン × UIウィジェット語彙 × レジストリの突き合わせ
 *   3. ファイル内容のシグナル（<select, <table, role="dialog" 等）→ カテゴリトークン化して突き合わせ
 *
 * 通過条件:
 *   - 候補コンポーネントを既に import している（= 再利用済み）
 *   - ファイル先頭付近に「// @reuse-checked: <既存部品を使えない理由>」を記載
 *     （= 調査済みの証跡。理由なしには独自実装できない）
 */

import { existsSync, readFileSync, readdirSync, statSync } from 'fs';
import { join, basename, resolve, dirname, relative } from 'path';
import { fileURLToPath } from 'url';
import { loadConfig, escapeRegExp } from './harness-config.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const PROJECT_ROOT = resolve(__dirname, '..', '..');

// 従来挙動を再現する既定値（config不在時に使用）
const DEFAULT_JS_ROOT = 'src/resources/js';
const DEFAULT_SHARED_DIRS = ['Components', 'features/common/components', 'hooks', 'utils'];
const DEFAULT_STOPWORDS = [
  'view', 'page', 'pages', 'section', 'panel', 'container', 'wrapper', 'layout',
  'item', 'items', 'list', 'detail', 'details', 'info', 'header', 'footer',
  'content', 'component', 'components', 'common', 'base', 'main', 'sub', 'mini',
  'cell', 'row', 'col', 'column', 'group', 'box', 'area', 'bar', 'block',
  'use', 'hook', 'hooks', 'util', 'utils', 'helper', 'helpers', 'index',
  'type', 'types', 'new', 'edit', 'add', 'data', 'app', 'test', 'tests',
];
const DEFAULT_WIDGET_VOCAB = [
  'select', 'dropdown', 'autocomplete', 'combobox', 'picker', 'chooser',
  'modal', 'dialog', 'popup', 'popover', 'drawer',
  'table', 'grid', 'pagination', 'paginator',
  'toast', 'notification', 'snackbar', 'alert',
  'tooltip', 'badge', 'button', 'spinner', 'loading', 'loader', 'skeleton',
  'avatar', 'input', 'field', 'form', 'search', 'filter', 'condition', 'conditions',
  'tag', 'menu', 'tab', 'tabs', 'accordion', 'upload', 'import', 'export',
  'pdf', 'markdown', 'editor', 'preview', 'gantt', 'chart', 'diagram',
  'calendar', 'date', 'month', 'year', 'empty', 'state', 'icon', 'label',
  'debounce', 'throttle', 'scroll', 'outside', 'user',
];

const CFG = loadConfig().componentDuplication || {};
const JS_ROOT_REL = (CFG.jsRoot || DEFAULT_JS_ROOT).replace(/^\/+|\/+$/g, '');
const JS_ROOT = join(PROJECT_ROOT, ...JS_ROOT_REL.split('/'));

// 共通部品の置き場（レジストリ収集対象 & ここへの新規作成は正当なので検査除外）
const SHARED_DIR_RELS = CFG.sharedDirs || DEFAULT_SHARED_DIRS;
const SHARED_DIRS = SHARED_DIR_RELS.map((d) => join(JS_ROOT, ...d.split('/')));

// 汎用すぎて突き合わせに使わない語
const STOPWORDS = new Set(CFG.stopwords || DEFAULT_STOPWORDS);

// 「共通部品がありがちなUI/処理」の語彙。この語での一致のみブロック対象にして誤爆を防ぐ
const WIDGET_VOCAB = new Set(CFG.widgetVocab || DEFAULT_WIDGET_VOCAB);

// 再利用調査済みマーカー。reuseRequireReason:true なら後続に理由文字列を必須とする
const REUSE_MARKER_STR = CFG.reuseMarker || '@reuse-checked:';
const REUSE_REQUIRE_REASON = CFG.reuseRequireReason === true;
// ブロック時に案内する参照ドキュメント（プロジェクト別。未指定＝DA既定、"" で非表示）
const REUSE_DOCS_REF =
  CFG.reuseDocsRef !== undefined
    ? CFG.reuseDocsRef
    : 'CLAUDE.md「共通コンポーネント再利用」/.claude/rules/frontend/atomic-design.md';

// ファイル内容 → カテゴリトークン（レジストリと同じ語彙で突き合わせる）
const CONTENT_SIGNALS = [
  [/<select[\s>]|role=["'](combobox|listbox)["']|<datalist/i, ['select', 'autocomplete', 'dropdown']],
  [/role=["']dialog["']|aria-modal|createPortal/i, ['modal', 'dialog']],
  [/<table[\s>]/i, ['table']],
  [/role=["']tooltip["']/i, ['tooltip']],
  [/toast|snackbar/i, ['toast']],
  [/role=["']tab["']/i, ['tab', 'tabs']],
  [/type=["']file["']|FileReader|dropzone/i, ['upload', 'import']],
  [/animate-spin/i, ['spinner', 'loading']],
  [/debounce/i, ['debounce']],
  [/IntersectionObserver|infinite/i, ['scroll', 'infinite']],
  [/click.?outside/i, ['outside']],
  [/toLocaleDateString|format(Date|Time)/, ['date']],
  [/remark|rehype/i, ['markdown']],
  [/gantt/i, ['gantt']],
  [/jsPDF|pdfmake|@react-pdf/i, ['pdf']],
];

// マーカー存在（理由必須なら「同一行に」非空の理由文字列を要求。改行跨ぎで
// 次行のコードを理由と誤認しないよう、行内空白[ \t]のみ許容する）
const REUSE_CHECKED_MARKER = REUSE_REQUIRE_REASON
  ? new RegExp(escapeRegExp(REUSE_MARKER_STR) + '[ \\t]*\\S')
  : new RegExp(escapeRegExp(REUSE_MARKER_STR));
const MAX_CANDIDATES_SHOWN = 10;

/** PascalCase / camelCase / kebab-case / snake_case をトークン分割 */
function tokenize(name) {
  return name
    .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
    .replace(/([A-Z]+)([A-Z][a-z])/g, '$1 $2')
    .split(/[^A-Za-z0-9]+|\s+/)
    .map((t) => t.toLowerCase())
    .filter((t) => t.length >= 2 && !STOPWORDS.has(t));
}

/** 共通部品レジストリ構築: [{ name, relPath, tokens:Set }] */
function buildRegistry() {
  const registry = [];

  function walk(dir) {
    if (!existsSync(dir)) return;
    let entries;
    try {
      entries = readdirSync(dir);
    } catch {
      return;
    }
    for (const entry of entries) {
      if (entry === '__tests__' || entry === 'node_modules') continue;
      const fullPath = join(dir, entry);
      let stat;
      try {
        stat = statSync(fullPath);
      } catch {
        continue;
      }
      if (stat.isDirectory()) {
        walk(fullPath);
      } else if (/\.tsx?$/.test(entry) && !/^index\.tsx?$|\.test\.|\.spec\./.test(entry)) {
        const name = entry.replace(/\.tsx?$/, '');
        const relPath = relative(JS_ROOT, fullPath).replace(/\\/g, '/');
        // ファイル名 + 直上ディレクトリ名（例: molecules/dropdown/DropdownMenu.tsx）をトークン化
        const tokens = new Set([...tokenize(name), ...tokenize(basename(dirname(fullPath)))]);
        registry.push({ name, relPath, tokens });
      }
    }
  }

  for (const dir of SHARED_DIRS) walk(dir);
  return registry;
}

// config由来のパスマッチ片（絶対パスの部分一致に使う）
const JS_ROOT_MATCH = '/' + JS_ROOT_REL + '/';
const SHARED_LOCATION_MATCHES = SHARED_DIR_RELS.map(
  (d) => JS_ROOT_MATCH + d.replace(/^\/+|\/+$/g, '') + '/'
);

function isSharedLocation(filePath) {
  const n = filePath.replace(/\\/g, '/');
  return SHARED_LOCATION_MATCHES.some((s) => n.includes(s));
}

function isTargetNewFile(filePath) {
  const n = filePath.replace(/\\/g, '/');
  return (
    n.includes(JS_ROOT_MATCH) &&
    /\.tsx?$/.test(n) &&
    !/\.test\.|\.spec\.|__tests__|\.d\.ts$/.test(n) &&
    !isSharedLocation(filePath) &&
    !existsSync(filePath) // 新規作成のみ検知（既存修正は対象外）
  );
}

/** 候補を既に import 済みか（= 再利用している）を判定。import文のみを見る */
function isAlreadyImported(content, candidate) {
  // import { Foo } from '...' に候補名が現れるか
  const nameInImport = new RegExp(
    `import[^;]*\\b${candidate.name}\\b[^;]*from\\s+['"][^'"]+['"]`, 's'
  );
  if (nameInImport.test(content)) return true;
  // import元パスが候補のディレクトリを指しているか（共通置き場に限定）
  const dirPath = dirname(candidate.relPath).replace(/\\/g, '/');
  const escaped = dirPath.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return new RegExp(`from\\s+['"]@?/?(?:[^'"]*/)?${escaped}(?:/[^'"]*)?['"]`).test(content);
}

/** 新規ファイルの名前・内容から再利用候補を探す */
function findReuseCandidates(fileName, content, registry) {
  const nameTokens = tokenize(fileName);
  const signalTokens = new Set();
  for (const [regex, tokens] of CONTENT_SIGNALS) {
    if (regex.test(content)) tokens.forEach((t) => signalTokens.add(t));
  }

  // ブロック判定に使うのはウィジェット語彙に含まれるトークンのみ（誤爆防止）
  let queryTokens = new Set(
    [...nameTokens, ...signalTokens].filter((t) => WIDGET_VOCAB.has(t))
  );
  if (queryTokens.size === 0) return { exact: null, candidates: [] };

  // そのカテゴリの共通部品を既にimport済みなら、カテゴリごと「調査済み」とみなす
  const satisfied = new Set();
  for (const entry of registry) {
    if (!isAlreadyImported(content, entry)) continue;
    for (const t of queryTokens) {
      if (entry.tokens.has(t)) satisfied.add(t);
    }
  }
  queryTokens = new Set([...queryTokens].filter((t) => !satisfied.has(t)));

  const lower = fileName.toLowerCase();
  let exact = null;
  const scored = [];
  for (const entry of registry) {
    if (entry.name.toLowerCase() === lower) {
      exact = entry;
      continue;
    }
    let score = 0;
    for (const t of queryTokens) {
      if (entry.tokens.has(t)) score++;
    }
    if (score > 0) {
      scored.push({ entry, score });
    }
  }
  scored.sort((a, b) => b.score - a.score);
  return { exact, candidates: scored.map((s) => s.entry) };
}

function block(fileName, exact, candidates) {
  console.error(`[reuse-gate] 🛑 新規実装「${fileName}」に対して既存の共通部品候補が見つかりました。`);
  if (exact) {
    console.error(`[reuse-gate] ‼ 同名の共通部品が既に存在します: ${exact.relPath}`);
  }
  const shown = candidates.slice(0, MAX_CANDIDATES_SHOWN);
  if (shown.length > 0) {
    console.error(`[reuse-gate] 再利用候補（実装前に必ず中身を確認すること）:`);
    for (const c of shown) {
      console.error(`[reuse-gate]    - ${c.relPath}`);
    }
    if (candidates.length > shown.length) {
      console.error(`[reuse-gate]    ...他 ${candidates.length - shown.length} 件`);
    }
  }
  console.error(`[reuse-gate] 対応手順:`);
  console.error(`[reuse-gate]  1. 候補で要件を満たせる → 再利用する（不足があれば共通側をprops拡張。feature内に複製しない）`);
  console.error(`[reuse-gate]  2. 汎用化できる新規UI/処理 → 共通置き場（${SHARED_DIR_RELS.join(' / ')}）に作成する`);
  console.error(`[reuse-gate]  3. 候補を精査した結果、独自実装が正当な場合のみ → ファイル先頭付近に「// ${REUSE_MARKER_STR} <候補を使えない理由>」を記載して再実行する`);
  if (REUSE_DOCS_REF) console.error(`[reuse-gate] 参照: ${REUSE_DOCS_REF}`);
  process.exit(2);
}

function main() {
  let input = '';
  process.stdin.setEncoding('utf8');
  process.stdin.on('data', (chunk) => { input += chunk; });
  process.stdin.on('end', () => {
    try {
      const data = JSON.parse(input);
      if ((data.tool_name || '') !== 'Write') {
        process.exit(0);
        return;
      }

      const filePath = data.tool_input?.file_path || '';
      const content = data.tool_input?.content || '';

      if (!isTargetNewFile(filePath)) {
        process.exit(0);
        return;
      }
      if (REUSE_CHECKED_MARKER.test(content)) {
        process.exit(0); // 調査済み宣言あり → 通過
        return;
      }

      const fileName = basename(filePath).replace(/\.tsx?$/, '');
      const registry = buildRegistry();
      const { exact, candidates } = findReuseCandidates(fileName, content, registry);

      if (exact || candidates.length > 0) {
        block(fileName, exact, candidates);
        return;
      }

      process.exit(0);
    } catch {
      process.exit(0); // hook自身の異常でセッションを壊さない
    }
  });
}

main();
