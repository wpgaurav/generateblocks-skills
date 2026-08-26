#!/usr/bin/env python3
"""
preflight — validate hand-authored GenerateBlocks markup before delivery.

Implements the recovery-rules.md §7 checklist as executable assertions, plus the
field-notes.md and design-quality.md deltas. Catches the failures that are
invisible on inspection: no-op escape tables, misordered keys, CSS cache branches
missing from styles, class-list drift, and thick rounded surfaces.

    python3 preflight.py section.html --post-id 1173976
    python3 preflight.py page.html --links 62      # also assert internal link count

Exit 0 = clean, 1 = failures. Warnings never fail the run.
"""
import re, json, sys, argparse, collections

_BS = chr(92)
_U = lambda c: _BS + 'u' + c

def unsub(s):
    """Reverse the five WordPress substitutions so the JSON can be parsed."""
    s = s.replace(_U('002d') + _U('002d'), '--')
    s = s.replace(_U('003c'), '<').replace(_U('003e'), '>')
    s = s.replace(_U('0026'), chr(38))
    return s.replace(_U('0022'), _BS + chr(34))

ORDER = {
    'element': ['uniqueId','tagName','styles','css','globalClasses','htmlAttributes','align','className'],
    'text':    ['uniqueId','tagName','content','styles','css','globalClasses','htmlAttributes',
                'icon','iconLocation','iconOnly','className'],
    'media':   ['uniqueId','tagName','styles','css','globalClasses','htmlAttributes',
                'mediaId','linkHtmlAttributes','className'],
    'shape':   ['uniqueId','html','styles','css','globalClasses','htmlAttributes','className'],
    'query':   ['uniqueId','tagName','styles','css','globalClasses','htmlAttributes',
                'queryType','paginationType','query','inheritQuery','showTemplateSelector','className'],
    'looper':  ['uniqueId','tagName','styles','css','globalClasses','htmlAttributes','className'],
    'loop-item': ['uniqueId','tagName','styles','css','globalClasses','htmlAttributes','className'],
    'query-no-results': ['uniqueId','tagName','styles','css','globalClasses','htmlAttributes','className'],
    'query-page-numbers': ['uniqueId','tagName','styles','css','globalClasses',
                           'htmlAttributes','midSize','className'],
}

SUPPORTED_AT_RULES = ('@media', '@supports', '@container')


def style_nodes(node):
    """Yield every styles mapping, including selector and at-rule branches."""
    if not isinstance(node, dict):
        return
    yield node
    for value in node.values():
        if isinstance(value, dict):
            yield from style_nodes(value)


def style_has_property(styles, prefix):
    prefix = prefix.lower()
    return any(
        isinstance(key, str) and key.lower().startswith(prefix)
        for node in style_nodes(styles)
        for key, value in node.items()
        if not isinstance(value, dict)
    )


def style_has_branch(styles, text):
    text = text.lower()
    return any(
        isinstance(key, str) and text in key.lower() and isinstance(value, dict)
        for node in style_nodes(styles)
        for key, value in node.items()
    )


def style_has_at_rule_family(styles, family):
    family = family.lower()
    return any(
        isinstance(key, str) and key.lower().startswith(family) and isinstance(value, dict)
        for node in style_nodes(styles)
        for key, value in node.items()
    )


def _length_px(value):
    """Return the largest absolute px-equivalent length found in a CSS value."""
    if not isinstance(value, str):
        value = str(value)
    found = []
    for number, unit in re.findall(r'(?<![\w.#-])(-?\d*\.?\d+)\s*(px|rem|em)?\b', value, re.I):
        amount = abs(float(number))
        found.append(amount * (16 if unit.lower() in {'rem', 'em'} else 1))
    return max(found, default=0)


def _plain_styles(node):
    return {key: value for key, value in node.items() if not isinstance(value, dict)}


