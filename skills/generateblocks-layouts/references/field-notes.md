---
title: Field Notes — real conversion fixes & doc contradictions
description: Hard-won lessons from converting an existing HTML/CSS design to GenerateBlocks V2 by hand. Covers the safe escaping workflow, the documentation contradictions (and which source wins), and the pre-delivery validation pass. Read this when hand-authoring blocks or migrating a designed section.
---

# Field Notes

Practical rectifications from a real hand-conversion (a multi-card hero:
27 blocks — element/text/shape — styled with theme CSS variables). These
supplement `recovery-rules.md`; they don't replace it. Where a rule here
conflicts with `SKILL.md` or `block-types.md`, **this file and
`recovery-rules.md` win** — they reflect what actually round-trips.

---

## 1. The escaping workflow that actually works

Don't try to hand-type `\u002d\u002d` while authoring — you'll miss some and
mangle others. Author with **literal** `--`, `<`, `>`, `&`, then run a
post-processor that applies the substitutions to **the JSON inside block
delimiters only**, leaving the rendered HTML body untouched. (Five
substitutions exist — see `recovery-rules.md` §1; the script below covers the
four character ones. The fifth, `\"` → `\u0022`, only matters when a JSON
string value contains a double quote, e.g. inline HTML in `content` — add it
as the LAST replace step if needed.)

This mirrors what WordPress does: `serialize_block_attributes()` applies the
substitutions to the whole serialized JSON string, but the HTML body
between the delimiters is plain HTML and keeps literal characters.

```python
import re, json
lines = open('section.html').read().split('\n')
out = []
delim = re.compile(r'^(<!-- wp:[^\s]+ )(\{.*\})( -->)$')   # opening delimiters only
for ln in lines:
    m = delim.match(ln)
    if m:
        pre, j, post = m.groups()
        json.loads(j)                                       # validate structure BEFORE escaping
        j = (j.replace('--', '\\u002d\\u002d')              # order matters: -- first
               .replace('<', '\\u003c')
               .replace('>', '\\u003e')
               .replace('&', '\\u0026'))
        out.append(pre + j + post)
    else:
        out.append(ln)                                      # HTML body: leave literal
open('section.html','w').write('\n'.join(out))
```

Consequences you must keep straight — the same value appears in **two** forms:

| Where | `--color-primary` | `<em>` | `?a=1&b=2` |
|---|---|---|---|
| JSON (inside `<!-- wp: … -->`) | `var(--color-primary)` | `<em>` | `?a=1&b=2` |
| Rendered HTML body | `var(--color-primary)` | `<em>` | `?a=1&amp;b=2` |

So an inline `style="color:var(--color-primary)"` on an `<em>` in a text
block's **content attribute** is escaped (`var(--…)`), but the **same
markup in the rendered `<h1>` body** stays literal.

### 1.1 The no-op trap

`"-- → --"` is a no-op. In a `<<'PY'` heredoc the replacement target must be a
**raw/literal backslash sequence**:

```python
.replace('--', r'--')          # ❌ WRONG — replaces -- with -- (does nothing)
.replace('--', '\\u002d\\u002d')  # ✅ RIGHT — emits literal --
```

After running, grep to prove it: `grep '^<!-- wp:' f.html | grep -o '{.*}' | grep -c -- '--'` must be `0`.

**The trap is worse than it looks: it also strikes when you WRITE the tool.**
Authoring a generator script through an editor/agent that processes escapes can
silently strip the backslashes out of your source, turning
`('--', '\\u002d\\u002d')` into `('--', '--')` — a table of four no-ops that
looks correct on screen. This has shipped broken markup.

Build the escape strings from `chr(92)` so no writer, linter, or heredoc can
touch them:

```python
_BS = chr(92)
_U  = lambda code: _BS + 'u' + code
SUBS = [('--', _U('002d') + _U('002d')),
        ('<',  _U('003c')),
        ('>',  _U('003e')),
        ('&',  _U('0026'))]
QUOTE = _U('0022')          # for the fifth substitution
```

Then assert before you trust it:

```python
assert SUBS[0][1] != '--', 'escape table is a no-op'
```

---

## 2. Documentation contradictions — which source wins

These bit during the conversion. The resolutions below are what round-trips.

### 2.1 `htmlAttributes`: plain object, never array

Older docs showed the **array** form
`[{"attribute":"href","value":"…"}]`. That form triggers recovery and has
been purged from this repo (June 2026 rebuild). `recovery-rules.md` §3.1 is
authoritative:

```json
"htmlAttributes":{"href":"https://example.com/","rel":"noopener"}   // ✅
"htmlAttributes":[{"attribute":"href","value":"…"}]                  // ❌ recovery
```

