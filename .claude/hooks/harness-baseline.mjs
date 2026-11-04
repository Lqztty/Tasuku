#!/usr/bin/env node
/**
 * harness-baseline.mjs — grandfather ベースライン生成器（1回実行）
 *
 * `harness.config.json`(fileSizeGuard) の roots / extensions / ignoreGlobs に従って
 * プロジェクトを走査し、現時点で hardLimit を超えるファイル一覧を
 * `.claude/harness-baseline.json` へ凍結する。
 *
 * file-size-guard は grandfatherExisting:true のとき、ここに載るファイルへの
 * 編集を block ではなく SOFT 警告へ降格する（＝採用時点の既存巨大ファイルを免除）。
 *
 * 使い方:  node .claude/hooks/harness-baseline.mjs
 *          （移管でファイルが分割/消滅したら再実行して集合を更新する）
 */

import { readFileSync, readdirSync, statSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { loadConfig, matchesAnyGlob, toRelPosix, PROJECT_ROOT } from './harness-config.mjs';

const DEFAULTS = {
  hardLimit: 800,
  roots: ['/src/app/', '/src/resources/js/'],
  extensions: ['.php', '.ts', '.tsx', '.js', '.jsx'],
  ignoreGlobs: ['**/vendor/**', '**/node_modules/**', '**/dist/**', '**/build/**', '**/.claude/**'],
  baselineFile: '.claude/harness-baseline.json',
};

const cfg = { ...DEFAULTS, ...(loadConfig().fileSizeGuard || {}) };

// roots は "/src/app/" のような区切り込み文字列。走査開始点を導出する
function rootDirs() {
  const dirs = new Set();
  for (const r of cfg.roots) {
    const seg = r.replace(/^\/+|\/+$/g, ''); // "src/app"
    dirs.add(join(PROJECT_ROOT, seg));
  }
  return [...dirs];
}

const files = {};
let scanned = 0;

function walk(dir) {
  let entries;
  try {
    entries = readdirSync(dir);
  } catch {
    return;
  }
  for (const entry of entries) {
    const full = join(dir, entry);
    let st;
    try {
      st = statSync(full);
    } catch {
      continue;
    }
    const rel = toRelPosix(full);
    if (matchesAnyGlob(rel, cfg.ignoreGlobs)) continue;
    if (st.isDirectory()) {
      walk(full);
    } else {
      if (!cfg.extensions.some((e) => entry.toLowerCase().endsWith(e.toLowerCase()))) continue;
      scanned++;
      let n;
      try {
        n = readFileSync(full, 'utf8').split('\n').length;
      } catch {
        continue;
      }
      if (n > cfg.hardLimit) files[rel] = n;
    }
  }
}

for (const d of rootDirs()) walk(d);

const out = {
  generatedNote: 'grandfather baseline — harness-baseline.mjs で再生成。手で編集しない。',
  hardLimit: cfg.hardLimit,
  count: Object.keys(files).length,
  files,
};

const dest = join(PROJECT_ROOT, cfg.baselineFile);
writeFileSync(dest, JSON.stringify(out, null, 2) + '\n', 'utf8');

console.log(`[harness-baseline] 走査 ${scanned} ファイル / 凍結 ${out.count} 件 (>${cfg.hardLimit}行)`);
console.log(`[harness-baseline] 出力: ${cfg.baselineFile}`);
for (const [p, n] of Object.entries(files).sort((a, b) => b[1] - a[1])) {
  console.log(`  ${String(n).padStart(5)}  ${p}`);
}
