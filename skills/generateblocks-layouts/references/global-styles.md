---
title: Global Styles and Design Tokens
description: Source-verified GenerateBlocks Pro Global Styles storage, CSS Mode editing, load order, block attachment, token boundaries, and safe maintenance.
---

# Global Styles and Design Tokens

GenerateBlocks Pro Global Styles are reusable CSS class contracts. Use them for
styles that genuinely repeat across blocks and pages, not as a dumping ground
for one-off values.

Read `css-mode.md` for selector and at-rule grammar and `design-quality.md`
before creating a visual system.

## Current Storage Model

In Pro 2.7.1, each current Global Style is a published `gblocks_styles` record.
Important meta fields are:

| Meta key | Purpose |
|---|---|
| `gb_style_selector` | class selector/name |
| `gb_style_data` | structured styles object |
| `gb_style_css` | compiled CSS |

`gblocks_global_style` is the deprecated V1 CPT. Do not create new records
there.

The frontend concatenates published Global Style CSS in menu order and caches
the result. Order therefore affects equal-specificity conflicts.

## Applying a Global Style

A local block references class names through `globalClasses`:

```json
"globalClasses":["button-primary","content-action"]
```

The same class names appear in rendered HTML. The Global Style itself owns the
reusable CSS; local `styles`/`css` should contain only instance-specific
overrides.

Do not attach a class that does not exist and assume the plugin will create it.

## Good Boundaries

Strong Global Style candidates:

- primary and secondary action contracts;
- content rails;
- form fields and validation states;
- a repeated interactive card shell;
- a shared metadata row;
- a layout primitive used across many templates.

Weak candidates:

- one campaign's hero background;
- `margin-top-7` or other one-off utility noise;
- a card span tied to one grid;
- an arbitrary color copied from a screenshot;
- a selector named only for where it currently sits.

Global Styles reduce local CSS only when the contract really repeats. They do
not replace the theme's tokens or every local block style.

## Token Layering

Use this priority:

1. theme/project tokens and variables;
2. reusable Global Style contracts;
3. local block styles for the instance;
4. inline values only when they are genuinely dynamic.

WordPress `theme.json` and the active theme can expose variables such as:

```css
var(--wp--preset--color--primary)
var(--wp--preset--spacing--40)
var(--wp--style--global--content-size)
```

GenerateBlocks also exposes `var(--gb-container-width)`. In block-comment JSON,
WordPress serializes the double dash as `\u002d\u002d`; in rendered HTML/CSS it
remains literal `--`.

Inspect the target before using any variable. A variable name in an old example
is not proof that the current theme defines it.

## CSS Mode for Global Styles

Pro 2.6+ can edit a Global Style through:

- the regular Styles panel on a block using that class;
- the CSS Properties panel;
- Edit CSS in GenerateBlocks > Global Styles.

CSS Mode updates `gb_style_data` and the compiled `gb_style_css`. It supports:

- declarations on the fixed class selector;
- one nested selector level;
- `@media`, `@supports`, and `@container`;
- one selector level combined with one at-rule level.

It does not support comments, keyframes, fonts, imports, or arbitrary at-rules.
The Global Styles modal fixes the parent selector; you edit declarations and
relative branches, not an unrelated site-wide stylesheet.

## Responsive Global Styles

Use the site's registered at-rule key exactly:

```json
{
  "display":"grid",
  "gridTemplateColumns":"repeat(3,minmax(0,1fr))",
  "@media (max-width:1024px)":{
    "gridTemplateColumns":"repeat(2,minmax(0,1fr))"
  },
  "@media (max-width:767px)":{
    "gridTemplateColumns":"1fr"
  }
}
```

GenerateBlocks 2.4.1 native Mobile ends at 767px. Pro permits custom queries;
preserve existing custom 768px boundaries during unrelated edits.

If a component responds to its container rather than the viewport, set
`containerType`/`containerName` on the owner and use a registered `@container`
branch in the dependent style.

## Naming

Names should describe a component contract or semantic role:

```text
button-primary
button-secondary
content-rail
form-control
article-card
metadata-row
```

Avoid position and appearance-only names that will lie after redesign:

```text
left-card
blue-box
section-3
big-shadow
```

Follow the nearest project naming convention if it already exists.

## Load Order and Specificity

Published order is CSS load order. If 2 classes set the same property with the
same specificity, the later class wins.

Before solving a conflict with `!important`:

1. inspect the block's local styles;
2. inspect all attached Global Styles;
3. check Global Style order;
4. check theme selectors and inheritance;
5. decide which layer should own the property.

Reorder only when the change is safe for every consumer.

## Rename, Delete, and Usage Safety

Renaming a Global Style changes its stored selector and CSS. It does not rewrite
the old class name on blocks already using it. Those blocks can lose styling.

Before renaming or deleting:

1. use the Pro usage endpoint or authenticated search to enumerate consumers;
2. snapshot selector, structured data, compiled CSS, status, and order;
3. update consumers and style under a guarded plan;
4. read back stored records and affected blocks;
5. verify the full consumer set.

Relevant Pro REST reads include:

```text
generateblocks-pro/v1/global-classes/get
generateblocks-pro/v1/global-classes/get_css
generateblocks-pro/v1/global-classes/get_styles
generateblocks-pro/v1/global-classes/{id}/usage
generateblocks-pro/v1/global-classes/check_class_name
```

These routes require appropriate authenticated capabilities. Do not expose
backend IDs or private operational details in public copy.

## Import and Pattern Behavior

Patterns can carry Global Style definitions and block class references. During
import, the plugin checks for existing selectors and can import style data/CSS.

Before importing into a site with an established design system:

- inventory name collisions;
- compare semantics, not only selector spelling;
- avoid overwriting a same-named class with a different contract;
- remove pattern-specific decorative styles that do not belong globally;
- verify all imported responsive at-rules against the destination.

## Design Quality

- Do not create a global rounded-card class that combines a 2px border with a
  radius.
- Do not standardize card soup, pill CTAs, gradient borders, or hover lift on
  noninteractive content.
- Keep font-family ownership in the theme unless the project says otherwise.
- Use real states: hover, focus-visible, disabled, error, success, and long
  content where relevant.
- A shared class should make the product/site more coherent, not merely make
  copied markup shorter.

## Live gauravtiwari.org Snapshot

Observed 2026-08-26:

- 45 published current Global Styles;
- no deprecated `gblocks_global_style` records;
- compiled Global Style CSS around 6.6KB;
- no responsive at-rule branches in those 45 styles at that time;
- local blocks carried the responsive behavior.

This is evidence for the current site, not a general recommendation. Recheck
before changing the system.

## Verification

- Selector name is valid and unique.
- `gb_style_data` and `gb_style_css` represent the same declarations.
- CSS Mode reopens without validation errors.
- Published order produces the intended cascade.
- Every attached block retains the class name.
- Mobile, tablet, focus, dark mode, forced colors, and reduced motion are
  checked where the class affects them.
- Renames/deletes verify every usage result, not one sample page.
- Dynamic CSS/cache invalidation completes through the normal save path.

Official reference: <https://learn.generatepress.com/blocks/block-guide/getting-started-generateblocks/generateblocks-pro/global-styles/>
