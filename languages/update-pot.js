/**
 * WordPress i18n POT/PO updater
 * Scans all plugin PHP files and regenerates woo_ua.pot,
 * then merges new strings into each .po file (preserving existing translations).
 */

const fs   = require('fs');
const path = require('path');

const ROOT        = path.resolve(__dirname, '..');
const LANG_DIR    = __dirname;
const TEXT_DOMAIN = 'woo_ua';
const NOW         = new Date().toISOString().slice(0, 16).replace('T', ' ') + '+0000';

// Directories / files to skip (third-party)
const SKIP_DIRS = [
  'includes/action-scheduler',
  'includes/addons/twilio_sms/lib',
  'includes/EDD_SL_Plugin_Updater.php',
  'languages',
  'node_modules',
  '.git',
];

// ── 1. Collect all PHP files ──────────────────────────────────────────────────
function collectPhpFiles(dir, files = []) {
  for (const entry of fs.readdirSync(dir)) {
    const full = path.join(dir, entry);
    const rel  = path.relative(ROOT, full).replace(/\\/g, '/');
    if (SKIP_DIRS.some(s => rel.startsWith(s) || rel === s)) continue;
    const stat = fs.statSync(full);
    if (stat.isDirectory()) collectPhpFiles(full, files);
    else if (entry.endsWith('.php')) files.push(full);
  }
  return files;
}

// ── 2. Extract translated strings from PHP source ─────────────────────────────
// Regex pieces (built from template literals to avoid backslash hell):
// PHP single-quoted string:  '([^'\\]|\\.)*'
// PHP double-quoted string:  "([^"\\]|\\.)*"
const SQ = `'((?:[^'\\\\]|\\\\.)*)'`;   // capture group: single-quoted content
const DQ = `"((?:[^"\\\\]|\\\\.)*)"`;   // capture group: double-quoted content
const STR = `(?:${SQ}|${DQ})`;           // either quote style

// Simple family: __(), _e(), esc_html__(), esc_html_e(), esc_attr__(), esc_attr_e(), esc_js()
const SIMPLE_FN = `(?:esc_html_e|esc_html__|esc_attr_e|esc_attr__|esc_js|_e|__)`;
const SIMPLE_RE = new RegExp(
  `${SIMPLE_FN}\\s*\\(\\s*${STR}\\s*,\\s*['"]${TEXT_DOMAIN}['"]`,
  'g'
);

// Plural family: _n('singular','plural',$n,'woo_ua')
const PLURAL_RE = new RegExp(
  `_n\\s*\\(\\s*${STR}\\s*,\\s*${STR}[^)]*['"]${TEXT_DOMAIN}['"]`,
  'g'
);

function unescapePhpStr(s) {
  return s.replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, '\\').replace(/\\n/g, '\n').replace(/\\t/g, '\t');
}

function lineOf(content, index) {
  let line = 1;
  for (let i = 0; i < index; i++) {
    if (content[i] === '\n') line++;
  }
  return line;
}

function extractStrings(content, relPath) {
  const results = [];

  let m;

  // Reset lastIndex
  SIMPLE_RE.lastIndex = 0;
  while ((m = SIMPLE_RE.exec(content)) !== null) {
    // m[1] = single-quoted, m[2] = double-quoted
    const raw = m[1] !== undefined ? m[1] : m[2];
    if (raw === undefined || raw === null) continue;
    results.push({
      msgid: unescapePhpStr(raw),
      file:  relPath,
      line:  lineOf(content, m.index),
    });
  }

  PLURAL_RE.lastIndex = 0;
  while ((m = PLURAL_RE.exec(content)) !== null) {
    // Groups: 1,2 = singular (sq/dq), 3,4 = plural (sq/dq)
    const singRaw = m[1] !== undefined ? m[1] : m[2];
    const plurRaw = m[3] !== undefined ? m[3] : m[4];
    if (!singRaw) continue;
    results.push({
      msgid:        unescapePhpStr(singRaw),
      msgid_plural: plurRaw ? unescapePhpStr(plurRaw) : undefined,
      file:         relPath,
      line:         lineOf(content, m.index),
    });
  }

  return results;
}

