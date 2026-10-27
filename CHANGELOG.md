# Changelog

Notable changes to GenerateBlocks Skills. Entries describe this repository, not
releases of the GenerateBlocks plugins themselves.

## [Unreleased]

Findings from rebuilding gauravtiwari.org's Black Friday deals page in native
GenerateBlocks with Pro conditions (GB 2.5.0 / Pro 2.8.0, WordPress 7.1.3).

### Fixed

- `conditions.md`: stored condition types use underscores (`post_meta`,
  `date_time`); hyphenated types always evaluated false. `gbBlockCondition` is a
  string; integer IDs were silently dropped by the editor. Added the stored rule
  shape, `meta_key|value` custom rules, and the loop-context pattern for
  conditioning a card on another record.
- `gb_serialize.ordered()` dropped Pro's injected `gbBlockCondition` and
  `gbBlockConditionInvert`; it now keeps them in native order (before
  `className`) and stringifies the ID. Preflight accepts that order and fails
  integer condition IDs.

### Documentation

- Multi-layer backgrounds: native recompiling drops per-layer size and position,
  so use the `background` shorthand.
- Element `span` and text `dt`/`dd` fail validation (symptoms documented).
- OR'd `meta_query` clauses can make a Query Loop take minutes; time them first.
- gauravtiwari.org template traps: the PBB canvas prints no GB CSS, and the
  `.format` typography margins need a root reset.
- Site Agent: what happens when Application Passwords are disabled, and the
  SSH + WP-CLI fallback for writes.

## [2026.10.27] - 2026-10-27

GenerateBlocks 2.5.0 and Pro 2.8.0 stable, verified live through Site Agent.

### Fixed

- `build_css()` now reproduces the native 2.5 Styles Builder byte for byte
  (1,453 generated style objects checked in the live editor, 800 of them from an
  untuned seed). Before, it diverged in four ways:
  - custom properties were lowercased (`--myAccent` became `--my-accent`, broken CSS);
  - `@media` blocks kept authored order instead of merging and sorting mobile-first
    after `@supports`/`@container`;
  - selector branches kept authored order instead of key order;
  - bare `:hover`, `.child` and `[attr]` keys were attached instead of compiled as
    descendants.
  It also now matches native declaration order (shorthand-aware, vendor-prefix
  aware, custom properties kept in place), border/border-radius longhand merging,
  `url('…')` quoting, selector minification and comma-list expansion.
- Recompiled five example blocks whose stored `css` used the pre-2.5 rule order.

### Changed

- Reference source updated to GenerateBlocks 2.5.0 (wordpress.org checksums
  verified); local Pro reference is 2.8.0. Version notes across the skills now
  say 2.5.0 / Pro 2.8.0 instead of the beta pair.
- Documented 2.5 behavior: inline-only local CSS with the removed filters, the
  `generateblocks_default_at_rules` preset filter, the 50-post editor Query
  Loop preview cap, the toolbar Pattern Library entry, escaped-JSON save-gate
  detection, and the fixed compiled at-rule order.
- `mcp-publishing.md` adds Site Agent as a server option with a tested
  read-splice-write-verify workflow, version gating from `site-context`, and the
  saving-account trust rule for dynamic tags.

### Validation

- Native regression fixture `scripts/fixtures/native_css_2_5_0.json` (93 cases)
  plus three new compiler tests; 25 tooling tests pass.
- A 59-block draft of every example saved byte-identical through Site Agent,
  reopened with 0 invalid blocks and no dirty state, and rendered through the
  `generateblocks-inline-css` tag with no per-page CSS file. The draft and
  temporary sessions were removed afterwards.

### Fixed (since 2026.09.20)

- Wrapped the migration guide's `wp_update_post()` and `update_post_meta()`
  calls in `wp_slash()`. Both functions unslash their input, so the documented
  migration, backup and rollback stripped the backslashes from escaped comment
  JSON and broke migrated blocks. The migration now keeps the first original as
  the rollback copy.
- Added the PHP unslash hazard to the MCP publishing guide's transport hazards.

## [2026.09.20] - 2026-09-20

### Fixed

- Corrected the native Text/Headline icon example: preserve separate icon and
  label wrappers, omit the outer plain `gb-text` class, and let HTML-sourced
  fields serialize from the body.
- Clarified inline, synced and registered pattern handling without inferring
  block versions from editor labels or changing shared sources implicitly.
- Added native before/after/icon-only fixtures and preflight regression checks
  for missing label wrappers, conflicting outer classes and incorrect icon order.

### Documentation and validation

- Published the wiki handbook and clarified installation guidance, including the
  released installer's legacy Codex output path.
- 22 tooling tests, seven native icon variants and an embedded-pattern round trip
  passed. The reported customer pattern has not yet been supplied for verification.

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

[2026.09.20]: https://github.com/wpgaurav/generateblocks-skills/compare/v2026.09.18...v2026.09.20
