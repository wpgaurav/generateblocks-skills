---
title: Recovery Error Rules (read FIRST)
description: Every known cause of "Attempt Recovery" errors in the WordPress block editor when emitting GenerateBlocks markup, with the exact fix.
---

# Recovery Error Rules

This file is the authoritative checklist. Read it before generating any GB markup.
Every rule below comes from a real failure observed in production.

## The meta-rule (read this first)

> **The WordPress block editor validates blocks by re-serializing the
> attributes and string-comparing against the markup you pasted. Any
> deviation — even semantically-equivalent JSON or HTML — is treated as
> corruption and triggers "Attempt Recovery".**

This is the unifying principle behind every rule in this file. The goal isn't
"valid JSON and valid HTML." The goal is **byte-identical to what the editor
itself would emit if it round-tripped the block**.

That means:
- The same JSON key order
- The same string-escape form for special characters
- The same class list (including any auto-injected duplicates)
- The same spacing and quoting

The block's saved `css` string is an attribute, not a value recomputed during
initial validation. Canonical minification and declaration order still matter
for durable generated output because a later style edit can recompile `css`,
but they are not independent recovery triggers. See §2 and `css-mode.md`.

If you remember nothing else, remember this: **emit what the editor emits, not
what you think is correct**.

---

## 1. JSON encoding rules (the silent killers)

WordPress's `serialize_block_attributes()` runs five substitutions on the JSON
string after `wp_json_encode()` to make it safe for the HTML comment context
the block delimiter lives in:

| Literal | Canonical form | Why |
|---|---|---|
| `--` | `\u002d\u002d` | Avoids HTML comment terminator collision |
| `<` | `\u003c` | Defends against `</script>` injection |
| `>` | `\u003e` | Same |
| `&` | `\u0026` | Defends against entity injection |
| `\"` (escaped quote) | `\u0022` | Quote inside a JSON string value |

If you emit the literal form, the editor re-serializes to the escaped form,
the strings differ, recovery fires. **Apply all five substitutions to every
JSON string value** inside block delimiter attributes (`styles` values, `css`
strings, `htmlAttributes` values, content strings — everywhere).

The fifth matters whenever a string value contains a double quote — e.g.
inline HTML in a text block's `content` or an SVG string in a text block's
`icon` attribute. Never emit `\"` — the canonical form is `\u0022`:

```json
"content":"Read the \u003ca href=\u0022https://example.com/\u0022\u003eguide\u003c/a\u003e now"
```

### 1.1 Escape `--` as `\u002d\u002d`

```json
// WRONG
"styles":{"maxWidth":"var(--gb-container-width)"}
"css":".gb-element-x{color:var(--accent)}"

// RIGHT
"styles":{"maxWidth":"var(\u002d\u002dgb-container-width)"}
"css":".gb-element-x{color:var(\u002d\u002daccent)}"
```

Applies to: every CSS custom property reference, every vendor prefix
(`--webkit-...`), every double-dash in any string.

### 1.2 Escape `&` as `\u0026`

```json
// WRONG
"htmlAttributes":{"href":"https://example.com/?a=1&b=2"}

// RIGHT
"htmlAttributes":{"href":"https://example.com/?a=1\u0026b=2"}
```

Applies to: query strings with multiple params (`?a=1&b=2`), URL fragments,
any string anywhere with a literal `&`.

In the **rendered HTML body** (the part outside the block delimiter), the
same `&` is written as `&amp;`:

```html
<a href="https://example.com/?a=1&amp;b=2">link</a>
```

So a single URL with two query params appears in three different forms across
one block:

| Place | Form |
|---|---|
| JSON `htmlAttributes.href` | `https://example.com/?a=1\u0026b=2` |
| Rendered HTML `<a href="...">` | `https://example.com/?a=1&amp;b=2` |
| What it actually means | `https://example.com/?a=1&b=2` |

All three are correct in their respective places.

### 1.3 Escape `<` and `>` as `\u003c` and `\u003e`

Rare in normal use but bites if a string value contains literal angle
brackets:

```json
// WRONG
"content":"Use the <button> tag"

// RIGHT
"content":"Use the \u003cbutton\u003e tag"
```

### 1.4 Use single quotes inside `css` strings

Anything inside `content:''`, `font-family:'...'`, `url('...')` must use single
quotes. Double quotes would terminate the surrounding JSON string.

```json
"css":".x::after{content:'→'}"
"css":".y{font-family:'Inter',sans-serif}"
```

