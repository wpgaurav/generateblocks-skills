---
title: Skill router (read first)
description: Tells Claude which reference file to open for which task. Always check this before generating GenerateBlocks markup.
---

# GenerateBlocks Skill Router

Read this file FIRST, then load only what the task needs. Loading the wrong
file (or loading too much) wastes context — be precise.

Verified against: GenerateBlocks free **2.4.1**, GB Pro **2.7.1** on
WordPress **7.1** (live + installed source, 2026-08-26).

## Before doing anything

**Always read these two, every task:**

1. `references/recovery-rules.md` — every cause of "Attempt Recovery" errors
   plus the exact fix. The bug-prevention manual. Non-negotiable.
2. The task file(s) from the table below.

If the task touches dynamic data in ANY way (loops, custom fields, dates,
titles, archives), also read `dynamic-tags.md` — older tag syntax floating
around the internet (and in old markup) is wrong and silently fails.

---

## Task → file routing

| If the user is asking for... | Read |
|---|---|
| A static section / hero / cards / grid (no dynamic data) | `block-types.md`, `css-patterns.md` |
| **CSS Mode, CSS Properties, raw CSS, nested selectors, `styles` vs `css`** | `css-mode.md` |
| Hover, focus, transitions, pseudo-elements, child selectors | `css-mode.md`, `css-patterns.md` |
| Entrance animations, scroll effects, micro-interactions, motion | `animations.md` |
| SVG icons or decorative shapes | `svg-icons.md` |
| Responsive layout / breakpoints | `responsive.md` |
| Container queries, `@supports`, reduced motion, forced colors | `css-mode.md`, `responsive.md` |
| **Blog grid, archive, related posts, any dynamic post list** | `query-block.md` + `dynamic-tags.md` |
| Pagination on a query | `query-block.md` §1.5 |
| Dynamic titles, dates, images, meta, author boxes | `dynamic-tags.md` |
| **ACF fields, repeaters, options pages, Meta Box, custom fields** | `acf-and-custom-fields.md` |
| Conditional visibility (roles, devices, scheduling, meta) | `conditions.md` |
| Accordion / tabs / carousel / mega menu / modal / site header | `pro-interactive.md` |
| Contact form, newsletter signup, any form | `pro-forms.md` |
| **Full site: headers, footers, archive/single templates, FSE, GeneratePress Elements** | `template-authoring.md` |
| "What needs Pro?" / Pro feature overview / global classes | `gb-pro.md` |
| Design tokens, theme.json bridge | `global-styles.md` |
| Block patterns / pattern registration | `patterns.md` |
| Performance / CSS delivery | `performance.md` |
| Inventing or materially changing a visual design | `design-quality.md` (+ `/design-slop` when available) |
| Migrating V1 blocks (`container`, `headline`, `grid`) | `migrations.md` |
| Markup that's failing / debugging | `troubleshooting.md`, `recovery-rules.md` |
| Hand-converting an existing design / bulk escaping / pre-delivery validation | `field-notes.md` |
| Core `core/query` loops (only if explicitly requested) | `query-loops.md` |

---

## File map

