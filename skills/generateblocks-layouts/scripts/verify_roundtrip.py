#!/usr/bin/env python3
"""
verify_roundtrip — prove an MCP/REST write did not corrupt block markup.

Compares the markup you sent against the raw `post_content` you read back and
names the specific mangling when they differ. A 200 response proves nothing:
the common MCP write failures (escape reversal, re-serialization, wpautop,
wp_kses) all return success and break the block on the next editor open.

    # write, then re-read with context=edit and save content.raw
    python3 verify_roundtrip.py --local hero-section.html --remote fetched-raw.html

`--local` may be a full page or just the section you spliced in; it is matched
as a subtree of `--remote`. Use `-` for stdin on either side.

Exit 0 = the transport was honest. Exit 1 = named failures.
See references/mcp-publishing.md for what each failure means and what to do.
"""
import re, json, sys, argparse

_BS = chr(92)
_U = lambda c: _BS + 'u' + c
ESCAPES = [_U('002d') + _U('002d'), _U('003c'), _U('003e'), _U('0026'), _U('0022')]

def unsub(s):
    """Reverse the five WordPress substitutions so the JSON can be parsed."""
    s = s.replace(_U('002d') + _U('002d'), '--')
    s = s.replace(_U('003c'), '<').replace(_U('003e'), '>')
    s = s.replace(_U('0026'), chr(38))
    return s.replace(_U('0022'), _BS + chr(34))

BLOCK_RE = re.compile(r'<!--\s+/?wp:([a-z][a-z0-9-]*(?:/[a-z][a-z0-9-]*)?)\s*(\{.*?\})?\s*/?-->',
                      re.DOTALL)

def top_keys(raw):
    """Depth-1 JSON keys in source order (no parsing — order must be preserved)."""
    d, i, out = 0, 0, []
    while i < len(raw):
        c = raw[i]
        if c == '{': d += 1
        elif c == '}': d -= 1
        elif c == '"' and d == 1:
            j = raw.index('"', i + 1)
            if raw[j+1:j+2] == ':': out.append(raw[i+1:j])
            i = j
        i += 1
    return out

def scan(text):
    """Index opening block delimiters that carry attributes, keyed by uniqueId."""
    by_id, anon, names = {}, [], []
    for m in BLOCK_RE.finditer(text):
        name, raw = m.group(1), m.group(2)
        names.append(name)
        if not raw:
            continue
        try:
            attrs = json.loads(unsub(raw))
        except ValueError:
            attrs = None
        uid = attrs.get('uniqueId') if isinstance(attrs, dict) else None
        rec = {'name': name, 'raw': raw, 'attrs': attrs, 'uid': uid}
        if uid:
            by_id.setdefault(uid, rec)
        else:
            anon.append(rec)
    return by_id, anon, names

