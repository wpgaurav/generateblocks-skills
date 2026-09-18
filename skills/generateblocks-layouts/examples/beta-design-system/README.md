# Paper and Ink: a reusable GenerateBlocks product-page kit

A working, local-only example for free **2.5.0-beta.1** and Pro **2.8.0-beta.1**.
Tested on WordPress **7.1.1** / PHP **8.4**, September 18, 2026.

The same 65-block composition uses 19 tokens and 23 ordinary Global Styles.
Paper uses a warm canvas, rust accent, and serif headings. Ink uses a deep green
canvas, mint accent, and system-sans headings. Mobile spacing is shared.

## Files

| File | Use |
|---|---|
| `paper.design-system.json` | Importable Paper tokens and styles |
| `ink.design-system.json` | Importable Ink tokens and styles |
| `build-kit.js` | Builds native blocks in the actual editor; resolves destination form and block IDs |
| `paper-page.html`, `ink-page.html` | Observed editor exports from the two test sites |
| `paper-form.html` | Observed native Pro form serialization |
| `patterns/` | Hero, comparison, query, FAQ, signup, and footer exports |
| `theme/` | Minimal classic canvas; visual design belongs to GB |
| `setup-local.php` | Creates fixture records, enables forms, installs the local mail sink |
| `local-mail-sink.php` | Captures mail attempts on localhost; sends no email |

The raw HTML is evidence with source-site IDs and URLs. Do not paste it into a
different site without remapping IDs, form references, query records, and URLs.
The builder performs that mapping using the destination's actual records.

## Opt-in example

This kit intentionally adds shared Global Styles and Design Tokens. Prompt the
user first and run it only after they explicitly opt in to both. It is not the
default starter for local-styled layouts. `build()` refuses to run without
`sharedSystemRequested: true`, including when `importStyles: false`, because the
page still introduces shared dependencies.

## Reproduce on a new disposable site

1. Install and activate the supplied free/Pro betas under normal plugin slugs
   `generateblocks` and `generateblocks-pro`. Vendor plugins are not bundled here.
2. Copy `theme/` to `wp-content/themes/gb-beta-kit-canvas/`.
3. Run `wp eval-file /absolute/path/to/setup-local.php --use-include --path=/absolute/path/to/site`.
   The script accepts localhost only, refuses duplicate setup, installs the mail
   sink, and returns the real `pageId` and `postIds`. It enables forms for the
   next request. Keep the returned IDs.
4. Open that page in its authenticated block editor. Load `build-kit.js` into the
   editor's top-level JavaScript context using the local development tools.
5. Run `await gbBetaKit.build({pageId: YOUR_ID, postIds: YOUR_POST_IDS, preset: 'paper', sharedSystemRequested: true})`.
   This imports the system, creates a local form, serializes native blocks,
   publishes the **local** page, and verifies `content.raw` against the payload.
6. Capture the returned object for its `content`, `formContent`, `designSystem`,
   `patterns`, and new `formId`. Pass that `formId` on a retry to reuse the form.
7. Reload the editor, verify no invalid blocks, save, then inspect the frontend.

The builder uses the installed beta's asynchronous CSS compiler and native
`generateBlocksProDesignSystem.importMissing()` integration. It loads the local
shipped integration script if needed; it does not call a remote service.

For Ink on a **clean site**, use `preset: 'ink'`. To test actual portability,
import the exported Paper file on the destination first, then change its root
tokens through the UI or the checksummed API in `../../references/design-systems-beta.md`.
Build the destination with `importStyles: false` after that deliberate rebrand.

**Missing-only import preserves existing names.** Importing the Ink file over
an established Paper system is not a rebrand operation. Our re-import test
preserved the destination's mint accent and reported 23 existing styles.

## What the demo proves

- Native free blocks plus Pro accordion and form render correctly together.
- Queries use destination records and resolve dynamic titles/excerpts.
- A single style targets both a GB link and the form's submit button.
- The token parser/compiler preserves the responsive root branch.
- Both actual editor reload/save cycles retained byte-identical page content.
- The destination imports the shared system and can use different brand values.
- Seven widths per site (360, 390, 767, 768, 1024, 1025, 1440) showed no page overflow.
- Keyboard accordion activation, required-email validation, and successful local
  submissions worked. The mail sink recorded the simulated actions; no email left.

The comparisons describe the kit's actual construction. There are no invented
customers, testimonials, sales figures, prices, or product screenshots.

## Limits

This is a local example, not a production deployment or a complete accessibility
audit. No GenerateCloud account/provider flow, older WordPress matrix, third-party
checkout, or all-role Editor Access matrix was tested. The minimal theme avoids
unrelated theme overrides; repeat the relevant checks with the destination theme.

The mail sink is test infrastructure. Configure the real form stack deliberately
before using the composition in production; do not ship the local setup/sink as
part of a public theme. The page's demo disclosure must also be rewritten for a
real product.
