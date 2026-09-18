---
title: HTML and CSS conversion patterns
description: Source-specific mapping and targeted examples; GenerateBlocks serialization belongs to the layout skill.
---

# HTML/CSS conversion

Use this when source structure or CSS needs interpretation. The layout skill's
authoring contract owns block names, escaping, states, IDs, and validation.

## Preserve the useful DOM

| Source | Mapping |
|---|---|
| Section with constrained inner content | element `section` + inner element only when it owns a content rail |
| Flex/grid wrapper | one element owning the layout |
| Heading/paragraph | Text or appropriate core prose block; preserve heading meaning |
| Block-level linked card/button | element `a` wrapping content blocks; keep the full destination |
| Inline link | preserve it in rich text |
| List/table/captioned figure/media player | corresponding core block |
| Repeated data-driven cards | V2 query family; preserve source query intent, not just visible sample cards |

Do not turn every BEM class into a block. Keep a wrapper only when it owns
semantics, layout, clipping, inheritance, or interaction. Preserve source IDs
used by anchors and scripts; changing them requires updating their consumers.

## Map every CSS layer

Put declarations, transitions, states, supported pseudo-elements, and responsive
branches in structured `styles`, then compile the same tree into `css`. There
is no separate “complex CSS only” bucket. For direct CSS parsing or unsupported
nesting, use the layout skill's `css-mode.md`.

Given this source:

```css
.cards { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 24px; }
@media (max-width:767px) { .cards { grid-template-columns: 1fr; gap: 16px; } }
```

The local style input is:

```json
{
  "display": "grid",
  "gridTemplateColumns": "repeat(3,minmax(0,1fr))",
  "gap": "24px",
  "@media (max-width:767px)": {"gridTemplateColumns": "1fr", "gap": "16px"}
}
```

Compile this with the destination's real selector. Preserve a deliberate source
768px boundary if present; do not silently replace it with a native default.

For a button state, keep the entire contract together:

```json
{
  "backgroundColor": "#153e35",
  "color": "#fff",
  "transition": "background-color .2s ease",
  "&:hover": {"backgroundColor": "#0c2923"},
  "&:focus-visible": {"outline": "2px solid #153e35", "outlineOffset": "4px"}
}
```

These are conceptual style inputs, not import-ready block markup or a default
brand palette. Preserve the actual source values.

## Cases needing another reference

- Parent-hover child effects: read the relevant selector recipe in the layout
  skill's `css-mode.md`/`css-patterns.md`; preserve editable source and state CSS.
- Sticky sidebar: retain its measured header offset and make its mobile behavior
  explicit; inspect scrolling ancestors before assuming `position:sticky` works.
- Keyframes or font-face: simplify effects and use available fonts. Do not add
  external CSS or hide unsupported rules in the compiled local cache.
- Interactions: use native Pro tabs, accordions, and other available blocks.
  Use appropriate core blocks when needed. Do not carry custom JavaScript or
  another builder into a GenerateBlocks conversion without an explicit request.
- Missing CSS: inspect the target's theme and adjacent content. Use measured
  local values, identify remaining assumptions, and apply the design-quality
  gate if a new visual decision is necessary. Do not invent shared tokens.

## Conversion check

Compare visible copy, destinations, imagery, heading order, and working states
against the source at the relevant widths. A smaller DOM is useful only if those
behaviors survive. Report any unsupported behavior instead of silently dropping
it. For difficult saved markup, route to the layout recovery catalog by symptom.
