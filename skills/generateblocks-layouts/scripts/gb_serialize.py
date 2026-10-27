"""
gb_serialize — GenerateBlocks V2 css compiler + block-attribute serializer.

Reproduces two things the plugin/WordPress do, so hand-authored markup round-trips:

  build_css(selector, styles)  -> the `css` attribute the plugin compiles from `styles`
  serialize_attrs(attrs)       -> WordPress serialize_block_attributes() incl. all
                                  six substitutions
  ordered(block_type, attrs)   -> canonical block.json key order, className last
  make_layout_id(...)          -> actual post ID or random four-digit layout scope
  make_unique_id(...)          -> {section}-{id_scope}-{sequence}{suffix}

The base/at-rule compiler was validated against 467 production blocks. It also
supports the one-selector/one-at-rule grammar exposed by Pro CSS Mode. Algorithm
documented in references/field-notes.md 6 and references/css-mode.md.

Usage:
    from gb_serialize import build_css, make_unique_id, serialize_attrs, ordered
    uid = make_unique_id('hero', 1173976, 1)
    a = ordered('element', {"uniqueId":uid,"tagName":"section","styles":S})
    a["css"] = build_css(f".gb-element-{uid}", S)
    print("<!-- wp:generateblocks/element " + serialize_attrs(a) + " -->")

See also scripts/preflight.py for the pre-delivery validation pass.
"""
import json
import os
import re
import secrets
from functools import cmp_to_key

def make_layout_id(post_id=None, used_ids=()):
    """Choose one numeric scope per layout; this does not create a WP record.

    Pass known occupied numeric scopes in used_ids when combining layouts.
    Reuse the returned value for all blocks in this layout.
    """
    if post_id is not None:
        if isinstance(post_id, bool) or not isinstance(post_id, int) or post_id < 1:
            raise ValueError("post_id must be a positive integer or None")
        return post_id
    occupied = set(used_ids)
    available = [number for number in range(1000, 10000) if number not in occupied]
    if not available:
        raise ValueError("no unused four-digit layout IDs remain")
    return secrets.choice(available)

def make_unique_id(section, post_id, sequence, suffix=''):
    """Return a layout-scoped ID using a real post ID or make_layout_id fallback."""
    if not isinstance(section, str) or not re.fullmatch(r'[a-z][a-z0-9-]*', section):
        raise ValueError("section must be a lowercase component name")
    if section.endswith('-'):
        raise ValueError("section must not end with a hyphen")
    if isinstance(post_id, bool) or not isinstance(post_id, int) or post_id < 1:
        raise ValueError("post_id must be a positive integer")
    if isinstance(sequence, bool) or not isinstance(sequence, int) or sequence < 1:
        raise ValueError("sequence must be a positive unpadded integer")
    if not isinstance(suffix, str) or not re.fullmatch(r'[a-z]?', suffix):
        raise ValueError("suffix must be empty or one lowercase letter")
    return f"{section}-{post_id}-{sequence}{suffix}"

# ---- camelCase -> kebab-case -------------------------------------------------
def kebab(prop):
    """camelCase key -> CSS property, as 2.5 does. Custom properties keep their case."""
    if prop.startswith('--'):
        return prop
    return re.sub(r'([a-z0-9]|(?=[A-Z]))([A-Z])', r'\1-\2', prop).lower()

_QUOTED = re.compile(r'("(?:[^"\\]|\\.)*"|\'(?:[^\'\\]|\\.)*\')')

def _outside_quotes(text, fn):
    """Apply fn to the unquoted parts of text only."""
    parts = _QUOTED.split(text)
    return ''.join(part if i % 2 else fn(part) for i, part in enumerate(parts))

def minify_value(v):
    """Native 2.5 value minification: no space after '(' or around ',', none before ')'.

    Operator spaces inside calc()/clamp() stay, because CSS math requires them.
    """
    def squeeze(part):
        part = re.sub(r'\s*,\s*', ',', part)
        part = re.sub(r'\(\s+', '(', part)
        return re.sub(r'\s+\)', ')', part)
    value = _URL.sub(_quote_url, str(v).strip())
    return _outside_quotes(value, squeeze).strip()

