---
title: Compact GenerateBlocks authoring contract
description: Required rules for new markup and conversions; detailed schemas and recovery cases are loaded only when needed.
---

# Authoring contract

Read this before emitting GenerateBlocks markup. It is the shared contract for
the layout and conversion skills. Use `_index.md` for task-specific details;
`recovery-rules.md` is the diagnostic catalog, not a mandatory full read.

## Scope and target

- Default to **local block styles**. When introducing styling, prompt once:
  “Would you like shared Global Styles, Design Tokens, or both for reuse across
  pages? Otherwise I'll keep this layout's styling local.” Proceed with shared
  records/dependencies only after explicit opt-in; silence means local. Preserve
  existing references and theme inheritance. An earlier explicit choice for this
  task does not need another prompt. Honor the chosen subset: styles-only is not
  token approval. Edge cases: `styling-scope.md`.
- Inspect the target's actual plugin versions, block conventions, breakpoints,
  and design language. Examples are not proof of a site's current configuration.
  The tested beta pair is free 2.5.0-beta.1 / Pro 2.8.0-beta.1 on WordPress 7.1.1;
  the older local Pro folder is 2.7.0-rc.1 despite older documentation labels.
- Use the real post ID when it exists. Otherwise choose one random four-digit
  number (1000–9999) for the layout, avoiding known existing scopes. Do not create
  a WordPress record just to obtain an ID. New IDs use
  `{section}-{id_scope}-{sequence}{optional_letter}`, e.g. `hero-5824-1`.
  Call `make_layout_id(post_id=None, used_ids=...)` once, then reuse that scope
  with `make_unique_id()`. Preserve existing IDs during edits/imports unless they
  collide. The fallback is only a block-ID scope, never a REST target, form ID,
  query record, slug, or commerce variation ID. Keep sequences unpadded.

## Blocks and semantics

Use GenerateBlocks (including Pro) plus appropriate core blocks. A GenerateBlocks
design or conversion does not authorize Scripts Manager, Page Block, Custom HTML,
separate stylesheets/scripts, another builder, or theme/plugin changes. Preserve
the content using native blocks; replace custom interactions with Pro equivalents
and simplify unsupported effects. External code requires an explicit request.

| Need | Block / structure |
|---|---|
| Layout | `generateblocks/element`, semantic `section`, `div`, `article`, etc. |
| Heading, text, real button | `generateblocks/text`, appropriate `h1`–`h6`, `p`, `span`, `button` |
| Action link | element `a` with `htmlAttributes.href`, containing a text `span` |
| Image | `generateblocks/media`; use `core/image` for static captioned images |
| SVG | `generateblocks/shape` |
| Dynamic list | `generateblocks/query` → `looper` → `loop-item` |
| Lists, tables, media players, emoji | appropriate core blocks; `core/list` uses `className:"list"` |

Never substitute legacy `/container`, `/grid`, `/headline`, `/button`, `/image`,
or `/query-loop`. Text blocks are leaves; element links need child blocks, not
raw text. Inline links belong inside rich text. Do not use text `a` plus `href`
for generated action links. Consult `block-types.md` for unfamiliar tags or
optional attributes; not every HTML tag is supported by every block.

## Serialization

Prefer the installed editor's serializer for unfamiliar blocks, especially Pro
forms and interactive blocks. For repeated hand-authored output, generate from
a script using `scripts/gb_serialize.py`:

The Python helper takes short free-block names (`element`, `text`, etc.), not
namespaced or Pro names. Its public functions below are sufficient for normal
use; run the CLI tools directly with `--help` rather than reading their entire
implementations unless debugging requires it.

- `ordered(block_type, attrs)` follows block declaration order, with `className`
  last. Text's `content` is third when present; normal static text derives it from
  the HTML body and usually omits it from comment JSON.
- `serialize_attrs()` applies WordPress 7.1.1's six comment-JSON substitutions:
  literal backslash→`\u005c`, `--`→`\u002d\u002d`, `<`→`\u003c`, `>`→`\u003e`,
  `&`→`\u0026`, escaped quote→`\u0022`. Verify the serializer on older core.
  HTML outside comments keeps normal HTML escaping, e.g. `&amp;` in URLs.
