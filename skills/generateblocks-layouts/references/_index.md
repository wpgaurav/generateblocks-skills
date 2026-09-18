---
title: GenerateBlocks reference router
description: Load the authoring contract, then only the references needed by the task.
---

# Reference router

For markup, read `authoring-contract.md` once, then select matching references
below. Reuse already-loaded guidance in the same task. Read-only questions need
only the relevant topic. The diagnostic catalog is not a prerequisite for every
layout. Examples never authorize shared styles, tokens, or publication.

| Task or uncertainty | Read |
|---|---|
| Familiar static Element/Text layout, ordinary states, native breakpoints | Contract is sufficient; optional `examples/basic/` or `examples/layouts/` |
| Unfamiliar tag/attribute; Media or Shape save markup | Relevant block in `block-types.md`; `svg-icons.md` for SVG |
| New or materially changed visual direction | `design-quality.md` and nearest brand guidance; broader design critique only when needed |
| Raw CSS editing, complex selectors, CSS Mode parsing | `css-mode.md`; `css-patterns.md` only for a needed recipe |
| Custom breakpoints, container queries, responsive cascade problem | `responsive.md` |
| Hover/reduced-motion or other animation beyond the contract | `animations.md` |
| Dynamic post lists | `query-block.md` + `dynamic-tags.md` |
| Custom fields, ACF repeaters/options | `acf-and-custom-fields.md` + `dynamic-tags.md`; query guide when looping |
| Conditional rendering | `conditions.md` |
| Forms | `pro-forms.md`; native editor output for field serialization |
| Accordion, tabs, carousel, navigation, header, overlays | Relevant section of `pro-interactive.md` |
| Which features require Pro? | `gb-pro.md` |
| Shared styling choice unclear or implicit pattern dependencies | `styling-scope.md` |
| Explicitly requested Global Styles/tokens | `global-styles.md`; add `design-systems-beta.md` for Pro 2.8 |
| Reusable compositions/pattern imports | `patterns.md` |
| Full-site templates or GeneratePress Elements | `template-authoring.md` |
| CSS delivery, caches, performance | `performance.md` |
| Authorized MCP/REST publishing | `mcp-publishing.md` before writing |
| V1 migration | `migrations.md` |
| Explicit legacy core/query request | `query-loops.md`; otherwise use the V2 query family |
| Recovery or preflight failure | Matching topic in `recovery-rules.md`; `troubleshooting.md` if unresolved |
| Target convention inspection or bulk conversion diagnostics | Relevant section of `field-notes.md` |

The stable references record earlier free 2.4.1/Pro 2.7.1 observations. The beta
workflow was tested with free 2.5.0-beta.1/Pro 2.8.0-beta.1 on WordPress 7.1.1.
Verify the target instead of treating those observations as its installed state.
