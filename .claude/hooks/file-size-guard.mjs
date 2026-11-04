#!/usr/bin/env node
/**
 * file-size-guard.mjs — PostToolUse hook (Edit|Write)
 *
 * coding-style の「1ファイル800行max」を強制する。
 * 編集後のファイルが閾値を超えたら Claude にフィードバックを返し、
 * SOLID に沿った分割の計画を要求する。
 *
 * 設定は `.claude/harness.config.json`（fileSizeGuard）で上書き可能。
 * config不在時は下記 DEFAULTS（＝従来のLaravel+React挙動）で動作する。
 *
 * 閾値:
 *   HARD_LIMIT 超え → decision:block で分割対応を要求
 *   SOFT_LIMIT 超え → additionalContext で事前警告
 *
 * 追加挙動:
 *   grandfatherExisting … baseline に凍結された既存巨大ファイルは block→SOFT警告へ降格
 *   sizeExemptMarker    … 新規ファイルでも「// @size-exempt: <理由>」があれば通過（理由必須）
 *   splitHints          … block/警告文の分割指針をプロジェクト別に差し替え
 */

import { readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import {
  loadConfig,
  matchesAnyGlob,
  toRelPosix,
  toPosix,
  escapeRegExp,
  PROJECT_ROOT,
} from './harness-config.mjs';

// 従来挙動を再現する既定値（config不在時に使用）
const DEFAULTS = {
  hardLimit: 800,
  softLimit: 600,
  roots: ['/src/app/', '/src/resources/js/'],
  extensions: ['.php', '.ts', '.tsx', '.js', '.jsx'],
  ignoreGlobs: [
    '**/vendor/**',
    '**/node_modules/**',
    '**/dist/**',
    '**/build/**',
    '**/.claude/**',
  ],
  grandfatherExisting: false,
  baselineFile: '.claude/harness-baseline.json',
  sizeExemptMarker: null,
  splitHints:
    'view→カード/セクション単位のコンポーネント抽出、store→slice分割、Controller→UseCase抽出、hooks/utils切り出し。',
};

function output(obj) {
  process.stdout.write(JSON.stringify(obj));
  process.exit(0);
}

// ---- 設定解決 ----------------------------------------------------------------
const cfg = { ...DEFAULTS, ...(loadConfig().fileSizeGuard || {}) };
const HARD_LIMIT = cfg.hardLimit;
const SOFT_LIMIT = cfg.softLimit;

// ---- 入力取得 ----------------------------------------------------------------
let input;
try {
  input = JSON.parse(readFileSync(0, 'utf8'));
} catch {
  process.exit(0); // 入力不正時は何もしない（hookでセッションを壊さない）
}

const filePath = input?.tool_input?.file_path;
if (!filePath) process.exit(0);

const norm = toPosix(filePath).toLowerCase();
const extOk = cfg.extensions.some((e) => norm.endsWith(e.toLowerCase()));
const rootOk = cfg.roots.some((r) => norm.includes(r.toLowerCase()));
const relPath = toRelPosix(filePath);
const ignored = matchesAnyGlob(relPath, cfg.ignoreGlobs);

if (!extOk || !rootOk || ignored) process.exit(0);

// ---- ファイル読込 & 行数 -----------------------------------------------------
let content;
try {
  content = readFileSync(filePath, 'utf8');
} catch {
  process.exit(0); // 削除済み・読込不可は対象外
}
const lineCount = content.split('\n').length;

if (lineCount <= SOFT_LIMIT) process.exit(0);

// ---- grandfather 判定（凍結済み既存巨大ファイル）-----------------------------
function loadBaseline() {
  const path = join(PROJECT_ROOT, cfg.baselineFile);
  if (!existsSync(path)) return null;
  try {
    const data = JSON.parse(readFileSync(path, 'utf8'));
    // 形式: { "files": { "<relPath>": <凍結時行数> } } もしくは配列
    if (Array.isArray(data)) {
      const m = new Map();
      for (const p of data) m.set(p, 0);
      return m;
    }
    const m = new Map();
    for (const [p, n] of Object.entries(data.files || {})) m.set(p, n);
    return m;
  } catch {
    return null; // 破損時は免除なし（block側に安全に倒す）
  }
}

const baseline =
  cfg.grandfatherExisting && lineCount > HARD_LIMIT ? loadBaseline() : null;
const frozenSize = baseline && baseline.has(relPath) ? baseline.get(relPath) : null;
const isGrandfathered = frozenSize !== null;

// ---- HARD 超過 ---------------------------------------------------------------
if (lineCount > HARD_LIMIT) {
  // 凍結済み → block を SOFT 警告へ降格（青天井防止のため肥大化は明示）
  if (isGrandfathered) {
    const grew =
      frozenSize > 0 && lineCount > frozenSize
        ? `（凍結時 ${frozenSize} 行 → 現在 ${lineCount} 行に増加。これ以上の肥大化は避けてください）`
        : `（凍結済み・分割は移管フェーズで計画的に対応）`;
    output({
      hookSpecificOutput: {
        hookEventName: 'PostToolUse',
        additionalContext:
          `注意: ${filePath} は ${lineCount} 行で上限 ${HARD_LIMIT} 行を超えていますが、` +
          `baseline に凍結された既存ファイルのため今回はブロックしません${grew}。` +
          `小規模修正は継続してよいですが、タスク完了時に分割リファクタをユーザーへ提案してください。`,
      },
    });
  }

  // 新規/非凍結ファイルでも @size-exempt マーカー（理由必須）があれば通過
  if (cfg.sizeExemptMarker) {
    // 理由を同一行に必須化（改行跨ぎで次行コードを理由と誤認させない）
    const marker = new RegExp(escapeRegExp(cfg.sizeExemptMarker) + '[ \\t]*\\S');
    if (marker.test(content)) process.exit(0);
  }

  output({
    decision: 'block',
    reason:
      `【ファイルサイズ規約違反】${filePath} は現在 ${lineCount} 行で、上限 ${HARD_LIMIT} 行を超えています。\n` +
      `対応（必須）:\n` +
      `1. この編集で新規作成した/大幅に肥大化させた場合 → 即座にSOLID原則（単一責任）に沿って複数ファイルへ分割すること。\n` +
      `2. 既存の巨大ファイルへの小規模修正の場合 → 修正自体は継続してよいが、タスク完了報告時に必ず分割リファクタリングをユーザーへ提案すること（勝手に大規模リファクタはしない）。\n` +
      `3. 分割作業中でこの警告が既知の場合 → そのまま分割を続行してよい。\n` +
      (cfg.sizeExemptMarker
        ? `4. 要件定義モック等で意図的に分割しない正当な理由がある場合 → ファイル先頭付近に「${cfg.sizeExemptMarker} <理由>」を記載して再実行すること。\n`
        : '') +
      `分割の目安: ${cfg.splitHints}`,
  });
}

// ---- SOFT 超過（HARD未満）----------------------------------------------------
output({
  hookSpecificOutput: {
    hookEventName: 'PostToolUse',
    additionalContext:
      `注意: ${filePath} は ${lineCount} 行です（推奨 200-400行、上限 ${HARD_LIMIT} 行）。` +
      `これ以上肥大化させる前に、責務単位でのファイル分割を検討してください。`,
  },
});
