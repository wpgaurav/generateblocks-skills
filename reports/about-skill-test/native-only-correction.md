# Native-only correction

The first test moved the source stylesheet and JavaScript into Scripts Manager. That failed the intended GenerateBlocks authoring workflow. The corrected draft uses GenerateBlocks/Pro and core Details only.

- Draft 1178400 remains a draft; published About page 16260 was read back unchanged.
- 348 native blocks, 333 with editable local styles. Zero invalid blocks after reopening the editor.
- All added Scripts Manager header CSS and footer JavaScript removed; other fields preserved. No Global Styles or Design Tokens created.
- Two native Pro tab components replace the custom tab code. Seven native core Details disclosures retained. Click, arrow/Home/End navigation and FAQ keyboard toggling checked.
- Custom reading progress, motion toggle, process next/count controls and three keyframe animations removed. Content remains; those effects are simplified.
- 155 matched content elements had no differences in the measured style properties at 375/768/1440 light and 375/1440 dark. No horizontal overflow. This is structured style verification, not a screenshot or pixel comparison.
- Stored content is byte-identical to submitted native serialization. Preflight passed with a documented exception for existing decorative interlocking-ring geometry, not new rounded card styling.
- Four local skills reinstalled in Claude-sync and bundles rebuilt. Eleven helper tests passed. Installed files matched source.

Evidence: `output/about-effectiveness/native-only/` contains snapshots, editable recipe, native serialization, raw readback, responsive checks, interaction checks, editor validation and installation verification.

## Accordion padding follow-up

A source-matching appearance still left bordered FAQs with text against their
edges. All seven questions and answers were updated through native block styles:
24px inline padding at desktop width and 16px on mobile. Frontend checks at 375px
and 1061px confirmed the side padding, expanded-answer spacing, and no overflow.
The theme retained a 27px computed bottom padding on answers.

Only the FAQ region changed and stored content remained byte-identical to the
submitted markup. Native parsing returned zero invalid blocks. The editor canvas
was not available for visual inspection during this follow-up, so editor spacing
is unverified. The earlier full source-style comparison above predates this
intentional padding improvement.

This fix exposed an Element answer accidentally compiled with a Text selector.
Preflight now catches a known free-block prefix paired with the wrong block type
for that block's own ID, while allowing selectors for child blocks with other IDs.
The helper suite now has 15 passing tests, including the four-digit scope fallback.