_URL = re.compile(r'url\s*\(\s*([\'"]?)([^)]*)\)?')

def _quote_url(match):
    """2.5 rewrites every url() as url('...'), keeping inner spaces."""
    inner = re.sub(r'^[\'"]|[\'"]$', '', match.group(2))
    return f"url('{inner}')"

# ---- declaration order (postcss-sorting "properties-order": "alphabetical") --
with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'css_shorthands.json'), encoding='utf-8') as _fh:
    _SHORTHANDS = json.load(_fh)['shorthands']
_VENDOR = re.compile(r'^-\w+-')

def _is_shorthand(short, longhand):
    children = _SHORTHANDS.get(short)
    if not children:
        return False
    return longhand in children or any(_is_shorthand(c, longhand) for c in children)

def _unprefixed(prop):
    name = _VENDOR.sub('', prop).lower()
    return name[4:] if name.startswith('osx-') else name

def _compare_decl(a, b):
    (ia, pa), (ib, pb) = a, b
    ua, ub = _unprefixed(pa), _unprefixed(pb)
    if _is_shorthand(ua, ub):
        return -1
    if _is_shorthand(ub, ua):
        return 1
    if ua == ub:
        prefixed_a, prefixed_b = bool(_VENDOR.match(pa)), bool(_VENDOR.match(pb))
        if not prefixed_a and prefixed_b:
            return 1
        if prefixed_a and not prefixed_b:
            return -1
        return ia - ib
    return -1 if ua <= ub else 1

def _sides(decls, prefix):
    found = {}
    for prop, value in decls:
        for side in ('top', 'right', 'bottom', 'left'):
            if side in prop:
                found[side] = value
                break
    return found

def _box(found):
    t, r, b, l = (found.get(k, '') for k in ('top', 'right', 'bottom', 'left'))
    if '' in (t, r, b, l):
        return ''
    if t == b and r == l:
        return t if t == r else f"{t} {r}"
    return f"{t} {r} {b} {l}"

def _border_side(decls):
    width = style = color = ''
    for prop, value in decls:
        if 'width' in prop:
            width = value
        elif 'style' in prop:
            style = value
        elif 'color' in prop:
            color = value
    return f"{width} {style} {color}" if width and style and color else ''

def _merge_longhand(decls):
    """Port of 2.5's gb-merge-longhand: complete longhand sets become appended shorthands."""
    out = list(decls)
    borders = [d for d in out if d[0].startswith('border-') and 'radius' not in d[0]]
    radii = [d for d in out if d[0].startswith('border-') and 'radius' in d[0]]
    margins = [d for d in out if d[0].startswith('margin-')]
    paddings = [d for d in out if d[0].startswith('padding-')]
    appended = []
    side_values = {}
    for side in ('top', 'right', 'bottom', 'left'):
        group = [d for d in borders if d[0].startswith('border-' + side)]
        value = _border_side(group)
        if value:
            side_values[side] = (value, group)
    if len(side_values) == 4 and len({v for v, _ in side_values.values()}) == 1:
        appended.append(('border', next(iter(side_values.values()))[0]))
        out = [d for d in out if d not in borders]
    else:
        for side, (value, group) in side_values.items():
            appended.append(('border-' + side, value))
            out = [d for d in out if d not in group]
    if len(radii) == 4:
        corners = {}
        for prop, value in radii:
            for corner in ('top-left', 'top-right', 'bottom-right', 'bottom-left'):
                if corner in prop:
                    corners[corner] = value
        tl, tr, br, bl = (corners.get(k, '') for k in ('top-left', 'top-right', 'bottom-right', 'bottom-left'))
        value = ('' if not (tl and tr and br and bl) else
                 (tl if tl == tr else f"{tl} {tr}") if (tl == br and tr == bl) else f"{tl} {tr} {br} {bl}")
        appended.append(('border-radius', value))
        out = [d for d in out if d not in radii]
    for name, group in (('margin', margins), ('padding', paddings)):
        if len(group) == 4:
            appended.append((name, _box(_sides(group, name))))
            out = [d for d in out if d not in group]
    return out + appended

