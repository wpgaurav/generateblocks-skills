---
title: Figma layout and style mapping
description: Translate measured design properties into local block styles; preserve behavior and distinguish evidence from inference.
---

# Figma mapping

Use source frames, variants, exported assets, and inspectable CSS when available.
A screenshot provides visual evidence but not exact fonts, breakpoints, component
states, CMS bindings, or interactions. Record those uncertainties rather than
inventing design tokens or treating every frame as an absolute-positioned canvas.

When using Figma tools, follow their required Figma skill/tool prerequisites.
This reference does not substitute for them or expand access to a private file.

## Layout

| Figma property | CSS decision |
|---|---|
| Auto Layout horizontal/vertical | flex row/column |
| Fixed item spacing | `gap` |
| Space between | `justifyContent:"space-between"` when source behavior matches |
| Frame padding | owning container padding |
| Hug contents | intrinsic sizing; avoid unnecessary fixed dimensions |
| Fill container | flexible width/track, `minWidth:0` where shrinking is required |
| Repeated aligned columns | grid with `minmax(0,1fr)` where appropriate |
| Overlay/anchored decoration | positioned element only when the relationship requires it |

Example: horizontal Auto Layout, 24px gap, 32px padding, vertically centered:

```json
{
  "display": "flex",
  "flexDirection": "row",
  "gap": "24px",
  "padding": "32px",
  "alignItems": "center"
}
```

This is editable `styles` input. The shared authoring contract explains how to
compile and serialize it; do not maintain a second compiled-CSS example here.

## Typography and units

Preserve font family, weight, size, line height, and measured tracking. Check that
the font is actually available and approved on the destination; don't silently
download a substitute. A 48px heading with 120% line height maps to
`fontSize:"48px"`, `lineHeight:"1.2"`. A source `-0.02em` tracking value can map
to `letterSpacing:"-0.02em"`; it is not a default styling recommendation.

Only introduce `clamp()` after choosing minimum/maximum behavior supported by
the design. Converting every fixed size into an arbitrary fluid formula changes
the design. At a 16px root, 24px = 1.5rem, 32px = 2rem, and 80px = 5rem.
Confirm the site's root size before using that arithmetic.

For an actual shadow with x=0, y=20px, blur=60px and 15% black, the CSS value is
`0 20px 60px rgba(0,0,0,0.15)`. Preserve inset/spread and layered shadows when
present. Radius, shadow, and opacity values are source facts, not reasons to add
those treatments to every component.

## Responsive and interaction decisions

Frame widths are observations, not automatically breakpoint boundaries. Use the
mobile/tablet frames and destination's breakpoint contract to determine changes
in layout, order, spacing, and type. Preserve a deliberate custom boundary.
If only desktop exists, state the mobile inference and apply the design-quality
gate. Don't merely shrink everything proportionally.

Use supplied variants for hover, focus, disabled, selected, open, error, and
success states. When a state is absent, add only the interaction/accessibility
behavior necessary for a usable component and identify material design choices.
Keep states and at-rules in `styles` as well as compiled CSS.

Map semantic content through the layout contract: core blocks for tables,
lists, media players, and captioned images; Pro components for real tabs,
accordion, carousel, or navigation behavior. Use queries for actual CMS-driven
collections, not hardcoded duplicates of the visible sample cards.

Compare content, assets, hierarchy, dimensions, and responsive behavior against
the source after serialization. Native editor validity alone does not establish
visual fidelity or intact rich text.