def read(path):
    if path == '-':
        return sys.stdin.read()
    with open(path, encoding='utf-8', newline='') as fh:
        return fh.read()

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--local', required=True,
                    help='markup you sent (file, or - for stdin)')
    ap.add_argument('--remote', required=True,
                    help='content.raw you read back (file, or - for stdin)')
    ap.add_argument('--strict-whitespace', action='store_true',
                    help='fail on whitespace-only drift instead of warning')
    a = ap.parse_args()

    local, remote = read(a.local), read(a.remote)
    fails, warns, oks = [], [], []
    F, W, O = fails.append, warns.append, oks.append

    l_by_id, l_anon, l_names = scan(local)
    r_by_id, r_anon, r_names = scan(remote)
    print(f"local {len(l_names)} delimiters / remote {len(r_names)} delimiters")

    # --- the happy path ------------------------------------------------------
    if local in remote:
        print("  OK    section survived byte-identical\n\nNO FAILURES")
        return 0

    # --- rendered instead of raw --------------------------------------------
    if l_names and not r_names:
        F("remote has no block delimiters — you read content.rendered, not "
          "content.raw (or the write path stripped them)")
        for m in fails: print("  FAIL ", m)
        print(f"\n{len(fails)} FAILURE(S)")
        return 1

    # --- dropped blocks ------------------------------------------------------
    missing = [uid for uid in l_by_id if uid not in r_by_id]
    if missing:
        F(f"{len(missing)} block(s) absent from the read-back -> {missing[:4]}")
    else:
        O(f"all {len(l_by_id)} identified blocks present in the read-back")

    # --- per-block attribute integrity --------------------------------------
    reversed_escapes, reordered, changed, ws_only = [], [], [], []
    for uid, lb in l_by_id.items():
        rb = r_by_id.get(uid)
        if rb is None or lb['raw'] == rb['raw']:
            continue
        l_esc = sum(lb['raw'].count(e) for e in ESCAPES)
        r_esc = sum(rb['raw'].count(e) for e in ESCAPES)
        if l_esc and r_esc < l_esc:
            reversed_escapes.append(uid)
        elif lb['attrs'] is not None and lb['attrs'] == rb['attrs']:
            if top_keys(lb['raw']) != top_keys(rb['raw']):
                reordered.append(uid)
            else:
                ws_only.append(uid)
        else:
            keys = sorted(set(list((lb['attrs'] or {}).keys()) +
                              list((rb['attrs'] or {}).keys())))
            diff = [k for k in keys
                    if (lb['attrs'] or {}).get(k) != (rb['attrs'] or {}).get(k)]
            changed.append(f"{uid}:{','.join(diff[:3]) or 'unparseable'}")

    if reversed_escapes:
        F(f"escape substitutions reversed in {len(reversed_escapes)} block(s) -> "
          f"{reversed_escapes[:4]}; the transport JSON-decoded and re-encoded "
          "post_content (mcp-publishing.md §4)")
    if reordered:
        F(f"attribute key order changed in {len(reordered)} block(s) -> "
          f"{reordered[:4]}; the server ran parse_blocks/serialize_blocks")
    if changed:
        F(f"attribute values changed in {len(changed)} block(s) -> {changed[:4]}")
    if ws_only:
        (F if a.strict_whitespace else W)(
            f"attribute whitespace drift in {len(ws_only)} block(s) -> {ws_only[:4]}")
    if not (reversed_escapes or reordered or changed or ws_only) and l_by_id:
        O("block attributes identical for every uniqueId")

    # --- content-filter artifacts -------------------------------------------
    def artifacts(text):
        return len(re.findall(r'<(?:p|br\s*/?)>\s*<!--\s+/?wp:', text)) + \
               len(re.findall(r'-->\s*<(?:p|br\s*/?)>', text))
    l_art, r_art = artifacts(local), artifacts(remote)
    if r_art > l_art:
        F(f"wpautop artifacts around {r_art - l_art} delimiter(s) — content "
          "filters ran on the write path")
    else:
        O("no wpautop artifacts introduced")

    l_svg, r_svg = local.count('<svg'), remote.count('<svg')
    if missing:
        W("skipped the inline-SVG check — a dropped block already explains any loss")
    elif r_svg < l_svg:
        F(f"inline SVG lost: {l_svg} sent, {r_svg} returned — wp_kses stripped it; "
          "write as a user with unfiltered_html")
    elif l_svg:
        O(f"all {l_svg} inline SVG element(s) survived")

    l_amp = len(re.findall(r'&amp;', local))
    r_amp = len(re.findall(r'&amp;', remote))
    if r_amp > l_amp:
        W(f"{r_amp - l_amp} extra &amp; in the read-back — double entity encoding")

    # --- residual difference -------------------------------------------------
    if not fails:
        squash = lambda s: re.sub(r'\s+', ' ', s).strip()
        if squash(local) in squash(remote):
            (F if a.strict_whitespace else W)(
                "bytes differ but only outside block attributes (indentation or "
                "line endings); harmless for validation, still worth pinning down")
        else:
            F("section not found byte-identical in the read-back and no known "
              "signature matched — diff the two files by hand before writing again")

    for m in oks:   print("  OK   ", m)
    for m in warns: print("  WARN ", m)
    for m in fails: print("  FAIL ", m)
    print("\nNO FAILURES" if not fails else f"\n{len(fails)} FAILURE(S)")
    return 1 if fails else 0

if __name__ == '__main__':
    sys.exit(main())
