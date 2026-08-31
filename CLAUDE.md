# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository contains GenerateBlocks WordPress plugin development and skills for creating/converting layouts.

**Contents:**
- `generateblocks/` - Free plugin source code (2.4.1)
- `generateblocks-pro/` - Pro plugin source code (2.7.1 — Editor Access, Forms, CSS Mode; git-ignored)
- `skills/` - Claude Code skills for GenerateBlocks workflows
- `examples/` - Golden example sections + production pages from gauravtiwari.org

## Skills

All skills follow Claude Code skill folder convention with `SKILL.md` as the main entry point.

### Available Skills

| Skill | Folder | Purpose |
|-------|--------|---------|
| **GenerateBlocks Layouts** | `skills/generateblocks-layouts/` | Build layouts using GenerateBlocks V2 blocks |
| **HTML to GenerateBlocks** | `skills/html-to-generateblocks/` | Convert HTML/CSS to GenerateBlocks format |
| **Elementor to GenerateBlocks** | `skills/elementor-to-generateblocks/` | Migrate Elementor layouts to clean GB blocks |
| **Figma to GenerateBlocks** | `skills/figma-to-generateblocks/` | Convert Figma designs to GB blocks |

### Skill Structure

```
skills/
├── generateblocks-layouts/
│   ├── SKILL.md              # Main skill entry (V2 blocks, styling, patterns)
│   ├── references/           # Detailed documentation
│   │   ├── _index.md         # Skill router — read first
│   │   ├── recovery-rules.md # Attempt Recovery error catalog (read every task)
│   │   ├── block-types.md    # Element, Text, Media, Shape verified specs
│   │   ├── dynamic-tags.md   # Canonical dynamic tag catalog + syntax
│   │   ├── query-block.md    # V2 query/looper/loop-item + Pro query extensions
│   │   ├── acf-and-custom-fields.md # ACF patterns, repeater loops, options
│   │   ├── conditions.md     # Pro conditions + free alternatives
│   │   ├── template-authoring.md # Full sites: GP Elements, FSE, archives
│   │   ├── animations.md     # Motion: hover, keyframes, scroll-driven
│   │   ├── gb-pro.md         # GenerateBlocks Pro overview (2.7)
│   │   ├── pro-forms.md      # Pro Forms system (2.6+)
│   │   ├── pro-interactive.md# Accordion/Tabs/Carousel/Nav/Header/Overlays
│   │   ├── css-patterns.md   # Hover, transitions, gradients
│   │   ├── responsive.md     # Media queries, breakpoints
│   │   ├── svg-icons.md      # Shape block, inline SVG
│   │   ├── troubleshooting.md# Debug recipes
│   │   ├── query-loops.md    # LEGACY core/query patterns
│   │   ├── global-styles.md  # Design tokens, theme.json
│   │   ├── patterns.md       # Block pattern registration
│   │   ├── performance.md    # CSS delivery optimization
│   │   ├── mcp-publishing.md # Live-site writes over MCP/REST
│   │   └── migrations.md     # V1 to V2 migration guide
│   └── examples/             # Copy-paste ready blocks
│       ├── basic/            # Single blocks (buttons, containers)
│       ├── compound/         # Combined blocks (cards, features)
│       ├── layouts/          # Full sections (hero, services)
│       └── svg/              # Icons and decorative shapes
├── html-to-generateblocks/
│   ├── SKILL.md              # Conversion workflow, patterns
│   └── CLAUDE.md             # Trigger conditions
├── elementor-to-generateblocks/
│   ├── SKILL.md              # DIVception cleanup, widget mapping
│   └── CLAUDE.md             # Trigger conditions
└── figma-to-generateblocks/
    ├── SKILL.md              # Figma CSS mapping, design inference
    └── CLAUDE.md             # Trigger conditions
```

### Using Skills

**Trigger phrases:**
- "GenerateBlocks", "GB blocks", "GB layouts" → `generateblocks-layouts`
- "HTML to GenerateBlocks", "convert to GB" → `html-to-generateblocks`
- "Elementor to GenerateBlocks", "convert Elementor" → `elementor-to-generateblocks`
- "Figma to GenerateBlocks", "convert Figma design" → `figma-to-generateblocks`

## Development Commands

### GenerateBlocks (Free)

Working directory: `generateblocks/`

```bash
# Build
npm run build              # Production build
npm run start              # Watch mode with hot reload
npm run clean              # Reset dist/ to git state

# Linting
npm run lint:js            # ESLint JavaScript
npm run lint:pkg-json      # Package.json validation

# Testing
npm run test:unit          # Jest unit tests
npm run test:e2e           # Playwright E2E tests
npm run test:e2e:67        # E2E with WordPress 6.7
npm run test:e2e:68        # E2E with WordPress 6.8
npm run test:e2e:trunk     # E2E with WordPress trunk

# Local WordPress
npm run wp-env:start       # Start @wordpress/env
npm run wp-env:stop        # Stop environment
npm run wp-env:clean       # Clean environment

# Package
npm run package            # Create plugin zip
npm run googleFonts        # Download Google Fonts
```