### 1.5 No trailing commas, no unescaped newlines

The `css` attribute is a single-line minified string. If you need a literal
newline inside content, encode `\n`.

### 1.6 Inline HTML `style=""` is NOT JSON

Inside the rendered HTML body (e.g. `style="..."` on a real `<span>`), use
literal characters: `var(--foo)`, `&amp;`, `<`, `>`. The HTML body is not
JSON-parsed, so the five substitutions above do not apply there.

This is why the canonical link with a multi-param URL has `&amp;` in the
HTML body and `\u0026` in the JSON — same character, different escape rules
for different contexts.

---

## 2. Styles and compiled CSS

GenerateBlocks V2 has two related attributes:

- `styles`: the editable structured source used by the Styles panel and Pro
  CSS Mode;
- `css`: the compiled frontend cache scoped to the block's unique selector.

The `css` attribute is not re-derived merely to validate a block on load. A
hand-authored string can therefore load without recovery even when it differs
from `styles`. That is not durable: a later style edit can recompile `css` from
`styles` and remove anything represented only in the cache.

Read `css-mode.md` for the full grammar.

### 2.1 Keep `styles` and `css` semantically aligned

Transitions, hover/focus states, pseudo-elements, child selectors, and at-rules
belong in structured `styles`, with the compiled result mirrored in `css`.

```json
"styles":{
  "transition":"background-color .2s ease",
  "&:hover":{"backgroundColor":"#222"},
  "@media (max-width:767px)":{"width":"100%"}
}
```

Do not put those rules only in `css`. `preflight.py` treats CSS without a
matching styles branch as durability debt.

### 2.2 One selector level is supported

GenerateBlocks 2.4/Pro 2.7 supports one structured selector level, including:

```text
&:hover
&:focus-visible
&::before
& > .child
& + .sibling
svg
```

Flatten compound behavior into one selector:

```css
&:hover > .child { color: currentColor; }
```

Do not nest `.child` inside `&:hover`; that is a second selector level and CSS
Mode rejects it.

### 2.3 Only 3 at-rule families are structured

CSS Mode and the `styles` object support:

- `@media`;
- `@supports`;
- `@container`.

They support one at-rule level, optionally combined with one selector level.
Do not put `@keyframes`, `@font-face`, `@import`, or nested at-rules into
structured block styles. Use the owning project stylesheet.

### 2.4 Canonical generated CSS is compact

For new hand-authored blocks, compile `css` from `styles` with
`scripts/gb_serialize.py`. Its output is single-line, deterministic, and strips
spaces after commas in function arguments:

```css
repeat(4,minmax(0,1fr))
rgba(0,0,0,.1)
clamp(1rem,4vw,2.5rem)
```

This is a durability/canonical-output rule, not an independent recovery test.
Existing editor or imported content can contain harmless formatting
differences; preserve it unless the task includes style normalization.

Whitespace around `+` and `-` in CSS math is different and required:

```css
clamp(2.75rem,1.9rem + 3.4vw,4.25rem) /* valid */
clamp(2.75rem,1.9rem+3.4vw,4.25rem)   /* invalid */
```

### 2.5 Avoid CSS escape ambiguity

`content:'\2192'` is fragile inside a JSON string because the backslash also
participates in JSON escaping. Prefer literal visible characters, an SVG shape,
or real text content:

```css
.gb-text-link::after{content:'→'}
```

Better still, put meaningful arrows and labels in the rendered HTML so they are
available to assistive technology.

### 2.6 CSS property order

The editor/compiler can normalize declaration order and collapse complete box
longhands into shorthand. Use `build_css()` for deterministic new output rather
than hand-sorting rules. Do not claim that a non-alphabetical existing string
alone causes recovery; live editor-authored and imported blocks can preserve
other valid orders.

### 2.7 Comments and global CSS

CSS Mode strips comments. It also rejects unrelated site-wide selectors. Keep
block CSS scoped to the block. Put page-wide selector graphs, font declarations,
and keyframes in the project-approved stylesheet layer.

---

## 3. Block-level HTML rules

### 3.1 `htmlAttributes` MUST be a plain object, never an array

```json
// RIGHT
"htmlAttributes":{"href":"https://example.com/","target":"_blank","rel":"noopener"}

// WRONG — guaranteed recovery
"htmlAttributes":[{"attribute":"href","value":"https://example.com/"}]
```