def _has_positive_radius(styles):
    return any(
        isinstance(key, str)
        and 'border' in key.lower()
        and 'radius' in key.lower()
        and _length_px(value) > 0
        for key, value in styles.items()
    )


def _has_thick_border(styles):
    for key, value in styles.items():
        low = key.lower() if isinstance(key, str) else ''
        if not low.startswith('border') or 'radius' in low or 'color' in low or 'style' in low:
            continue
        if _length_px(value) >= 2:
            return True
    return False


def thick_rounded_scopes(styles, inherited_radius=False, inherited_border=False, scope='base'):
    """Find style branches where a rounded surface and thick border coexist."""
    if not isinstance(styles, dict):
        return []
    plain = _plain_styles(styles)
    radius = inherited_radius or _has_positive_radius(plain)
    border = inherited_border or _has_thick_border(plain)
    failures = [scope] if radius and border else []

    for key, value in styles.items():
        if not isinstance(value, dict):
            continue
        # At-rules and same-element states inherit the base surface. Child
        # selectors such as `svg` or `> .child` describe another element.
        same_surface = key.startswith('@') or bool(re.match(r'^&(?::|\.|\[)[^ >+~]*$', key))
        failures.extend(
            thick_rounded_scopes(
                value,
                radius if same_surface else False,
                border if same_surface else False,
                f'{scope} > {key}',
            )
        )
    return failures


