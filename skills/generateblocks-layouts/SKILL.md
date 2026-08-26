---
name: generateblocks-layouts
description: Build and audit WordPress layouts with GenerateBlocks V2, including CSS Mode, responsive at-rules, dynamic data, Pro components, and recovery-safe block serialization. Use for new GB layouts, conversions, repairs, and hand-authored block markup.
metadata:
  compatibility: "GenerateBlocks 2.4.1 + GenerateBlocks Pro 2.7.1 on WordPress 7.1. Live- and source-verified 2026-08-26."
---

# GenerateBlocks V2 Layout Builder

Build professional WordPress layouts with GenerateBlocks V2: four core blocks,
the Query/Looper family for dynamic content, dynamic tags for data binding,
and GB Pro for CSS Mode, global styles, interactive components, forms, and
conditions.

## Read first — routing

This file holds only the non-negotiables. The depth lives in `references/`:

1. **`references/_index.md`** — task router. Tells you which files to load.
2. **`references/recovery-rules.md`** — every known cause of "Attempt
   Recovery" errors with the exact fix. **Read on every task.** Skip it and
   you will produce broken markup.
3. The task-specific reference(s) from the router — query loops, dynamic
   tags, ACF, animations, conditions, forms, template authoring, Pro blocks.

For styling work, also read:

- **`references/css-mode.md`** when CSS Mode, raw CSS, nested selectors,
  Global Styles, or the `styles`/`css` relationship matters.
- **`references/responsive.md`** for any breakpoint, media query, container
  query, or responsive composition.
- **`references/design-quality.md`** whenever the skill invents or materially
  changes a visual direction.

If the task involves dynamic data at all, `references/dynamic-tags.md` is
mandatory — the tag syntax is precise and wrong forms fail silently.

## The meta-rule (read this twice)

> The WordPress block editor validates blocks by **re-serializing and
> string-comparing against the markup you pasted**. Any deviation — even
> semantically-equivalent JSON or HTML — is treated as corruption and
> triggers "Attempt Recovery".

Emit what the editor emits, not what you think is correct. That means the
canonical JSON key order, the five string substitutions
(`--`→`\u002d\u002d`, `<`→`\u003c`, `>`→`\u003e`, `&`→`\u0026`,
`\"`→`\u0022`), and the exact class lists. Compile new `css`
deterministically from `styles` so later editor saves remain stable, but do not
misdiagnose harmless formatting in an existing CSS cache as a recovery cause.
Details: `recovery-rules.md` and `css-mode.md`.

## The blocks

| Block | Class pattern | Use for |
|---|---|---|
| `generateblocks/element` | `gb-element-{id} gb-element` | Containers: div, section, article, header, footer, nav, main, figure, a, ul, ol, li, dl, dt, dd |
| `generateblocks/text` | `gb-text-{id} gb-text` | Text: p, span, div, h1–h6, a, button, figcaption, li |
| `generateblocks/media` | `gb-media-{id} gb-media` | Images (img only). Static AND dynamic loop images |
| `generateblocks/shape` | `gb-shape-{id} gb-shape` | Inline SVG icons/shapes |
| `generateblocks/query` + `looper` + `loop-item` (+ `query-no-results`, `query-page-numbers`) | `gb-query-{id}` etc. | All dynamic post lists — `query-block.md` |

GB Pro adds 27 more (accordion, tabs, carousel, navigation, site-header,
forms) — `gb-pro.md` has the map.

Use core blocks for specialized content: `core/image` (captions),
`core/list`, `core/table`, `core/video`, `core/embed`, `core/paragraph`
(emoji). Full table: `block-types.md` §5.

## The ten commandments

Before serialization, apply these 2 default presentation rules:

- **Do not invent decorative eyebrows.** Start with the heading unless the
  nearest project design system explicitly requires a useful category, status,
  or navigation label. Repeated tiny uppercase pretitles are not hierarchy.
