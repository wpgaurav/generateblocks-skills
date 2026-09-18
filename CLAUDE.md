# GenerateBlocks Skills

Follow `AGENTS.md` for repository policy and source-version boundaries.

- Layout authoring: `skills/generateblocks-layouts/SKILL.md`.
- Shared compact rules: `skills/generateblocks-layouts/references/authoring-contract.md`.
- Conditional task routing: `skills/generateblocks-layouts/references/_index.md`.
- Conversions: the HTML, Elementor, or Figma skill; its mapping examples are
  conditional references, not required reads for every conversion.
- Local block styling is the default; prompt before introducing shared Global
  Styles/Design Tokens and proceed only after explicit opt-in.

Do not reload the full recovery manual during routine authoring. Use its relevant
section for an actual failure or unresolved serialization question. Keep native
editor validation, visible-content checks, and raw readback requirements intact.

Build bundles with `bash build-bundles.sh`. Run focused tooling checks with
`python3 skills/generateblocks-layouts/scripts/test_tools.py`.
The plugin folders are reference packages; inspect their actual contents before
assuming npm build/test scripts are present.