Applies to every block, including `media` (use `{"src":"…","alt":"…"}`).

### 2.2 `className`: omit the id-class (Option A)

(An old `SKILL.md` rule said to include the uniqueId class in `className` —
fixed in the June 2026 rebuild.) **Follow Option A — omit the id-class**; the
plugin auto-injects `gb-{type}-{id}` when `styles` is non-empty:

```json
"className":"gb-element"            // element  → rendered: class="gb-element-{id} gb-element"
"className":"gb-element alignfull"  // full-width section
// text / shape / media: omit className entirely
```

### 2.3 Rendered base-class ORDER differs by block type

Not a contradiction to fight — just match the editor. What's really going on
(verified against live-site exports in `examples/from-gauravtiwari-org/`):
the order depends on whether the JSON carries `className`.

- **`className` present** (the editor adds `"className":"gb-element"` for
  element blocks): rendered = id-class **prepended** →
  `gb-element-{id} gb-element`.
- **`className` absent** (the editor's default for text/media/shape): the
  save function emits the base class itself and the id-class is **appended**
  → `gb-text custom-classes gb-text-{id}`.

Editor-default conventions per block:

| Block | JSON | Rendered class list |
|---|---|---|
| `element` | `"className":"gb-element"` | `gb-element-{id} gb-element` (id first) |
| `text` | no className | `gb-text gb-text-{id}` (base first, id appended) |
| `media` | no className | `gb-media gb-media-{id}` (base first) |
| `shape` | no className | `gb-shape gb-shape-{id}` (base first) |

Both authoring paths round-trip (recovery-rules §3.3). Pick ONE convention
per block and make JSON + body agree. When `styles` is empty, no id-class is
injected at all — production text blocks without styles render plain
`class="gb-text"`.

### 2.4 `styles` longhand vs `css` shorthand for padding/margin

The editor stores shorthand `padding` as **four longhand keys** in the
`styles` object, but the `css` string is taken as-authored (shorthand is fine).
Match the known-good pattern:

```json
"styles":{"paddingTop":"3rem","paddingBottom":"3rem","paddingLeft":"1rem","paddingRight":"1rem"},
"css":".gb-element-x{padding:3rem 1rem}"
```

### 2.5 `shape`: the `html` attribute is optional

`recovery-rules.md` §3.4 lists `html` 2nd for shape, but the working pattern
omits it and puts the SVG in the rendered `<span>` body, with sizing via
`styles.svg`. Keep SVG attribute order per SKILL.md rule #23
(`stroke-linejoin, stroke-linecap, stroke-width, stroke, fill, viewBox, height, width`).

### 2.6 `text`: static editor output usually keeps text in the body

`recovery-rules.md` §3.4 lists `content` 3rd in the text block's key order
(correct per `block.json`). But the editor **does not serialize it** for
ordinary static text — the string lives only in the rendered inner HTML.

Measured on a production page in July 2026 (520-block `/services/`, GB free 2.4.0-rc.1 /
Pro 2.7.0-rc.1): **0 of 199** text blocks carried `content`; **199 of 199**
also omitted `className` and rendered base-class-first.

```html
<!-- RIGHT — matches the editor -->
<!-- wp:generateblocks/text {"uniqueId":"item-1173976-23a","tagName":"h3","styles":{...},"css":"..."} -->
<h3 class="gb-text gb-text-n23a">Blog Setup &amp; Monetization</h3>
<!-- /wp:generateblocks/text -->

<!-- Do not add source attributes merely to duplicate static body text -->
<!-- wp:generateblocks/text {"uniqueId":"item-1173976-23a","tagName":"h3","content":"Blog Setup &amp; Monetization","styles":{...},"className":"gb-text"} -->
```

That measurement describes one editor-authored target, not every record on a
mixed site. An August 2026 sample of the 100 most recently modified
GenerateBlocks-bearing records on gauravtiwari.org contained 11,598 GB blocks
and mixed `content`/`className` conventions, including generated/imported
markup. Preserve the target's existing convention. For new static text, omit a
duplicate `content` attribute; use it when a dynamic binding or the measured
target requires it.

Note the ampersand: `&` is `&amp;` in the rendered body, and would be
`&amp;` in JSON — the entity is part of the string, then the `&` of the
entity gets substituted. Three-layer escaping, same as §1.

### 2.7 The `css` attribute is NOT re-derived during block validation

This is the single most useful thing to know about `css`, and it resolves a lot
of anxiety in this repo.

`css` is the plugin's cached compile of `styles`. The editor's recovery check
compares **serialized attributes**, and `css` is just another string attribute —
it is not recomputed and diffed at load time. Evidence: the same production page
carries hand-authored `css` strings that are internally inconsistent (some
`clamp(2rem, 4vw, 3rem)` with spaces, some without; hand-written `:hover` and
`transition:` inside `css`; non-alphabetical property order) and loads without a
single recovery error.

What this means in practice:

- **Recovery is driven by:** JSON key order, the five escapes, `htmlAttributes`
  shape, and the rendered class list. Get those right and blocks validate.
- **`css` still has to be CORRECT**, because it is what actually renders until
  someone re-saves the block from the editor.
- **Transitions, states, selectors, and at-rules belong in `styles` and the
  compiled `css`.** Pro CSS Mode supports one selector level plus `@media`,
  `@supports`, and `@container`. If a human later changes the block's styles,
  CSS present only in the cache can be silently lost. Read `css-mode.md`.

---

## 3. Robustness decisions when converting a real design

- **External image in a custom layout → CSS `background-image` on an element
  block**, not `generateblocks/media`. It sidesteps the media-block
  `htmlAttributes` shape ambiguity (§2.1) and the missing attachment `mediaId`
  for CDN-hosted images. Add `role="img"` + `aria-label` for accessibility.
  Use `core/image` only when you need a real caption.
- **Style with the theme's CSS variables** (`var(--color-bg)`,
  `var(--color-headline)`, `var(--radius-l)`, `var(--font-head)`) + `color-mix`
  for surfaces. The section then **inherits the theme and flips with the
  theme's dark mode for free** — no `[data-theme="dark"]` overrides, which can't
  go in the `css` string cleanly anyway.
