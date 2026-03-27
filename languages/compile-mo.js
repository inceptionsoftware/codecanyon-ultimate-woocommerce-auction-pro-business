/**
 * Compiles .po files to binary .mo files (GNU MO format, little-endian).
 * No external tools required.
 */

const fs   = require('fs');
const path = require('path');

const LANG_DIR = __dirname;

// ── Parse a .po file → array of {msgid, msgstr} ──────────────────────────────
function parsePo(content) {
  const pairs = [];
  const blocks = content.split(/\n\n+/);

  for (const block of blocks) {
    if (!block.trim()) continue;
    const lines = block.trim().split('\n');

    let msgid = null, msgstr = null;
    let inMsgid = false, inMsgstr = false;

    for (const l of lines) {
      if (l.startsWith('#')) { inMsgid = inMsgstr = false; continue; }

      const idM  = l.match(/^msgid\s+"(.*)"$/);
      const stM  = l.match(/^msgstr\s+"(.*)"$/);
      const cont = l.match(/^"(.*)"$/);

      if (idM)  { msgid  = idM[1];  inMsgid = true;  inMsgstr = false; continue; }
      if (stM)  { msgstr = stM[1];  inMsgstr = true; inMsgid  = false; continue; }
      if (cont) {
        if (inMsgid  && msgid  !== null) msgid  += cont[1];
        if (inMsgstr && msgstr !== null) msgstr += cont[1];
      }
    }

    // Skip plural-forms entries and the header entry (msgid "")
    if (msgid === null || msgid === '') continue;
    // Skip msgstr[n] plural entries for now (simplified)
    if (msgstr === null) continue;

    const unescape = s => s
      .replace(/\\n/g, '\n').replace(/\\t/g, '\t')
      .replace(/\\r/g, '\r').replace(/\\"/g, '"').replace(/\\\\/g, '\\');

    pairs.push({ msgid: unescape(msgid), msgstr: unescape(msgstr) });
  }
  return pairs;
}

// ── Write binary MO file ──────────────────────────────────────────────────────
function writeMo(pairs, outPath) {
  // Sort by msgid (required by MO spec for binary search)
  pairs.sort((a, b) => a.msgid < b.msgid ? -1 : a.msgid > b.msgid ? 1 : 0);

  const N = pairs.length;

  // Encode all strings as UTF-8 Buffers
  const origBufs  = pairs.map(p => Buffer.from(p.msgid,  'utf8'));
  const transBufs = pairs.map(p => Buffer.from(p.msgstr, 'utf8'));

  // MO file layout:
  //   0:  magic (4)
  //   4:  revision (4) = 0
  //   8:  N strings (4)
  //   12: offset of orig table (4)
  //   16: offset of trans table (4)
  //   20: hash table size (4) = 0
  //   24: hash table offset (4) = 28
  //   28: orig  table: N × [len(4) + off(4)]
  //   28 + N*8: trans table: N × [len(4) + off(4)]
  //   28 + N*16: string data

  const HEADER_SIZE  = 28;
  const TABLES_SIZE  = N * 8 * 2;   // orig + trans
  const stringsStart = HEADER_SIZE + TABLES_SIZE;

  // Calculate offsets for all strings (orig first, then trans)
  let offset = stringsStart;
  const origOffsets  = origBufs.map(b  => { const o = offset; offset += b.length + 1; return o; });
  const transOffsets = transBufs.map(b => { const o = offset; offset += b.length + 1; return o; });

  const totalSize = offset;
  const buf = Buffer.alloc(totalSize, 0);

  // Magic (little-endian)
  buf.writeUInt32LE(0x950412de, 0);
  // Revision
  buf.writeUInt32LE(0, 4);
  // N
  buf.writeUInt32LE(N, 8);
  // Orig table offset
  buf.writeUInt32LE(HEADER_SIZE, 12);
  // Trans table offset
  buf.writeUInt32LE(HEADER_SIZE + N * 8, 16);
  // Hash table size = 0
  buf.writeUInt32LE(0, 20);
  // Hash table offset
  buf.writeUInt32LE(HEADER_SIZE + N * 8 * 2, 24);

  // Orig table
  for (let i = 0; i < N; i++) {
    buf.writeUInt32LE(origBufs[i].length,  HEADER_SIZE + i * 8);
    buf.writeUInt32LE(origOffsets[i],       HEADER_SIZE + i * 8 + 4);
  }

  // Trans table
  const transTableStart = HEADER_SIZE + N * 8;
  for (let i = 0; i < N; i++) {
    buf.writeUInt32LE(transBufs[i].length,  transTableStart + i * 8);
    buf.writeUInt32LE(transOffsets[i],       transTableStart + i * 8 + 4);
  }

  // String data
  for (let i = 0; i < N; i++) {
    origBufs[i].copy(buf, origOffsets[i]);
    // null terminator already 0 from alloc
  }
  for (let i = 0; i < N; i++) {
    transBufs[i].copy(buf, transOffsets[i]);
  }

  fs.writeFileSync(outPath, buf);
}

// ── Process all .po files ─────────────────────────────────────────────────────
const poFiles = fs.readdirSync(LANG_DIR).filter(f => /^woo_ua-.+\.po$/.test(f));

for (const poFile of poFiles) {
  const poPath = path.join(LANG_DIR, poFile);
  const moPath = poPath.replace(/\.po$/, '.mo');

  const content = fs.readFileSync(poPath, 'utf8');
  const pairs   = parsePo(content);

  // Only include entries with a non-empty msgstr
  const translated = pairs.filter(p => p.msgstr && p.msgstr.trim() !== '');

  writeMo(translated, moPath);
  console.log(`Compiled: ${poFile} → ${path.basename(moPath)}  (${translated.length} translated strings)`);
}

console.log('\nDone.');