def declarations(styles, sort_keys=False):
    """One styles level -> ordered 'prop:value' list, as the 2.5 frontend compiler emits.

    Root-level maps (the base rule and an at-rule's own properties) are key-sorted
    first, which puts custom properties first; branch maps keep insertion order.
    Custom properties then keep their positions while the other declarations are
    sorted alphabetically (shorthand before longhand, vendor prefix first) into the
    slot of the first regular declaration. Complete longhand sets merge last.
    """
    items = [(k, v) for k, v in styles.items() if not isinstance(v, dict)]
    if sort_keys:
        items.sort(key=lambda kv: kv[0])
    decls = [(kebab(k), minify_value(v)) for k, v in items if str(v).strip() != '']
    regular = [(i, p) for i, (p, _) in enumerate(decls) if not p.startswith('--')]
    if regular:
        ordered_regular = [decls[i] for i, _ in sorted(regular, key=cmp_to_key(_compare_decl))]
        first, inserted, merged = regular[0][0], False, []
        for i, decl in enumerate(decls):
            if decl[0].startswith('--'):
                merged.append(decl)
            elif not inserted:
                merged.extend(ordered_regular)
                inserted = True
        decls = merged
    return [f"{p}:{v}" for p, v in _merge_longhand(decls)]

SUPPORTED_AT_RULES = ('@media', '@supports', '@container')


def normalize_at_rule(at_rule):
    """Normalize an at-rule the way the 2.7 CSS editor stores common queries."""
    if not isinstance(at_rule, str):
        raise ValueError("at-rule must be a string")

    raw = at_rule.strip()
    family = next((name for name in SUPPORTED_AT_RULES if raw.startswith(name)), None)
    if not family:
        raise ValueError(
            f"unsupported at-rule {at_rule!r}; use @media, @supports, or @container"
        )

    suffix = raw[len(family):].strip()
    if not suffix or any(char in suffix for char in '{};'):
        raise ValueError(f"invalid at-rule {at_rule!r}")

    def compact_parentheses(match):
        return re.sub(r'\s+', '', match.group(0))

    if ':' in suffix:
        suffix = re.sub(r'\([^()]*\)', compact_parentheses, suffix)

    return f"{family} {suffix}"


def _split_top_level(text, sep=','):
    """Split on sep outside (), [] and quotes."""
    parts, depth, buf, quote = [], 0, '', ''
    for ch in text:
        if quote:
            buf += ch
            if ch == quote:
                quote = ''
            continue
        if ch in '"\'':
            quote = ch
        elif ch in '([':
            depth += 1
        elif ch in ')]':
            depth -= 1
        elif ch == sep and depth == 0:
            parts.append(buf)
            buf = ''
            continue
        buf += ch
    parts.append(buf)
    return parts

def minify_selector(selector):
    """Drop whitespace around combinators and commas, collapse other runs to one space."""
    def squeeze(part):
        part = re.sub(r'\s+', ' ', part)
        part = re.sub(r'\s*([>+~,])\s*', r'\1', part)
        part = re.sub(r'\(\s+', '(', part)
        return re.sub(r'\s+\)', ')', part)
    return _outside_quotes(selector, squeeze).strip()

