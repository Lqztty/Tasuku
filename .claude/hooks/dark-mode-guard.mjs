#!/usr/bin/env node
/**
 * dark-mode-guard.mjs — PostToolUse hook (Edit|Write)
 *
 * ライト/ダーク両モード対応の強制。
 * 編集で「追加された」コード片（Edit.new_string / Write.content）の中に、
 * ニュートラル系カラー（white/black/gray/slate/zinc/neutral/stone）の
 * Tailwindクラスを含むのに `dark:` バリアントが無い className を検知したら、
 * Claude にフィードバックを返して dark: 併記を要求する。
 *
 * 誤検知防止:
 *   - アクセント色背景（bg-blue-500等）上の text-white/black は両モード共通なので除外
 *   - dark: を1つでも含むクラス文字列は対応済みとみなす
 *   - 既存コードは検査しない（追加分のみ）
 */

import { readFileSync } from 'node:fs';

// ニュートラル系カラーのユーティリティ（モードで切替が必要な色）
const NEUTRAL_COLOR =
  /\b(?:bg|text|border|divide|ring|placeholder|from|to|via)-(?:white|black|(?:gray|slate|zinc|neutral|stone)-\d{2,3})(?:\/\d{1,3})?\b/g;
// アクセント色背景（この上の text-white/black は両モード共通で正当）
const ACCENT_BG =
  /\bbg-(?:red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d{2,3}\b/;
const TEXT_ON_ACCENT = /^(?:[a-z-]+:)*text-(?:white|black)$/;

const MAX_SHOWN = 5;

function output(obj) {
  process.stdout.write(JSON.stringify(obj));
  process.exit(0);
}

/** 追加コード片から文字列リテラルを抽出し、dark:未対応のクラス文字列を返す */
function findViolations(code) {
  const violations = [];
  const stringLiteral = /"([^"\n]*)"|'([^'\n]*)'|`([^`]*)`/g;
  let m;
  while ((m = stringLiteral.exec(code)) !== null) {
    const s = m[1] ?? m[2] ?? m[3] ?? '';
    if (!s || s.length > 2000) continue;

    const neutrals = s.match(NEUTRAL_COLOR);
    if (!neutrals) continue;
    if (/dark:/.test(s)) continue; // dark:対応済み

    // アクセント背景上の text-white/black のみなら正当
    if (ACCENT_BG.test(s)) {
      const nonTextNeutrals = neutrals.filter(
        (n) => !TEXT_ON_ACCENT.test(n) && !/text-(?:white|black)/.test(n)
      );
      if (nonTextNeutrals.length === 0) continue;
    }

    violations.push({ classString: s.trim().slice(0, 120), neutrals: [...new Set(neutrals)] });
  }
  return violations;
}

let input;
try {
  input = JSON.parse(readFileSync(0, 'utf8'));
} catch {
  process.exit(0);
}

const toolName = input?.tool_name || '';
const filePath = input?.tool_input?.file_path || '';

if (!/\.tsx$/.test(filePath) || /\.test\.|\.spec\.|__tests__/.test(filePath)) {
  process.exit(0);
}
if (!filePath.replace(/\\/g, '/').includes('/resources/js/')) {
  process.exit(0);
}

// 「追加された」コード片のみを検査対象にする
let addedCode = '';
if (toolName === 'Write') {
  addedCode = input?.tool_input?.content || '';
} else if (toolName === 'Edit') {
  addedCode = input?.tool_input?.new_string || '';
} else {
  process.exit(0);
}

// 意図的に dark: 不要な場合のエスケープマーカー（追加分に含まれていれば通過）
if (addedCode.includes('dark-mode-checked')) {
  process.exit(0);
}

const violations = findViolations(addedCode);
if (violations.length === 0) {
  process.exit(0);
}

const shown = violations
  .slice(0, MAX_SHOWN)
  .map((v) => `  - "${v.classString}" ← ${v.neutrals.join(', ')} に dark: が必要`)
  .join('\n');
const more =
  violations.length > MAX_SHOWN ? `\n  ...他 ${violations.length - MAX_SHOWN} 箇所` : '';

output({
  decision: 'block',
  reason:
    `【ダークモード対応漏れ】${filePath} に追加したコードで、ニュートラル系カラーに dark: バリアントが併記されていません。\n` +
    `${shown}${more}\n` +
    `対応（必須）: 今追加した className に dark: バリアントを併記してください（例: bg-white dark:bg-gray-900 / text-gray-900 dark:text-gray-100 / border-gray-200 dark:border-gray-700）。\n` +
    `本プロジェクトはライト/ダーク両モード対応が必須です（.claude/rules/ui.md 参照）。周辺の既存コードの dark: パターンに合わせること。\n` +
    `例外（bg画像上の固定色等）で意図的に dark: 不要な場合は、該当行に「/* dark-mode-checked */」コメントを付ければ以後この編集では指摘しません。`,
});