### 3.2 Use full absolute URLs in `htmlAttributes.href`

The editor canonicalizes relative URLs to absolute on save → mismatch →
recovery.

```json
// RIGHT
"htmlAttributes":{"href":"https://gauravtiwari.org/contact/"}

// WRONG
"htmlAttributes":{"href":"/contact/"}
```

### 3.3 `className` and the auto-injected id-class

GenerateBlocks **automatically injects** `gb-{type}-{uniqueId}` into the
rendered HTML class list whenever `styles` is non-empty. This is in addition
to whatever you put in the `className` attribute.

The editor's canonical serialization is **the union, with duplicates kept as-is**.
So if `className` already contains the id-class, the rendered HTML has it
**twice**:

```json
// className includes id-class:
"className":"gb-element-top1 gb-element alignfull"
```
```html
<!-- canonical rendered HTML — id-class appears TWICE -->
<header class="gb-element-top1 gb-element-top1 gb-element alignfull">
```

There are two ways to stay drift-free. Pick one and stick to it:

**Option A (recommended): omit the id-class from `className`.** Let the
plugin auto-inject it. The rendered HTML has it once.

```json
"className":"gb-element alignfull"
```
```html
<header class="gb-element-top1 gb-element alignfull">
```

This is cleaner output. Use it for new work.

**Option B: include the id-class in `className` AND duplicate it in the HTML.**

```json
"className":"gb-element-top1 gb-element alignfull"
```
```html
<header class="gb-element-top1 gb-element-top1 gb-element alignfull">
```

Ugly but valid. Use this only when matching an existing pattern that already
emits the duplicate.

**The rule applies to every block type**, not just element. `gb-text-{id}`,
`gb-media-{id}`, `gb-shape-{id}`, `gb-query-{id}`, `gb-looper-{id}`,
`gb-loop-item-{id}`, `gb-query-page-numbers-{id}` all auto-inject the same
way.

**When `styles` is empty** (e.g. an empty `"styles":{}`), the plugin does NOT
auto-inject the id-class. In that case the rendered HTML class list is exactly
what you put in `className`. This is rare — most blocks have non-empty styles.

