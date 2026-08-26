---
title: CSS Mode and Styles Data
description: Source-verified guide to GenerateBlocks Pro CSS Mode, supported selectors and at-rules, and the durable relationship between block styles and compiled CSS.
---

# CSS Mode and Styles Data

GenerateBlocks Pro CSS Mode is a code editor for the same structured styles
used by the visual Styles panel. It is not an Additional CSS field and it does
not introduce a `cssMode` block attribute.

Verified against GenerateBlocks 2.4.1 and GenerateBlocks Pro 2.7.1. CSS Mode
was introduced in Pro 2.6.

## The Data Contract

| Layer | Purpose | Saved where |
|---|---|---|
| Styles controls | Visual editor for properties, selectors, and at-rules | `styles` object |
| CSS Properties panel | Add or edit browser-supported properties on the current selector | `styles` object |
| CSS Mode | Edit all local or Global Style CSS in one code surface | parsed back into `styles` |
| `css` attribute | Compiled frontend CSS for a local block | block attribute |
| Global Style CSS | Compiled stylesheet for Pro Global Styles | `gblocks_styles` records/meta |

For local blocks, `styles` is the editable source and `css` is the compiled
cache used on the frontend. Keep them semantically aligned. A selector,
transition, or media query present only in `css` can render today but disappear
after a later style edit recompiles the block.

CSS Mode itself has no markup effect. Do not emit any of these:

```json
{"cssMode":true}
{"customCss":"..."}
{"additionalCss":"..."}
```

## Where CSS Mode Appears

Pro exposes the same editor in 3 places:

1. Enable CSS Mode from a block's Styles panel or toolbar.
2. Use the CSS Properties panel for the current selector.
3. Open Edit CSS for a Global Style in GenerateBlocks > Global Styles.

CSS Mode shows declarations relative to the current block or Global Style
selector. Do not paste an unrelated site-wide stylesheet into a block.

## Supported CSS Shape

CSS Mode accepts:

- properties on the current selector;
- one level of nested selector;
- `@media`, `@supports`, and `@container` at-rules;
- a supported at-rule around a selector, or inside a selector;
- CSS custom properties;
- browser-supported current and future properties;
- `!important`, though it should be exceptional.

The editor validates before Apply. Invalid or unsupported input is rejected
rather than partially saved. The source parser also rejects potentially unsafe
values such as JavaScript URLs.

### Base declarations

CSS Mode input:

```css
display: grid;
gap: 1.5rem;
grid-template-columns: repeat(3,minmax(0,1fr));
```

Stored shape:

```json
{
  "display":"grid",
  "gap":"1.5rem",
  "gridTemplateColumns":"repeat(3,minmax(0,1fr))"
}
```

### States and pseudo-elements

Use `&` when the selector concatenates with the current selector:

```css
transition: background-color .22s ease,border-color .22s ease;

&:hover {
  border-color: currentColor;
}

&:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 3px;
}

&::before {
  content: '';
}
```

Stored shape:

```json
{
  "transition":"background-color .22s ease,border-color .22s ease",
  "&:hover":{"borderColor":"currentColor"},
  "&:focus-visible":{"outline":"2px solid currentColor","outlineOffset":"3px"},
  "&::before":{"content":"''"}
}
```

Do not write the hover rule only into `css`. The nested branch belongs in
`styles` so the visual editor and CSS Mode can preserve it.

### Child and sibling selectors

One selector level is supported:

```css
> .child { min-width: 0; }
& + .sibling { margin-top: 1rem; }
svg { width: 1em; height: 1em; }
&:hover > .child { color: currentColor; }
```

These are valid single selectors. This is not:

```css
&:hover {
  > .child { color: currentColor; }
}
```

Flatten it to `&:hover > .child`. Never add a wrapper solely to make a
decorative selector easier.

### At-rules

Use the target site's registered query exactly:

