# Text icons and embedded patterns

The Text-with-icon example in `references/block-types.md` was invalid in the native GenerateBlocks editor. It put `gb-text` on the outer element, omitted the label's `span.gb-text` wrapper and incorrectly instructed authors to duplicate the HTML-sourced SVG in comment JSON.

The corrected example comes from the installed GenerateBlocks 2.5.0-beta.1 serializer on the existing local WordPress 7.1.1/Pro 2.8.0-beta.1 fixture. The same save structure is present in the bundled free 2.4.1 source. No WordPress record was changed for this check.

## Results

- Original documentation example: native `isValid` was false.
- Seven native variants passed parsing and byte-identical reserialization: before, after, icon-only, unstyled, empty label, formatted label and no icon. Expected label text and icon counts were preserved.
- A containing Element with the original heading and an independently edited native copy validated and reserialized exactly. Both labels were retained and the editor remained clean.
- 22 focused Python tests pass, including the captured native fixtures and rejection of missing label wrappers, conflicting outer classes, reversed icon order, label content in icon-only blocks and JSON-only icons.
- All 16 bundled HTML examples pass preflight.

## Scope

This confirms and fixes a defect in the skill, not the exact cause of the customer's report. Their working pattern, broken output and versions have not been supplied.

The pattern guidance now distinguishes inline content from `core/block` references and registered `core/pattern` slugs. Resolve the source, preserve its actual block version and wrappers, and do not edit a synced source or detach it without the requested scope. The editor label Headline is not proof of legacy `generateblocks/headline` markup.

## Reproduction

Run `python3 skills/generateblocks-layouts/scripts/test_tools.py`. The native fixtures are in `scripts/fixtures/text-icons.json`. Run `scripts/check_text_icons_native.js` in a matching local block editor to repeat the seven-case native check; it only serializes/parses temporary in-memory blocks and returns a JSON report. It does not insert or save page content.

Detailed original/native/pattern output is retained under `output/icon-pattern-fix/` in the working checkout.