- **Never use zero-padded numbers.** Use `1`, `2`, and `3`, never `01`, `02`, or
  `03`, in visible copy, step labels, section numbering, filenames, CSS classes, or
  newly generated IDs. Preserve exact fixed-format external data only, such as ISO
  dates, versions, timestamps, URLs, code samples, or third-party IDs.

1. **Canonical attribute order per block** (block.json declaration order;
   `className` last). Text block puts `content` 3rd. Pro blocks differ —
   check `pro-interactive.md` before emitting Pro JSON.
2. **The five JSON substitutions** on every string value in block comments.
   Inline HTML `style=""` in the body is NOT JSON — literal characters there.
3. **Class lists follow the measured target.** For a new element block, prefer
   Option A: `"className":"gb-element"`, rendered as
   `class="gb-element-{id} gb-element"`. Text/media/shape commonly omit
   `className` and render base-first. Preserve an existing block's convention;
   never normalize it during an unrelated edit.
4. **`htmlAttributes` is a plain object**, never an array. Absolute URLs in
   `href`.
5. **`styles` is the editable source; `css` is its compiled cache.** Put base
   declarations, nested selectors, transitions, and supported at-rules in
   `styles`, then mirror the compiled result in `css`. CSS Mode supports one
   selector level plus `@media`, `@supports`, and `@container`. A selector in
   `css` without an equivalent `styles` branch is durability debt because an
   editor save can remove it. Details: `css-mode.md`.
6. **Links**: element `<a>` wrapping a text `span` child. Text `<a>` strips
   its href; element `<a>` with raw text triggers recovery. Inline links go
   inside a text block's rich-text content.
7. **Dynamic tags**: `{{tag option:value|option2:value}}` — space after the
   tag name, pipes between options, no quotes ever. `{{post_permalink}}`,
   `{{featured_image size:large}}`, `{{post_meta key:field}}`,
   `{{term_list tax:category}}`. Wrong tags save fine and render as literal
   text — worse than recovery. `dynamic-tags.md` is law.
8. **No HTML comments** other than `<!-- wp:... -->` delimiters. Compact
   nesting — closing comment adjacent to closing tag.
9. **Dynamic loop images** use `generateblocks/media` (+ tag in `src`);
   static captioned images use `core/image`.
10. **Responsive**: use the installed build's exact at-rule keys. In 2.4.1 the
    native defaults are `@media (max-width:1024px)` for Tablet & Mobile and
    `@media (max-width:767px)` for Mobile. Pro allows custom queries. Preserve
    existing custom `768px` rules; do not silently rewrite their boundary.
    Keep at-rules in `styles` and mirror them in `css`.

## Output requirements

- **Always write generated blocks to a file** (`{section-name}.html`), never
  inline in chat — block code breaks chat formatting and truncates.
- **Run `scripts/preflight.py <file>` before delivering.** It executes the
  `recovery-rules.md` §7 checklist as assertions and catches what inspection
  misses: no-op escape tables, misordered keys, duplicate uniqueIds, stray
  CSS not represented in `styles`, mixed class-list conventions, thick rounded
  surfaces, and invalid `clamp()` `+` spacing.
  Pass the actual WordPress post ID with `--post-id N`; new output fails if any
  GenerateBlocks ID is outside that post namespace. Add `--links N` to assert
  the internal link count survived a conversion.
- Summarize in chat: what was built, block count, anything needing Pro.

## Tooling

| Script | Use |
|---|---|
| `scripts/gb_serialize.py` | `make_unique_id()` for post-scoped IDs, `build_css()` for `styles`→`css`, `serialize_attrs()` for all five WP substitutions, and `ordered()` for canonical key order. Import these; don't re-derive them by hand. |
| `scripts/preflight.py` | Pre-delivery validation. Run with `--post-id N` for all new output. Exit 0 = clean. |

For anything beyond a few blocks, **generate the markup from a script** rather
than hand-typing it. Hand-authored escapes and key order drift; a generator plus
preflight does not. See `field-notes.md` §1.1 for the escape-table no-op trap
that has shipped broken markup more than once.

## Before hand-authoring for an EXISTING site