def resolve_selector(selector, nested):
    """Resolve one selector key relative to the current selector, as 2.5 does.

    A part starting with `&` attaches to the selector (`&:hover`, `&::before`,
    `& > .x`). Any other part is a descendant, including `:hover`, `.child` and
    `[attr]`: write `&:hover` for the same element. Each part of a top-level
    comma list is resolved on its own.
    """
    if not isinstance(nested, str) or not nested.strip():
        raise ValueError("nested selector must be a non-empty string")

    nested = nested.strip()
    if any(char in nested for char in '{};'):
        raise ValueError(f"invalid nested selector {nested!r}")
    if nested == '&':
        raise ValueError("bare '&' selector is redundant")
    if nested.startswith('@'):
        raise ValueError("at-rules must be handled separately")
    resolved = []
    for base in _split_top_level(selector):
        for part in _split_top_level(nested):
            part = part.strip()
            resolved.append(base.strip() + part[1:] if part.startswith('&') else f"{base.strip()} {part}")
    return minify_selector(','.join(resolved))


def _rule(selector, style_map, sort_keys=False):
    props = declarations(style_map, sort_keys)
    return f"{selector}{{{';'.join(props)}}}" if props else ""


# ---- media query order (port of 2.5's postcss-gb-sort-media-queries) --------
_I = re.IGNORECASE
_MIN_MAX_W = re.compile(r'(!?\(\s*min(-device)?-width)(.|\n)+\(\s*max(-device)?-width|\(\s*width\s*>(=)?(.|\n)+\(\s*width\s*<(=)?|(!?\(.*<(=)?\s*width\s*<(=)?)', _I)
_MAX_MIN_W = re.compile(r'(!?\(\s*max(-device)?-width)(.|\n)+\(\s*min(-device)?-width|\(\s*width\s*<(=)?(.|\n)+\(\s*width\s*>(=)?|(!?\(.*>(=)?\s*width\s*>(=)?)', _I)
_MIN_MAX_H = re.compile(r'(!?\(\s*min(-device)?-height)(.|\n)+\(\s*max(-device)?-height|\(\s*height\s*>(=)?(.|\n)+\(\s*height\s*<(=)?|(!?\(.*<(=)?\s*height\s*<(=)?)', _I)
_MAX_MIN_H = re.compile(r'(!?\(\s*max(-device)?-height)(.|\n)+\(\s*min(-device)?-height|\(\s*height\s*<(=)?(.|\n)+\(\s*height\s*>(=)?|(!?\(.*>(=)?\s*height\s*>(=)?)', _I)

def _tester(first, other, fallback):
    fallback = re.compile(fallback, _I)
    return lambda q: bool(first.search(q)) or (not other.search(q) and bool(fallback.search(q)))

_is_min_w = _tester(_MIN_MAX_W, _MAX_MIN_W, r'\(\s*min(-device)?-width|\(\s*width\s*>(=)?')
_is_max_w = _tester(_MAX_MIN_W, _MIN_MAX_W, r'\(\s*max(-device)?-width|\(\s*width\s*<(=)?')
_is_min_h = _tester(_MIN_MAX_H, _MAX_MIN_H, r'\(\s*min(-device)?-height|\(\s*height\s*>(=)?')
_is_max_h = _tester(_MAX_MIN_H, _MIN_MAX_H, r'\(\s*max(-device)?-height|\(\s*height\s*<(=)?')
_MAX = float('inf')

def _query_px(q):
    m = re.search(r'(-?\d*\.?\d+)(ch|em|ex|px|rem)', q)
    if m is None and (_is_min_w(q) or _is_min_h(q)):
        m = re.search(r'(\d)', q)
        return float(m.group(1)) if m else _MAX
    if m is None:
        return _MAX
    n, unit = float(m.group(1)), m.group(2)
    return {'ch': n * 8.8984375, 'em': n * 16, 'rem': n * 16, 'ex': n * 8.296875}.get(unit, n)

def _cmp(a, b):
    return (a > b) - (a < b)

