---
name: elementor-to-generateblocks
description: Convert Elementor layouts to clean GenerateBlocks V2 format, eliminating
  DIVception
metadata:
  version: 1.0.0
  author: Gaurav Tiwari
  updated: '2026-09-18'
  trigger:
  - Elementor to GenerateBlocks
  - convert Elementor
  - Elementor migration
  - clean up Elementor
  - simplify Elementor
  tags:
  - wordpress
  - generateblocks
  - elementor
  - conversion
  - migration
  compatibility: Stable V2 guidance plus free 2.5.0-beta.1 / Pro 2.8.0-beta.1 design-system
    routing
---

# Elementor to GenerateBlocks

Migrate Elementor layouts to semantic native blocks. Reduce unnecessary wrappers
without dropping content, device visibility, CMS queries, or working widgets.

Use GenerateBlocks (including Pro) and core blocks only. Keep CSS in native
block styles and interactions in native blocks. Do not add Scripts Manager,
Page Block, Custom HTML/CSS/JS, or another builder without an explicit request.

## Shared contract

Use the companion `generateblocks-layouts` skill's `references/authoring-contract.md`
before emitting markup, and its `references/_index.md` to select task-specific
guides. With installed skills it is the sibling `../generateblocks-layouts/`;
a standalone bundle carries the same dependency under
`references/generateblocks-layouts/`. Do not read both copies or the entire library.

**Local block styles are the default.** Prompt once about shared Global Styles
and Design Tokens unless the user already chose. Create/import shared records
or add new shared dependencies only after explicit opt-in; preserve existing
references. The companion contract owns the exact scope and prompt. Use the real post ID
when available, otherwise one random four-digit scope for the layout.

## Conversion workflow

1. Inventory sections/containers, widgets, forms, dynamic sources, and links.
2. Inspect actual layout values and device ranges; class names are not numeric
   specifications. Use the widget map for unfamiliar or interactive widgets.
3. Flatten only redundant wrappers. Preserve anchor/script dependencies and
   migrate form/query configuration deliberately.
4. Generate `{section}-converted.html`, reconcile the widget inventory, and run
   the shared validation flow. Measure rather than promise speed improvements.

The companion contract contains the familiar static Element/Text path. Load
[this conversion reference](references/widget-map.md) only when the source mapping
requires it. New visual decisions additionally require `design-quality.md`.
Dynamic data requires `dynamic-tags.md` plus the relevant query/field guide;
interactive Pro blocks require their native schemas/save output.

## Delivery

Write blocks to files, not chat. Use the companion serializer helpers and run
`preflight.py FILE --id-scope SCOPE`. Confirm unfamiliar output in the actual editor
and compare visible content and behavior; static preflight is not sufficient.
A live write also requires `mcp-publishing.md`, a snapshot, and raw-content
readback. A conversion request by itself does not authorize publication.

Keep serialization, class-order, link, CSS-state, and breakpoint rules in the
companion contract rather than duplicating them here. Examples are not current
brand defaults or import-ready markup for a different post.
