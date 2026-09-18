---
name: generateblocks-layouts
description: Build and audit WordPress layouts with GenerateBlocks V2, including CSS Mode, responsive at-rules, dynamic data, Pro components, and recovery-safe block serialization. Use for new GB layouts, conversions, repairs, and hand-authored block markup.
metadata:
  compatibility: "Stable guidance: free 2.4.1 + Pro 2.7.1. Beta design-system workflow tested with free 2.5.0-beta.1 + Pro 2.8.0-beta.1 on WordPress 7.1.1/PHP 8.4, 2026-09-18."
---

# GenerateBlocks layout builder

Build or repair GenerateBlocks V2 layouts using the installed plugin's native
serialization. This skill supports local styling, dynamic content, Pro blocks,
and explicitly requested shared systems.

**Use GenerateBlocks and core blocks only.** Keep styling in native block
`styles`/`css` and use native Pro blocks for interactions. Do not introduce
Scripts Manager, Page Block, Custom HTML/CSS/JS, another builder, or theme/plugin
code to complete a GenerateBlocks design. An external implementation requires
an explicit user request. Simplify unsupported effects within native blocks.

## Read only what the task needs

Before generating markup, read [authoring-contract.md](references/authoring-contract.md).
It owns the scope prompt, IDs, blocks, serialization, CSS parity, responsive
basics, and validation rules. Use [_index.md](references/_index.md) to select
additional references. Do not load the entire library or recovery catalog for
a routine static layout; the compact contract is sufficient for familiar
Element/Text layouts using ordinary states and native breakpoints.

**Local styles are the default.** Prompt once about shared Global Styles and
Design Tokens unless the user already chose. Add shared records or new shared
dependencies only after explicit opt-in; preserve existing references. The
contract contains the exact prompt; `styling-scope.md` covers edge cases.

## Workflow

1. Inspect the source/target, versions, and design guidance. Use the real post ID
   when available; otherwise use a random four-digit block-ID scope.
2. Select semantic blocks and preserve content/behavior. For new designs, read
   `design-quality.md`; for dynamic data, read `dynamic-tags.md` and its task guide.
3. Generate file output with the native serializer or `scripts/gb_serialize.py`.
   Keep editable `styles` and compiled `css` aligned.
4. Run `scripts/preflight.py FILE --id-scope SCOPE`, then validate unfamiliar output
   in the real editor. Check visible content and relevant responsive/interactivity
   behavior. Static preflight alone is not proof of editor validity.
5. For live writes, first read `mcp-publishing.md`; snapshot, splice, read back,
   and verify with `scripts/verify_roundtrip.py`.

Keep summaries short: what changed, output path, required Pro features, tests,
and any unverified behavior. Source conversion does not authorize publication.

## Examples and failures

Use examples as structural starting points; replace their IDs, URLs, and content.
Choose a relevant file in `examples/basic`, `compound`, `layouts`, or `svg` only
when needed. `examples/beta-design-system` is an opt-in shared-system example,
not the default starter.

For a recovery/preflight failure, open the matching topic in `recovery-rules.md`
or `troubleshooting.md`. For an unfamiliar attribute/tag, use `block-types.md`
or installed `block.json`. Reuse scripts rather than duplicating escape tables
or embedding another block manual here.
