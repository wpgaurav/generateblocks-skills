---
title: CSS Delivery and Performance
description: Source-verified GenerateBlocks 2.4 CSS delivery modes, cache invalidation, Global Style reuse, DOM discipline, and performance verification.
---

# CSS Delivery and Performance

GenerateBlocks V2 saves compiled CSS with each local block, collects CSS for
the blocks present on the request, and delivers the result inline or through a
generated file. Performance work starts by measuring the site's current mode,
not by adding guessed filters.

Verified against GenerateBlocks 2.4.1 and GenerateBlocks Pro 2.7.1.

## Local Styles Data

A local block normally carries:

- `styles`: editable structured data used by the Styles panel and CSS Mode;
- `css`: compiled frontend CSS scoped to the block's unique selector.

The PHP renderer reads the saved `css` attribute. It does not rebuild the
declarations from `styles` on every frontend request. This makes
`styles`/`css` parity a correctness and maintenance concern, not merely an
editor concern.

## Delivery Modes

GenerateBlocks' default option is:

```php
'css_print_method' => 'file'
```

The active mode is filtered through `generateblocks_css_print_method`.
GenerateBlocks falls back to inline CSS when file delivery is unavailable or
in contexts such as previews, the Customizer, and AMP.

### File mode

For a singular record, the generated file is stored under:

```text
wp-content/uploads/generateblocks/style-{post_id}.css
```

The file receives a modification-time version query. GenerateBlocks checks
that the directory/file is writable and falls back to inline CSS if generation
fails.

CSS smaller than the `generateblocks_css_inline_length` threshold (500 bytes
by default) stays inline instead of creating a tiny request.

### Inline mode

GenerateBlocks registers the `generateblocks` style handle and attaches the
collected CSS through `wp_add_inline_style()`.

The plugin's current core integration forces `is_single()` requests to inline
mode. Pages and other contexts follow the configured/filtered mode unless a
fallback condition applies.

### Live gauravtiwari.org state

Observed 2026-08-26:

- `css_print_method`: `inline`;
- container width: `1366`;
- responsive preview syncing: enabled;
- GenerateBlocks Google Fonts: disabled;
- 45 published Pro Global Styles.

Recheck before future work. These are site state, not universal defaults.

## Cache Invalidation

When a post containing GenerateBlocks saves, the plugin records its current GB
version and marks generated CSS for refresh. Reusable block changes can
invalidate consuming records. Global Style changes also clear the dynamic CSS
post cache.

If file-mode CSS looks stale:

1. confirm the record contains GenerateBlocks and its dynamic CSS version meta;
2. inspect the `generateblocks_dynamic_css_posts` option;
3. verify the uploads/generateblocks directory is writable;
4. trigger the project's approved regeneration/save flow;
5. distinguish origin CSS from edge/browser cache before retrying writes.

Do not delete the entire uploads directory to solve one stale file.

## Global Styles and Repetition

Identical local block styles still produce selectors scoped to each unique ID.
Do not assume GenerateBlocks deduplicates 20 copied local cards into one rule.

Use Pro Global Styles for a genuinely repeated component contract:

- primary/secondary actions;
- shared content rails;
- form controls;
- repeated interactive card shells;
- semantic metadata rows.

Global Styles compile in published order. Later equal-specificity rules can
override earlier ones. Reorder with intent and inspect all blocks using the
class before renaming or deleting it.

Do not turn one-off spacing values into global utility noise.

## CSS Size Discipline

- Prefer project variables over repeated literal palettes.
- Use intrinsic layout to remove redundant media-query branches.
- Keep state and responsive branches in `styles`; remove stale rules from both
  `styles` and `css`.
- Avoid page-level selector graphs inside block CSS.
- Use one visual treatment per surface instead of accumulating declarations
  for border, tint, shadow, glow, and blur.
- Do not duplicate the same responsive declarations across every child when a
  parent layout change solves the problem.

CSS Mode supports only one selector level and `@media`, `@supports`, and
`@container`. Keyframes and fonts belong in the owning project stylesheet.

## DOM Discipline

Every wrapper must own semantics, layout, clipping, inheritance, or state.

Prefer:

```text
section
  inner rail
    layout
      content
      evidence/media
```

Avoid wrapper chains named only `wrapper`, `inner`, `content-wrapper`, and
`card-inner` when none has a distinct job.

Use core blocks where they are simpler and more semantic:

- core/list for lists;
- core/table for tabular comparisons;
- core/image for static images with captions;
- core/video/embed for media;
- core headings/paragraphs when theme prose styles should own typography.

## Interactive Pro Assets

Accordion, tabs, carousel, navigation, overlays, and forms can enqueue their
own runtime assets. Use the built-in component only when its interaction is
needed; a static 3-item row does not need a carousel.

After adding a Pro component:

- inspect network requests and transferred bytes;
- verify the asset is not duplicated by the theme;
- test keyboard and reduced-motion behavior;
- confirm below-the-fold interactivity does not delay the page's primary
  content.

Do not add a generic defer filter without checking dependency order and the
actual handles registered by the installed build.

## Images

- Use WordPress attachment data so `srcset`, `sizes`, intrinsic dimensions,
  and alt text remain correct.
- Never lazy-load the actual LCP image.
- Lazy-load below-the-fold media.
- Match the requested display size; do not render multi-megabyte originals as
  thumbnails.
- Use `generateblocks/media` for dynamic/loop images and custom-layout images;
  use `core/image` when a static image needs a caption.
- Decorative SVGs should be compact and `aria-hidden`; meaningful icons need a
  visible label or accessible name.

## Read-Only Inspection

WP-CLI examples:

```bash
wp option get generateblocks --format=json
wp option get generateblocks_dynamic_css_posts --format=json
wp post meta get POST_ID _generateblocks_dynamic_css_version
```

Source-level filters verified in 2.4.1:

```text
generateblocks_css_print_method
generateblocks_css_inline_length
generateblocks_dynamic_css_priority
generateblocks_css_output
generateblocks_process_block_css
generateblocks_block_css
```

Do not document or deploy imaginary convenience filters such as
`generateblocks_use_external_css`, `generateblocks_minify_css`, or a generic
frontend script strategy without confirming them in the installed source.

## Verification

- Confirm the actual delivery mode on the rendered target.
- Check that block CSS exists once and selectors match the stored IDs.
- In file mode, confirm the generated file is current and cacheable.
- In inline mode, measure HTML/CSS size rather than assuming inline is faster.
- Compare before/after request count, transfer size, LCP, CLS, and interaction
  readiness when the change is performance-driven.
- Test cached and uncached responses separately.
- Check that removing a block removes its CSS after the normal save/cache
  invalidation path.
- Confirm the design still works without animation and at 200% zoom.