def compare_media(a, b):
    """Mobile-first comparator for @media params: other queries, min-* ascending, max-* descending, print last."""
    pa, pb = bool(re.search('print', a, _I)), bool(re.search('print', b, _I))
    oa, ob = bool(re.fullmatch('print', a, _I)), bool(re.fullmatch('print', b, _I))
    if pa and pb:
        return 1 if (not oa and ob) else -1 if (oa and not ob) else _cmp(a, b)
    if pa:
        return 1
    if pb:
        return -1
    min_a, max_a = _is_min_w(a) or _is_min_h(a), _is_max_w(a) or _is_max_h(a)
    min_b, max_b = _is_min_w(b) or _is_min_h(b), _is_max_w(b) or _is_max_h(b)
    if (not min_a and not max_a) or (not min_b and not max_b):
        if min_a or max_a or min_b or max_b:
            return -1 if (min_b or max_b) else 1
        return _cmp(a, b)
    if min_a and max_b:
        return -1
    if max_a and min_b:
        return 1
    va, vb = _query_px(a), _query_px(b)
    if va == _MAX and vb == _MAX:
        return _cmp(a, b)
    if va == _MAX:
        return 1
    if vb == _MAX:
        return -1
    if va > vb:
        return -1 if max_a else 1
    if va < vb:
        return 1 if max_a else -1
    return _cmp(a, b)


def _group(styles, depth_error):
    """Split one styles map into (own properties, {branch: map}, {at-rule: map}) like 2.5's Wr()."""
    props, branches, at_rules = {}, {}, {}
    for key, value in styles.items():
        if key.startswith('@'):
            if not isinstance(value, dict):
                continue
            at_rules[key] = _deep_merge(at_rules.get(key, {}), value)
        elif isinstance(value, dict):
            for inner_key, inner in value.items():
                if inner_key.startswith('@'):
                    if not isinstance(inner, dict):
                        continue
                    if any(isinstance(v, dict) for v in inner.values()):
                        raise ValueError("an at-rule inside a selector cannot contain another branch")
                    bucket = at_rules.setdefault(inner_key, {})
                    bucket[key] = _deep_merge(bucket.get(key, {}), inner)
                elif isinstance(inner, dict):
                    raise ValueError(depth_error)
                else:
                    branches.setdefault(key, {})[inner_key] = inner
        else:
            props[key] = value
    return props, branches, at_rules

def _deep_merge(base, extra):
    out = dict(base)
    for k, v in extra.items():
        out[k] = _deep_merge(out[k], v) if isinstance(v, dict) and isinstance(out.get(k), dict) else v
    return out

def build_css(selector, styles):
    """Compile local V2 styles exactly as the 2.5 Styles Builder (frontend mode) does.

    Order: base rule (keys sorted), branch rules (sorted by key), every distinct
    non-media at-rule in authored encounter order (including at-rules nested in
    a branch), then all @media blocks merged by query and sorted mobile-first.
    Inside an at-rule the same order applies. One selector level and one
    at-rule level are supported.
    """
    if not isinstance(selector, str) or not selector.strip():
        raise ValueError("selector must be a non-empty string")
    if not isinstance(styles, dict):
        raise ValueError("styles must be a dict")

    selector = selector.strip()
    for key in styles:
        if key.startswith('@'):
            normalize_at_rule(key)
        elif isinstance(styles[key], dict):
            resolve_selector(selector, key)
    props, branches, at_rules = _group(styles, "only one selector level is supported")

    def body(own, nested, sort_own):
        parts = [_rule(selector, own, sort_keys=sort_own)]
        parts += [_rule(resolve_selector(selector, k), nested[k]) for k in sorted(nested)]
        return ''.join(parts)

    output = [body(props, branches, True)]
    blocks = {}
    for raw, value in at_rules.items():
        at_rule = normalize_at_rule(raw)
        own, nested, deeper = _group(value, "a selector inside an at-rule cannot contain another branch")
        if deeper:
            raise ValueError("only one at-rule level is supported")
        for k in nested:
            resolve_selector(selector, k)
        compiled = body(own, nested, True)
        if not compiled:
            continue
        if at_rule in blocks:
            blocks[at_rule] += compiled
        else:
            blocks[at_rule] = compiled
    media = [k for k in blocks if k.startswith('@media ')]
    output += [f"{k}{{{blocks[k]}}}" for k in blocks if not k.startswith('@media ')]
    output += [f"{k}{{{blocks[k]}}}" for k in sorted(media, key=cmp_to_key(lambda a, b: compare_media(a[7:], b[7:])))]
    return ''.join(output)