- **Drop decorative pseudo/SVG background layers** (animated grids, gradient
  orbs, circuit SVGs). They need multi-property `::before/::after` rules whose
  alphabetical-sort + vendor-prefix ordering (`-webkit-mask-image` vs
  `mask-image`) is fragile in the `css` string. Recreate them as a GB Pro
  "Effects" layer or a small global CSS snippet, not inline block CSS. The
  structural look (card system, mono labels, badges, accent color) survives
  without them.
- **Empty decorative elements** (a badge dot) are fine as an element block with
  `styles` and an empty body: `<span class="gb-element-x gb-element"></span>` —
  keep it on one line (compact nesting, §3.6).

---

## 4. Pre-delivery validation pass

Recovery is an **editor-side** re-serialization check — you cannot fully
reproduce it headlessly. But you can catch the structural/escaping failures
that cause most of it. Run this before handing off the file:

```python
import re, json
txt = open('section.html').read()
opens  = re.findall(r'<!-- wp:(generateblocks/\w+) (\{.*?\}) -->', txt)
closes = re.findall(r'<!-- /wp:(generateblocks/\w+) -->', txt)
assert len(opens) == len(closes), (len(opens), len(closes))      # balanced delimiters

for typ, j in opens:                                              # JSON valid after un-escaping
    raw = (j.replace('\\u002d\\u002d','--').replace('\\u003c','<')
             .replace('\\u003e','>').replace('\\u0026','&'))
    d = json.loads(raw)
    assert list(d)[0] == 'uniqueId'                              # canonical key order starts right
    if 'className' in d:                                          # className is last among declared
        assert list(d).index('className') > list(d).index('css')

stack = []                                                        # nesting balance
for m in re.finditer(r'<!-- (/?)wp:(generateblocks/\w+)', txt):
    if m.group(1) == '': stack.append(m.group(2))
    else: assert stack.pop() == m.group(2)
assert not stack
print("structurally valid:", len(opens), "blocks")
```

Then state the limit honestly: structure + escaping are verified; the only
real recovery proof is pasting into the editor. Offer to push it to a **draft**
page and hand back the edit URL so the user can confirm it loads clean.

---

## 5. Quick checklist (this file's deltas, on top of recovery-rules.md §7)

- [ ] Authored with literal chars, then escaped JSON-only via the §1 script
- [ ] `grep` proves zero literal `--` inside delimiter JSON
- [ ] `htmlAttributes` are objects (§2.1)
- [ ] `className` omits the id-class for element; omitted entirely for text/shape/media (§2.2)
- [ ] Rendered class order: element id-first, others base-first (§2.3)
- [ ] External images via `background-image`, not media block (§3)
- [ ] Decorative grid/orb/SVG layers dropped or moved to global CSS (§3)
- [ ] Ran the §4 validation script; reported the headless-verification limit

---

## 6. The `css` compiler — reproducing plugin output

When you want `css` to match what the plugin would generate (not required for
recovery, see §2.7, but it keeps the block clean through future edits), this is
the algorithm. It was reverse-engineered and validated against **467 production
blocks** that carry both `styles` and `css`, reproducing 89% byte-for-byte —
every residual difference being a place where the *live* markup was
hand-authored and internally inconsistent.

