---
title: Design systems in free 2.5 and Pro 2.8 beta
description: Verified tokens, managed root saves, expanded selectors, native imports, CSS delivery, and a reusable two-brand kit.
---

# Design systems: free 2.5 / Pro 2.8 beta

Use this guide for Design Tokens, the Design workspace, Style Book, multi-selector
Global Styles, or portable design-system kits. These features require Pro 2.8.
Keep the older-version guidance when the destination still uses Pro 2.7.

**Opt-in workflow:** read `styling-scope.md`. Prompt before adding shared styles
or tokens unless the user already requested them. The capabilities and example
below do not make shared styling the default; ordinary builds use local styles.

## Verification boundary

Tested locally on 2026-09-18 with WordPress **7.1.1**, PHP **8.4**, GenerateBlocks
**2.5.0-beta.1**, and Pro **2.8.0-beta.1**. Both test sites used the minimal canvas
theme in `../examples/beta-design-system/`. This is not a production upgrade or
a compatibility claim for every theme, plugin, role, or beta.

The local reference folder `generateblocks-pro/` reports **2.7.0-rc.1**, although
older repo prose called it 2.7.1. The earlier live 2.7.1 observation is a separate
historical record. Never infer a package version from its folder or stable tag.

Bundled schema comparison: 9 free schemas unchanged; 27 Pro schemas unchanged
except `form-render.editorStyle`. Editor and PHP changes still require runtime
testing even when attribute schemas match.

## After opt-in: build the requested shared system

For a user-requested shared system, inspect existing theme variables first, then choose
the smallest useful system:

1. Shared values: semantic color, type, spacing, width, and detail tokens.
2. Base styles: body, headings, links, lists, tables, media, and form controls.
3. Components: genuinely repeated contracts such as buttons, rails, and fields.
4. Patterns: reusable compositions with replaceable content.
5. Pages: composition and justified local exceptions.

Tokens do not replace design judgment. Keep site-specific typography and content
hierarchy; avoid creating a class for every numeric value. Existing pages do not
automatically adopt new tokens. Map their current owners before migrating.

## Tokens and the managed root

A token is a CSS custom property plus optional editor metadata. The metadata lives
in `gb_style_tokens` on the one managed `gblocks_styles` post whose selector is
`:root`. Values live in that post's `gb_style_data`; compiled output lives in
`gb_style_css`. The database token metadata is a versioned envelope; the REST
field exposes a **row array**, not that envelope.

| Token field | Meaning |
|---|---|
| `name` | CSS name such as `--kit-accent` |
| `type` | `color`, `unit`, or `text` |
| `label` | Readable picker label |
| `scope` | Camel-case CSS properties where the picker should offer it |
| `category` | Organizational grouping |
| `display` | Optional quick-edit control/preset metadata; use current UI schema |
| `id` | Site-local identity; omit from portable file artifacts |

An empty scope does not disable the CSS variable; it hides the token from pickers.
Responsive values belong in root `@media`/other supported branches, not duplicated
metadata rows. Referencing another token with `var()` allows semantic aliases.
Color-mix/OKLCH-derived colors retain their relationship to the source token.

`--gb-container-width`, with type `unit` and a usable value, takes over the free
plugin's container-width control. The old saved setting is retained and returns
when that token no longer owns the width. This does not override an unrelated
theme's container rules automatically.

Token at-rules can express OS color-scheme preferences. They do not implement a
manual theme toggle, persistence, or an entire dark palette automatically.

## Guarded root writes

Prefer the UI or current plugin importer. For an authorized API workflow:

1. GET `/generateblocks-pro/v1/styles/root` to read `postId`, `styles`, `css`,
   `tokens`, and `checksum`. A read can return `status: absent` without creating
   anything.
2. If needed, POST the **same route** to create the managed root. Do not create
   a `:root` record through `/wp/v2/gblocks_styles`.
3. Compile the full candidate styles with the installed Styles Builder. In this
   beta `gbp.stylesBuilder.getCss(selector, styles)` is **asynchronous**.
4. POST `/wp/v2/gblocks_styles/{postId}` with `gb_style_data`, `gb_style_css`,
   `gb_style_tokens` (row array), and `gb_base_checksum` from the last read.
5. Read back all fields and verify frontend output. Preserve unrelated values.

The root save is an aggregate. Updating metadata piecemeal bypasses lifecycle,
consistency, and cache behavior. A stale checksum returns
`gb_style_checksum_conflict`; reload and reconcile instead of forcing a save.
The source keeps three previous root snapshots. They are not a site backup.

Observed: one update changed the second test site's colors, heading family/scale,
and section spacing; components retained their structure and responsive behavior.
The stale-checksum test was rejected without a write.

## Global Styles now target more than classes

The beta accepts approved primary identities such as a class, ID, HTML tag,
universal/attribute selector, or a standalone `:is()`/`:where()` group of approved
atoms. `:root` has its own protected lifecycle.

**Do not interpret “any selector” as arbitrary REST primary-selector syntax.**
Relationships and states belong in nested rules or the supported additional
selectors pathway. The backend canonicalizes/validates the identity.

The “also applies to” data uses:

- `gb_style_targets`: additional selectors;
- `gb_style_targets_css`: their compiled CSS;
- `gb_style_category`: organization.

