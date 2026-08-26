---
title: GenerateBlocks Design Quality Gate
description: GenerateBlocks-specific anti-slop checks for hierarchy, surfaces, content, responsive composition, accessibility, and maintainable block structure.
---

# GenerateBlocks Design Quality Gate

Use this before styling a layout the user did not fully specify and before
shipping any material redesign. Load `/design-slop` when available. The nearest
project design system owns brand decisions; this file owns the GenerateBlocks
implementation check.

## Start With Context

Inspect before inventing:

- the page's job and one primary action;
- real copy, proof, prices, limitations, and states;
- theme variables, Global Styles, container rails, type scale, radii, and
  registered at-rules;
- adjacent pages and the actual mobile journey;
- whether a core block, theme component, pattern, or existing Global Style
  already solves the need.

If the business name could be swapped without changing the section, the design
is not specific enough.

## Structure Before Surfaces

Build the reading and decision order first. Use semantic tags and the fewest
wrappers that own layout, clipping, inheritance, or interaction.

Do not default to:

- hero + 3 feature cards + testimonial grid + CTA;
- alternating image/text rows for mechanical variety;
- equal groups of 3 because the grid is convenient;
- a separate rounded box around every idea;
- centered long-form copy;
- decorative labels above every heading.

A GenerateBlocks element should exist because it owns semantics, layout,
state, inheritance, or a real surface. `section > inner rail > layout` is often
enough.

## Surface Decision Ladder

For each group, stop at the first treatment that works:

1. spacing and alignment;
2. a 1px divider;
3. a restrained tonal background;
4. a card for an independently interactive or genuinely distinct unit;
5. elevation only when the surface truly floats.

One surface gets one dominant separation treatment. Do not stack border, tint,
shadow, glow, and blur.

### Hard rule

Never combine a border of 2px or more with a rounded card, panel, callout,
content box, or section container. One thick side still counts. Focus rings,
selected states with a verified contrast need, and an explicitly approved
brutalist system are narrow exceptions.

`preflight.py` checks this rule in the `styles` object. Use
`--allow-thick-rounded` only for an approved exception and explain it.

## Cards, Pills, and Decoration

- Reserve cards for distinct or interactive units, not ordinary paragraphs.
- Flatten nested cards. Use one outer surface and inner spacing/dividers.
- Keep a modest radius scale. A new radius per block is prompt-by-prompt drift.
- Reserve pills for tags, filters, status, and capsule controls. Primary CTAs
  are not automatically pills.
- Use icons only when they improve recognition. Keep one family and stroke
  language.
- Avoid generic gradient blobs, glass panels, glowing borders, sparkle icons,
  fake dashboards, unsupported counters, and decorative charts.
- Motion must communicate state or hierarchy. Noninteractive cards do not need
  hover lift.

## Typography and Copy

Inherit the theme's font family unless the project explicitly says otherwise.
Create hierarchy with the project's type scale, measure, weight, spacing, and
alignment.

Reject:

- giant vague headings;
- tiny gray body copy beside oversized display type;
- repeated uppercase eyebrows used as decoration;
- arbitrary bold words compensating for weak structure;
- generic benefits and buttons such as "Learn More";
- invented testimonials, metrics, scarcity, clients, or logos.

Use concrete tasks, real constraints, and action labels that predict the next
step.

## Responsive Composition

Responsive work is not "stack everything at 768px."

For every meaningful layout:

- define what changes at tablet and mobile and why;
- preserve a logical content and focus order;
- keep primary actions near the choice they belong to;
- prevent grid children from overflowing with `minmax(0,1fr)` or `min-width:0`;
- adapt tables, filters, navigation, sticky UI, and media deliberately;
- test long titles, untranslated strings, empty fields, and missing images;
- verify 200% zoom, landscape mobile, and an awkward tablet width;
- check 767/768 and 1024/1025 when native GenerateBlocks boundaries are used.

Prefer intrinsic CSS (`min()`, `max()`, `clamp()`, grid auto-fit, flex-wrap)
when it removes a breakpoint without changing the design intent. Add an at-rule
when the composition, not merely a number, needs to change.

## Interaction and Accessibility

- Use native links, buttons, lists, headings, details, and form controls.
- Provide visible `:focus-visible` styles with sufficient contrast.
- Keep interactive targets at least 44px on coarse pointers where practical.
- Do not hide essential reading content until JavaScript or animation runs.
- Include `prefers-reduced-motion:reduce` for nonessential motion.
- Do not depend on hover for mobile access.
- Handle loading, empty, error, success, disabled, and long-content states when
  the component can encounter them.
- Give meaningful images accurate alt text and hide decorative SVGs from
  assistive technology.

## GenerateBlocks Maintainability

- Keep `styles` and compiled `css` semantically aligned.
- Use CSS Mode's supported one-level selectors and at-rules; do not bury a
  site-wide stylesheet in a block.
- Reuse an existing Global Style when the component contract is truly shared.
- Do not create global classes for one-off spacing nudges.
- Name section and component IDs semantically and scope them to the real post.
- Keep core blocks for prose, lists, tables, and captioned images when they are
  the simpler semantic choice.
- Avoid `!important` unless a measured cascade conflict cannot be solved at the
  owning layer.

## Final Gate

- Is the purpose and primary action obvious in a few seconds?
- Does every container, card, effect, icon, and animation earn its place?
- Is every claim real and supportable?
- Does mobile feel composed rather than collapsed?
- Are keyboard, focus, contrast, zoom, reduced motion, and non-ideal states
  handled?
- Can another editor safely change the block through the visual Styles panel or
  CSS Mode without losing hand-written CSS?
- Does the result look specific to this brand and audience?

If any answer is no, simplify or repair before shipping.
