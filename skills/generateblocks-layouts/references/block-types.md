---
title: Block Types Reference
description: Source-verified attribute specifications for the four GenerateBlocks V2 core blocks (element, text, media, shape) with canonical markup examples.
---

# Block Types Reference

The four core V2 blocks, verified against `dist/blocks/*/block.json` in
GB 2.4.1. All examples follow `recovery-rules.md` (target-aware class lists, plain
object `htmlAttributes`, escaped `--`, sorted minified css).

2.4 note: `text.content` and `media.mediaId` are flagged `"role":"content"`
in block.json (powers Pro 2.7 content-only editing). Editor schema only — it
changes neither attribute order nor saved markup.

Common declared attributes on every block: `uniqueId`, `styles` (object),
`css` (string), `globalClasses` (array), `htmlAttributes` (**plain object**).
Existing markup can also carry a `className` field. When present, it serializes
last. Preserve the target's established convention and make the rendered class
list match; do not normalize class order during an unrelated edit.

The `styles` object accepts camelCase CSS properties, one level of nested
selectors (`&:hover`, `&::before`, `& > .child`), and `@media` / `@supports`
/ `@container` at-rule keys (those three only). Read `css-mode.md`.

---

## 1. Element Block (`generateblocks/element`)

Container for layout structure. Holds inner blocks — never raw text.

**Attribute order:** `uniqueId, tagName, styles, css, globalClasses, htmlAttributes, align` (+ `className` last)

**tagName enum (verified):** `div`, `section`, `article`, `aside`, `header`,
`footer`, `nav`, `main`, `figure`, `a`, `ul`, `ol`, `li`, `dl`, `dt`, `dd`

No `blockquote`, no `form`, no `button` — those need core blocks or a text
block (`button`).

### Basic container

```html
<!-- wp:generateblocks/element {"uniqueId":"cont1","tagName":"div","styles":{"backgroundColor":"#f5f5f5","padding":"2rem"},"css":".gb-element-cont1{background-color:#f5f5f5;padding:2rem}","className":"gb-element"} -->
<div class="gb-element-cont1 gb-element">
    <!-- child blocks -->
</div>
<!-- /wp:generateblocks/element -->
```

### Full-width section + inner container

```html
<!-- wp:generateblocks/element {"uniqueId":"sect1","tagName":"section","styles":{"backgroundColor":"#0a0a0a","paddingTop":"6rem","paddingBottom":"6rem","paddingLeft":"1.5rem","paddingRight":"1.5rem"},"css":".gb-element-sect1{background-color:#0a0a0a;padding:6rem 1.5rem}","align":"full","className":"gb-element alignfull"} -->
<section class="gb-element-sect1 gb-element alignfull">
    <!-- wp:generateblocks/element {"uniqueId":"sect2","tagName":"div","styles":{"maxWidth":"var(\u002d\u002dgb-container-width)","marginLeft":"auto","marginRight":"auto"},"css":".gb-element-sect2{margin-left:auto;margin-right:auto;max-width:var(\u002d\u002dgb-container-width)}","className":"gb-element"} -->
    <div class="gb-element-sect2 gb-element">
        <!-- section content -->
    </div>
    <!-- /wp:generateblocks/element -->
</section>
<!-- /wp:generateblocks/element -->
```

### Responsive grid

```html
<!-- wp:generateblocks/element {"uniqueId":"grid1","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"repeat(3,minmax(0,1fr))","gap":"2rem","@media (max-width:1024px)":{"gridTemplateColumns":"repeat(2,minmax(0,1fr))"},"@media (max-width:767px)":{"gridTemplateColumns":"1fr"}},"css":".gb-element-grid1{display:grid;gap:2rem;grid-template-columns:repeat(3,minmax(0,1fr))}@media (max-width:1024px){.gb-element-grid1{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (max-width:767px){.gb-element-grid1{grid-template-columns:1fr}}","className":"gb-element"} -->
<div class="gb-element-grid1 gb-element">
    <!-- grid items -->
</div>
<!-- /wp:generateblocks/element -->
```

### Link wrapper (the canonical action-link pattern)

Element `<a>` wraps inner blocks; href lives in `htmlAttributes` (plain
object, absolute URL):

```html
<!-- wp:generateblocks/element {"uniqueId":"link1","tagName":"a","styles":{"display":"block","textDecoration":"none"},"css":".gb-element-link1{display:block;text-decoration:none}","htmlAttributes":{"href":"https://example.com/page/","target":"_blank","rel":"noopener"},"className":"gb-element"} -->
<a class="gb-element-link1 gb-element" href="https://example.com/page/" target="_blank" rel="noopener">
    <!-- wp:generateblocks/text {"uniqueId":"link2","tagName":"span","styles":{},"css":""} -->
    <span class="gb-text">Read the guide →</span>
    <!-- /wp:generateblocks/text -->
</a>
<!-- /wp:generateblocks/element -->
```

See `recovery-rules.md` §4 for why links must be element-`<a>`-wrapping-text,
never a bare text `<a>` with href, never element `<a>` with raw text.

---

## 2. Text Block (`generateblocks/text`)

