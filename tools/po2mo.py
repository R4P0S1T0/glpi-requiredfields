#!/usr/bin/env python3
"""Compile locales/*.po to .mo without gettext tools: python3 tools/po2mo.py locales/pt_BR.po
shortcut: handles only single-line msgid/msgstr (no plurals or multiline); use msgfmt if that changes."""
import ast
import struct
import sys

for po in sys.argv[1:]:
    entries, msgid, header = {}, None, []
    for line in open(po, encoding="utf-8"):
        line = line.strip()
        if line.startswith("msgid "):
            msgid = ast.literal_eval(line[6:])
        elif line.startswith("msgstr "):
            entries[msgid] = ast.literal_eval(line[7:])
        elif line.startswith('"') and msgid == "":
            header.append(ast.literal_eval(line))
    entries[""] = "".join(header)
    keys = sorted(entries)
    ids = b"".join(k.encode() + b"\0" for k in keys)
    strs = b"".join(entries[k].encode() + b"\0" for k in keys)
    n, start = len(keys), 7 * 4 + 16 * len(keys)
    offs, o = [], 0
    for k in keys:
        offs += [len(k.encode()), start + o]
        o += len(k.encode()) + 1
    toffs, o = [], 0
    for k in keys:
        toffs += [len(entries[k].encode()), start + len(ids) + o]
        o += len(entries[k].encode()) + 1
    out = struct.pack("Iiiiiii", 0x950412DE, 0, n, 28, 28 + 8 * n, 0, 0)
    out += struct.pack(f"{2 * n}i", *offs) + struct.pack(f"{2 * n}i", *toffs) + ids + strs
    open(po[:-3] + ".mo", "wb").write(out)
