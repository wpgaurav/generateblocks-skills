# GenerateBlocks Skills

LLM-optimized skill documentation and development resources for the [GenerateBlocks](https://generateblocks.com/) WordPress plugin.

**Source-verified against GenerateBlocks free 2.4.1 and GB Pro 2.7.1** (August 2026), including a read-only check of the same active versions on WordPress 7.1 at gauravtiwari.org. The styling guidance covers Pro CSS Mode, structured selectors, native/custom at-rules, CSS delivery, and a GenerateBlocks-specific anti-slop gate.

## What the skills can build

- **Static sections** — heroes, pricing, cards, FAQs, CTAs, full landing pages
- **Dynamic content** — query loops (blog grids, related posts, archives), 27 dynamic tags with exact syntax
- **Custom fields** — ACF text/image/link/group fields, repeater loops, options pages, meta queries
- **Animations** — hover micro-interactions, keyframe entrances, CSS scroll-driven reveals, reduced-motion guards
- **Conditions** — GB Pro block/menu conditions, form-field conditions, free-plugin alternatives
- **GB Pro blocks** — accordion, tabs, carousel, navigation, site header, overlays/mega menus, the 2.6 Forms system
- **Full-site templates** — GeneratePress Elements (loop templates, page heroes, hooks, display rules), FSE block themes

## Quick Install

Install skills for your AI coding assistant with one command:

```bash
git clone https://github.com/wpgaurav/generateblocks-skills.git
cd generateblocks-skills
chmod +x install.sh
./install.sh
```

This launches an interactive installer that sets up skills for your preferred tools.

### Supported Tools

| Tool | Install Location | Format |
|------|-----------------|--------|
| **Claude Code** | `~/.claude/skills/` | Skill directories |
| **Cursor** | `~/.cursor/skills/` | Skill directories |
| **Windsurf** | `~/.windsurf/rules/` | Combined markdown |
| **OpenAI Codex CLI** | `~/.codex/instructions.md` | Combined markdown |
| **Gemini CLI** | `GEMINI.md` (project root) | Combined markdown |
| **GitHub Copilot** | `.github/copilot-instructions.md` | Combined markdown |
| **Cline / Roo Code** | `.clinerules` (project root) | Combined markdown |
| **Aider** | `CONVENTIONS.md` (project root) | Combined markdown |

### Install Options

```bash
./install.sh              # Interactive mode (choose tools)
./install.sh --all        # Install for all tools
./install.sh claude       # Claude Code only
./install.sh cursor codex # Multiple tools at once
./install.sh --help       # Show help
```

---

## Alternative: Manual Setup

If you prefer not to use the installer, you have two options:

### Option A: Upload a Skill File

Download a `.skill` file and upload it at the start of a new chat in Claude.ai, ChatGPT, Gemini, or any AI assistant.

| Skill | Download |
|-------|----------|
| **GenerateBlocks Layouts** | [.skill](importable/generateblocks-layouts.skill) |
| **HTML to GenerateBlocks** | [.skill](importable/html-to-generateblocks.skill) |
| **Elementor to GenerateBlocks** | [.skill](importable/elementor-to-generateblocks.skill) |
| **Figma to GenerateBlocks** | [.skill](importable/figma-to-generateblocks.skill) |

### Option B: Point Your AI to the Skill File

With Claude Code, Cursor, or Windsurf, just reference the skill directly:

```
Read skills/generateblocks-layouts/SKILL.md and create a testimonial slider with 3 cards
```

Direct links to skill files:
- [`skills/generateblocks-layouts/SKILL.md`](skills/generateblocks-layouts/SKILL.md)
- [`skills/html-to-generateblocks/SKILL.md`](skills/html-to-generateblocks/SKILL.md)
- [`skills/elementor-to-generateblocks/SKILL.md`](skills/elementor-to-generateblocks/SKILL.md)
- [`skills/figma-to-generateblocks/SKILL.md`](skills/figma-to-generateblocks/SKILL.md)

---

## Validated Examples

Use the compact examples inside
[`skills/generateblocks-layouts/examples/`](skills/generateblocks-layouts/examples/).
They cover buttons, containers, an interactive card, a restrained hero, a V2
Query/Looper grid, and SVGs. Every file passes the bundled `preflight.py`.

Before inserting one into WordPress:

1. create/resolve the destination record;
2. regenerate every ID with the real numeric post ID;
3. replace example URLs/content/media;
4. inherit the destination's design system and registered at-rules;
5. run `preflight.py <file> --post-id <ID>`.

The root [`examples/`](examples/) gallery contains older exploration and
production exports. Treat it as historical visual/structural reference, not as
recovery-safe copy-paste markup. It predates the current CSS Mode parity,
post-scoped ID, and anti-slop gates.

---

## What's Included

```
generateblocks-skills/
├── install.sh                 # Multi-tool skill installer
├── CLAUDE.md                  # Claude Code project instructions
├── AGENTS.md                  # Universal LLM instructions
├── skills/                    # Skill source files
│   ├── generateblocks-layouts/
│   │   ├── SKILL.md           # Main entry point (slim — depth in references/)
│   │   ├── references/        # Routed reference files (see map below)
│   │   └── examples/          # Basic, compound, layout, SVG examples
│   ├── html-to-generateblocks/
│   ├── elementor-to-generateblocks/
│   └── figma-to-generateblocks/
├── importable/                # .skill and .zip files for upload
├── examples/                  # Historical exploration/exports; not current copy-paste fixtures
├── generateblocks/            # Plugin source (2.4.1) for reference
└── generateblocks-pro/        # Pro plugin source (2.7.1, git-ignored) for reference
```

### Skills

| Skill | Purpose |
|-------|---------|
| **GenerateBlocks Layouts** | Build anything with GB V2: static sections, query loops, dynamic tags, ACF data, animations, conditions, Pro forms/interactive blocks, full-site templates |
| **HTML to GenerateBlocks** | Convert HTML/CSS to GenerateBlocks block markup |
| **Elementor to GenerateBlocks** | Migrate Elementor layouts to clean GB blocks |
| **Figma to GenerateBlocks** | Convert Figma designs to GB blocks |

### Reference map (generateblocks-layouts/references/)

| File | Covers |
|------|--------|
| `_index.md` | Task router — which file to load for which job |
| `recovery-rules.md` | Every known cause of "Attempt Recovery" errors + exact fixes |
| `field-notes.md` | Real-conversion lessons: escaping workflow, validation scripts |
| `block-types.md` | Element/Text/Media/Shape verified attribute schemas |
| `dynamic-tags.md` | Canonical catalog of all 27 dynamic tags + exact syntax |
| `query-block.md` | Query/Looper/Loop-Item + Pro query extensions |
| `acf-and-custom-fields.md` | ACF fields, repeater loops, options pages, Meta Box |
| `conditions.md` | Pro block/menu/form conditions + free alternatives |
| `template-authoring.md` | Full sites: GeneratePress Elements, FSE, archive templates |
| `animations.md` | Hover, keyframes, scroll-driven animation, reduced motion |
| `gb-pro.md` | Pro feature map (27 blocks, global classes, Editor Access, version timeline) |
| `pro-forms.md` | Pro Forms (2.6+): fields, validation, ESP integrations, Turnstile |
| `pro-interactive.md` | Accordion, Tabs, Carousel, Navigation, Site Header, Overlays |
| `css-mode.md` | CSS Mode, CSS Properties, selectors, supported at-rules, `styles`/`css` parity |
| `design-quality.md` | GenerateBlocks-specific anti-slop and responsive implementation gate |
| `mcp-publishing.md` | Pushing blocks to a live site over MCP or REST: server choice, round trip, transport hazards |
| `css-patterns.md` · `svg-icons.md` · `responsive.md` | Durable styling patterns |
| `global-styles.md` · `patterns.md` · `performance.md` · `migrations.md` · `troubleshooting.md` | Supporting guides |

### Importable Formats

The `importable/` folder contains two formats for each skill:
- **`.skill`** — Upload to any AI chat (Claude.ai, ChatGPT, Gemini)
- **`.zip`** — Compressed skill with references included

After editing any skill, regenerate all bundles with `./build-bundles.sh`.

---

## GenerateBlocks V2 Quick Reference

Four core blocks plus the query family:

```
generateblocks/element    → Containers (div, section, nav, figure, a, ul/ol/li, dl/dt/dd)
generateblocks/text       → Text (h1-h6, p, span, div, a, button, figcaption, li)
generateblocks/media      → Images (img only — static and dynamic)
generateblocks/shape      → SVG icons
generateblocks/query      → Dynamic lists (+ looper, loop-item, query-no-results, query-page-numbers)
```

**The rules that matter most:**

- Use `generateblocks/element` (NOT `/container`), `generateblocks/text` (NOT `/headline` or `/button`)
- `htmlAttributes` is a plain object (`{"href":"https://example.com/about/"}`) — never an array
- **Links**: element `<a>` wrapping a text `span` child. Text `<a>` strips its href on save; element `<a>` with raw text triggers recovery
- JSON attribute order follows each block's `block.json` declaration order, `className` last
- `styles` is the editable source; compile the same states/selectors/at-rules into local `css`
- Native Mobile is `@media (max-width:767px)` in 2.4.1; preserve deliberate custom queries
- Pro CSS Mode has no `cssMode` markup attribute

Block format (Option A — the plugin auto-injects the id-class):

```html
<!-- wp:generateblocks/element {"uniqueId":"hero-1173976-1","tagName":"section","styles":{...},"css":"...","className":"gb-element"} -->
<section class="gb-element-hero-1173976-1 gb-element">
    <!-- content -->
</section>
<!-- /wp:generateblocks/element -->
```

**Dynamic tags** — space after the tag name, pipe-separated options, no quotes:

```
{{post_title link:post}}
{{post_permalink}}
{{featured_image size:large}}
{{post_excerpt length:25}}
{{post_date dateFormat:M j, Y}}
{{term_list tax:category|sep:, }}
{{post_meta key:my_acf_field}}
{{post_meta key:repeater.0.subfield}}
```

`{{post_url}}`, `{{featured_image_url}}`, `{{post_terms}}`, `{{acf}}`, and any `key="quoted"` form **do not exist** — they save fine and render as literal text. The full catalog is in [`references/dynamic-tags.md`](skills/generateblocks-layouts/references/dynamic-tags.md).

---

## Example Prompts

> "Create a hero section with headline, subheadline, two CTA buttons, and a 4-stat bar"

> "Build a 3-column pricing table with a highlighted middle tier"

> "Build a related-posts section: 3 posts from the same category, current post excluded"

> "Loop my ACF repeater team_members into a 3-column team grid"

> "Build an archive loop template for GeneratePress Elements that inherits the query"

> "Add a scroll-reveal animation to this section with a reduced-motion fallback"

> "Convert this HTML to GenerateBlocks: [paste HTML]"

---

## Limitations

- **No external URLs** — Provide HTML, screenshots, or descriptions
- **No custom JavaScript** — interactions are CSS-only, plus GB Pro's built-in
  JS blocks (accordion, tabs, carousel, overlays, instant pagination)
- **Placeholder images** — Replace with real images after generation
- **Hover inference** — Interactive states inferred from static designs
- **UI-managed settings** — condition rules, form actions, overlay triggers,
  and display rules are configured in wp-admin, not in block markup; the
  skills list these as manual steps
- **Version-dependent features** — always inspect the target. Editor Access
  requires Pro 2.7+ with free 2.4+; CSS Mode and Forms require Pro 2.6+

---

## Development

```bash
cd generateblocks

npm run build          # Production build
npm run start          # Watch mode
npm run test:unit      # Jest unit tests
npm run test:e2e       # Playwright E2E
npm run wp-env:start   # Local WordPress
```

---

## Push generated blocks into a live WordPress site

The skills generate block markup as a string. To get that string into a real
record — read the current page, splice a section in, write it back — pair this
repo with an MCP server that exposes a WordPress write surface, or use plain
REST.

Read [`references/mcp-publishing.md`](skills/generateblocks-layouts/references/mcp-publishing.md)
before the first write. A write that returns `200` can still corrupt the block:
GenerateBlocks markup is validated by re-serializing it and string-comparing
against what was stored, so anything in the path that runs `parse_blocks()`,
`wpautop`, or `wp_kses` over the payload breaks it silently.

**The one requirement for any server: it must read and write raw
`post_content` as an opaque string.**

| Route | What it is | Licence / cost | Write happens |
|-------|------------|----------------|---------------|
| [**WordPress MCP Adapter**](https://github.com/WordPress/mcp-adapter) | Official bridge from the Abilities API to MCP. Successor to the archived `Automattic/wordpress-mcp`. | GPL-2.0-or-later, free | Your server |
| [**Novamira**](https://github.com/use-novamira/novamira) | Self-hosted plugin + MCP server: PHP execution, WP-CLI, filesystem, block editor workflows. Direct client-to-site connection. | AGPL-3.0-or-later, free; paid Pro tier | Your server |
| [**WPVibe**](https://wordpress.org/plugins/vibe-ai/) (SeedProd) | Free plugin plus a hosted MCP service. One-click auth from wp-admin, builder skills, approval gates. | Plugin free; hosted service has a free daily allowance | Cloud service brokers the call |
| [**Respira**](https://github.com/respira-press/respira-wordpress-mcp) | Plugin + MCP server with builder-native adapters; lists GenerateBlocks among 17 supported builders. Snapshots, approval gates, rollback. | Commercial, trial available | Your server |
| **Plain REST** | `GET/POST /wp-json/wp/v2/pages/{id}` with an Application Password. No plugin, no abstraction to audit. | Built in | Your server |

Whichever route you pick, verify the read-back rather than trusting the status
code:

```bash
python3 skills/generateblocks-layouts/scripts/verify_roundtrip.py \
  --local hero-section.html --remote fetched-raw.html
```

It names the specific corruption — reversed `\u002d\u002d` escapes, attribute
key-order drift from a `parse_blocks()` round trip, `wpautop` artifacts,
`wp_kses`-stripped SVG — instead of leaving you to diff by hand.

Typical pairing:

```
Read skills/generateblocks-layouts/SKILL.md, then generate a testimonial
grid with 3 cards and insert it as a new section at the top of /about on
my staging site.
```

Staging first, always. Novamira's PHP-execution surface in particular is
scoped by its own docs to dev and staging — an agent with arbitrary PHP does
not belong on a production site.

---

## Other LLMs

For non-Claude assistants (GPT, Gemini, etc.), see **`AGENTS.md`** for universal instructions — or just run `./install.sh` to set up your tool automatically.

## License

- Plugin source code: GPL-2.0-or-later
- Skill documentation: MIT
