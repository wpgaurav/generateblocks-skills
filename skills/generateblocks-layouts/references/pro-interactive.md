---
title: GB Pro interactive blocks
description: Accordion, Tabs, Carousel, Navigation, Site Header, Overlays, Mega Menus — block hierarchies, verified attributes, and hand-authoring guidance.
---

# GB Pro Interactive Blocks

All of these require GB Pro. They follow the same serialization rules as free
blocks (`recovery-rules.md` applies in full), with block-specific generated
class patterns and frontend JS that the plugin enqueues automatically when the
block is present.

Do not derive every Pro selector as `gb-{slug}-{uniqueId}`. Native save output
in Pro 2.8.0-beta.1 uses `gb-accordion__item-{id}`,
`gb-accordion__toggle-{id}`, and `gb-accordion__content-{id}` for those children.
For programmatic styling, obtain the class from that block's native save output
before compiling local CSS. A guessed class can yield valid saved blocks whose
styles never apply; the local efficiency benchmark exposed this distinction.

## ⚠ Attribute declaration order differs per block

Recovery rule §3.4 (JSON keys serialize in block.json declaration order) still
applies, but several Pro blocks declare attributes in a different order than
the free blocks. Verified orders:

```
accordion / accordion-item / accordion-toggle / accordion-content / tabs /
tabs-menu / tab-menu-item / tab-items / tab-item
  uniqueId, tagName, styles, css, globalClasses, htmlAttributes [, extras]

carousel / carousel-items / carousel-pagination / navigation / menu-container /
site-header
  uniqueId, styles, css, globalClasses, tagName, htmlAttributes [, extras]

carousel-control
  uniqueId, controlType, openIcon, closeIcon, iconLocation, iconOnly, content,
  carouselId, tagName, htmlAttributes, styles, globalClasses, css

menu-toggle
  uniqueId, openIcon, closeIcon, iconLocation, styles, css, globalClasses,
  htmlAttributes, tagName, content, iconOnly

classic-menu
  menu, uniqueId, styles, css, globalClasses
```

Extras position: `openByDefault` (accordion-item) and `tabItemOpen`
(tab-menu-item, tab-item) come after `htmlAttributes`; `openIcon`/`closeIcon`
on accordion-toggle-icon likewise.

## 1. Accordion

```
generateblocks-pro/accordion                tagName: div|section|aside|nav|ul|ol|li
└── generateblocks-pro/accordion-item       tagName: div|section|aside|li
                                            + openByDefault (bool)
    ├── generateblocks-pro/accordion-toggle tagName: div|button (use button)
    │   └── [text blocks for the label]
    │   └── generateblocks-pro/accordion-toggle-icon
    │       tagName: span, + openIcon/closeIcon (SVG strings)
    └── generateblocks-pro/accordion-content tagName: div|section|aside|ul|ol
        └── [any blocks]
```

- ARIA wiring (`aria-expanded`, `aria-controls`, generated IDs) is added by
  the save/render functions — don't hand-write it.
- `openByDefault:true` on an item renders it expanded.
- Nested accordions are supported (2.1+).
- FAQ schema is available as an accordion option in the editor UI.
- Frontend: `dist/accordion.js` + `accordion-style.css`, auto-enqueued.

**Padding is required for every accordion, including core Details.** Give the
question/toggle and expanded answer explicit, consistent inline padding; keep
text and icons clear of borders. A useful starting point is 24px on desktop and
16px on mobile, with sufficient block padding and space around the toggle icon.
Verify both closed and expanded states in the editor and frontend. Do not carry
zero-padding source rules into a bordered accordion without correcting them.
Inspect computed padding as well as stored values: theme logical properties can
override physical padding. If the editor canvas cannot be inspected, report that
limit separately from successful block parsing or frontend checks.

**Hand-authoring guidance:** the toggle/content wiring (IDs, aria, state
classes) is generated. Build one accordion in the editor, copy its serialized
markup as your template, then replicate items. Do not invent the rendered
HTML from the block comments alone.

## 2. Tabs