Save targets and their compiled CSS together. Additional selectors have ownership
checks and are not all assignable classes. Only actual class identities belong in
a block's `globalClasses` array. HTML tag styles apply through CSS normally.

The kit uses `.kit-button` plus `.kit-form button[type="submit"]`, so a GB link
and the form button share one treatment. Verify actual third-party markup before
adding a plugin selector. Keep cascade/specificity and load order deliberate.

## Style Book and Design workspace

The Design dashboard and editor Design panel expose tokens and Global Styles.
The Style Book previews common HTML elements; Site Preview shows an actual page.
Edits are sitewide. Inspect at least one real page after a base style changes:
the specimen sheet cannot reveal every theme override or plugin state.

Design management follows the Global Styles/token capabilities. Quick-edit token
controls do not automatically give ordinary content editors design permission.
Editor Access remains a separate system introduced before this beta.

## Portable files and imports

The full portable file used by this beta has this outer shape:

```json
{
  "kind": "generateblocks/design-system",
  "version": 1,
  "globalStyles": [
    {
      "kind": "generateblocks/global-style",
      "version": 1,
      "selector": ":root",
      "isPartial": false,
      "styles": {"--kit-accent": "#943f28"}
    }
  ],
  "designTokens": [
    {"name": "--kit-accent", "type": "color", "label": "Accent", "scope": ["color", "backgroundColor"], "category": "Color"}
  ]
}
```

Ordinary style entries additionally carry their selector, structured styles,
optional category and targets. Use the dashboard export or the validated example
files instead of treating internal post meta as a portable file format.

In this exact beta the shipped integration is
`window.generateBlocksProDesignSystem.importMissing(artifact)`, provided by
`dist/design-system-import.js`. It is a **version-specific integration**, not a
promised permanent API. The example loads the installed script in the real
editor, where WordPress and the Pro style compiler are already available.

Verified import behavior:

- Clean destination: 19 tokens and 23 ordinary Global Styles imported.
- After destination rebranding: re-importing the source created no tokens/styles,
  reported 23 existing styles, and preserved the destination accent.
- Missing-only import is not an update deployment mechanism. A deliberate
  overwrite needs a separate plan and verification.

Pattern dependency source walks registered token references recursively, including
references in responsive values and preset options. It exports a partial root
fragment. Unregistered external stylesheet variables do not become portable
tokens automatically. The tests here validate full design-system file import;
they do not establish a live GenerateCloud provider/consumer result.

Page/form/query IDs and attachment URLs are not portable tokens. The example
builder resolves new record IDs and regenerates block IDs on the destination.
Its raw HTML exports are **observed fixtures**, not drop-in cross-site imports.

## CSS Mode and delivery

The tested `cssToStyles(root.css)` → awaited `getCss(':root', styles)` round trip
preserved all 19 tokens and the mobile branch exactly. This exercises the beta's
CSS Mode parser/compiler, not every visual control or arbitrary CSS grammar.

Free 2.5 uses **inline-only page-local CSS**. Its old generated-file API is a
compatibility shim, not an alternative delivery setting. Pro Global Styles still
use their separate file/inline delivery path. A local probe verified inline
block CSS with no per-page file, while the shared global stylesheet loaded.

## Serialization findings

- WordPress 7.1.1's `serialize_block_attributes()` uses **six substitutions**:
  literal backslash, double dash, angle brackets, ampersand, and escaped quotes.
  The helper now preserves literal CSS escapes as `\u005c`. Check the installed
  core on older WordPress rather than assuming a version boundary.
- Omit unused `htmlAttributes` instead of explicitly emitting `{}`. The actual
  server save converted the empty object to `[]`; omitting it restored byte parity.
- Use the real editor serializer for form/accordion save output and context.
  Both generated pages reloaded and saved with 65 valid blocks and unchanged
  `content.raw`.

The serializer source and HTML validation are related but distinct. JSON key
order/escape formatting are useful byte-roundtrip conventions; a formatting
difference alone is not proof that Gutenberg will report a block recovery error.

## Usage and cleanup

The token usage endpoint is
`/generateblocks-pro/design-tokens/v1/usages?name=--kit-accent&refresh=true`.
The scan covers GB styles, eligible post content, token preset values, and other
root properties. It can be partial: default post scan 5,000 recent eligible
posts and result-candidate cap 50. It does not establish absence from external
theme/plugin CSS. Renaming/deleting does not rewrite all references.

## Reusable example

Read `../examples/beta-design-system/README.md`. The example includes Paper and
Ink presets, a shared page builder, six section exports, a real Pro form, native
query/accordion blocks, a minimal theme, and a localhost mail sink.

Validation covered two real editor saves, byte readback, full-file imports and
collisions, parser/compiler parity, stale checksum rejection, 14 responsive
measurements (360 through 1440px), keyboard accordion activation, required-field
validation, and successful local submissions without external mail delivery.
This is not a complete accessibility audit or a test of every Pro component.

Official references: [release](https://generatepress.com/generateblocks-pro-2-8-0/),
[Design Tokens](https://learn.generatepress.com/blocks/block-guide/getting-started-generateblocks/generateblocks-pro/design-tokens/),
[Style Book](https://learn.generatepress.com/blocks/block-guide/getting-started-generateblocks/generateblocks-pro/the-style-book/).