```
references/
├── _index.md                 ← you are here
├── recovery-rules.md         ← MUST read every task. Recovery error catalog.
├── field-notes.md            ← Real-conversion lessons: escaping workflow, validation script
├── block-types.md            ← Element/Text/Media/Shape verified specs
├── css-mode.md               ← Pro CSS Mode, supported selectors/at-rules, styles/css contract
├── design-quality.md         ← GenerateBlocks-specific anti-slop implementation gate
├── dynamic-tags.md           ← Canonical tag catalog + syntax. Wins all conflicts.
├── query-block.md            ← Query/Looper/Loop-Item + Pro query extensions
├── acf-and-custom-fields.md  ← ACF patterns, repeater loops, options pages
├── conditions.md             ← Pro conditions + free alternatives
├── template-authoring.md     ← Full-site building: FSE, GP Elements, templates
├── animations.md             ← Motion: hover, keyframes, scroll-driven, a11y
├── gb-pro.md                 ← Pro overview + feature map (2.7)
├── pro-forms.md              ← Forms system deep dive
├── pro-interactive.md        ← Accordion/Tabs/Carousel/Nav/Header/Overlays
├── css-patterns.md           ← Durable states, surfaces, buttons, selectors
├── svg-icons.md              ← Shape block + inline SVG patterns
├── responsive.md             ← Native/custom at-rules + responsive composition
├── global-styles.md          ← Design tokens, theme.json bridge
├── patterns.md               ← Block pattern registration
├── performance.md            ← CSS delivery optimization
├── migrations.md             ← V1 → V2 migration guide
├── query-loops.md            ← LEGACY core/query patterns (only on request)
├── responsive-legacy.md      ← Older breakpoint patterns (reference only)
└── troubleshooting.md        ← Debug recipes for known failures
```

---

## Output rules (always apply)

1. **Output to a file**, never inline in chat. Filename: `{section}-section.html`
   or `{slug}.html`. Place in `output/` if working in this repo, otherwise
   wherever the user wants.
2. **Resolve the actual WordPress post ID before serialization.** A new record
   must be created as a draft first. Generate new IDs as
   `{section}-{post_id}-{sequence}` and never leave `{post_id}` literal.
3. **Run the pre-flight checklist** from `recovery-rules.md` §7 against your
   output before saving. Use `scripts/preflight.py <file> --post-id <ID>`.
4. **Summarize in chat**: purpose, block count, anything that needs Pro,
   anything skipped due to a recovery rule.
5. **Anti-slop gate**: if you invented or materially changed any part of the
   design, run `design-quality.md` before delivering and load `/design-slop`
   when it is available. The nearest project design system owns brand-specific
   decisions.

---

## Decision shortcuts

- **Static image with caption?** → `core/image`, not `generateblocks/media`.
- **Dynamic image inside a loop?** → `generateblocks/media` with
  `{{featured_image size:large}}` in `htmlAttributes.src`.
- **Action link / button-styled link?** → element `<a>` wrapping a text `span`
  child. Never text `<a>` with href (stripped). Never element `<a>` with raw
  text (recovery).
- **Inline link inside a sentence?** → write the `<a>` in the text block's
  rich-text content (escaped in JSON, literal in HTML body).
- **List?** → `core/list` with `className:"list"`.
- **Emoji?** → `core/paragraph`.
- **Dynamic tag?** → `{{tag option:value|option2:value}}` — space after tag
  name, pipes between options, NO quotes. `dynamic-tags.md` is law.
- **ACF field?** → `{{post_meta key:field_name}}` (no `{{acf}}` tag exists).
- **ACF repeater loop?** → Pro `queryType:"post_meta"` — `acf-and-custom-fields.md` §4.
- **Related posts?** → Pro `"post__not_in":["current"]` + tax_query
  `"terms":["current"]`.
- **Inheriting an archive query?** → `"inheritQuery":true,"query":{}`.
- **Conditional block?** → Pro `"gbBlockCondition":<condition post ID>` —
  rules are built in the dashboard, not inline.
- **CSS variable in JSON?** → escape it: `var(\u002d\u002dgb-foo)`.
- **CSS variable in inline `style=""`?** → literal: `var(--gb-foo)`.
- **Quote inside a JSON string value?** → `\u0022`, never `\"`.
- **GenerateBlocks Mobile?** → `@media (max-width:767px)` in 2.4.1.
- **Existing `max-width:768px` rule?** → preserve it as a custom boundary
  unless a breakpoint migration was explicitly approved.
- **CSS Mode?** → edit `styles`; compile the same structure into local `css`.
  Never invent a `cssMode` attribute.

Default minimum context for any task: `recovery-rules.md` + `block-types.md`
+ the task file. Add `dynamic-tags.md` whenever data is dynamic.
