/**
 * harness-config.mjs — 共有ハーネス設定ローダ
 *
 * `.claude/harness.config.json` を読み込み、各フック（file-size-guard /
 * component-duplication-check / baseline生成器）へ設定を供給する共通モジュール。
 *
 * 設計原則:
 *   - 外部依存ゼロ（node組込のみ）。生徒プロジェクトへ .mjs をそのまま移植可能。
 *   - config不在・破損時は {} を返し、各フックがハードコード既定へフォールバックする
 *     （＝設定を置かないプロジェクトでも従来挙動で安全に動く）。
 *   - パスは常に repo-relative POSIX（`src/resources/js/...`）へ正規化して突き合わせる。
 */

import { readFileSync, existsSync } from 'node:fs';
import { resolve, dirname, relative, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
// .claude/hooks/ から2つ上がプロジェクトルート
export const PROJECT_ROOT = resolve(__dirname, '..', '..');

/** `.claude/harness.config.json` を読む。不在・破損時は {}（＝既定へフォールバック） */
export function loadConfig() {
  const path = join(PROJECT_ROOT, '.claude', 'harness.config.json');
  if (!existsSync(path)) return {};
  try {
    return JSON.parse(readFileSync(path, 'utf8'));
  } catch {
    return {};
  }
}

/** 正規表現メタ文字をエスケープ */
export function escapeRegExp(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * glob → 完全一致 RegExp。
 *   **  … 任意階層（区切り `/` を跨ぐ）。直後が `/` の場合は0階層も許容
 *   *   … `/` 以外の任意文字列
 *   ?   … `/` 以外の1文字
 * 例: `**​/*.g.dart` → /^(?:.*\/)?[^/]*\.g\.dart$/
 */
export function globToRegExp(glob) {
  let re = '';
  for (let i = 0; i < glob.length; i++) {
    const c = glob[i];
    if (c === '*') {
      if (glob[i + 1] === '*') {
        i++; // 2つ目の *
        if (glob[i + 1] === '/') {
          i++; // 続く /
          re += '(?:.*/)?'; // 任意階層（0階層可）
        } else {
          re += '.*';
        }
      } else {
        re += '[^/]*';
      }
    } else if (c === '?') {
      re += '[^/]';
    } else if (c === '/') {
      re += '/';
    } else {
      re += escapeRegExp(c);
    }
  }
  return new RegExp('^' + re + '$');
}

/** relPath が globs のいずれかにマッチするか */
export function matchesAnyGlob(relPath, globs) {
  return globs.some((g) => globToRegExp(g).test(relPath));
}

/** 絶対/相対いずれのパスも repo-relative POSIX へ正規化 */
export function toRelPosix(filePath) {
  const abs = resolve(PROJECT_ROOT, filePath);
  return relative(PROJECT_ROOT, abs).split('\\').join('/');
}

/** パス区切りを `/` に正規化（絶対のまま） */
export function toPosix(filePath) {
  return filePath.split('\\').join('/');
}