**Legacy form (no `className` at all):** older validated markup in this repo
omits `className` entirely and renders `class="gb-element gb-element-{id}"`
(base class first — the plugin's backup class generation). This round-trips
too, but the order differs from the Option A form. Never mix forms within one
block: either `"className":"gb-element"` + id-first body (new work), or no
className + base-first body (when matching existing legacy markup).

### 3.4 JSON attribute key order matters

The editor serializes block attributes in a fixed key order (declaration order
from each block's `block.json`). Even though `{"a":1,"b":2}` and `{"b":2,"a":1}`
are semantically identical JSON, the editor's diff is **string-based**, so a
different key order in your output → mismatch → recovery.

The canonical order **per block** (verified against `block.json` declarations):

```
generateblocks/element
  uniqueId, tagName, styles, css, globalClasses, htmlAttributes, align

generateblocks/text
  uniqueId, tagName, [content], styles, css, globalClasses, htmlAttributes,
  icon, iconLocation, iconOnly
  -- `content` is declared 3rd but is normally ABSENT; text lives in the
     rendered inner HTML. Omit it unless binding a dynamic tag.

generateblocks/media
  uniqueId, tagName, styles, css, globalClasses, htmlAttributes,
  mediaId, linkHtmlAttributes

generateblocks/shape
  uniqueId, html, styles, css, globalClasses, htmlAttributes

generateblocks/query
  uniqueId, tagName, styles, css, globalClasses, htmlAttributes,
  queryType, paginationType, query, inheritQuery, showTemplateSelector

generateblocks/looper
  uniqueId, tagName, styles, css, globalClasses, htmlAttributes

generateblocks/loop-item
  uniqueId, tagName, styles, css, globalClasses, htmlAttributes

generateblocks/query-page-numbers
  uniqueId, tagName, styles, css, globalClasses, htmlAttributes, midSize
```

Verified unchanged in GB 2.4.0 — the only block.json delta is an additive
`"role":"content"` flag on `text.content` and `media.mediaId`, which is
editor schema only and never serialized into markup.

**`className` is a WordPress core attribute** (provided by `supports.className`).
It is NOT in `block.json`. Empirically, `className` serializes **after** the
block's declared attributes — i.e. last in the JSON object.

Things that bite most often:

- **Text block: `content` is 3rd in `block.json` — but the editor normally does
  not serialize it at all.** Text lives in the rendered inner HTML instead.
  Verified against a production page: **0 of 199** text blocks carried a
  `content` attribute. Emitting one desyncs from what the editor writes back.
  Omit `content`; put the text in the body. IF you ever do emit it (dynamic-tag
  bindings are the real case), it goes 3rd, before `styles`. See
  `field-notes.md` §2.6.
- **Shape block: no `tagName`**, `html` is 2nd.
- **`htmlAttributes` BEFORE `className`** — `className` is always last among
  the common attributes.
- **`styles` BEFORE `css`** — both are styling, styles comes first.
- **Query block: `queryType, paginationType, query, inheritQuery`** in that
  order. `query` is NOT last.

When in doubt, omit the attribute rather than guess. An unset attribute is
safer than a misordered one.

### 3.6 Compact nesting — closing comments adjacent to closing tags

```html
<!-- WRONG -->
<!-- wp:generateblocks/element {"uniqueId":"x"} -->
<div class="gb-element-x gb-element">

</div>

<!-- /wp:generateblocks/element -->

<!-- RIGHT -->
<!-- wp:generateblocks/element {"uniqueId":"x"} -->
<div class="gb-element-x gb-element"></div>
<!-- /wp:generateblocks/element -->
```

Blank lines inside an empty block trigger recovery on some block types.

### 3.5 Never add stray HTML comments

The only allowed comments are `<!-- wp:... -->` block delimiters. No section
labels, no explanations, no `<!-- TODO -->`. Every other comment breaks
parsing.

---

## 4. The `<a>` tag rules (read carefully)

There are TWO competing failure modes for links. The correct pattern threads
between them.

### Failure mode A: text `<a>` strips its href on save

A `generateblocks/text` block with `tagName: "a"` does **not** reliably
preserve `htmlAttributes.href` through save. The href gets stripped.

### Failure mode B: element `<a>` with raw text content triggers recovery

A `generateblocks/element` with `tagName: "a"` is a container — it expects
inner blocks. If you put raw text inside (no `<!-- wp: -->` child), the
editor tries to recover.

### The correct pattern: element `<a>` wrapping a text child

For action links, buttons, and any link with visible text:

```html
<!-- wp:generateblocks/element {"uniqueId":"link1","tagName":"a","htmlAttributes":{"href":"https://example.com/page/"},"styles":{...},"css":"...","className":"gb-element"} -->
<a class="gb-element-link1 gb-element" href="https://example.com/page/">
    <!-- wp:generateblocks/text {"uniqueId":"link2","tagName":"span","styles":{...},"css":"..."} -->
    <span class="gb-text gb-text-link2">Read the full guide →</span>
    <!-- /wp:generateblocks/text -->
</a>
<!-- /wp:generateblocks/element -->
```

Why this works:
- Element `<a>` has an inner block child → no "raw text content" recovery
- `htmlAttributes.href` on the element block survives save → href preserved
- The text child is a `span`, not an `a` → no href stripping
- Arrow is a literal `→` in the text content → no `\2192` escape problems

For text-only inline links inside paragraphs (e.g. an `<a>` inside a sentence),
write the `<a>` directly into the rich text content of a `generateblocks/text`
paragraph block — don't wrap each one in its own block.

---

## 5. Block-type selection rules

### 5.1 Use `core/image` for static images with captions

`generateblocks/media` is fragile when combined with figcaptions and inline
border styles. For any static image that needs a caption, use `core/image`:

```html
<!-- wp:image {"id":123,"sizeSlug":"large","linkDestination":"none","style":{"border":{"radius":"0.75rem"}}} -->
<figure class="wp-block-image size-large has-custom-border" style="border-radius:0.75rem">
    <img src="https://example.com/image.jpg" alt="..." class="wp-image-123"/>
    <figcaption class="wp-element-caption">Caption text here</figcaption>
</figure>
<!-- /wp:image -->
```

Use `generateblocks/media` only for: hero/background-style images that participate
in a custom GB layout and don't need a caption, AND images inside dynamic
contexts (loop items, query loops) where you need GB's dynamic source binding.

### 5.2 Element vs Text vs Media vs Shape

| Need | Block |
|---|---|
| Container, layout wrapper | `generateblocks/element` |
| Visible text (heading, paragraph, span) | `generateblocks/text` |
| Linked container wrapping inner blocks | `generateblocks/element` with `tagName:"a"` |
| Static image with caption | `core/image` |
| Dynamic / loop image | `generateblocks/media` |
| SVG icon | `generateblocks/shape` |
| List | `core/list` with `className:"list"` |
| Emoji | `core/paragraph` (GB renders emoji glyphs incorrectly) |

### 5.3 No mixed content inside an element block

An element block holds inner blocks OR is empty. It cannot hold raw text
between its tags.

---

## 6. Responsive rules

### 6.1 Use the installed at-rule keys

GenerateBlocks 2.4.1 registers:

```text
@media (min-width:1025px)
@media (min-width:768px)
@media (max-width:1024px) and (min-width:768px)
@media (max-width:1024px)
@media (max-width:767px)
```

Pro permits custom queries. Preserve an existing custom `max-width:768px`
boundary; use `767px` for new work that means the native Mobile query.

### 6.2 Put responsive rules in `styles`

```json
"styles":{
    "display":"grid",
    "gridTemplateColumns":"repeat(3,1fr)",
    "@media (max-width:767px)":{
        "gridTemplateColumns":"1fr"
    }
}
```

Compile the same branch into `css`. Do not add the media query only to the
cache string.

### 6.3 One selector level can be responsive

CSS Mode supports one selector level plus one at-rule level. Flatten the
selector rather than nesting selectors inside selectors:

```json
"styles":{
  "&:hover > .child":{
    "@media (max-width:767px)":{"color":"inherit"}
  }
}
```

If the rule is only cosmetic and adds brittle selector coupling, remove it.

---

## 7. Pre-flight checklist

Before saving any output to a file, verify:

**Post-scoped unique IDs**
- [ ] The actual numeric WordPress post ID was resolved before serialization
- [ ] Every newly generated ID matches `{section}-{post_id}-{sequence}{optional_suffix}`
- [ ] The sequence is not zero-padded
- [ ] No literal `{post_id}` token, slug, guessed ID, product-detail ID, or variation ID is used
- [ ] Existing stored IDs are preserved unless their blocks are being replaced
- [ ] `scripts/preflight.py <file> --post-id <ID>` passes

**JSON string escapes (the five substitutions)**
- [ ] Every `--` inside JSON strings is `\u002d\u002d`
- [ ] Every `&` inside JSON strings is `\u0026`
- [ ] Every `<` inside JSON strings is `\u003c`
- [ ] Every `>` inside JSON strings is `\u003e`
- [ ] Every `"` inside JSON string values is `\u0022` (never `\"`)

**JSON attribute order**
- [ ] Attributes appear in canonical key order (see §3.4): `uniqueId`, `tagName`, `styles`, `css`, `globalClasses`, `htmlAttributes`, `align`, then `className` if present
- [ ] `htmlAttributes` always comes BEFORE `className`
- [ ] `styles` always comes BEFORE `css`

**className and rendered class list**
- [ ] Existing blocks preserve their target convention unless replaced
- [ ] New blocks use one internally consistent convention per block type
- [ ] If `className` includes the generated ID class, the body duplicates it only when the measured target does
- [ ] The rendered HTML class list matches the chosen convention byte-for-byte

**Styles / CSS durability**
- [ ] Every transition, state, selector, and at-rule in `css` has an equivalent branch in `styles`
- [ ] Only one selector level is used
- [ ] Only `@media`, `@supports`, and `@container` are used in structured styles
- [ ] The installed or project-defined at-rule keys are used exactly
- [ ] New compiled `css` is single-line and deterministic
- [ ] CSS math keeps whitespace around `+` and `-`
- [ ] No fragile `\xxxx` generated-content escapes; use literal text or SVG

**Design quality**
- [ ] No rounded surface combines with a 2px-or-thicker border unless an approved functional exception is documented
- [ ] Cards are distinct or interactive, not wrappers around ordinary content
- [ ] Mobile/tablet composition, focus-visible, reduced motion, overflow, and long content were checked

**HTML body**
- [ ] `htmlAttributes` is `{}`, never `[]`
- [ ] All `href` values in JSON are absolute `https://...` URLs with `&` encoded as `\u0026`
- [ ] All `href` values in rendered `<a>` HTML are the same URL with `&` encoded as `&amp;`
- [ ] Element `<a>` blocks contain at least one inner block child
- [ ] No HTML comments other than WP block delimiters
- [ ] No `core/image` figure rendered as `generateblocks/media` if it has a caption (and vice versa for dynamic loop images)
