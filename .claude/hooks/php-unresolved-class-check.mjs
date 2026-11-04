#!/usr/bin/env node
/**
 * php-unresolved-class-check.mjs — PostToolUse hook (Edit|Write)
 *
 * 「use 漏れ → 誤った名前空間解決 → Class not found（ランタイム落ち）」を編集直後に検知する。
 *
 * PHP は短縮クラス名を現在の名前空間で解決するため、別名前空間のクラスを
 * `use` せずに `Foo::class` や `new Foo` と書くと、存在しない
 * `App\<現在>\Foo` を指してしまい、その参照が実行された瞬間に致命的エラーになる。
 * PHPStan/Larastan が無い現状ではランタイム（＝本番）まで誰も気づけない。
 *
 * 検知ロジック（誤爆を抑えるため app 内に実体があるクラスだけを対象にする）:
 *   編集後ファイル内の `Foo::` / `new Foo` のうち、
 *     - `use` 済みでなく
 *     - 同名前空間ディレクトリに Foo.php が無く
 *     - このファイル自身が定義するクラスでもなく
 *     - しかし src/app 配下のどこかに Foo.php（= App のクラス）が実在する
 *   ものを「import 漏れ」として decision:block でフィードバックする。
 *
 * グローバル/vendor クラス（DateTime 等）は app 内に実体が無いので対象外。
 * それらの取りこぼしは Larastan（正式導入）に委ねる。
 */

import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { join, dirname, basename, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const PROJECT_ROOT = resolve(__dirname, '..', '..');
const APP_ROOT = join(PROJECT_ROOT, 'src', 'app'); // PSR-4: App\ => src/app/

const TARGET = /[\\/]src[\\/]app[\\/].*\.php$/i;
const EXCLUDE = /[\\/](vendor|node_modules)[\\/]/i;

// PHP の予約語・疑似クラス名（大文字始まりでも解決不要なもの）
const RESERVED = new Set(['self', 'static', 'parent', 'class']);

function output(obj) {
  process.stdout.write(JSON.stringify(obj));
  process.exit(0);
}

/** src/app 配下の全 PHP クラスの basename → [絶対パス...] を収集 */
function buildAppClassIndex() {
  const index = new Map(); // 'ArchitectProject' => ['/abs/src/app/Models/Architect/ArchitectProject.php']
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
      if (st.isDirectory()) {
        walk(full);
      } else if (entry.endsWith('.php')) {
        const name = entry.slice(0, -4);
        if (!index.has(name)) index.set(name, []);
        index.get(name).push(full);
      }
    }
  }
  walk(APP_ROOT);
  return index;
}

/** 絶対パス（src/app 配下）→ App\... の FQN 名前空間部分（クラス名は含めない） */
function pathToFqnPrefix(absPath) {
  const rel = absPath
    .slice(APP_ROOT.length)
    .replace(/^[\\/]/, '')
    .replace(/\\/g, '/');
  const dir = dirname(rel); // 例: Models/Architect
  return dir === '.' ? 'App' : 'App\\' + dir.split('/').join('\\');
}

/** コメント・文字列リテラルを除去して誤検知を減らす */
function stripNoise(src) {
  return src
    .replace(/\/\*[\s\S]*?\*\//g, ' ') // ブロックコメント
    .replace(/\/\/[^\n]*/g, ' ') // 行コメント
    .replace(/#[^\n]*/g, ' ') // #コメント
    .replace(/'(?:\\.|[^'\\])*'/g, "''") // シングルクオート文字列
    .replace(/"(?:\\.|[^"\\])*"/g, '""'); // ダブルクオート文字列
}