// ── 3. Build master string map ────────────────────────────────────────────────
const phpFiles  = collectPhpFiles(ROOT);
const stringMap = new Map(); // msgid → { msgid_plural?, refs: Set }

for (const file of phpFiles) {
  const content = fs.readFileSync(file, 'utf8');
  const rel     = path.relative(ROOT, file).replace(/\\/g, '/');
  for (const entry of extractStrings(content, rel)) {
    const key = entry.msgid;
    if (!stringMap.has(key)) {
      stringMap.set(key, { msgid_plural: entry.msgid_plural, refs: new Set() });
    }
    const rec = stringMap.get(key);
    rec.refs.add(`${entry.file}:${entry.line}`);
    if (entry.msgid_plural && !rec.msgid_plural) rec.msgid_plural = entry.msgid_plural;
  }
}

console.log(`Extracted ${stringMap.size} unique strings from ${phpFiles.length} PHP files.`);

// ── 4. PO string quoting ──────────────────────────────────────────────────────
function quotePoString(s) {
  if (!s) return '""';
  const escaped = s
    .replace(/\\/g, '\\\\')
    .replace(/"/g, '\\"')
    .replace(/\n/g, '\\n"\n"')
    .replace(/\t/g, '\\t')
    .replace(/\r/g, '');
  return `"${escaped}"`;
}

function poEntry(msgid, msgid_plural, refs, msgstr, msgstr_plural) {
  if (msgstr === undefined) msgstr = '';
  if (!msgstr_plural) msgstr_plural = ['', ''];

  let out = '';
  // Reference comments (group onto lines ≤ 78 chars)
  const refArr = [...refs].sort();
  let line = '#:';
  for (const r of refArr) {
    if (line.length + 1 + r.length > 78 && line !== '#:') {
      out += line + '\n';
      line = '#: ' + r;
    } else {
      line += ' ' + r;
    }
  }
  out += line + '\n';

  if (msgid_plural) out += '#, php-format\n';
  out += `msgid ${quotePoString(msgid)}\n`;
  if (msgid_plural) {
    out += `msgid_plural ${quotePoString(msgid_plural)}\n`;
    out += `msgstr[0] ${quotePoString(msgstr_plural[0] || '')}\n`;
    out += `msgstr[1] ${quotePoString(msgstr_plural[1] || '')}\n`;
  } else {
    out += `msgstr ${quotePoString(msgstr)}\n`;
  }
  return out;
}

// ── 5. Write .pot file ────────────────────────────────────────────────────────
const potHeader = `# Ultimate WooCommerce Auction Pro - Business
# Copyright (C) ${new Date().getFullYear()}
# This file is distributed under the same license as the plugin.
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: Ultimate WooCommerce Auction Pro - Business\\n"
"Report-Msgid-Bugs-To: \\n"
"POT-Creation-Date: ${NOW}\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"Language: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n"
"X-Generator: uwa-pot-updater 1.0\\n"

`;

let potContent = potHeader;
const sortedEntries = [...stringMap.entries()].sort((a, b) => a[0].localeCompare(b[0]));
for (const [msgid, rec] of sortedEntries) {
  potContent += poEntry(msgid, rec.msgid_plural, rec.refs) + '\n';
}

const potPath = path.join(LANG_DIR, 'woo_ua.pot');
fs.writeFileSync(potPath, potContent, 'utf8');
console.log(`Written:  languages/woo_ua.pot  (${stringMap.size} strings)`);

// ── 6. Parse existing .po file ────────────────────────────────────────────────
function parsePo(content) {
  const entries = new Map();
  // Split on blank lines between entries
  const blocks = content.split(/\n(?=\n)/);
  let currentBlock = [];
  const allBlocks = [];

  for (const b of content.split(/\n\n+/)) {
    allBlocks.push(b.trim());
  }

  for (const block of allBlocks) {
    if (!block) continue;
    const lines = block.split('\n');
    let msgid = null, msgstr = '', msgid_plural = null;
    const msgstr_plural = [];

    for (let i = 0; i < lines.length; i++) {
      const l = lines[i];
      const idMatch    = l.match(/^msgid\s+"(.*)"$/);
      const idpMatch   = l.match(/^msgid_plural\s+"(.*)"$/);
      const strMatch   = l.match(/^msgstr\s+"(.*)"$/);
      const stpMatch   = l.match(/^msgstr\[(\d+)\]\s+"(.*)"$/);
      const contMatch  = l.match(/^"(.*)"$/);

      if (idMatch)  { msgid  = idMatch[1]; }
      if (idpMatch) { msgid_plural = idpMatch[1]; }
      if (strMatch) { msgstr = strMatch[1]; }
      if (stpMatch) { msgstr_plural[parseInt(stpMatch[1])] = stpMatch[2]; }
      // Handle multi-line strings (continuation lines)
      if (contMatch && msgid !== null) {
        // Append to the last declared field
        if (stpMatch) { /* handled above */ }
        else if (strMatch) { /* handled above */ }
        else if (msgstr_plural.length > 0) {
          msgstr_plural[msgstr_plural.length - 1] += contMatch[1];
        } else if (msgstr !== undefined) {
          msgstr += contMatch[1];
        }
      }
    }

    if (msgid !== null && msgid !== '') {
      const unescape = s => (s || '')
        .replace(/\\n/g, '\n').replace(/\\t/g, '\t')
        .replace(/\\"/g, '"').replace(/\\\\/g, '\\');
      entries.set(unescape(msgid), {
        msgstr: unescape(msgstr),
        msgid_plural: msgid_plural ? unescape(msgid_plural) : null,
        msgstr_plural: msgstr_plural.map(s => unescape(s || '')),
      });
    }
  }
  return entries;
}

// Extract the raw header block from a .po file (up to first msgid "" entry's end)
function extractPoHeader(content) {
  // The header is the block that starts with msgid ""
  const match = content.match(/^([\s\S]*?msgstr[\s\S]*?\n)\n/m);
  return match ? match[1] + '\n\n' : null;
}

// ── 7. Update each .po file ───────────────────────────────────────────────────
const poFiles = fs.readdirSync(LANG_DIR).filter(f => /^woo_ua-.+\.po$/.test(f));

for (const poFile of poFiles) {
  const poPath    = path.join(LANG_DIR, poFile);
  const content   = fs.readFileSync(poPath, 'utf8');
  const lang      = poFile.replace(/^woo_ua-/, '').replace(/\.po$/, '');
  const existing  = parsePo(content);
  const headerRaw = extractPoHeader(content);

  // Update POT-Creation-Date in existing header
  let header = headerRaw || '';
  if (header) {
    header = header.replace(/POT-Creation-Date:[^\n]*\n/, `POT-Creation-Date: ${NOW}\n`);
  } else {
    // Fallback minimal header
    header = `msgid ""\nmsgstr ""\n"Language: ${lang}\\n"\n"Content-Type: text/plain; charset=UTF-8\\n"\n\n`;
  }

  let newContent = header;
  let added = 0, kept = 0;

  for (const [msgid, rec] of sortedEntries) {
    const prev          = existing.get(msgid);
    const msgstr        = prev ? prev.msgstr        : '';
    const msgstr_plural = prev ? prev.msgstr_plural : ['', ''];
    if (!prev) added++; else kept++;
    newContent += poEntry(msgid, rec.msgid_plural, rec.refs, msgstr, msgstr_plural) + '\n';
  }

  fs.writeFileSync(poPath, newContent, 'utf8');
  console.log(`Updated:  languages/${poFile}  (${kept} kept, ${added} new)`);
}

console.log('\nAll done. Compile .mo files from each updated .po using Poedit or msgfmt.');
