# Changelog

Notable changes to GenerateBlocks Skills. Entries describe this repository, not
releases of the GenerateBlocks plugins themselves.

## [2026.09.18] - 2026-09-18

Changes since `v2026.09.12`.

### Added

- Guidance tested with GenerateBlocks 2.5.0-beta.1 and Pro 2.8.0-beta.1, covering
  Design Tokens, Style Book, expanded selectors and design-system imports.
- An optional Paper and Ink example kit with explicit opt-in for shared styles
  and tokens, plus local testing fixtures.
- A shared authoring contract and focused HTML, Elementor and Figma conversion
  references. Standalone converter bundles include the layout dependency.
- Random four-digit layout scopes when no WordPress post ID exists. Known post
  IDs remain preferred; fallback scopes never identify REST write targets.
- A preflight check for CSS compiled with the wrong free-block type prefix.
- README project-support links.

### Changed

- GenerateBlocks/Pro and appropriate core blocks are the default authoring
  boundary. External code and other builders require an explicit request.
- Local block styling is the default. Assistants prompt before introducing
  shared Global Styles or Design Tokens and require explicit opt-in.
- Routine supplied-design instructions decreased from 11,446 to 2,019 words
  (82.4%). This measures instruction text, not total token use, cost or latency.
- Detailed references load by task or failure instead of being mandatory for
  every layout. Native validation and stored-content checks remain required.
- Installers include validation scripts; bundles verify their contents against
  source files and provide matching `.zip` and `.skill` archives.
- The tracked free-plugin reference source was updated to GenerateBlocks 2.4.1.

### Fixed

- Guidance that moved supported styling outside GenerateBlocks or left rules
  only in compiled CSS instead of editable block styles.
- Native selector handling, including Pro child prefixes and tested relative
  ancestor-state selectors for the Pro 2.8 beta compiler.
- Missing question and answer padding in bordered accordions/Details. Checks
  cover expanded states, mobile spacing and theme overrides.
- Empty styles/attribute objects that could become arrays during server saves,
  plus literal-backslash serialization.
- Rich-text cloning and decorative markup guidance where valid blocks could
  still lose visible content.
- Border checks that mistook color-mix percentages for border widths.

### Validation

- 15 focused tooling tests pass, including the four-digit fallback and CSS
  owner mismatch regression.
- All four installed skills and standalone bundles match their source files.
- The native About-page test covered responsive styling, dark mode, tab keyboard
  behavior and FAQ disclosure. The later padding fix passed frontend checks;
  the editor canvas could not be inspected visually during that follow-up.

[2026.09.18]: https://github.com/wpgaurav/generateblocks-skills/compare/v2026.09.12...v2026.09.18