function main() {
  let input;
  try {
    input = JSON.parse(readFileSync(0, 'utf8'));
  } catch {
    process.exit(0);
  }

  const filePath = input?.tool_input?.file_path || '';
  if (!filePath || !TARGET.test(filePath) || EXCLUDE.test(filePath)) {
    process.exit(0);
  }

  let raw;
  try {
    raw = readFileSync(filePath, 'utf8');
  } catch {
    process.exit(0); // 削除済み等は対象外
  }

  const nsMatch = raw.match(/namespace\s+([^;]+);/);
  if (!nsMatch) process.exit(0); // 名前空間なしファイルは対象外
  const ns = nsMatch[1].trim();

  // このファイルの想定ディレクトリ（同名前空間クラスの存在確認用）
  const fileDir = dirname(filePath);

  // このファイル自身が定義するクラス/IF/trait/enum 名
  const selfDefined = new Set();
  for (const m of raw.matchAll(/\b(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/g)) {
    selfDefined.add(m[1]);
  }

  // use インポート（短縮名 / エイリアス）
  const imports = new Set();
  for (const m of raw.matchAll(/^use\s+([^;]+);/gm)) {
    let u = m[1].trim();
    if (/^(function|const)\s/i.test(u)) continue;
    const asMatch = u.match(/\s+as\s+(\w+)$/i);
    if (asMatch) {
      imports.add(asMatch[1]);
      continue;
    }
    const parts = u.split('\\');
    imports.add(parts[parts.length - 1]);
  }

  const appIndex = buildAppClassIndex();
  const code = stripNoise(raw);

  // 参照抽出: `Foo::` と `new Foo`
  const referenced = new Map(); // name => 最初の行番号
  function recordRef(name, matchIndex) {
    if (referenced.has(name)) return;
    const line = code.slice(0, matchIndex).split('\n').length;
    referenced.set(name, line);
  }

  // 静的参照 Foo::  （直前が \ . -> :: $ 英数字 でないこと）
  for (const m of code.matchAll(/(^|[^\w\\>$:])([A-Z][A-Za-z0-9_]*)::/g)) {
    recordRef(m[2], m.index + m[1].length);
  }
  // new Foo   （new \Foo は除外＝バックスラッシュ無しのみ）
  for (const m of code.matchAll(/\bnew\s+([A-Z][A-Za-z0-9_]*)\b/g)) {
    recordRef(m[1], m.index);
  }

  const violations = [];
  for (const [name, line] of referenced) {
    if (RESERVED.has(name.toLowerCase())) continue;
    if (imports.has(name)) continue;
    if (selfDefined.has(name)) continue;
    // 同名前空間（同ディレクトリ）に実体があれば import 不要
    if (existsSync(join(fileDir, name + '.php'))) continue;
    // app 内のどこかに実体がある = import 漏れの可能性大
    const candidates = appIndex.get(name);
    if (!candidates || candidates.length === 0) continue; // 実体なし(グローバル/vendor) → 対象外
    // 同ディレクトリの候補しか無いケースは上で弾かれる。ここは別ディレクトリの実体。
    const fqns = candidates.map((p) => `${pathToFqnPrefix(p)}\\${name}`);
    violations.push({ name, line, fqns });
  }

  if (violations.length === 0) process.exit(0);

  const lines = violations
    .map((v) => {
      const use = v.fqns.length === 1 ? `use ${v.fqns[0]};` : v.fqns.map((f) => `use ${f};`).join(' もしくは ');
      return `  - ${v.name}（${v.line}行目付近）→ 追加すべき: ${use}`;
    })
    .join('\n');

  output({
    decision: 'block',
    reason:
      `【import 漏れの疑い / Class not found の恐れ】${filePath}\n` +
      `現在の名前空間「${ns}」で、use されていないクラスを短縮名で参照しています。\n` +
      `PHP はこれを存在しない「${ns}\\<クラス名>」として解決するため、その行が実行された瞬間に\n` +
      `「Class not found」で落ちます（本番500エラーの典型パターン）。\n` +
      `該当:\n${lines}\n` +
      `対応: ファイル冒頭に上記の use 文を追加してください（別名前空間の同名クラスに注意）。\n` +
      `※ このクラスを意図的に別物として使っている場合のみ、完全修飾（先頭に \\）で書くか正しい use を記載してください。`,
  });
}

main();
