"""
gb_serialize — GenerateBlocks V2 css compiler + block-attribute serializer.

Reproduces two things the plugin/WordPress do, so hand-authored markup round-trips:

  build_css(selector, styles)  -> the `css` attribute the plugin compiles from `styles`
  serialize_attrs(attrs)       -> WordPress serialize_block_attributes() incl. all
                                  five substitutions
  ordered(block_type, attrs)   -> canonical block.json key order, className last
  make_unique_id(...)          -> {section}-{post_id}-{sequence}{suffix}

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
import re

def make_unique_id(section, post_id, sequence, suffix=''):
    """Return a post-scoped, unpadded GenerateBlocks uniqueId."""
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
    return re.sub(r'([A-Z])', lambda m: '-' + m.group(1).lower(), prop)

# ---- shorthand collapsing ----------------------------------------------------
# The plugin collapses a complete longhand set into its shorthand and appends the
# result AFTER the alphabetized longhands.
BOX_GROUPS = {
    'padding': ('paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'),
    'margin':  ('marginTop', 'marginRight', 'marginBottom', 'marginLeft'),
}

def _collapse_box(vals):
    """(t,r,b,l) -> shortest equivalent CSS shorthand."""
    t, r, b, l = vals
    if t == r == b == l:      return t
    if t == b and r == l:     return f"{t} {r}"
    if r == l:                return f"{t} {r} {b}"
    return f"{t} {r} {b} {l}"

def minify_value(v):
    """Strip comma whitespace while preserving required CSS-math operator space."""
    return re.sub(r',\s+', ',', str(v)).strip()

def declarations(styles):
    """styles dict (one level, no at-rules) -> ordered list of 'prop:value'."""
    plain, collapsed = {}, []
    consumed = set()
    for short, longs in BOX_GROUPS.items():
        if all(k in styles for k in longs):
            collapsed.append((short, minify_value(_collapse_box(tuple(styles[k] for k in longs)))))
            consumed.update(longs)
    for k, v in styles.items():
        if k in consumed or k.startswith('@') or k.startswith('&') or k.startswith(':'):
            continue
        if isinstance(v, dict):
            continue
        plain[kebab(k)] = minify_value(v)
    out = [f"{p}:{plain[p]}" for p in sorted(plain)]
    out += [f"{p}:{v}" for p, v in collapsed]
    return out

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


def resolve_selector(selector, nested):
    """Resolve one CSS Mode selector branch relative to the current selector."""
    if not isinstance(nested, str) or not nested.strip():
        raise ValueError("nested selector must be a non-empty string")

    nested = nested.strip()
    if any(char in nested for char in '{};'):
        raise ValueError(f"invalid nested selector {nested!r}")
    if nested == '&':
        raise ValueError("bare '&' selector is redundant")
    if nested.startswith('@'):
        raise ValueError("at-rules must be handled separately")
    if '&' in nested:
        return nested.replace('&', selector)
    if nested.startswith(':') or nested.startswith('[') or nested.startswith('.'):
        return f"{selector}{nested}"
    return f"{selector} {nested}"


def _rule(selector, style_map):
    props = declarations(style_map)
    return f"{selector}{{{';'.join(props)}}}" if props else ""


def _nested_rules(selector, style_map, allow_nested_at_rule=True):
    """Compile one selector level and, optionally, one at-rule inside it."""
    out = []
    for key, value in style_map.items():
        if not isinstance(value, dict):
            continue
        if key.startswith('@'):
            if not allow_nested_at_rule:
                raise ValueError("only one at-rule level is supported")
            at_rule = normalize_at_rule(key)
            inner = _rule(selector, value)
            if any(isinstance(v, dict) for v in value.values()):
                raise ValueError("an at-rule inside a selector cannot contain another branch")
            if inner:
                out.append(f"{at_rule}{{{inner}}}")
        else:
            raise ValueError("only one selector level is supported")
    return ''.join(out)


def build_css(selector, styles):
    """Compile local V2 styles, one selector level, and supported at-rules."""
    if not isinstance(selector, str) or not selector.strip():
        raise ValueError("selector must be a non-empty string")
    if not isinstance(styles, dict):
        raise ValueError("styles must be a dict")

    selector = selector.strip()
    output = [_rule(selector, styles)]

    # CSS Mode prints selector branches before root at-rules.
    for key, value in styles.items():
        if not isinstance(value, dict) or key.startswith('@'):
            continue
        resolved = resolve_selector(selector, key)
        output.append(_rule(resolved, value))
        output.append(_nested_rules(resolved, value))

    for key, value in styles.items():
        if not isinstance(value, dict) or not key.startswith('@'):
            continue
        at_rule = normalize_at_rule(key)
        if any(k.startswith('@') for k, v in value.items() if isinstance(v, dict)):
            raise ValueError("only one at-rule level is supported")

        inner = [_rule(selector, value)]
        for nested, nested_values in value.items():
            if not isinstance(nested_values, dict):
                continue
            resolved = resolve_selector(selector, nested)
            inner.append(_rule(resolved, nested_values))
            if any(isinstance(v, dict) for v in nested_values.values()):
                raise ValueError("a selector inside an at-rule cannot contain another branch")
        compiled = ''.join(inner)
        if compiled:
            output.append(f"{at_rule}{{{compiled}}}")

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
    """json_encode + the five substitutions, preserving key insertion order."""
    s = json.dumps(attrs, separators=(',', ':'), ensure_ascii=False)
    s = s.replace(_BS + '"', _U('0022'))          # escaped quote -> \u0022
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

def ordered(block_type, attrs):
    if block_type not in ORDER:
        raise ValueError(f"unsupported block type {block_type!r}")
    return {k: attrs[k] for k in ORDER[block_type] if k in attrs}
