# AGENTS.md

Universal instructions for all LLM assistants working in this repo.
**The deep knowledge lives in `skills/generateblocks-layouts/`** — read its
`SKILL.md`, then `references/_index.md` (router), then
`references/recovery-rules.md` before emitting any GenerateBlocks markup.
This file is only the orientation summary.

Plugin source in this repo: GenerateBlocks free **2.4.1** (`generateblocks/`),
GB Pro **2.7.1** (`generateblocks-pro/`). The skill was also checked against
the same active versions on WordPress 7.1 at gauravtiwari.org on 2026-08-26.
Canonical current docs: learn.generatepress.com.

## V2 block names (never use V1 names)

| Need | Correct block | NOT these |
|---|---|---|
| Containers | `generateblocks/element` | ❌ `/container`, `/grid` |
| Text / buttons | `generateblocks/text` | ❌ `/headline`, `/button` |
| Images | `generateblocks/media` | ❌ `/image` |
| SVG icons | `generateblocks/shape` | — |
| Dynamic lists | `generateblocks/query` + `looper` + `loop-item` | ❌ `/query-loop` (legacy) |

Class order varies with the stored convention. New element blocks prefer
`gb-element-{uniqueId} gb-element` with Option A; editor-authored text/media/
shape blocks commonly render base-first. Measure and preserve an existing
target instead of normalizing it.

## The five serialization rules that break everything

1. **Five JSON substitutions** in block-comment strings:
   `--`→`\u002d\u002d`, `<`→`\u003c`, `>`→`\u003e`, `&`→`\u0026`, `\"`→`\u0022`.
2. **Attribute key order = block.json declaration order**, `className` last.
   Text block: `content` is 3rd. (Per-block orders:
   `references/recovery-rules.md` §3.4 and `references/pro-interactive.md`.)
3. **`htmlAttributes` is a plain object** — `{"href":"https://..."}` — never
   an array. Absolute URLs only.
4. **`styles` is the editable source; `css` is the compiled local cache.**
   Keep states, transitions, one-level selectors, and `@media`/`@supports`/
   `@container` in `styles`, then compile the same structure into `css`.
   CSS Mode is an editor for this data; it has no `cssMode` attribute.
5. **Links**: element `<a>` wrapping a text `span` child. Text `<a>` strips
   href on save; element `<a>` with raw text triggers recovery.

## Dynamic tags — exact syntax (silently fails if wrong)

```
{{tag_name option:value|option2:value}}     ← space after tag name, pipes, NO quotes
```

Real tags: `{{post_title}}`, `{{post_permalink}}`, `{{post_excerpt length:20}}`,
`{{featured_image size:large}}`, `{{post_meta key:field_name}}`,
`{{term_list tax:category}}`, `{{post_date dateFormat:M j, Y}}`.

These do NOT exist: `{{post_url}}`, `{{featured_image_url}}`, `{{post_terms}}`,
`{{acf}}`, any `key="quoted"` form. Full catalog:
`skills/generateblocks-layouts/references/dynamic-tags.md`.

ACF: `{{post_meta key:acf_field}}`, nested via dot notation
(`key:repeater.0.subfield`); repeater loops via Pro `queryType:"post_meta"`.

## Other hard rules

- **No HTML comments** except `<!-- wp:... -->` delimiters.
- **Compact nesting** — closing comment adjacent to closing tag.
- New compiled CSS strips spaces after commas, but CSS math keeps required
  whitespace around `+` and `-`: `clamp(2rem,1rem + 3vw,4rem)`.
- **Unique IDs**: `{section}-{post_id}-{sequence}{letter?}` —
  `hero-1173976-1`, `card-1173976-12b`. Resolve the real post ID first.
- **Output to files**, never inline in chat.
- Static captioned images → `core/image`; loop images →
  `generateblocks/media` with `{{featured_image size:large}}` src.
- Lists → `core/list` (`className:"list"`); emoji → `core/paragraph`.
- Responsive: native Tablet & Mobile is `@media (max-width:1024px)` and native
  Mobile is `@media (max-width:767px)` in 2.4.1. Pro custom queries are valid;
  preserve existing custom `768px` boundaries.
- **Invented/materially changed designs** must pass
  `references/design-quality.md`: no thick rounded outlines, no card soup,
  purposeful effects, deliberate mobile composition, real proof/states, and
  accessible interaction.

## Skills in this repo

| Skill | Purpose |
|---|---|
| `skills/generateblocks-layouts/` | Build any GB layout — the knowledge base |
| `skills/html-to-generateblocks/` | Convert HTML/CSS to GB markup |
| `skills/elementor-to-generateblocks/` | Migrate Elementor layouts |
| `skills/figma-to-generateblocks/` | Convert Figma designs |

Converters delegate all markup rules to `generateblocks-layouts/references/`
— never duplicate or contradict them.

## Reference routing (inside generateblocks-layouts/references/)

- Recovery errors → `recovery-rules.md` (read EVERY task)
- Block specs → `block-types.md` · Dynamic data → `dynamic-tags.md`
- CSS Mode/raw CSS/selectors → `css-mode.md`
- Invented design/anti-slop gate → `design-quality.md`
- Query loops → `query-block.md` · ACF → `acf-and-custom-fields.md`
- Animations → `animations.md` · Conditions → `conditions.md`
- Forms → `pro-forms.md` · Accordion/tabs/nav → `pro-interactive.md`
- Full-site templates → `template-authoring.md` · Pro map → `gb-pro.md`
