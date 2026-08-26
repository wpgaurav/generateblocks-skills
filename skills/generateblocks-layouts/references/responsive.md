---
title: Responsive Styles and At-Rules
description: Source-verified GenerateBlocks 2.4 responsive queries, Pro custom at-rules, structured styles data, composition patterns, and verification.
---

# Responsive Styles and At-Rules

GenerateBlocks V2 stores responsive declarations as at-rule branches inside
the block or Global Style `styles` object. The `css` attribute mirrors the
compiled result for local blocks.

Read `css-mode.md` with this file when editing CSS directly.

## Installed Query Contract

GenerateBlocks 2.4.1 registers 5 native media queries through
`generateblocks_get_media_queries()`:

| Editor label | Styles key | Intended range |
|---|---|---|
| Desktop | `@media (min-width:1025px)` | 1025px and wider |
| Desktop & Tablet | `@media (min-width:768px)` | 768px and wider |
| Tablet | `@media (max-width:1024px) and (min-width:768px)` | 768px through 1024px |
| Tablet & Mobile | `@media (max-width:1024px)` | 1024px and narrower |
| Mobile | `@media (max-width:767px)` | 767px and narrower |

The default Mobile query is `767px`, not `768px`.

GenerateBlocks Pro can register custom `@media` and `@container` queries. A
site can therefore have a valid custom `@media (max-width:768px)` rule alongside
the native queries. Before editing an existing page:

1. inspect the installed versions;
2. read `generateblocks_get_media_queries()` or the localized editor settings;
3. inventory the at-rule keys already stored on the target;
4. preserve existing custom boundaries unless the user approved a migration.

## Styles First, CSS Mirrored

Use this shape for new local block output:

```json
{
  "styles": {
    "display": "grid",
    "gap": "2rem",
    "gridTemplateColumns": "repeat(3,minmax(0,1fr))",
    "@media (max-width:1024px)": {
      "gap": "1.5rem",
      "gridTemplateColumns": "repeat(2,minmax(0,1fr))"
    },
    "@media (max-width:767px)": {
      "gap": "1rem",
      "gridTemplateColumns": "1fr"
    }
  },
  "css": ".gb-element-grid{display:grid;gap:2rem;grid-template-columns:repeat(3,minmax(0,1fr))}@media (max-width:1024px){.gb-element-grid{gap:1.5rem;grid-template-columns:repeat(2,minmax(0,1fr))}}@media (max-width:767px){.gb-element-grid{gap:1rem;grid-template-columns:1fr}}"
}
```

The JSON shown above is conceptual. In Gutenberg block comments, run it
through `serialize_attrs()` so `--`, `<`, `>`, `&`, and embedded quotes use
WordPress's canonical substitutions.

Do not put a responsive rule only in `css`. It can render immediately, but a
later Styles-panel or CSS Mode edit can recompile the block from `styles` and
remove the orphan rule.

## Cascade Order

At-rule order is part of the design.

Desktop-first order:

1. base declarations;
2. `max-width:1024px`;
3. `max-width:767px`.

Mobile-first order:

1. mobile base declarations;
2. `min-width:768px`;
3. `min-width:1025px`.

Both approaches work. Do not mix directions casually. Overlapping min/max
queries are safe only when you can explain which rule should win at every
boundary.

## Design the Composition, Not Device Labels

Use a breakpoint when the composition needs to change:

- columns no longer have useful minimum widths;
- navigation needs a different control;
- a sticky panel should return to normal flow;
- actions need to regroup or reorder;
- a comparison needs a different representation;
- artwork starts obscuring content;
- tap targets become crowded.

Do not add a media query only because a value looks familiar. Prefer intrinsic
CSS when it preserves intent:

```json
{
  "display":"grid",
  "gap":"clamp(1rem,2vw,2rem)",
  "gridTemplateColumns":"repeat(auto-fit,minmax(min(100%,18rem),1fr))"
}
```

This can remove an unnecessary grid breakpoint while still allowing a later
query for a genuine layout change.

## Common Patterns

### 4 to 2 to 1 grid

```json
{
  "display":"grid",
  "gap":"1.5rem",
  "gridTemplateColumns":"repeat(4,minmax(0,1fr))",
  "@media (max-width:1024px)":{
    "gridTemplateColumns":"repeat(2,minmax(0,1fr))"
  },
  "@media (max-width:767px)":{
    "gridTemplateColumns":"1fr"
  }
}
```

### Split layout to one column

```json
{
  "display":"grid",
  "gap":"clamp(2rem,5vw,4rem)",
  "gridTemplateColumns":"minmax(0,5fr) minmax(0,7fr)",
  "@media (max-width:1024px)":{
    "gridTemplateColumns":"1fr"
  }
}
```

