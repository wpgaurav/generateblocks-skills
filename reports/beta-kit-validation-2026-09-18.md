# GenerateBlocks beta kit: implementation and verification

Completed September 18, 2026, using the supplied free 2.5.0-beta.1 and Pro
2.8.0-beta.1 packages on WordPress 7.1.1 / PHP 8.4.

## Working demos

| Preset | Local page | Record IDs |
|---|---|---|
| Paper | [Warm canvas, rust accent, serif headings](http://localhost:8922/?page_id=6) | Page 6, form 34, root 10 |
| Ink | [Dark green canvas, mint accent, sans headings](http://localhost:8923/?page_id=5) | Page 5, form 33, root 9 |

Sites are registered in WordPress Studio as **GenerateBlocks Beta Kit A** and
**GenerateBlocks Beta Kit B**. Their directories are
`~/Studio/generateblocks-beta-kit-a` and
`~/Studio/generateblocks-beta-kit-b`. They are local test sites.

The maintained kit lives in
`skills/generateblocks-layouts/examples/beta-design-system/` and is included in
the layout skill bundle. It contains two importable design-system JSON files,
the native editor builder, six section exports, native form/page exports, a
minimal canvas theme, and localhost-only setup/mail-capture files.

## Verified results

| Check | Result |
|---|---|
| Native page serialization | 65 blocks per page; zero invalid blocks |
| Real editor reload/save | Both saves succeeded; `content.raw` remained byte-identical |
| Clean destination import | 19 tokens and 23 ordinary Global Styles imported |
| Re-import after rebrand | No duplicated tokens/styles; 23 existing styles reported; Ink accent preserved |
| Token CSS Mode parser/compiler | Exact compiled CSS round trip, including mobile branch |
| Design dashboard | Style Book renders; 23 styles and all token categories visible; token CSS Mode opens as Applied |
| Stale root write | Rejected with `gb_style_checksum_conflict` |
| Shared-selector style | GB CTA and native form submit consume the same Global Style |
| Dynamic query | Destination-specific post records render their titles/excerpts |
| Accordion | Space/Enter keyboard activation updates `aria-expanded` and reveals content |
| Required field | Empty email fails browser validity |
| Form success | Both sites show the configured success state; each mail sink recorded one simulated action |
| External delivery | None; the localhost mail sink intercepts the configured mail action |
| Local CSS probe | CSS appears inline; no per-page CSS file; shared global stylesheet still loads |
| Code/tooling | 10 focused serializer/preflight tests; all four skill frontmatters validate |

The two-brand import used the exported Paper artifact in the clean second site.
The second site's root was then updated through the current-checksum REST path
to the Ink values. Its page/form/query records were rebuilt for its own IDs.

## Responsive and interaction measurements

Tested actual viewport widths of **360, 390, 767, 768, 1024, 1025, and 1440px**
on each site: 14 measurements, no page-level horizontal overflow. The comparison
table has its own horizontal scrolling region. Both layouts use one column
through 767px and two above it.

| Measurement | Paper | Ink |
|---|---|---|
| Desktop section padding | 80px | 72px |
| Mobile section padding | 48px | 48px |
| Heading family | Georgia/serif | system UI/sans |
| Body text contrast | 14.71:1 | 13.56:1 |
| Primary button contrast | 7.00:1 | 10.11:1 |

The measured primary CTA height was about 52px. Keyboard focus displayed a 2px
solid outline. Contrast figures cover the measured base body and primary button
colors, not every hover state or element on the site. No screenshot-based visual
approval or complete accessibility audit is claimed.

## Changes incorporated into the skills

- Added the versioned Pro 2.8 design-system guide and bundled working example.
- Added routing from all three conversion skills.
- Documented managed root storage, token scopes, checksummed writes, selector
  restrictions, additional-selector CSS, and missing-only import behavior.
- Separated free 2.5 inline-only local CSS from older free modes and Pro global CSS.
- Corrected the Pro source-folder version discrepancy and the form REST/meta names.
- Reconciled the shared canonical serializer's literal-backslash handling with
  the current WordPress core source: six substitutions on 7.1.1.
- Preserved the shared preflight fix that prevents color-mix percentages from
  being mistaken for border widths.
- Added detection for explicitly empty `htmlAttributes`, which the actual server
  converted from `{}` to `[]` during this test.
- Clarified that block HTML validation and byte-level transport checks are
  different checks; JSON key order alone does not establish a recovery error.
- Moved legacy converter frontmatter fields into supported `metadata`.

## Practical limits

These checks establish a usable beta workflow on the two local sites. They do
not establish compatibility with every theme, production caching stack, old
WordPress version, commerce plugin, or user role. The full-file design-system
import was tested; a live GenerateCloud provider/consumer flow was not.

The raw HTML exports contain source-site IDs and URLs. Use the builder with
destination IDs instead of treating those exports as portable templates.
The native import integration is specific to this beta and should be rechecked
when upstream changes. Missing-only import does not deploy updates over existing
names.

No production site or public release was changed. The supplied vendor source
folders remain local references and are excluded from the skill bundles.
