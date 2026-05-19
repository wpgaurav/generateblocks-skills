# GenerateBlocks Skills

LLM-optimized skill documentation and development resources for [GenerateBlocks](https://generateblocks.com/) WordPress plugin.

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

## Copy-Paste Examples

Browse the [`examples/`](examples/) folder for **38 ready-to-use templates** across 14 section types:

| Section | Description |
|---------|-------------|
| [Hero Section](examples/01-hero/) | Stats bar, dual CTAs (5 variations) |
| [Pricing Table](examples/02-pricing/) | 3-tier with "Popular" badge |
| [Card Grid](examples/03-card-grid/) | Blog posts, portfolio |
| [Feature List](examples/04-feature-list/) | 6 features with icons |
| [FAQ Section](examples/05-faq/) | Numbered Q&A, two columns |
| [Testimonials](examples/06-testimonials/) | Quotes, avatars, stars |
| [Sticky CTA](examples/07-sticky-cta/) | Dark banner, dual buttons |
| [Post Grid](examples/08-post-grid/) | Featured + small posts |
| [Stats Section](examples/09-stats/) | 4 metrics on dark bg |
| [Services Grid](examples/10-services/) | Bento layout |
| [Logo Carousel](examples/11-logo-carousel/) | Client/partner logos |
| [Team Grid](examples/12-team-grid/) | Team member cards |
| [Timeline](examples/13-timeline/) | Vertical timeline |
| [Comparison Table](examples/14-comparison-table/) | Feature comparison |

Each folder contains multiple variations plus the prompt that generated them.

**Bonus:** Check out [`examples/from-gauravtiwari-org/`](examples/from-gauravtiwari-org/) for real-world sections from production sites.

### Using Examples

1. Open any [`examples/`](examples/) folder
2. Copy any `output-*.html` file content
3. In WordPress, open your page/post
4. Switch to Code Editor (three dots menu > Code Editor)
5. Paste the blocks
6. Switch back to Visual Editor

---

## What's Included

```
generateblocks-skills/
├── install.sh                 # Multi-tool skill installer
├── CLAUDE.md                  # Claude Code project instructions
├── AGENTS.md                  # Universal LLM instructions
├── skills/                    # Skill source files
│   ├── generateblocks-layouts/
│   │   ├── SKILL.md           # Main entry point
│   │   ├── references/        # Block types, CSS, responsive, queries, etc.
│   │   └── examples/          # Basic, compound, layout, SVG examples
│   ├── html-to-generateblocks/
│   ├── elementor-to-generateblocks/
│   └── figma-to-generateblocks/
├── importable/                # .skill and .zip files for upload
├── examples/                  # 38 golden examples across 14 sections
└── generateblocks/            # Plugin source (V2.2.0) for reference
```

### Skills

| Skill | Purpose |
|-------|---------|
| **GenerateBlocks Layouts** | Build layouts using GB V2 blocks (element, text, media, shape) |
| **HTML to GenerateBlocks** | Convert HTML/CSS to GenerateBlocks block markup |
| **Elementor to GenerateBlocks** | Migrate Elementor layouts to clean GB blocks |
| **Figma to GenerateBlocks** | Convert Figma designs to GB blocks |

### Importable Formats

The `importable/` folder contains two formats for each skill:
- **`.skill`** — Upload to any AI chat (Claude.ai, ChatGPT, Gemini)
- **`.zip`** — Compressed skill with references included

---

## GenerateBlocks V2 Quick Reference

Four block types:

```
generateblocks/element  → Containers (div, section, nav, etc.)
generateblocks/text     → Text (h1-h6, p, span, a, button)
generateblocks/media    → Images
generateblocks/shape    → SVG icons
```

**V2 Naming Rules:**
- Use `generateblocks/element` (NOT `/container`)
- Use `generateblocks/text` (NOT `/headline` or `/button`)
- Classes MUST be: `gb-element-{id} gb-element` and `gb-text gb-text-{id}`
- `htmlAttributes` must be a plain object (`{"href":"/about"}`) — never an array
- Element `<a>` with text-only content causes recovery errors — use `generateblocks/text` with `tagName: "a"` for simple text links; only use `generateblocks/element` with `tagName: "a"` when wrapping inner blocks

Block format:

```html
<!-- wp:generateblocks/element {"uniqueId":"hero001","className":"gb-element-hero001 gb-element","tagName":"section","styles":{...},"css":"..."} -->
<section class="gb-element-hero001 gb-element">
    <!-- content -->
</section>
<!-- /wp:generateblocks/element -->

<!-- wp:generateblocks/text {"uniqueId":"hero002","tagName":"h1","styles":{...},"css":"..."} -->
<h1 class="gb-text gb-text-hero002">Heading</h1>
<!-- /wp:generateblocks/text -->
```

Key attributes:
- `uniqueId` — Required for CSS targeting (format: `hero001`, `card023`)
- `tagName` — HTML element type
- `styles` — CSS properties as JSON (camelCase)
- `css` — Base CSS string (minified, alphabetically sorted). Pseudo-elements, media queries in css. NO hover/transitions
- `htmlAttributes` — Additional HTML attributes (href, target, aria-*)

---

## Example Prompts

> "Create a hero section with headline, subheadline, two CTA buttons, and a 4-stat bar"

> "Build a 3-column pricing table with a highlighted middle tier"

> "Make a testimonial grid with avatars, star ratings, and quote marks"

> "Convert this HTML to GenerateBlocks: [paste HTML]"

---

## Limitations

- **No external URLs** — Provide HTML, screenshots, or descriptions
- **Static output** — CSS-only, no JavaScript interactions
- **Placeholder images** — Replace with real images after generation
- **Hover inference** — Interactive states inferred from static designs

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

## Push generated blocks into a live WordPress install

The skills generate block markup as a string. If you want your AI
assistant to also push that markup into a real WordPress site (read
the current page, find a container by id or text, swap children, write
back), pair this repo with an MCP server that exposes a write surface
for WordPress.

[Respira for WordPress](https://github.com/respira-press/respira-wordpress)
is one such MCP server. Its native Gutenberg adapter round-trips
`generateblocks/element`, `generateblocks/text`, `generateblocks/media`,
and `generateblocks/shape` byte-faithfully, and supports
snapshot-before-write with one-click rollback for safe iteration.

Typical pairing:

```
Read skills/generateblocks-layouts/SKILL.md, then generate a
testimonial grid with 3 cards and use the Respira MCP to insert
it as a new section at the top of /about on my staging site.
```

Any Gutenberg-aware MCP server works the same way. The skills here
are server-agnostic.

---

## Other LLMs

For non-Claude assistants (GPT, Gemini, etc.), see **`AGENTS.md`** for universal instructions — or just run `./install.sh` to set up your tool automatically.

## License

- Plugin source code: GPL-2.0-or-later
- Skill documentation: MIT