```
generateblocks-pro/tabs
├── generateblocks-pro/tabs-menu
│   └── generateblocks-pro/tab-menu-item    tagName: div|button|ul|ol|li, + tabItemOpen
└── generateblocks-pro/tab-items
    └── generateblocks-pro/tab-item         tagName: div|ul|ol|li, + tabItemOpen
```

- The Nth `tab-menu-item` controls the Nth `tab-item` — order is the link.
- Set `tabItemOpen:true` on exactly one menu item AND its matching item.
- In Pro 2.8 beta, supply stable IDs and `tablist`/`tab`/`tabpanel` roles through
  native `htmlAttributes` when save output does not include them. The plugin
  runtime manages `aria-selected`, `tabindex`, and ID-based control/label links.
  Verify click and arrow/Home/End behavior against the installed version.
- Frontend: `dist/tabs.js`.

## 3. Carousel (Pro 2.5+)

```
generateblocks-pro/carousel
├── generateblocks-pro/carousel-items
│   └── generateblocks-pro/carousel-item        (one per slide)
├── generateblocks-pro/carousel-control         tagName: button|a
│   controlType (prev|next), openIcon, iconOnly, carouselId
└── generateblocks-pro/carousel-pagination      (dots; server/JS-rendered)
```

- Swiper-based frontend (`dist/carousel.js`); options (autoplay, loop,
  slides-per-view, breakpoints) are set in the editor UI panel.
- Carousel settings live in editor-managed attributes/data — build the
  carousel shell in the editor, then hand-author only the `carousel-item`
  contents (those are ordinary blocks).

## 4. Navigation + Site Header (Pro 2.2+)

```
generateblocks-pro/site-header              tagName: div|section|aside|nav|header
└── [logo blocks, etc.]
└── generateblocks-pro/navigation           tagName: div|section|aside|nav
    + subMenuType ("hover" default)
    ├── generateblocks-pro/menu-toggle      tagName: button (mobile hamburger)
    │   openIcon/closeIcon (SVG), content, iconOnly
    └── generateblocks-pro/menu-container
        └── generateblocks-pro/classic-menu  menu: WP menu ID/slug
            (classic-menu-item / classic-sub-menu generated from the WP menu)
```

- `classic-menu` renders a **WordPress menu** (Appearance → Menus) — content
  comes from the menu, not from inner blocks.
- `subMenuType` controls dropdown behavior; mobile off-canvas/modal behavior
  is configured on the navigation/menu-container in the UI.
- Sticky header: set via the site-header UI (don't hand-write
  `data-gb-is-sticky` — verify the current attribute in the editor if needed).

## 5. Overlays (Pro 2.3+) — modals, off-canvas, mega menu panels

- Overlay templates are **`gblocks_overlay` posts** (Dashboard → GenerateBlocks
  → Overlays), built with normal blocks.
- An overlay is attached to a trigger element/menu item via the editor UI;
  placement options include modal, off-canvas panel, and anchored dropdown.
- **Mega menus** (2.3+): an overlay anchored to a WP menu item
  (`includes/mega-menus/class-mega-menus.php`). Configure on the menu item.
- Triggers: click, hover, exit intent, percentage scrolled, time delay, or a
  custom JS event (e.g. `wc-blocks_added_to_cart`), with cookie-based
  frequency capping. Entrance animations built in: fade / slide / scale from
  any direction with speed control.
- Hand-authoring: author the overlay's *content* as normal block markup
  inside the overlay post; leave trigger wiring and animation settings to
  the UI.

## 6. Decision guide

| Need | Use |
|---|---|
| FAQ with schema | Accordion (+ FAQ schema option) |
| Content switcher / pricing toggle | Tabs |
| Testimonial/logo slider | Carousel |
| Site header + menu (FSE-free) | Site Header + Navigation + Classic Menu |
| Modal / slide-in panel / mega menu | Overlay |
| One-off collapsible without Pro | Native `core/details`; do not invent GB details tags or use Custom HTML |

## 7. Free-plugin fallbacks

Without Pro, tell the user the section needs Pro, and offer:
- Accordion → native `core/details` or stacked sections (always-open)
- Tabs → anchor-linked sections
- Carousel → CSS scroll-snap row (overflow-x scroll on an element block —
  works with free GB, no JS; see `css-patterns.md`)
- Site header → theme header (GeneratePress + Elements)
