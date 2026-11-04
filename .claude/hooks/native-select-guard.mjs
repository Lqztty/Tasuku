#!/usr/bin/env node
/**
 * native-select-guard.mjs — PostToolUse hook (Edit|Write)
 *
 * 動的なリストを native <select> で書いたときに、既存の共通部品
 * AutocompleteSingle への置き換えを促す。
 *
 * 背景:
 *   native <select> のドロップダウンはブラウザ描画のため、
 *   - 検索・絞り込みができない（担当者や案件が増えると使い物にならない）
 *   - 配色をアプリ側で制御しきれない（実際にダークモードで
 *     半透明背景と白文字が重なり、選択肢が真っ白で読めない不具合が発生した）
 *   という問題がある。件数が多いリストほど影響が大きい。
 *
 * 検知対象（誤検知を避けるため、価値がある場合だけに絞る）:
 *   追加コード片の中の <select> のうち、内側で `.map(` を使って
 *   オプションを動的生成しているものだけ。
 *   優先度・ステータスのように <option> を数個ベタ書きする用途は
 *   native のままで適切なので対象外とする。
 *
 * 通過条件:
 *   - AutocompleteSingle 等を既に import している（= 併用中とみなす）
 *   - 「@native-select-ok: <理由>」を追加コードに記載（= 検討済みの証跡）
 */

import { readFileSync } from 'node:fs';

const ESCAPE_MARKER = /@native-select-ok:/;
// 既にオートコンプリート系部品を使っているファイルは併用とみなして通す
const AUTOCOMPLETE_IMPORT =
  /\b(?:AutocompleteSingle|AutocompleteMulti|AutocompleteSingleSelect|AutocompleteMultiSelect|ProjectSelect|CustomerSelect)\b/;

function output(obj) {
  process.stdout.write(JSON.stringify(obj));
  process.exit(0);
}

/**
 * 追加コード片から「動的オプションを持つ native select」を抽出する。
 * @returns {Array<{ line: number, snippet: string }>}
 */
function findDynamicSelects(code) {
  const hits = [];
  // <select ... > (大文字始まりの <Select> はコンポーネントなので対象外)
  const openTag = /<select\b/g;
  let m;
  while ((m = openTag.exec(code)) !== null) {
    const start = m.index;
    const closeIdx = code.indexOf('</select>', start);
    // 閉じタグが断片内に無い場合は、以降のコードを窓として見る
    const body = closeIdx === -1 ? code.slice(start, start + 1200) : code.slice(start, closeIdx);

    // オプションを .map( で動的生成しているかどうかだけを条件にする
    if (!/\.map\s*\(/.test(body)) continue;

    const line = code.slice(0, start).split('\n').length;
    const snippet = body.replace(/\s+/g, ' ').trim().slice(0, 100);
    hits.push({ line, snippet });
  }
  return hits;
}

let input;
try {
  input = JSON.parse(readFileSync(0, 'utf8'));
} catch {
  process.exit(0); // hook自身の異常でセッションを壊さない
}

const toolName = input?.tool_name || '';
const filePath = input?.tool_input?.file_path || '';

if (!/\.tsx$/.test(filePath) || /\.test\.|\.spec\.|__tests__/.test(filePath)) {
  process.exit(0);
}
if (!filePath.replace(/\\/g, '/').includes('/resources/js/')) {
  process.exit(0);
}

// 「追加された」コード片のみを検査対象にする（既存コードは指摘しない）
let addedCode = '';
if (toolName === 'Write') {
  addedCode = input?.tool_input?.content || '';
} else if (toolName === 'Edit') {
  addedCode = input?.tool_input?.new_string || '';
} else {
  process.exit(0);
}

if (ESCAPE_MARKER.test(addedCode)) {
  process.exit(0); // 検討済み宣言あり → 通過
}

const hits = findDynamicSelects(addedCode);
if (hits.length === 0) {
  process.exit(0);
}

// 既にオートコンプリート部品を使っているファイルなら、意図的な併用とみなす
let fileContent = '';
try {
  fileContent = readFileSync(filePath, 'utf8');
} catch {
  fileContent = '';
}
if (AUTOCOMPLETE_IMPORT.test(fileContent)) {
  process.exit(0);
}

const shown = hits
  .slice(0, 5)
  .map((h) => `  - ${h.line}行目付近: ${h.snippet}...`)
  .join('\n');
const more = hits.length > 5 ? `\n  ...他 ${hits.length - 5} 箇所` : '';

output({
  decision: 'block',
  reason:
    `【native <select> で動的リストを描画しています】${filePath}\n` +
    `${shown}${more}\n` +
    `\n` +
    `native <select> は次の問題があるため、件数が変動するリストには向きません。\n` +
    `  1. 検索・絞り込みができない（担当者や案件が増えると選びにくい）\n` +
    `  2. ドロップダウンがブラウザ描画で配色を制御しきれない\n` +
    `     （実際に、半透明背景＋白文字が重なりダークモードで選択肢が真っ白になる不具合が発生した）\n` +
    `\n` +
    `対応: 共通のオートコンプリート部品に置き換えてください。\n` +
    `  - 汎用: import { AutocompleteSingle, type AutocompleteOption } from '@/Components/molecules/autocomplete';\n` +
    `          options は { id, label } の配列。value / onChange / label / placeholder / allowClear を渡す。\n` +
    `  - ドメイン別の選択部品（顧客・案件など）は CLAUDE.md「共通処理再利用ゲート」の表を参照。\n` +
    `  - まだ部品が無いプロジェクトでは、先に Components/molecules/autocomplete/ に作ってから使う。\n` +
    `\n` +
    `例外: 選択肢が数個で固定のenum（優先度・ステータス等）は native のままが適切です。\n` +
    `その場合は追加したコードに「{/* @native-select-ok: 固定4択のため検索不要 */}」のように\n` +
    `理由を書けば、以後この編集では指摘しません。`,
});