def structured_style_issues(styles, context='root', scope='styles'):
    """Validate CSS Mode's one-selector/one-at-rule branch grammar."""
    if not isinstance(styles, dict):
        return [f'{scope} is not an object']
    issues = []
    for key, value in styles.items():
        if not isinstance(value, dict):
            continue
        is_at_rule = isinstance(key, str) and key.startswith('@')
        if is_at_rule and not key.startswith(SUPPORTED_AT_RULES):
            issues.append(f'{scope}: unsupported at-rule {key}')
            continue
        if context == 'root':
            next_context = 'at-rule' if is_at_rule else 'selector'
        elif context == 'selector':
            if not is_at_rule:
                issues.append(f'{scope}: selector nested inside selector ({key})')
                continue
            next_context = 'selector-at-rule'
        elif context == 'at-rule':
            if is_at_rule:
                issues.append(f'{scope}: at-rule nested inside at-rule ({key})')
                continue
            next_context = 'at-rule-selector'
        else:
            issues.append(f'{scope}: branch exceeds one selector + one at-rule ({key})')
            continue
        issues.extend(structured_style_issues(value, next_context, f'{scope} > {key}'))
    return issues

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

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('file')
    ap.add_argument('--post-id', type=int,
                    help='require all GenerateBlocks uniqueIds to use this WordPress post ID')
    ap.add_argument('--links', type=int, help='expected count of unique internal hrefs')
    ap.add_argument('--allow-thick-rounded', action='store_true',
                    help='downgrade the thick-border + rounded-surface gate to a warning')
    a = ap.parse_args()
    if a.post_id is not None and a.post_id < 1:
        ap.error('--post-id must be a positive integer')
    src = open(a.file, encoding='utf-8').read()

    fails, warns, oks = [], [], []
    F, W, O = fails.append, warns.append, oks.append
    def chk(bad, msg): (F if bad else O)(msg)

    blocks = re.findall(r'<!-- wp:(generateblocks/[a-z-]+) (\{.*?\}) /?-->', src)
    print(f"{a.file}: {len(blocks)} GenerateBlocks blocks")

    # --- JSON validity -------------------------------------------------------
    bad = 0
    parsed_blocks = []
    for name, raw in blocks:
        try:
            attrs = json.loads(unsub(raw))
            parsed_blocks.append((name, raw, attrs))
        except Exception as e:
            bad += 1
            if bad <= 3: F(f"invalid JSON in {name}: {e}")
    chk(bad, f"JSON parses: {len(blocks)-bad}/{len(blocks)}")

    jsons = [r for _, r in blocks]
    # --- §1 the five substitutions ------------------------------------------
    chk([j for j in jsons if '--' in j], "literal '--' in block JSON (want 0)")
    chk([j for j in jsons if chr(38) in j], "literal '&' in block JSON (want 0)")
    chk([j for j in jsons if '<' in j or '>' in j], "literal '<'/'>' in block JSON (want 0)")
    chk(src.count(_BS + chr(34)), f"backslash-quote in JSON (want 0, use {_U('0022')})")

    # --- §2 structured styles / compiled css durability ---------------------
    css_values = []
    orphan_transition = []
    orphan_hover = []
    orphan_at_rules = []
    unsupported_css_at_rules = []
    style_structure = []
    thick_rounded = []

    for name, raw, attrs in parsed_blocks:
        uid = attrs.get('uniqueId', '?')
        styles = attrs.get('styles') if isinstance(attrs.get('styles'), dict) else {}
        css_value = attrs.get('css') if isinstance(attrs.get('css'), str) else ''
        if css_value:
            css_values.append(css_value)
        style_structure.extend(
            f'{name} {uid}: {issue}' for issue in structured_style_issues(styles)
        )
        thick_rounded.extend(
            f'{name} {uid}: {scope}' for scope in thick_rounded_scopes(styles)
        )

        if re.search(r'\btransition(?:-[a-z-]+)?\s*:', css_value) and not style_has_property(styles, 'transition'):
            orphan_transition.append(f'{name} {uid}')
        if ':hover' in css_value and not style_has_branch(styles, ':hover'):
            orphan_hover.append(f'{name} {uid}')
        for family in SUPPORTED_AT_RULES:
            if family in css_value and not style_has_at_rule_family(styles, family):
                orphan_at_rules.append(f'{name} {uid}: {family}')
        for at_rule in re.findall(r'@(?!media\b|supports\b|container\b)([a-z-]+)', css_value, re.I):
            unsupported_css_at_rules.append(f'{name} {uid}: @{at_rule}')

    chk(style_structure,
        f"structured styles exceed CSS Mode grammar (want 0){' -> '+str(style_structure[:3]) if style_structure else ''}")
    chk(orphan_transition,
        f"transition in css without styles source (want 0){' -> '+str(orphan_transition[:3]) if orphan_transition else ''}")
    chk(orphan_hover,
        f"hover selector in css without styles source (want 0){' -> '+str(orphan_hover[:3]) if orphan_hover else ''}")
    chk(orphan_at_rules,
        f"at-rule in css without styles source (want 0){' -> '+str(orphan_at_rules[:3]) if orphan_at_rules else ''}")
    chk(unsupported_css_at_rules,
        f"unsupported at-rules in local css (want 0){' -> '+str(unsupported_css_at_rules[:3]) if unsupported_css_at_rules else ''}")

    comma_space = [c for c in css_values if re.search(r'\([^)]*,\s', c)]
    if comma_space:
        W(f"{len(comma_space)} css strings keep spaces after function commas; valid existing CSS, but build_css() emits compact new output")
    else:
        O("compiled css uses compact function commas")
    chk([c for c in css_values if '\n' in c], "multiline css strings (want 0)")
    bad_calc = [c for c in css_values if re.search(r'\d(?:rem|em|px|vw|vh|%)\+', c)]
    chk(bad_calc, "calc/clamp '+' without surrounding spaces (want 0 — silently invalid)")

    if thick_rounded:
        message = f"rounded surfaces with border >=2px -> {thick_rounded[:4]}"
        (W if a.allow_thick_rounded else F)(message)
    else:
        O("no thick rounded surfaces")

    # --- §3 html attributes / links -----------------------------------------
    chk(re.findall(r'"htmlAttributes":\[', src), "htmlAttributes as array (want 0)")
    rel = re.findall(r'"href":"(?!https?://|#|mailto:|tel:|\{\{)([^"]*)"', src)
    chk(rel, f"relative hrefs in JSON (want 0){' -> '+str(rel[:3]) if rel else ''}")
    chk(re.findall(r'<a class="gb-element-[^"]*"[^>]*>\s*[^<\s][^<]*</a>', src),
        "element <a> containing raw text (want 0 — needs an inner block)")

    # --- stray comments ------------------------------------------------------
    stray = [c for c in re.findall(r'<!--(.*?)-->', src, re.S)
             if not c.strip().startswith(('wp:', '/wp:'))]
    chk(stray, "stray HTML comments (want 0)")

    # --- class-list convention ----------------------------------------------
    for kind in ('element', 'text', 'media', 'shape'):
        cls = re.findall(rf'class="(gb-{kind}[^"]*)"', src)
        if not cls: continue
        idf = sum(1 for c in cls if re.match(rf'gb-{kind}-\w+', c))
        basef = len(cls) - idf
        if idf and basef:
            W(f"{kind}: MIXED class order ({idf} id-first, {basef} base-first) — "
              f"pick one convention per block type")
        else:
            O(f"{kind} class order consistent ({'id-first' if idf else 'base-first'}, {len(cls)})")

    # --- text blocks: content / className ------------------------------------
    tblocks = [(raw, attrs) for n, raw, attrs in parsed_blocks if n.endswith('/text')]
    ct = [(raw, attrs) for raw, attrs in tblocks if 'content' in attrs]
    dynamic_ct = [
        attrs for raw, attrs in ct
        if isinstance(attrs.get('content'), str) and '{{' in attrs.get('content', '')
    ]
    static_ct = len(ct) - len(dynamic_ct)
    if static_ct:
        W(f"{static_ct}/{len(tblocks)} text blocks duplicate static `content`; preserve only when the measured target requires it")
    elif ct:
        O(f"text `content` is dynamic/intentional ({len(ct)})")
    else: O(f"text blocks omit `content` ({len(tblocks)})")

    # --- key order -----------------------------------------------------------
    bad = 0
    for name, raw in blocks:
        kind = name.split('/')[1]
        if kind not in ORDER: continue
        got = top_keys(raw)
        want = [k for k in ORDER[kind] if k in got]
        if got != want:
            bad += 1
            if bad <= 3: F(f"key order {kind}: got {got} want {want}")
    chk(bad, f"attribute key order: {len(blocks)-bad}/{len(blocks)}")

    # --- uniqueId collisions -------------------------------------------------
    ids = [attrs.get('uniqueId') for _, _, attrs in parsed_blocks]
    dupes = [k for k, v in collections.Counter(ids).items() if v > 1]
    chk(dupes, f"duplicate uniqueIds (want 0){' -> '+str(dupes[:4]) if dupes else ''}")
    if a.post_id is not None:
        namespace = re.compile(
            rf'^[a-z][a-z0-9-]*-{a.post_id}-[1-9][0-9]*[a-z]?$'
        )
        outside = [uid for uid in ids if not isinstance(uid, str) or not namespace.fullmatch(uid)]
        chk(outside, f"post-scoped uniqueIds for post {a.post_id}"
                     f"{' -> '+str(outside[:4]) if outside else ''}")

    # --- link count ----------------------------------------------------------
    if a.links is not None:
        uniq = set(re.findall(r'"href":"(https?://[^"#]+)"', src))
        chk(len(uniq) != a.links, f"unique hrefs: {len(uniq)} (want {a.links})")

    # --- report --------------------------------------------------------------
    for m in oks:   print("  OK   ", m)
    for m in warns: print("  WARN ", m)
    for m in fails: print("  FAIL ", m)
    print("\nNO FAILURES" if not fails else f"\n{len(fails)} FAILURE(S)")
    return 1 if fails else 0

if __name__ == '__main__':
    sys.exit(main())