### GenerateBlocks Pro

Working directory: `generateblocks-pro/`

```bash
# Same commands as free version, plus:
npm run plugin-zip         # Create plugin zip
```

## Architecture

### GenerateBlocks V1 vs V2

**V1 (Legacy):** Specific block types
- `generateblocks/container`, `button`, `headline`, `grid`, `image`

**V2 (Current):** Generic element-based blocks
- `generateblocks/element` - Container (div, section, article, header, footer, nav, etc.)
- `generateblocks/text` - Text content (p, h1-h6, span, a, button)
- `generateblocks/media` - Images
- `generateblocks/shape` - SVG shapes/icons

**IMPORTANT V2 Naming:**
- Use `generateblocks/element` (NOT `/container`)
- Use `generateblocks/text` (NOT `/headline` or `/button`)
- New element blocks prefer `gb-element-{uniqueId} gb-element` with
  `"className":"gb-element"` (Option A, serialized last)
- Text/media/shape commonly omit `className` and render base-first
- Existing pages can mix conventions; measure and preserve the target per block
  type instead of normalizing it

### Plugin Structure

**Frontend (`src/`):**
- `blocks/` - Block implementations (React)
- `components/` - Reusable components (46+)
- `hooks/` - Custom hooks (15+)
- `hoc/` - Higher-order components
- `utils/` - Utilities
- `editor/`, `extend/`, `pattern-library/`, `dynamic-tags/`

**Backend (`includes/`):**
- `blocks/` - PHP block classes (server-side rendering)
- `class-do-css.php` - CSS generation
- `class-enqueue-css.php` - CSS enqueuing
- `class-dynamic-content.php` - Dynamic content
- `class-render-blocks.php` - Block rendering
- `class-query-loop.php` - Query loops
- `pattern-library/`, `dynamic-tags/`

**Build:**
- Webpack with `@wordpress/scripts`
- Custom config in `webpack.config.js`
- `@edge22/*` packages bundled separately
- Output: `dist/`

**Import aliases** (from `jsconfig.json`):
- `@utils/*`, `@components/*`, `@hooks/*`, `@hoc/*`

### Key Dependencies

- `@wordpress/block-editor`, `@wordpress/blocks`, `@wordpress/components`
- `@edge22/block-styles`, `@edge22/components`, `@edge22/styles-builder`
- `@tanstack/react-query`, `colord`, `react-select`, `uuid`

## Block Structure (V2)

```html
<!-- wp:generateblocks/element {"uniqueId":"abc123","tagName":"div","styles":{...},"css":"...","className":"gb-element"} -->
<div class="gb-element-abc123 gb-element">
    <!-- Inner blocks -->
</div>
<!-- /wp:generateblocks/element -->
```

**Attributes:**
- `uniqueId` - Required for CSS targeting
- `tagName` - HTML element type
- `styles` - Editable structured CSS, including one-level selectors and supported at-rules
- `css` - Compiled local frontend cache; keep it semantically aligned with `styles`
- `globalClasses` - Array of global CSS classes
- `htmlAttributes` - Plain object of HTML attrs: `{"href":"url","target":"_blank"}`. NOT array format
- **Links**: element `<a>` wrapping a text `span` child. Text `<a>` strips its `href` on save; element `<a>` with raw text (no inner blocks) causes recovery. Inline links go inside a text block's rich-text content
- **Dynamic tags**: `{{tag option:value|option2:value}}` — space after tag name, pipes between options, no quotes. Real tags: `{{post_permalink}}`, `{{featured_image size:large}}`, `{{post_meta key:field}}`, `{{term_list tax:category}}`. NOT `{{post_url}}`/`{{featured_image_url}}`/`{{acf}}` — those don't exist. See `skills/generateblocks-layouts/references/dynamic-tags.md`

## CSS Approaches

**Local V2 blocks:** `styles` is the editable structured source and `css` is
the compiled frontend cache. Keep base declarations, transitions, one-level
selectors, and `@media`/`@supports`/`@container` branches in `styles`, then
compile the same structure into `css`.

**Pro CSS Mode:** a code editor for the same `styles` data. It does not create
a `cssMode` attribute. See `skills/generateblocks-layouts/references/css-mode.md`.

**Delivery:** GenerateBlocks collects saved block CSS and delivers it inline or
through generated files according to site settings and runtime fallbacks.

## Unique ID Convention

Format: `{section}-{post_id}-{sequence}{letter}`
- Section: short lowercase component name
- Post ID: real numeric WordPress record ID
- Sequence: starts at 1 and is never zero-padded
- Letter: optional lowercase nesting suffix

Examples: `hero-1173976-1a`, `service-15975-23`, `card-42869-14b`
