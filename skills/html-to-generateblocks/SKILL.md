---
name: html-to-generateblocks
description: Convert HTML/CSS layouts to GenerateBlocks V2 format with inline styles
metadata:
  version: 2.0.0
  author: Gaurav Tiwari
  updated: '2026-09-18'
  trigger:
  - HTML to GenerateBlocks
  - convert to GB
  - convert HTML to blocks
  - GenerateBlocks conversion
  tags:
  - wordpress
  - generateblocks
  - conversion
  - html
  compatibility: Stable V2 guidance plus free 2.5.0-beta.1 / Pro 2.8.0-beta.1 design-system
    routing
---

# HTML/CSS to GenerateBlocks

Convert supplied HTML/CSS into editable native blocks while preserving content,
semantics, responsive behavior, and interactions.

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

1. Inventory the source DOM, CSS, links, media, and runtime behavior.
2. Remove wrappers only when they own no semantics, layout, or behavior.
3. Map declarations and states into local `styles`, then compile matching `css`.
   For uncertain mappings, read the relevant conversion pattern.
4. Generate `{section}-converted.html`; validate against the source and the
   shared authoring contract. Report any interaction that could not be preserved.

The companion contract contains the familiar static Element/Text path. Load
[this conversion reference](references/conversion-patterns.md) only when the source mapping
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