Leaf block for visible text. Cannot contain inner blocks; CAN contain inline
HTML (`<strong>`, `<em>`, `<a>`, `<span style="...">`) in its rich-text content.

**Attribute order:** `uniqueId, tagName, content, styles, css, globalClasses, htmlAttributes, icon, iconLocation, iconOnly` (+ `className` last) —
**`content` is 3rd when serialized**, the only block where styles is not 3rd.
For ordinary static rich text, the editor normally derives `content` from the
HTML body and omits it from the delimiter JSON. Preserve a target that already
uses another convention; do not add `content` merely to duplicate body text.

**tagName enum (verified):** `p`, `span`, `div`, `h1`–`h6`, `a`, `button`,
`figcaption`, `li`

### Heading

```html
<!-- wp:generateblocks/text {"uniqueId":"head1","tagName":"h1","styles":{"fontSize":"clamp(2rem,5vw,3.5rem)","fontWeight":"900","lineHeight":"1.1","letterSpacing":"-0.03em","color":"#0a0a0a"},"css":".gb-text-head1{color:#0a0a0a;font-size:clamp(2rem,5vw,3.5rem);font-weight:900;letter-spacing:-0.03em;line-height:1.1}"} -->
<h1 class="gb-text gb-text-head1">Page Title</h1>
<!-- /wp:generateblocks/text -->
```

### Paragraph with inline link

Inline links go in the rich-text body, not separate blocks. If the target
convention genuinely serializes a `content` attribute (for example a dynamic
binding), escape its inline HTML with the five substitutions. Ordinary static
rich text can remain body-only:

```html
<!-- wp:generateblocks/text {"uniqueId":"para1","tagName":"p","styles":{"fontSize":"1.125rem","lineHeight":"1.7","color":"#5c5c5c"},"css":".gb-text-para1{color:#5c5c5c;font-size:1.125rem;line-height:1.7}"} -->
<p class="gb-text gb-text-para1">Read our <a href="https://example.com/guide/">full guide</a> for details.</p>
<!-- /wp:generateblocks/text -->
```

### Icon support (`icon`, `iconLocation`, `iconOnly`)

A text block can carry an inline SVG icon. The icon HTML is stored in the
`icon` attribute (sourced from the `.gb-shape` span in the body):

```html
<!-- wp:generateblocks/text {"uniqueId":"feat1","tagName":"p","content":"Fast delivery","styles":{"display":"flex","alignItems":"center","columnGap":"0.5rem"},"css":".gb-text-feat1{align-items:center;column-gap:0.5rem;display:flex}","icon":"\u003csvg viewBox=\u00220 0 24 24\u0022 fill=\u0022none\u0022 stroke=\u0022currentColor\u0022 stroke-width=\u00222\u0022\u003e\u003cpath d=\u0022M5 13l4 4L19 7\u0022/\u003e\u003c/svg\u003e","iconLocation":"before","className":"gb-text"} -->
<p class="gb-text-feat1 gb-text"><span class="gb-shape"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg></span>Fast delivery</p>
<!-- /wp:generateblocks/text -->
```

`iconLocation`: `"before"` (default) or `"after"`. `iconOnly:true` hides the
text visually. The icon SVG appears twice — escaped in the JSON `icon`
attribute AND literally inside the `.gb-shape` span in the body; they must
match. If this dual-encoding feels risky, use a separate
`generateblocks/shape` block instead (simpler, same visual result).

### Button-tag text (for JS-triggered actions, not links)

```html
<!-- wp:generateblocks/text {"uniqueId":"btn1","tagName":"button","styles":{"backgroundColor":"#c0392b","border":"0","borderRadius":"0.375rem","color":"#ffffff","cursor":"pointer","padding":"0.875rem 1.75rem"},"css":".gb-text-btn1{background-color:#c0392b;border:0;border-radius:0.375rem;color:#ffffff;cursor:pointer;padding:0.875rem 1.75rem}"} -->
<button class="gb-text gb-text-btn1">Subscribe</button>
<!-- /wp:generateblocks/text -->
```