Measure the target's conventions first — plugin behaviour varies by version and
the live page is ground truth for the build actually installed. `field-notes.md`
§7 has the inspection script. Check versions via authenticated
`GET /wp-json/wp/v2/plugins`. Treat existing content as evidence, not as a
template: a site can contain editor-authored blocks, imported patterns, and
hand-authored markup with different conventions. Two points matter most:

- preserve the target's established `content`/`className` convention per block
  type rather than normalizing it during an unrelated edit;
- the `css` attribute is not re-derived during initial block validation, but a
  later style edit can recompile it from `styles`.

Resolve the numeric WordPress post ID before generating any block IDs. For a new
page, product, post, or template, create the record as a draft first, read back its
ID, and only then serialize the blocks. Never substitute the slug, FluentCart
product-detail ID, variation ID, a guessed number, or a literal `{post_id}` token.

## Unique ID convention

Every newly generated ID uses
`{section}-{post_id}-{sequence}{optional_suffix}`. Examples for WordPress post
`1173976`: `hero-1173976-1`, `hero-1173976-2a`, `card-1173976-14`.

- `section` is a short lowercase component name such as `hero`, `card`, or `faq`.
- `post_id` is the actual numeric WordPress post ID.
- `sequence` starts at `1` and is never zero-padded.
- `optional_suffix` is a lowercase nesting letter only when useful.

Use `make_unique_id('hero', post_id, 1)` from `scripts/gb_serialize.py` instead
of assembling IDs by hand. The `{post_id}` notation is documentation only and
must never survive into serialized markup. Preserve IDs already stored on an
existing block unless that block is being replaced; do not rename established IDs
solely to conform to this convention.

## Design inference (when no design is given)

Read the nearest project design system and inspect the target page before
inventing tokens. Theme variables, existing Global Styles, container width,
type scale, and current breakpoints outrank this skill.

If no project guidance exists, use a quiet baseline: inherit typography,
`var(--gb-container-width)` for the main rail, a restrained spacing scale,
1px separators, modest radii, and one clear action. Do not infer a site's
current design language from an old example or hard-code a brand palette.

For `gauravtiwari.org` or `gatilab.com`, load `/gt-design`; it owns the current
GT design language. The GenerateBlocks skill supplies delivery mechanics only.

## Design quality — no slop

Any design this skill **invents** or materially changes must pass
`references/design-quality.md`. If `/design-slop` is available, load it too;
the nearest project design system still owns brand-specific decisions. The
non-negotiables either way:

- **Never a thick border (2px+) on a rounded card, callout, or container**
  — including one thick side. Separate with whitespace first, then a 1px
  hairline, then a subtle background tint. Shadows only for elements that
  truly float.
- Cards only for independently interactive or genuinely distinct units.
  Whitespace before containers, dividers before cards — no card soup.
- One dominant visual idea per section. Gradients, glows, glass, blur, and
  decorative motion stay rare and purposeful — never crutches for weak
  hierarchy.
- Consistent, modest radius scale. Not every element a pill.
- Hierarchy comes from type scale, spacing, and alignment — not decoration.
- Real content and real states: no fabricated metrics or testimonials;
  survive long titles and empty fields; compose mobile deliberately instead
  of just stacking.
- Responsive behavior is part of the design, not a cleanup pass. Verify the
  content order, tap targets, overflow, long strings, and at least one awkward
  tablet width.

Final check: if the business name could be swapped without the section
changing, it's generic — make it specific before shipping.

## Complex layout strategy

For 50+ block sections: map the structure first, build bottom-up, keep one
post-scoped ID family per component, validate each chunk against the checklist
before assembling. See `troubleshooting.md` for failure recipes.

## Examples

Structural block templates live in `examples/`: `basic/` (buttons, containers),
`compound/` (cards), `layouts/` (hero, query blog grid), `svg/` (icons).
Namespace every example with the target post ID before serialization; an example
ID never overrides the convention above.
Golden full sections live at the repo root `examples/` (14 section types +
production pages from gauravtiwari.org).
