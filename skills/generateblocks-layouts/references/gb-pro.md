---
title: GenerateBlocks Pro overview (2.7)
description: What Pro adds on top of free GenerateBlocks — block catalog, global classes, query extensions, conditions, forms, overlays — with pointers to the deep-dive references.
---

# GenerateBlocks Pro (2.7.1)

Use Pro features only when the user has GB Pro installed. If unsure, default
to free-plugin patterns and say in chat which parts would need Pro.

Status note (August 2026): free 2.4.1 and Pro 2.7.1 are stable and active on
gauravtiwari.org. Both require WordPress 6.7 or later; free requires PHP 7.2+
and Pro requires PHP 7.4+. Recheck the target instead of assuming these
versions. GenerateBlocks 2.3 disabled the legacy core Additional CSS field on
GB blocks by default; Pro 2.6 added the structured CSS Editor/CSS Mode.

## What Pro adds — map

| Capability | Detail file |
|---|---|
| Accordion, Tabs, Carousel, Navigation, Site Header, Overlays, Mega Menus | `pro-interactive.md` |
| Forms (fields, validation, email/webhook/ESP integrations, Turnstile) — **2.6** | `pro-forms.md` |
| Block/menu conditions (`gbBlockCondition` → Conditions CPT) | `conditions.md` |
| Pro dynamic tags (`archive_title`, `site_*`, `option`, `term_meta`, `user_meta`, `loop_index`, `loop_item`, adjacent-post `source:`) | `dynamic-tags.md` §6 |
| ACF / custom-field deep integration | `acf-and-custom-fields.md` |
| Query extensions (`"current"` magic values, `stickyPosts`, `post_meta`/`option` loop types) | `query-block.md` §3, §6 |
| Global classes & Styles dashboard | below |
| CSS Properties and CSS Mode | `css-mode.md` |
| Pattern library (local CPT + remote pro library) | below |
| Editor Access & Control Sets (lock down editing per role) — **2.7** | below |

## Pro block catalog (27 blocks, verified)

```
Forms (2.6):     form, form-field, form-field-label, form-field-control, form-render
Accordion:       accordion, accordion-item, accordion-toggle, accordion-toggle-icon, accordion-content
Tabs:            tabs, tabs-menu, tab-menu-item, tab-items, tab-item
Carousel (2.5):  carousel, carousel-items, carousel-item, carousel-control, carousel-pagination
Navigation (2.2):navigation, menu-toggle, menu-container, classic-menu, classic-menu-item, classic-sub-menu
Header (2.2):    site-header
```

All namespaced `generateblocks-pro/{slug}`, class pattern
`gb-{slug}-{uniqueId}`, same recovery rules — but **attribute declaration
order differs per block**: see `pro-interactive.md` top section before
emitting any Pro block JSON.

Overlays, Conditions, Global Styles, and Editor Access are **not blocks** —
they're CPT-backed systems (`gblocks_overlay`, `gblocks_condition`,
`gblocks_styles`, `gb_access_profile`/`gb_access_set`) configured in the
dashboard and referenced from blocks by ID or applied globally.

## Global classes

- Created/managed in the **Global Styles dashboard**; current styles are
  `gblocks_styles` records (`gblocks_global_style` is the deprecated V1 CPT),
  with compiled CSS stored in post meta and cached for frontend delivery.
- A block opts in via its `globalClasses` array attribute; the class names
  are also written into the rendered HTML class list:

```json
"globalClasses":["button-primary"]
```
```html
<a class="gb-element-cta1 gb-element button-primary" href="...">
```

- Use global classes when the same component style repeats across the site
  (buttons, cards, badges). The per-block `styles`/`css` then carries only
  instance-specific overrides.
- 2.6 adds **CSS Mode** and a CSS Properties panel. CSS Mode parses supported
  CSS into the normal `styles` object and has no `cssMode` markup attribute.
  It supports one selector level and `@media`, `@supports`, and `@container`.
  Read `css-mode.md` before hand-authoring nested selectors or at-rules.
- Hand-authoring: reference existing global classes freely. Creating them
  programmatically goes through the Styles REST API — otherwise tell the user
  to create the class in the dashboard first.

## Editor Access & Control Sets (2.7)

Role-based editing restrictions for client sites — lock blocks down so
editors can change content but not design.

- **Access Profiles** (`gb_access_profile` CPT) define what a user group can
  do in the editor; **Control Sets** (`gb_access_set` CPT) define which
  block controls are exposed. Both are built in the GenerateBlocks
  dashboard; REST-backed; managing them needs `manage_options`, being
  subject to them needs only `edit_posts` (filter:
  `generateblocks_editor_access_capability`).
- Adds **content-only** and **read-only** editing modes to GB blocks (free
  2.4 marks `text.content` and `media.mediaId` with `role:content` to
  support this).
- **Requires free GenerateBlocks 2.4** — the feature silently stays off
  unless the free plugin exposes `generateblocks_supports(
  'block-inspector-slot' )`. V1 legacy blocks are excluded.
- No markup impact: nothing about Editor Access is serialized into block
  comments, so everything this skill emits is unaffected.

## Pattern library

- Local patterns: `wp_block` posts + `gblocks_pattern_collections` taxonomy.
- Remote pro pattern library fetched from generatepress.com (2.0+),
  "instant patterns" since 2.5.
- Pattern markup is just block markup — anything this skill emits can be
  saved as a pattern. See `patterns.md` for registration options.

## Version timeline (what exists at which Pro version)

| Version | Features |
|---|---|
| 2.0 | v2 rewrite: accordion/tabs v2, ACF dynamic tags, query extensions |
| 2.1 | Device visibility, nested accordions, FAQ schema, a11y options |
| 2.2 | Navigation + Site Header blocks |
| 2.3 | Overlays (modals, off-canvas, anchored), mega menus |
| 2.4 | Conditions system (blocks + menu items) |
| 2.5 | Carousel, site logo/URL tags, grid-template controls |
| 2.6 | Forms system + integrations, CSS Mode |
| 2.7 | Editor Access + Control Sets, content-only/read-only editing (needs free 2.4); forms editing now needs `edit_others_posts` |

## When to recommend Pro

- Accordions, tabs, carousels, mega menus, modals/off-canvas
- Site header/navigation built in blocks rather than the theme
- Native forms with ESP integrations
- Conditional rendering (roles, scheduling, devices, meta)
- Related-posts queries (`"current"` magic values) and ACF repeater loops
- Archive/site/option/term/user dynamic tags, prev/next post navigation
- Global classes / design-token management in the Styles dashboard
- Client sites needing role-locked editing (Editor Access / Control Sets, 2.7)

Everything else — layout, styling, static sections, standard query loops,
free dynamic tags — works on the free plugin.
