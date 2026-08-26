---
title: Motion and Interaction Feedback
description: Durable GenerateBlocks motion patterns using structured states, reduced-motion at-rules, project-owned keyframes, and anti-slop constraints.
---

# Motion and Interaction Feedback

GenerateBlocks handles ordinary interactive feedback through CSS properties and
one-level selectors in `styles`. Pro CSS Mode edits the same data. Keep the
compiled local `css` cache aligned.

CSS Mode does not support `@keyframes`. Put keyframes in the owning theme/plugin
stylesheet or another project-approved CSS surface.

## Choose Motion by Job

| Job | Durable route |
|---|---|
| Hover/focus color or boundary change | base transition + `&:hover` / `&:focus-visible` in `styles` |
| Child response to parent interaction | one structured selector such as `&:hover > .child` or a post-scoped parent selector on the child |
| Pseudo-element response | `&::before` / `&::after` and matching state branch |
| Reduced-motion fallback | `@media (prefers-reduced-motion:reduce)` in `styles` |
| Keyframe animation | owning project stylesheet, not CSS Mode |
| Accordion/tabs/carousel/navigation motion | Pro component settings and frontend behavior |
| Custom scroll-trigger behavior | project script/style layer with visible no-JS content |

## Interactive State Pattern

```json
{
  "backgroundColor":"var(--action)",
  "borderColor":"transparent",
  "color":"#fff",
  "transition":"background-color .2s ease,border-color .2s ease,color .2s ease",
  "&:hover":{"backgroundColor":"var(--action-hover)"},
  "&:focus-visible":{
    "outline":"2px solid var(--focus)",
    "outlineOffset":"3px"
  },
  "@media (prefers-reduced-motion:reduce)":{
    "transition":"none"
  }
}
```

The hover and focus branches must also appear in compiled `css`. Do not add
them only to the cache string.

## Parent and Child Motion

Keep one selector level:

```json
{
  "transition":"transform .18s ease",
  ".gb-element-card:hover &":{"transform":"translateX(0.25rem)"},
  "@media (prefers-reduced-motion:reduce)":{"transition":"none"}
}
```

When hand-authoring, replace the parent with its real post-scoped class. A
generic `.card:hover` selector can leak or collide.

You can also attach the rule to the parent:

```json
{
  "&:hover > .child":{"color":"currentColor"}
}
```

Do not nest `.child` inside `&:hover`; flatten it into one selector.

## Pseudo-Element Motion

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
    "transition":"transform .2s ease",
    "width":"100%"
  },
  "&:hover::after":{"transform":"scaleX(1)"},
  "@media (prefers-reduced-motion:reduce)":{
    "&::after":{"transition":"none"}
  }
}
```

Use this for a real affordance, not on every heading or card.

## Keyframes

`@keyframes`, `@font-face`, and arbitrary at-rules cannot round-trip through
CSS Mode's structured styles model. If a component genuinely needs keyframes:

1. give the animation a component-scoped name;
2. put the keyframes in the owning project stylesheet;
3. keep the block's `animation` property in `styles`;
4. add a reduced-motion branch that disables it;
5. render essential content visible before animation runs.

Block styles:

```json
{
  "animation":"feature-enter .35s ease both",
  "@media (prefers-reduced-motion:reduce)":{"animation":"none"}
}
```

Project stylesheet:

```css
@keyframes feature-enter {
  from { opacity: 0; transform: translateY(.5rem); }
  to { opacity: 1; transform: none; }
}
```

Do not hide all content with `opacity:0` and rely on JavaScript to reveal it.

## Performance

Prefer brief changes to opacity and transform for moving pixels. Color,
background-color, border-color, and box-shadow are suitable for restrained
interactive feedback.

Avoid animating layout properties such as width, height, margin, grid tracks,
or top/left during routine UI interactions. Measure any filter, blur, large
shadow, or continuous animation on mobile hardware.

## Motion Anti-Slop Gate

Reject motion when:

- every block animates on scroll;
- noninteractive cards lift or glow on hover;
- a cursor trail, parallax, tilt, or magnetic button adds no task value;
- autoplay hides information inside a carousel;
- motion delays reading or primary action;
- mobile behavior depends on hover;
- reduced motion removes content instead of only motion;
- animated counters present unsupported proof.

Use motion when it explains state, preserves spatial continuity, confirms an
action, or supports one deliberate signature field.

## Verification

- Keyboard focus is visible and follows the same information hierarchy as
  hover.
- Touch users can reach the same content and actions.
- `prefers-reduced-motion:reduce` removes nonessential movement.
- Content is visible when CSS animation or JavaScript fails.
- No layout shift occurs when motion initializes.
- States in `css` have equivalent structured `styles` branches.
- Pro components remain operable with keyboard and screen readers.