1. camelCase property → kebab-case (`gridTemplateColumns` → `grid-template-columns`).
2. If a complete longhand box set is present (`paddingTop/Right/Bottom/Left`),
   collapse it to the shortest shorthand (`6rem 1.5rem`) and **remove the longhands**.
3. Emit the remaining properties **alphabetically sorted**.
4. Append the collapsed shorthands **after** the sorted longhands — this is why
   real output reads `background-color;overflow;position;padding`, which looks
   non-alphabetical but is deterministic.
5. Strip whitespace **after commas** inside values (`clamp(2rem, 4vw, 3rem)` →
   `clamp(2rem,4vw,3rem)`). Do **not** strip spaces around `+` — `1.9rem + 3.4vw`
   is required for the calc expression to be valid.
6. Compile one-level selector branches (`&:hover`, `&::before`, child and
   sibling selectors) relative to the block selector.
7. Compile `@media`, `@supports`, and `@container` branches, including one
   selector level inside an at-rule or one at-rule level inside a selector.
8. Reject unsupported structured at-rules instead of hiding them only in
   `css`.

`scripts/gb_serialize.py` implements this, plus `serialize_block_attributes()`
and the canonical key order. Import it rather than re-deriving.

### 6.1 clamp() and the `+` operator

```css
clamp(2.75rem,1.9rem+3.4vw,4.25rem)      /* INVALID — whole declaration dropped, silently */
clamp(2.75rem,1.9rem + 3.4vw,4.25rem)    /* correct: no space after commas, spaces around + */
```

CSS requires whitespace around `+` inside `calc()`-style expressions. Combined
with rule 5 this is the one place where "minify everything" is wrong. Symptom of
getting it wrong: headings render at body size with **no console error**.

---

## 7. Verify against the TARGET SITE, not just the docs

The single highest-value step before hand-authoring for an existing site: pull
the page you're extending and measure its conventions. Plugin behaviour varies by
version, and the live page is ground truth for the build actually installed.

```python
import re, json
h = open('existing-page.html').read()
blocks = re.findall(r'<!-- wp:(generateblocks/[a-z-]+) (\{.*?\}) /?-->', h)
# key order actually used
import collections
print(collections.Counter(tuple(json.loads(r).keys()) for _, r in blocks).most_common())
# does this build serialize text `content`?
print(sum('"content"' in r for n, r in blocks if n.endswith('/text')))
# rendered class order
print(collections.Counter(c.split()[0] for c in re.findall(r'class="(gb-[^"]+)"', h)).most_common(4))
```

Check the installed versions too — authenticated plugin inventory or read-only
WP-CLI returns exact versions, which decides whether Pro features are available.
On 2026-08-26, gauravtiwari.org ran free 2.4.1 + Pro 2.7.1 on WordPress 7.1,
with native Mobile at `max-width:767px`. Its recent content still contained many
custom `max-width:768px` branches. Preserve those existing boundaries; use the
native query for new work unless the project deliberately defines another one.

A mixed result is normal and fine: a page can carry both legacy (no `className`,
base-first) and Option A (with `className`, id-first) blocks. Pick the dominant
convention **per block type** and stay internally consistent.

---

## 8. Layout gotchas that survive validation but break the page

These pass every recovery check and still render wrong.

### 8.1 Grid/flex columns need their own wrapper element

Emitting a column's contents directly into a grid container makes **each child**
a grid item. A two-column hero with 5 elements on the left becomes a 6-cell flow.

```html
<!-- WRONG: grid gets 6 items -->
grid(cols=2) -> [h1, lede, buttons, meta, panel]

<!-- RIGHT: grid gets 2 items -->
grid(cols=2) -> [ div(h1,lede,buttons,meta), div(panel) ]
```

Catch it by measuring, not eyeballing: assert the vertical gaps between siblings
are what the design specifies. Negative gaps mean elements are in different grid
cells than you think.

### 8.2 GB has no page-level stylesheet

A design built on a scoped token layer (`.wrapper { --ink: … }`) does not survive
conversion — every block owns isolated CSS. Either flatten tokens to literal
values via a generator palette, or set custom properties on a section block's
`styles` and let children inherit through the cascade. Decide deliberately; a
half-flattened design is unmaintainable.

If a design genuinely depends on one scoped stylesheet plus JS, a Marketers
Delight **Page Block** (`/page-block`) is the better delivery vehicle than GB.

### 8.3 Interactivity needs a `core/html` block

GB has no JS field. Filters, tabs-by-script, and custom behaviour ship as one
`core/html` block. GB Pro's interactive blocks (accordion, tabs, carousel) cover
the common cases without script — prefer them when they fit.