```css
@media (max-width:767px) {
  grid-template-columns: 1fr;
  gap: 1rem;
}

@supports (text-wrap:balance) {
  text-wrap: balance;
}

@container cards (max-width:40rem) {
  grid-template-columns: 1fr;
}
```

Stored shape:

```json
{
  "@media (max-width:767px)":{"gridTemplateColumns":"1fr","gap":"1rem"},
  "@supports (text-wrap:balance)":{"textWrap":"balance"},
  "@container cards (max-width:40rem)":{"gridTemplateColumns":"1fr"}
}
```

The editor normalizes at-rule formatting. GenerateBlocks 2.4.1 exposes these
native queries:

```text
@media (min-width:1025px)
@media (min-width:768px)
@media (max-width:1024px) and (min-width:768px)
@media (max-width:1024px)
@media (max-width:767px)
```

Pro can register custom media and container queries. A custom `768px` maximum
is valid, but it is not the native Mobile query. Preserve an existing custom
boundary; use `767px` for new work that means GenerateBlocks Mobile.

### At-rule plus selector

Both shapes below fit the single-selector/single-at-rule model:

```css
@media (max-width:767px) {
  &:focus-visible { outline-offset: 2px; }
}

&:hover {
  color: currentColor;
  @media (max-width:767px) { color: inherit; }
}
```

Do not nest an at-rule inside another at-rule or a selector inside another
selector.

## Unsupported Input

CSS Mode does not support:

- CSS comments, which are stripped;
- `@import`;
- `@keyframes`, `@font-face`, `@page`, or arbitrary at-rules;
- more than one selector nesting level;
- more than one at-rule nesting level;
- unrelated selectors from a site-wide stylesheet;
- selectors that attempt to edit the fixed parent selector in the Global
  Styles modal.

Put keyframes, font declarations, and genuinely page-scoped stylesheet logic
in the owning theme/plugin stylesheet or another project-approved CSS surface.
Do not hide unsupported CSS in a block's `css` attribute and call it durable.

## Copy and Paste Rules

Inside CSS Mode, prefer declarations and `&`-relative rules. Pasting a complete
rule is safe only when the rule belongs to the current selector. Unrelated
selectors are discarded or rejected.

Good:

```css
padding: 1.5rem;
border: 1px solid var(--color-border);

&:focus-visible {
  outline: 2px solid var(--color-focus);
}
```

Wrong surface:

```css
body .site-header nav a { color: red; }
@keyframes float { to { transform: translateY(-4px); } }
```

## Hand-Authoring Blocks

When generating Gutenberg markup:

1. Build the complete `styles` object first.
2. Compile the same structure into `css` with
   `scripts/gb_serialize.py::build_css()`.
3. Serialize the attributes with `serialize_attrs()`.
4. Run `scripts/preflight.py`.
5. Preserve target-site conventions for `content`, `className`, and existing
   custom at-rules.

Do not treat the current `css` string as the editable source when `styles`
already exists. If the 2 differ, decide which one represents the intended
design, repair the pair, and record that normalization in the handoff.

## Global Styles

CSS Mode can edit Pro Global Styles with the same selector and at-rule limits.
Global Styles are appropriate for repeated component primitives such as a
button, content rail, or shared card shell. They are not a reason to turn every
one-off visual choice into a global class.

Before renaming a Global Style, check its usage. Renaming the stored selector
does not rewrite class names already attached to blocks.

Order also matters. Global Style CSS is emitted in published order, so later
classes can win equal-specificity conflicts.

## Verification

- Reopen CSS Mode and confirm the declarations round-trip.
- Confirm the `styles` object contains every state and at-rule represented in
  `css`.
- Test the exact breakpoint edges, including 767/768 and 1024/1025 when used.
- Check focus-visible, reduced motion, long content, and horizontal overflow.
- If a Global Style changed, verify every known consumer rather than one demo
  block.

Official reference: <https://learn.generatepress.com/blocks/block-guide/getting-started-generateblocks/generateblocks-pro/css-editor/>