Each column needs its own wrapper element. Otherwise every heading, paragraph,
button, and image becomes an independent grid item.

### Row to column

```json
{
  "alignItems":"center",
  "display":"flex",
  "gap":"2rem",
  "@media (max-width:767px)":{
    "alignItems":"stretch",
    "flexDirection":"column",
    "gap":"1rem"
  }
}
```

### Sticky to normal flow

```json
{
  "position":"sticky",
  "top":"calc(var(--site-header-height,0px) + 1rem)",
  "@media (max-width:1024px)":{
    "position":"static",
    "top":"auto"
  }
}
```

Remember that the custom property's `--` must be escaped in block-comment
JSON, not in the rendered HTML body.

### Deliberate source order

Prefer DOM order that works for reading and keyboard navigation. Use visual
`order` only when focus order remains understandable:

```json
{
  "order":"2",
  "@media (max-width:767px)":{"order":"1"}
}
```

Do not use CSS order to conceal a structurally wrong document.

### Responsive type

Start with the project's type scale. Use `clamp()` when the role genuinely
scales:

```json
{
  "fontSize":"clamp(2rem,1.45rem + 2.2vw,3.25rem)",
  "lineHeight":"1.08"
}
```

Whitespace around `+` is required inside CSS math expressions. Commas can be
minified; the addition operator cannot.

### Device visibility

Visibility is usually a last resort. Duplicating meaningful content for
desktop and mobile creates accessibility and maintenance problems.

When a truly device-specific control is required:

```json
{
  "display":"none",
  "@media (max-width:767px)":{"display":"block"}
}
```

Confirm that an equivalent route remains available on every viewport and that
duplicate IDs or announcements are not introduced.

## Container Queries

Use a container query when a component's available width matters more than the
viewport.

Parent:

```json
{
  "containerName":"cards",
  "containerType":"inline-size"
}
```

Child layout:

```json
{
  "display":"grid",
  "gridTemplateColumns":"repeat(2,minmax(0,1fr))",
  "@container cards (max-width:40rem)":{
    "gridTemplateColumns":"1fr"
  }
}
```

CSS Mode supports `@container`; Pro can register reusable container queries.
Keep the component usable without relying on a site-specific container name
that may not exist at its destination.

## Other Responsive At-Rules

Motion preference:

```json
{
  "transition":"transform .22s ease,opacity .22s ease",
  "@media (prefers-reduced-motion:reduce)":{
    "transition":"none"
  }
}
```

Forced colors:

```json
{
  "@media (forced-colors:active)":{
    "borderColor":"CanvasText"
  }
}
```

Feature support:

```json
{
  "@supports (text-wrap:balance)":{
    "textWrap":"balance"
  }
}
```

Do not add support queries as decoration. Use them only when the fallback is
complete and the enhancement changes the result.

## Responsive Anti-Slop Gate

- Mobile is deliberately composed, not a tall stack of desktop cards.
- The primary action stays near the relevant decision.
- Long titles, untranslated strings, empty fields, and missing media do not
  break the layout.
- No horizontal overflow at 200% zoom.
- Fixed and sticky elements do not cover content or the on-screen keyboard.
- Tap targets remain large and separated.
- Images keep their subject and intrinsic dimensions.
- Tables and comparisons adapt without clipping essential information.
- Decorative effects do not overlap text.
- Tablet widths do not expose an accidental empty column.

## Verification Matrix

At minimum, check:

| Viewport/state | What to verify |
|---|---|
| 1280px or wider | rail width, full composition, no stretched measures |
| 1025px | desktop side of native tablet boundary |
| 1024px | tablet side, grid and sticky changes |
| 768px | tablet-only lower edge |
| 767px | mobile query is active |
| 375px | content order, tap targets, overflow, long labels |
| 200% zoom | no clipping or lost controls |
| reduced motion | content stays visible and usable |
| forced colors | focus and boundaries remain perceivable |

Also test an awkward in-between width such as 820px. Breakpoint edges can pass
while the space between them still looks broken.

## Common Failures

### `768px` used as native Mobile

The installed default is `max-width:767px`. Use `768px` only when the project
registered or deliberately chose it as a custom query.

### Media query only in `css`

Move it into `styles`, recompile `css`, and verify through CSS Mode.

### Descendant rule rejected by old tooling

GenerateBlocks 2.4/Pro 2.7 supports one selector level in structured styles.
Represent the selector as an `&`-relative branch and regenerate the compiled
CSS. Do not keep a valid CSS Mode selector only in the cache string.

### `!important` everywhere

Inspect selector ownership, at-rule order, Global Style order, and theme
specificity first. `!important` should describe a real constraint, not hide a
cascade you did not understand.