For links styled as buttons, use the element-`<a>` + text-span pattern, not
text `<a>` (its href doesn't survive saves).

---

## 3. Media Block (`generateblocks/media`)

Images only. **tagName enum: `img` — nothing else.** Self-closing body.

**Attribute order:** `uniqueId, tagName, styles, css, globalClasses, htmlAttributes, mediaId, linkHtmlAttributes` (+ `className` last)

| Attribute | Notes |
|---|---|
| `mediaId` | WP attachment ID (number). When > 0, the server adds `width`/`height`/`srcset`/`sizes` automatically at render. Use `0`/omit for external or dynamic images |
| `htmlAttributes` | `src` (required), `alt` (required — empty string for decorative), `loading`, `width`, `height` |
| `linkHtmlAttributes` | Plain object; when set with `href`, the save wraps the img in an `<a>` |

There is **no `mediaType` attribute** — older docs that show it are wrong.

### Static image (no caption)

```html
<!-- wp:generateblocks/media {"uniqueId":"img1","tagName":"img","styles":{"width":"100%","height":"auto","borderRadius":"1rem"},"css":".gb-media-img1{border-radius:1rem;height:auto;width:100%}","htmlAttributes":{"src":"https://example.com/photo.jpg","alt":"Team at work","loading":"lazy"},"className":"gb-media"} -->
<img class="gb-media-img1 gb-media" src="https://example.com/photo.jpg" alt="Team at work" loading="lazy"/>
<!-- /wp:generateblocks/media -->
```

### Dynamic image in a loop

```html
<!-- wp:generateblocks/media {"uniqueId":"img2","tagName":"img","styles":{"aspectRatio":"16/9","objectFit":"cover","width":"100%"},"css":".gb-media-img2{aspect-ratio:16/9;object-fit:cover;width:100%}","htmlAttributes":{"src":"{{featured_image size:large}}","alt":"{{featured_image key:alt}}","loading":"lazy"},"className":"gb-media"} -->
<img class="gb-media-img2 gb-media" src="{{featured_image size:large}}" alt="{{featured_image key:alt}}" loading="lazy"/>
<!-- /wp:generateblocks/media -->
```

(Tag syntax: `dynamic-tags.md`. `mediaId` omitted — there's no fixed attachment.)

### When NOT to use media

| Case | Use instead |
|---|---|
| Image with caption | `core/image` (figcaption support) |
| Gallery | `core/gallery` |
| Video / audio / embeds | `core/video`, `core/audio`, `core/embed` |

---

## 4. Shape Block (`generateblocks/shape`)

Inline SVG wrapped in a `<span class="gb-shape-{id} gb-shape">`. No `tagName`.

**Attribute order:** `uniqueId, html, styles, css, globalClasses, htmlAttributes` (+ `className` last)

The `html` attribute is HTML-sourced from the `.gb-shape` selector — the SVG
appears ONLY in the body (not duplicated in JSON), which makes shape the
safest icon carrier.

### Styling

Two working approaches:

1. **`styles.svg` object** — generates `.gb-shape-{id} svg{...}`:

```html
<!-- wp:generateblocks/shape {"uniqueId":"icon1","styles":{"display":"inline-flex","svg":{"fill":"currentColor","height":"1.5rem","width":"1.5rem"}},"css":".gb-shape-icon1{display:inline-flex}.gb-shape-icon1 svg{fill:currentColor;height:1.5rem;width:1.5rem}","className":"gb-shape"} -->
<span class="gb-shape-icon1 gb-shape"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
<!-- /wp:generateblocks/shape -->
```

2. **Wrapper styles + inline SVG attributes** (live-site pattern):

```html
<!-- wp:generateblocks/shape {"uniqueId":"check1","styles":{"width":"20px","height":"20px","color":"#10b981"},"css":".gb-shape-check1{color:#10b981;height:20px;width:20px}","className":"gb-shape"} -->
<span class="gb-shape-check1 gb-shape"><svg stroke-linejoin="round" stroke-linecap="round" stroke-width="3" stroke="currentColor" fill="none" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
<!-- /wp:generateblocks/shape -->
```

SVG attribute order in the body matters for round-trip stability — write:
`stroke-linejoin`, `stroke-linecap`, `stroke-width`, `stroke`, `fill`,
`viewBox`, `height`, `width` (the editor's observed reorder). More patterns:
`svg-icons.md`.

---

## Attribute summary

| Block | Order (block.json) | tagName enum | Inner blocks |
|---|---|---|---|
| element | uniqueId, tagName, styles, css, globalClasses, htmlAttributes, align | div section article aside header footer nav main figure a ul ol li dl dt dd | Yes |
| text | uniqueId, tagName, **content**, styles, css, globalClasses, htmlAttributes, icon, iconLocation, iconOnly | p span div h1–h6 a button figcaption li | No (inline HTML in content) |
| media | uniqueId, tagName, styles, css, globalClasses, htmlAttributes, mediaId, linkHtmlAttributes | img | No |
| shape | uniqueId, **html**, styles, css, globalClasses, htmlAttributes | — | No |

All four support `align: false`, `className: false` in block.json — meaning
no core alignment toolbar / additional-classes panel in the UI. `align` and
`className` still serialize (GB element declares `align` itself; `className`
comes from the serializer) — keep using `"align":"full"` +
`"className":"gb-element alignfull"` for full-width, exactly as production
markup does.

---

## 5. When to use core blocks instead

| Content | Block | Why |
|---|---|---|
| Captioned image | `core/image` | figcaption |
| Gallery / video / audio / embeds | `core/gallery` `core/video` `core/audio` `core/embed` | players & lightbox |
| Data table | `core/table` | semantics |
| List content | `core/list` (`className:"list"`) | rich-text list items |
| Quote with citation | `core/quote` | cite support |
| Code | `core/code` | escaping handled |
| Emoji-heavy text | `core/paragraph` | GB renders emoji glyphs incorrectly |
| Collapsible without Pro | `core/details` | free `<details>/<summary>` |

**Rule of thumb:** GB for structure + styling + dynamic data; core for
specialized content with built-in behavior. For dynamic post lists, prefer
GB query family over `core/query` (see `query-block.md`).
