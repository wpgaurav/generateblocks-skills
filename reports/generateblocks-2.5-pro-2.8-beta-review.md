# GenerateBlocks 2.5 / Pro 2.8 beta: building websites with a shared design system

Reviewed September 18, 2026. This initial report covered source and documentation. The authorized follow-up is now implemented; see [runtime validation](beta-kit-validation-2026-09-18.md) for the two local sites, updated skills, and tested kit. The initial review below is preserved as the proposal.

The strongest opportunity is to define a small design system once, then compose pages from patterns that share it. The new token registry, selector support, Style Book, and portable design-system data make that workflow substantially easier to manage in GenerateBlocks.

## Versions and comparison boundaries

| Local folder | Actual plugin header | Role in this review |
|---|---|---|
| `generateblocks 2/` | `2.5.0-beta.1` | New free build |
| `generateblocks-pro 2/` | `2.8.0-beta.1` | New Pro build |
| `generateblocks/` | `2.4.1` | Local free baseline |
| `generateblocks-pro/` | `2.7.0-rc.1` | Local Pro baseline; older than the documented 2.7.1 baseline |

The space and `2` are folder names. Both beta readmes still carry the previous stable tags; the plugin headers and constants identify the supplied builds. The official release pages currently list beta.1 for both releases: [free changelog](https://generatepress.com/generateblocks-2-5-0/) and [Pro changelog](https://generatepress.com/generateblocks-pro-2-8-0/).

Parsed comparison of every bundled `block.json` found:

- Free: 9 schemas before and after, identical parsed JSON.
- Pro: 27 schemas before and after; only `form-render.editorStyle` differs.
- No new block schemas or changed attribute definitions in these comparisons. This does not establish runtime compatibility: editor JavaScript and PHP have changed.

Existing forms, carousel, navigation, overlays, query extensions, CSS Mode, and Editor Access remain useful, but they are not introduced by these betas.

## What changes the way we can build

| Addition | Practical building method | Example |
|---|---|---|
| Managed Design Tokens | Define colors, spacing, type sizes, widths, and other shared values centrally | Change a section-spacing token once across every section using it |
| Responsive token values | Put a shared breakpoint decision in the token instead of repeating it on blocks | Reduce section padding and grid gaps together on mobile |
| Expanded Global Styles | Style base HTML and supported additional selectors alongside reusable classes | Share a button treatment between GB links and a form plugin's submit button |
| “Also applies to” selectors | Give one style several consumers without copying its declarations | Let `h2` and a heading utility share typography |
| Style Book and real-page preview | Establish typography, tables, lists, forms, and media before assembling pages | Check an article's blockquote and a landing page's form against the same system |
| Categories and design-system import/export | Maintain small, organized style kits that can move between sites | Reuse layout/components while supplying each site's own brand values |
| Token-aware patterns | Carry the registered tokens a pattern depends on with its style dependencies | Import a pricing section without manually chasing every registered color/spacing variable |
| OKLCH/color-mix controls | Derive shades and transparency from a selected color token | Keep hover and tinted-surface colors connected to the brand color |

The new UI exposes ordinary CSS custom properties with readable names and appropriate controls. It also allows control-specific token choices: spacing tokens can appear where spacing is edited, instead of crowding every picker. See the [Design Tokens guide](https://learn.generatepress.com/blocks/block-guide/getting-started-generateblocks/generateblocks-pro/design-tokens/).

The Style Book includes base text, headings, lists, forms, tables, and media. It supports desktop, tablet, and mobile preview, and saved changes apply sitewide. It is useful for theming ordinary content as well as bespoke layouts. See the [Style Book guide](https://learn.generatepress.com/blocks/block-guide/getting-started-generateblocks/generateblocks-pro/the-style-book/).

## Recommended workflow for our sites

### 1. Establish ownership of shared values

First inspect the active theme's variables and existing Global Styles. Adopt or deliberately bridge the values already in use. Do not introduce a second competing color or typography system simply because the token UI is new.

Use semantic names with a clear purpose:

| Token role | Examples of values it would own |
|---|---|
| Brand and surfaces | Accent, background, raised surface, main text, muted text, borders |
| Typography | Body size, lead size, heading scale, body and heading font stacks |
| Spacing | Inline gap, component gap, section spacing, page gutter |
| Layout | Reading width, wide content width, global container width |
| Details | Control radius and focus treatment values |

These are proposed roles, not an importable payload or an inventory of existing site tokens. Start with a small set justified by actual repeated decisions.

### 2. Build a base style sheet through the Style Book

Set readable body text, a deliberate heading scale, links with focus states, lists, tables, and form controls. Then inspect those decisions on real pages, where theme selectors, long titles, embeds, and plugin markup can affect the result.

For GauravTiwari.org, an editorial system should make article typography, comparison tables, forms, and landing pages agree. For a separate Gatilab build, the component structure can be reused with a different token set. Each site's theme and design language still need their own review.

### 3. Create a small component vocabulary

Useful Global Styles include `content-rail`, `section`, `button-primary`, `button-secondary`, `article-card`, `metadata-row`, and `form-control`. Follow established project names where they exist.

Put shared states and responsive behavior on these styles. Keep local block styling for the actual exception: a particular hero composition, image crop, or unusual grid placement. Do not convert every one-off value into a utility class.

Use supported additional selectors to bring third-party markup into the same visual system after inspecting its real HTML. This could help unify Core Forms or commerce controls with the page around them; this review did not validate selectors for either product.

### 4. Build patterns that express content jobs

Prioritize a product hero, feature comparison, testimonial, FAQ, signup section, article query, and conversion footer. Give each pattern a reason to exist and let it consume the shared tokens/components.

The new pattern dependency code follows registered token references recursively, including references in responsive values and preset options. That makes a pattern with an alias token more portable. Variables defined only in an external theme stylesheet are not automatically turned into portable registered tokens.

### 5. Separate editorial data from layout

Continue using query loops, dynamic tags, and custom fields for repeatable content such as resources, case studies, course listings, and articles. These capabilities predate the betas. The improvement is that their presentation can now draw from a more coherent, editable, portable design system.

Editor Access and Control Sets can then restrict routine edits to appropriate content controls. The new token quick-edit controls do not by themselves grant editors permission to manage the design system.

### 6. Give AI a system to use

For generated layouts, the deliverable should become:

1. An inventory or proposed manifest of shared tokens.
2. Existing or proposed Global Styles with explicit ownership.
3. Reusable patterns that reference those styles.
4. Page composition with limited instance-specific CSS.

This should reduce repeated literals and duplicated responsive decisions. It will not automatically improve typography, composition, accessibility, or conversion: those still require design judgment and testing. Existing serialization and `styles`/`css` consistency requirements remain relevant.

## Changes and limits that matter

- **Local CSS delivery changed.** Free 2.5 always emits page-local block CSS inline. Its former file-mode class is now a compatibility shim; old settings and regeneration guidance must not be applied to this version. Pro Global Styles still have their own file/inline delivery path. Moving truly shared rules into Global Styles can reduce local duplication, but a speed improvement needs measurement.
- **Tokens have responsive behavior, not a complete dark-mode interface.** The documented at-rule support can express `prefers-color-scheme` overrides. A manual theme toggle and its persistence still require deliberate implementation.
- **Selector support has a grammar.** The source limits primary selectors to approved atoms and certain groups. Compound/descendant rules need the supported nested or additional-selector pathways. The marketing phrase “any selector” is not permission to submit arbitrary primary selectors to REST.
- **Usage scans are bounded.** Token usage scans inspect GB-managed styles, editor content, preset options, and other root values. The source defaults to the 5,000 most recently modified eligible posts and caps result candidates at 50. A partial scan or absent result cannot establish that a variable is unused in theme/plugin CSS.
- **Rename/delete is not a whole-site refactor.** Existing references still need a planned migration. Importing also requires decisions about same-named styles and tokens; it is not a promise that two unrelated design systems can merge safely.
- **The managed root is special.** Token metadata and values are stored together on a managed `:root` Global Style. Writes use an aggregate save with checksum conflict detection and rolling snapshots. Treating tokens as an arbitrary standalone option would bypass the intended data model.
- **This release does not supply the upcoming GeneratePress template engine.** Site template ownership remains with the installed theme, block templates, or the existing Elements/hook setup. Styling third-party output is different from controlling its templates or behavior.
- **Beta UI fixes are meaningful.** The official beta.1 notes include token draft handling, duplicate names, preview framing, and large style-data previews. They support doing the first full build in a disposable or staging environment.

## Best first experiment

Build one reusable product-page kit on a disposable installation with both supplied betas. Include a hero, comparison table, query section, FAQ, form, and footer. Use shared tokens for colors, type, spacing, and width; share components through Global Styles.

Evaluate it by changing the accent, heading scale, and section spacing once, then checking every consumer. Export and import it into a second clean site and verify token dependencies, existing-name conflicts, editor save/reload, responsive behavior, keyboard states, and frontend CSS output.

Success would mean two clearly different branded pages built from the same component structure, with consistent editor controls and very little repeated local styling. No runtime experiment or installation was performed in this review.

## Repository follow-up

The current skills are explicitly based on free 2.4.1 / Pro 2.7.1. Before making these betas the authoring baseline:

- Add a versioned token/Style Book guide, including the managed root and its save contract.
- Extend Global Styles guidance for primary selectors, additional selectors, category organization, and import/export.
- Branch performance guidance by free version: page-local file mode through 2.4 versus inline-only from 2.5, while preserving the separate Pro Global Styles path.
- Extend pattern guidance with token dependencies and collision behavior.
- Verify token CSS Mode round trips and shared-selector serialization in a real editor before updating generated examples or claiming beta compatibility.
- Resolve the old local Pro header discrepancy before describing that folder as a 2.7.1 source baseline.

This review adds only this report; it does not update plugin sources, active skills, bundles, or any site.

## Local source evidence

Paths below are relative to the repository root.

- `generateblocks 2/plugin.php`: beta header and version constant.
- `generateblocks-pro 2/plugin.php`: Pro beta header and version constant.
- `generateblocks-pro/plugin.php`: old local Pro header (`2.7.0-rc.1`).
- `generateblocks 2/readme.txt`: free 2.5 change list.
- `generateblocks-pro 2/readme.txt`: Pro 2.8 change list.
- `generateblocks-pro 2/includes/design-tokens/class-design-tokens.php`: storage model, token types, control scopes, permissions, and container-width ownership.
- `generateblocks-pro 2/includes/styles/class-styles.php`: selector validation, additional selectors, assignable classes, and CSS assembly.
- `generateblocks-pro 2/includes/styles/class-styles-post-type.php`: new metadata and coordinated REST validation.
- `generateblocks-pro 2/includes/styles/class-styles-root.php`: managed root lifecycle and save guards.
- `generateblocks-pro 2/includes/styles/class-styles-root-snapshots.php`: three previous root snapshots.
- `generateblocks-pro 2/includes/design-tokens/class-design-tokens-usage.php`: usage coverage and scan limits.
- `generateblocks-pro 2/includes/pattern-library/class-pattern-library-rest.php`: design-system artifact and recursive token dependency collection.
- `generateblocks 2/includes/class-enqueue-css.php`: deprecated local generated-file compatibility API.
- `generateblocks 2/includes/class-inline-css.php`: current local CSS output.
- `generateblocks-pro 2/includes/styles/class-styles-enqueue.php`: separate global CSS delivery.

Official overview: [GenerateBlocks Pro 2.8: Design Systems](https://generatepress.com/generateblocks-pro-2-8-design-tokens-global-styles-update/).
