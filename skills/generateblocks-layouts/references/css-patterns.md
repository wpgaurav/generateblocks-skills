---
title: Durable CSS Patterns
description: Maintainable GenerateBlocks V2 styling patterns using structured styles, CSS Mode selectors, supported at-rules, accessible states, and restrained surfaces.
---

# Durable CSS Patterns

Build styling in the `styles` object and compile the same structure into the
local block's `css` attribute. Read `css-mode.md` before using nested selectors
or raw CSS and `design-quality.md` before inventing a visual direction.

## Choose the Right Layer

| Need | Preferred layer |
|---|---|
| One block's layout, spacing, color, type, or state | local `styles` + compiled `css` |
| Repeated component primitive | Pro Global Style |
| Project design token | theme/project variable |
| Keyframes, font declarations, page-level selector graph | owning theme/plugin stylesheet |
| Small inline rich-text exception | inline markup only when the editor truly owns it |

Do not scatter static site-wide CSS across dozens of block attributes. Do not
promote one-off nudges into Global Styles.

## Base, State, and At-Rule Shape

```json
{
  "backgroundColor":"var(--surface)",
  "border":"1px solid var(--border)",
  "borderRadius":"0.75rem",
  "color":"var(--text)",
  "transition":"background-color .22s ease,border-color .22s ease,color .22s ease",
  "&:hover":{
    "borderColor":"var(--text-muted)"
  },
  "&:focus-visible":{
    "outline":"2px solid var(--focus)",
    "outlineOffset":"3px"
  },
  "@media (max-width:767px)":{
    "borderRadius":"0.5rem"
  }
}
```

The transition, hover, focus, and media query all belong in `styles`. The
compiled `css` must contain the same branches.

## Action Links and Buttons

For a block-level action, use an element `<a>` with an inner text `<span>`.
The link wrapper owns hit area, layout, background, border, and focus. The text
child owns the label.

Suggested style shape:

```json
{
  "alignItems":"center",
  "backgroundColor":"var(--action)",
  "border":"1px solid transparent",
  "borderRadius":"0.375rem",
  "color":"#fff",
  "display":"inline-flex",
  "fontWeight":"600",
  "justifyContent":"center",
  "minHeight":"2.75rem",
  "paddingBottom":"0.75rem",
  "paddingLeft":"1.25rem",
  "paddingRight":"1.25rem",
  "paddingTop":"0.75rem",
  "textDecoration":"none",
  "transition":"background-color .22s ease,border-color .22s ease,color .22s ease",
  "&:hover":{"backgroundColor":"var(--action-hover)"},
  "&:focus-visible":{"outline":"2px solid var(--focus)","outlineOffset":"3px"}
}
```

Use one filled primary action per self-contained decision surface. Secondary
actions can be text links or quiet 1px-outline buttons. A 2px rounded outline
is not a default secondary style.

Do not:

- make every CTA a pill;
- animate all properties;
- add hover lift to noninteractive content;
- rely on color alone for disabled, selected, or error states;
- implement a button as a generic `div`.

## Quiet Surface Patterns

### Divider row

Use this before creating cards for ordinary facts or features:

```json
{
  "borderTop":"1px solid var(--border)",
  "display":"grid",
  "gap":"1rem",
  "gridTemplateColumns":"minmax(10rem,14rem) minmax(0,1fr)",
  "paddingBottom":"1.25rem",
  "paddingTop":"1.25rem",
  "@media (max-width:767px)":{
    "gridTemplateColumns":"1fr"
  }
}
```

### Recessed well

```json
{
  "backgroundColor":"var(--surface-muted)",
  "borderRadius":"0.75rem",
  "paddingBottom":"1.5rem",
  "paddingLeft":"1.5rem",
  "paddingRight":"1.5rem",
  "paddingTop":"1.5rem"
}
```

No border or shadow is needed unless the project system calls for one.

### Interactive card

Use a card only when the whole unit is independently actionable or genuinely
distinct:

```json
{
  "backgroundColor":"var(--surface)",
  "border":"1px solid var(--border)",
  "borderRadius":"0.75rem",
  "display":"flex",
  "flexDirection":"column",
  "paddingBottom":"1.5rem",
  "paddingLeft":"1.5rem",
  "paddingRight":"1.5rem",
  "paddingTop":"1.5rem",
  "textDecoration":"none",
  "transition":"border-color .22s ease,box-shadow .22s ease",
  "&:hover":{"borderColor":"var(--border-strong)"},
  "&:focus-visible":{"outline":"2px solid var(--focus)","outlineOffset":"3px"}
}
```

If a shadow is needed because the card floats, remove the decorative border or
keep it nearly invisible. Do not stack border, tint, shadow, and glow.

## Nested Selectors

CSS Mode supports one selector level. Keep the selector relative to the
current block:

```json
{
  "color":"var(--text)",
  "& a":{"color":"var(--link)"},
  "& a:hover":{"color":"var(--link-hover)"},
  "& > svg":{"height":"1em","width":"1em"}
}
```

Flatten compound behavior into one selector:

```json
{
  "&:hover > .child":{"color":"currentColor"}
}
```

Do not nest `.child` inside `&:hover`; CSS Mode rejects the second selector
level.

Prefer a child's own GenerateBlocks selector for reusable child state. A
parent-hover selector attached to the child can be represented as a valid
single nested selector and must remain post-scoped.

## Pseudo-Elements

Pseudo-elements are valid structured selectors:

```json
{
  "position":"relative",
  "&::after":{
    "backgroundColor":"currentColor",
    "bottom":"0",
    "content":"''",
    "height":"1px",
    "left":"0",
    "position":"absolute",
    "transform":"scaleX(0)",
    "transformOrigin":"left",
    "transition":"transform .22s ease",
    "width":"100%"
  },
  "&:hover::after":{"transform":"scaleX(1)"}
}
```

Use pseudo-elements for a meaningful affordance or separator, not to create a
background decoration stack. Literal visible text and icons are more robust
than generated-content glyphs.

## Semantic Status Treatment

A compact status can use a dot and text when status is real data:

```json
{
  "alignItems":"center",
  "display":"inline-flex",
  "gap":"0.5rem"
}
```

Do not put every label into a filled capsule. Category, filter, and state
controls may be pills when the project system uses them; headings do not need
decorative badges.

## Gradients, Shadows, and Effects

Use a gradient only when it represents the brand, data, depth, or a meaningful
visual field. One restrained gradient can be the section's dominant idea. It
must not compensate for vague content.

Use shadows only for actual elevation:

```json
{"boxShadow":"0 10px 30px rgba(0,0,0,.10)"}
```

Avoid:

- gradient borders around ordinary rounded cards;
- blurred blobs behind generic heroes;
- colored shadows on every card;
- glass, border, tint, and glow on the same surface;
- decoration that disappears in forced colors without an equivalent boundary.

## Global Styles

Good Global Style candidates:

- primary and secondary button contracts;
- the site's content rail;
- a shared interactive card shell;
- repeated form-control states;
- a semantic metadata row.

Weak candidates:

- `margin-top-7`;
- one campaign's glow;
- an isolated card span;
- a unique hero background;
- a selector named only for its current position.

Before adding a new class, check the existing Global Styles list and CSS load
order. Before renaming one, inspect usage because existing block class names are
not rewritten automatically.

## Motion and Reduced Motion

Keep content visible without animation. Prefer color, border-color, opacity,
and small transforms for interactive feedback.

```json
{
  "transition":"transform .18s ease,opacity .18s ease",
  "@media (prefers-reduced-motion:reduce)":{"transition":"none"}
}
```

CSS Mode does not support `@keyframes`. Put necessary keyframes in the owning
project stylesheet and keep a no-motion fallback.

## Final Checks

- `styles` and `css` contain the same states, selectors, and at-rules.
- No rounded surface carries a 2px-or-thicker border without an approved
  functional exception.
- Cards are distinct or interactive, not wrappers around ordinary copy.
- One primary action is visually dominant.
- Focus-visible works without mouse hover.
- Mobile and tablet are deliberately composed.
- Real content, long strings, empty states, and missing media survive.
- Effects remain readable in dark mode, forced colors, and reduced motion.