- `htmlAttributes` is a plain object, never an array. Omit it when unused;
  explicit `{}` can become `[]` during a server save. Omit empty `styles` for
  the same reason. Use absolute destinations; preserve intentional fragment
  links when same-page navigation or scripts depend on them.
- Match native class output. Styled element blocks can use
  `gb-element-{id} gb-element`; styled text commonly uses base-first
  `gb-text gb-text-{id}`. Unstyled native blocks may omit the ID class. Preserve
  the target convention rather than normalizing existing content.
- Pro prefixes vary: accordion children use names such as
  `gb-accordion__toggle-{id}`. Read native save output; do not infer every prefix
  from the block slug or visual role. An answer container can be an Element,
  so compiling it as `.gb-text-{id}` silently misses the rendered block.
- Preserve native parsed rich-text values when cloning. Deep-cloning the entire
  attributes object can lose their behavior and produce valid but empty blocks.
  RichText can also discard empty formatting tags such as decorative `<i>`;
  validate the rendered elements, not just their text or block validity.
- Emit only WordPress block comments, balance blocks/tags, and keep generated
  nesting compact. Byte parity checks transport integrity; JSON formatting
  differences alone do not prove a Gutenberg recovery error.

## Local styles

`styles` is editable source; `css` is its compiled cache. Store base declarations,
transitions, hover/focus states, pseudo-elements, and responsive branches in
`styles`, then compile **all the same rules** with `build_css(selector, styles)`.
Never keep a rule only in `css`. CSS Mode has no `cssMode` attribute.

Use camelCase declarations, `&:hover`/`&:focus-visible` for states, and
`& .child` for descendants. Supported structure is one selector level plus one
`@media`, `@supports`, or `@container` level. Distribute source declarations to
their owning blocks; stylesheet length is not a reason to move styling outside
GenerateBlocks. Flatten supported selectors. Omit or simplify effects requiring
keyframes, font declarations, imports, or unsupported nesting unless the user
explicitly requests external code. Direct CSS
editing or unusual selectors require `css-mode.md`.

Native Tablet & Mobile is `@media (max-width:1024px)`; Mobile is
`@media (max-width:767px)`. Preserve deliberate custom boundaries. Use
`minmax(0,1fr)` for shrinking grid tracks. Compile compact CSS but retain required
spaces around `+`/`-` in `calc()` and `clamp()`. Advanced responsive cases route
to `responsive.md`. In the beta native editor, `getCss()` is asynchronous.

Free 2.5 local CSS is inline-only. Older local modes and Pro Global Styles have
different delivery paths; consult `performance.md` only for delivery/cache work.

## Content and validation

- For dynamic content, read `dynamic-tags.md` plus the relevant query/field guide.
  Syntax is `{{tag option:value|option2:value}}`, with a space, pipes, and no
  quoted values. Do not guess tags (`post_url`, `featured_image_url`, `acf`).
- Preserve source copy, links, images, heading meaning, and interactions. Check
  visible content as well as block validity. Do not invent proof or testimonials.
- Accordions/Details need explicit question and answer padding. Check open and
  closed states in the editor and frontend, including mobile; text must not touch
  the edges of a bordered panel.
- For new/materially changed designs, apply `design-quality.md` and the nearest
  brand guidance (`gt-design` for GT/Gatilab). No decorative eyebrows, invented
  zero-padded labels, card soup, or thick borders on rounded surfaces. Retain
  keyboard/focus behavior, readable contrast, and deliberate mobile composition.
- Write block output to files, not chat. Run `scripts/preflight.py FILE --id-scope
  SCOPE` before delivery (`--post-id` is a compatible alias). It checks serialization/style hazards but does not replace
  native editor validation or functional testing. Fix failures, then retest only
  the affected checks. Use `recovery-rules.md` for the reported symptom.
  Count blocks recursively with the native parser; preflight's displayed count
  covers only its matched attributed free blocks.
- A live write additionally requires `mcp-publishing.md`: verify the exact target,
  snapshot `content.raw`, splice intended changes, write, fetch `content.raw`, and
  run `verify_roundtrip.py --local SENT --remote READBACK`. Check rendering and
  relevant interactions before claiming completion.