# ---- WordPress serialize_block_attributes() ---------------------------------
# Backslashes are built with chr(92) so no writer/linter can strip them.
_BS = chr(92)
_U = lambda code: _BS + 'u' + code
SUBS = [('--', _U('002d') + _U('002d')),
        ('<',  _U('003c')),
        ('>',  _U('003e')),
        (chr(38), _U('0026'))]

def serialize_attrs(attrs):
    """json_encode + the six substitutions, preserving key insertion order.

    Order matters. A literal backslash is folded to \u005c FIRST, because every
    later substitution injects backslashes of its own that must not be folded
    again. Verified against WordPress serialize_block_attributes() on
    WP 7.1 (2026-08-27):

        {"css":".x::before{content:'\u005ce9d9'}","q":"say \u0022hi\u0022",
         "amp":"a\u0026b","lt":"\u003cb\u003e","dd":"var(\u002d\u002dx)"}
    """
    s = json.dumps(attrs, separators=(',', ':'), ensure_ascii=False)
    s = s.replace(_BS + _BS, _U('005c'))          # literal backslash -> \u005c
    s = s.replace(_BS + '"', _U('0022'))          # escaped quote     -> \u0022
    for lit, esc in SUBS:
        s = s.replace(lit, esc)
    return s

# ---- canonical attribute order ----------------------------------------------
ORDER = {
    'element': ['uniqueId', 'tagName', 'styles', 'css', 'globalClasses',
                'htmlAttributes', 'align', 'className'],
    'text':    ['uniqueId', 'tagName', 'content', 'styles', 'css', 'globalClasses',
                'htmlAttributes', 'icon', 'iconLocation', 'iconOnly', 'className'],
    'media':   ['uniqueId', 'tagName', 'styles', 'css', 'globalClasses',
                'htmlAttributes', 'mediaId', 'linkHtmlAttributes', 'className'],
    'shape':   ['uniqueId', 'html', 'styles', 'css', 'globalClasses',
                'htmlAttributes', 'className'],
    'query':   ['uniqueId', 'tagName', 'styles', 'css', 'globalClasses',
                'htmlAttributes', 'queryType', 'paginationType', 'query',
                'inheritQuery', 'showTemplateSelector', 'className'],
    'looper':  ['uniqueId', 'tagName', 'styles', 'css', 'globalClasses',
                'htmlAttributes', 'className'],
    'loop-item': ['uniqueId', 'tagName', 'styles', 'css', 'globalClasses',
                  'htmlAttributes', 'className'],
    'query-no-results': ['uniqueId', 'tagName', 'styles', 'css', 'globalClasses',
                         'htmlAttributes', 'className'],
    'query-page-numbers': ['uniqueId', 'tagName', 'styles', 'css', 'globalClasses',
                           'htmlAttributes', 'midSize', 'className'],
}

# Attributes GenerateBlocks Pro injects into every block type. The native serializer
# writes them after the block's own attributes and before className. Pro registers
# gbBlockCondition as a string: an integer ID fails type validation and the editor
# silently drops the condition on its next save.
PRO_INJECTED = ('gbBlockCondition', 'gbBlockConditionInvert')


def key_order(block_type):
    keys = [k for k in ORDER[block_type] if k != 'className']
    return keys + list(PRO_INJECTED) + ['className']


def ordered(block_type, attrs):
    if block_type not in ORDER:
        raise ValueError(f"unsupported block type {block_type!r}")
    out = {k: attrs[k] for k in key_order(block_type) if k in attrs}
    if 'gbBlockCondition' in out:
        out['gbBlockCondition'] = str(out['gbBlockCondition'])
    return out
