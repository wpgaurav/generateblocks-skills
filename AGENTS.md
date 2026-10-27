# GenerateBlocks Skills

Repository instructions for all assistants. The maintained knowledge lives in
`skills/generateblocks-layouts/`; converters delegate to it.

## Loading and authoring

Read the selected skill's `SKILL.md`, then the layout skill's
`references/authoring-contract.md` before generating markup. Use
`references/_index.md` to select additional guides. The compact contract replaces
the old requirement to read the entire recovery catalog on every task.
`recovery-rules.md` remains the detailed diagnostic reference for a matching
failure or uncertainty.

Local block styles are the default. Prompt once about shared Global Styles and
Design Tokens unless the user already chose; introduce shared records or new
shared dependencies only after explicit opt-in. Preserve existing references.
See `references/styling-scope.md` for edge cases.

Build with GenerateBlocks/Pro and appropriate core blocks only. Keep CSS in the
owning blocks and use native interactions. External stylesheets, Scripts Manager,
Custom HTML/JavaScript, or another builder need an explicit user request.

Use a real post ID when available, otherwise a random four-digit block-ID scope.
This fallback never identifies a WordPress record for an API write.

The contract retains native V2 blocks, collision-checked IDs, editable styles/CSS
parity, canonical serialization, file output, preflight, and native editor checks.
Read `references/mcp-publishing.md` before an authorized live write: snapshot
`content.raw`, splice, and verify raw readback. For a new/materially changed
visual direction, apply `references/design-quality.md` and target brand guidance.

## Reference versions

- `generateblocks/`: free 2.5.0 (wordpress.org, checksums verified).
- `generateblocks-pro/`: Pro 2.8.0 (git-ignored).
- Live verification: free 2.5.0 / Pro 2.8.0 on gauravtiwari.org, WordPress
  7.1.3 / PHP 8.5, through Site Agent on 2026-10-27. The native-compiler
  regression set is `scripts/fixtures/native_css_2_5_0.json`.
- History: design-system workflow first tested on 2.5.0-beta.1 / 2.8.0-beta.1
  (WordPress 7.1.1, 2026-09-18); earlier live observation free 2.4.1 / Pro 2.7.1.

Inspect target versions before relying on these snapshots. Current official
reference: learn.generatepress.com. Vendor Pro sources remain local.

## Packaging

`skills/` contains layouts, HTML conversion, Elementor conversion, and Figma
conversion. Keep converter-specific examples in each converter's references;
do not duplicate the common serialization manual. `bash build-bundles.sh`
builds `.zip` and identical `.skill` files. Standalone converter bundles include
a generated copy of their layout dependency; edit its canonical source only.

Run serializer/preflight tests and skill validation after relevant changes, and
verify bundles and installed copies against their source. Do not modify unrelated
working-tree changes or publish a release without a release request.
