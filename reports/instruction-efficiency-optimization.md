# GenerateBlocks skill loading optimization

Completed September 18, 2026. All four locally installed skills and their bundles
were updated. Local block styles remain the default; shared Global Styles and
Design Tokens still require the user's explicit opt-in after the scope prompt.

## Measured change

Counts include the later native-only, padding, and four-digit ID fallback corrections.

Exact word counts for the routed GenerateBlocks guidance:

| Workflow | Before | After | Reduction |
|---|---:|---:|---:|
| Mandatory authoring base | 6,609 | 2,019 | 69.5% |
| Familiar responsive static layout, supplied design | 11,446 | 2,019 | 82.4% |
| New visual direction | 12,422 | 3,049 | 75.5% |
| Responsive query, supplied design | 12,489 | 5,509 | 55.9% |
| Explicitly requested shared beta system, new design | 15,205 | 7,388 | 51.4% |

These figures exclude external brand/design skills, project instructions, tool
schemas, conversation, implementation code inspected during debugging, and
generated output. They are not model-token, wall-clock, billing, or general
quality measurements. Unfamiliar blocks and advanced CSS still load their guides.

Entrypoints shrank from 1,997–2,568 words each to 415–450:

| Skill | Before | After |
|---|---:|---:|
| Layouts | 1,997 | 449 |
| HTML conversion | 2,433 | 415 |
| Elementor conversion | 2,261 | 424 |
| Figma conversion | 2,568 | 450 |

## Organization

- `authoring-contract.md` owns the shared scope, post IDs, block semantics,
  serialization, local styles/CSS parity, responsive basics, and validation flow.
- `_index.md` selects references by actual task or uncertainty.
- `recovery-rules.md` retains detailed diagnosis but is no longer a routine
  full-file prerequisite.
- Converter-specific structure, widget, and design examples moved into focused
  mapping references. Duplicate block manuals and contradictory old snippets
  were replaced with links to the canonical contract.
- The focused design-quality checklist remains required where appropriate.
  Broad external design critique is conditional; a new record or a minor
  inherited-spacing choice does not automatically load a full design catalog.
- Existing validator/serializer scripts were preserved. The contract explains
  their public interfaces so normal use need not load their implementation.

The reorganization also corrected stale converter instructions that placed
states only in compiled CSS, used unsafe action-link mappings, inferred fixed
brand defaults, or promised unmeasured performance benefits.

## Independent forward test and native validation

A separate agent received the new skills, two requests, actual local draft IDs,
and the permitted source files. It did not receive the previous reports or
expected markup. It generated local-only output and recorded its reading path.

| Case | Output | Native result |
|---|---|---|
| Supplied responsive three-card HTML conversion | 19 blocks, draft 49 | Zero invalid blocks; editor save retained byte-identical content |
| Published-post query with linked titles, excerpts, empty state | 8 blocks, draft 50 | Zero invalid blocks; editor save retained byte-identical content |

Both passed preflight and source-specific assertions. Parent verification checked:

- actual native parsing, raw API readback, editor reload/save;
- exact visible copy and link destinations;
- 375, 767, 768, and 1440px layouts without horizontal overflow;
- three/two desktop columns and one mobile column as requested;
- resolved query titles/excerpts and the temporary no-results case, followed by
  restoration of the original fixture;
- a visible 2px keyboard focus outline.

The independent scope evaluation reproduced the prompt and continued local
styling while awaiting a choice. Neither real fixture introduced shared records,
`globalClasses`, or Design Token references.

The test surfaced two documentation refinements: preflight's count excludes
attribute-less blocks such as query-no-results, and the design-quality routing
was too eager to load an external design catalog for minor choices. The contract
now distinguishes native recursive block counts, and the broad design routing is
conditional. The static case needed no full block, CSS, or recovery catalog.

This is two realistic generation cases, not a statistical success-rate study or
a complete Figma/Elementor migration matrix. Detailed guides and native editor
checks remain available for cases outside the tested scope.

## Installation and packaging

Canonical files were synced to Claude-sync and verified through the installed
`~/.claude/skills` paths. Codex uses the same canonical skill catalog paths.

Standalone converter archives now include a generated layout dependency under
`references/generateblocks-layouts/`. That makes the archives larger while their
default reading path is smaller. The dependency is generated from the single
maintained layout source, not separately edited. Every archive member was checked
against its source, and each `.skill` matches its `.zip` byte-for-byte.

The installer now copies validation scripts along with references/examples.
Combined-instruction installs record their reference roots. Isolated installer
copy tests, standalone extraction/validator checks, four skill frontmatter checks,
and the existing 10 tooling tests passed.

## Evidence

- `instruction-efficiency-comparison.json`: current counts and native test results.
- `efficiency/measure_instructions.py`: reproduces counts against the preserved
  pre-optimization baseline.
- `output/compact-forward-test/`: independent generator, two HTML fixtures,
  source checks, reading report, native saves, responsive and empty-state results.
- `output/instruction-optimization-before/`: recoverable source/installed snapshots.

No production page or shared style/token record was changed by this optimization.

## About-page follow-up

The initial About conversion was rejected because it placed styling and custom
JavaScript in Scripts Manager. Matching source appearance and valid block markup
did not make it an effective native GenerateBlocks conversion. The corrected draft
uses native block styling, Pro tabs, and core Details; unsupported custom effects
were simplified. A later review also required proper question/answer padding.
See [the correction and its verification limits](about-skill-test/native-only-correction.md).
These findings reinforce the authoring boundary and checks; the smaller reading
path alone is not proof of unchanged effectiveness.
