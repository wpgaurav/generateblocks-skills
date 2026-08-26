---
title: GenerateBlocks Patterns
description: Create, register, import, and verify reusable WordPress patterns containing GenerateBlocks V2 markup and optional Pro Global Styles.
---

# GenerateBlocks Patterns

A pattern packages block structure and content for insertion. It is not a
substitute for a design system. Use Pro Global Styles for reusable style
contracts and patterns for reusable compositions.

## Choose the Reuse Model

| Need | Use |
|---|---|
| Reusable CSS contract | Pro Global Style |
| Insertable starting composition | unsynced pattern |
| One centrally updated content/structure instance | synced pattern |
| Template-level structure | template/GeneratePress Element/project template system |
| One-off section | local blocks, no pattern |

Do not save every finished section as a pattern. A useful pattern has a clear
repeat case, safe defaults, and replaceable content.

## Before Building

Read:

- `recovery-rules.md` for block serialization;
- `block-types.md` for the blocks used;
- `css-mode.md` for states/selectors/at-rules;
- `responsive.md` for the destination breakpoint contract;
- `design-quality.md` when the pattern invents a visual direction.

Inspect the destination's tokens, Global Styles, block availability, and Pro
version. A pattern that depends on a missing class or Pro block is incomplete.

## Authoring Contract

- Use V2 block names.
- Keep `styles` and local compiled `css` aligned.
- Use the destination's native or registered at-rules.
- Keep action links as element `<a>` wrappers with inner text blocks.
- Use core blocks for prose, lists, tables, and captioned images where simpler.
- Use real sample content that exposes long titles and empty-state behavior;
  do not ship invented testimonials or metrics.
- Keep image attachments replaceable and document any required media.
- Run `preflight.py` on the pattern markup before registration/import.

## Unique IDs

GenerateBlocks styles are coupled to `uniqueId`. Before inserting hand-authored
markup directly into a real record, resolve that record's numeric post ID and
generate post-scoped IDs.

For patterns inserted through the editor, verify what the installed build does
with IDs during insertion/copy. Do not assume an old export's IDs are safe on a
new site. After insertion, check for duplicates within the destination record
and confirm every compiled selector matches its block.

## Registering a PHP Pattern

Use WordPress' pattern API in a theme/plugin that owns the composition:

```php
add_action(
    'init',
    function () {
        register_block_pattern_category(
            'project-sections',
            [ 'label' => __( 'Project Sections', 'project-textdomain' ) ]
        );

        register_block_pattern(
            'project/decision-list',
            [
                'title'       => __( 'Decision List', 'project-textdomain' ),
                'description' => __( 'A divided list for comparing concrete criteria.', 'project-textdomain' ),
                'categories'  => [ 'project-sections' ],
                'content'     => file_get_contents( __DIR__ . '/patterns/decision-list.html' ),
            ]
        );
    }
);
```

Keep the pattern HTML in a dedicated source file so block comments are not
damaged by PHP quoting. Follow the target repo's loading convention and avoid
runtime file reads if its build/package process compiles pattern files another
way.

File-based patterns in block themes can instead use WordPress pattern headers.
Use the target theme's established pattern structure.

## Pro Pattern Library and Imports

GenerateBlocks Pro can import remote/local patterns and associated Global
Styles. Before importing:

1. inventory required Pro blocks and classes;
2. compare incoming Global Style names with existing contracts;
3. preserve the destination's tokens and breakpoint strategy;
4. remove generic decorative classes that do not belong globally;
5. verify form, condition, overlay, and dynamic-data dependencies separately;
6. inspect the inserted markup, not only the preview image.

A same-named Global Style can still mean something different. Treat selector
collisions as semantic conflicts.

## Synced Patterns

Use synced patterns only when central updates are desired. Do not sync a section
whose copy, media, CTA, or query must differ per page.

Before editing a synced pattern:

- enumerate consumers;
- snapshot the pattern and affected records;
- verify every consumer after the change;
- check dynamic CSS/cache invalidation;
- preserve block IDs and dependencies unless the pattern is deliberately
  rebuilt.

## Pattern Design Quality

Patterns amplify both good and bad choices. Reject a pattern that standardizes:

- card soup;
- 2px rounded outlines;
- pill-shaped primary CTAs;
- repeated decorative eyebrows;
- fake logo/testimonial/metric sections;
- generic gradient heroes;
- hover motion on noninteractive content;
- a desktop grid that merely stacks into a long mobile wall;
- wrapper trees with no layout or semantic ownership.

Prefer structural patterns such as:

- a divided definition list;
- a 2-column decision/evidence split;
- an accessible FAQ disclosure group when questions are real;
- a responsive query grid with an empty state;
- a focused CTA with one primary action;
- a content rail and media pair using project tokens.

## Responsive Verification

- Use `max-width:767px` when the pattern means native GenerateBlocks Mobile
  on 2.4.1.
- Preserve destination custom queries rather than silently changing them.
- Verify 1025, 1024, 768, 767, 375, and one awkward tablet width.
- Confirm logical DOM/focus order, long strings, missing media, and no
  horizontal overflow at 200% zoom.

## Delivery Checklist

- Pattern title and description explain the real use case.
- Required Pro features and Global Styles are explicit.
- All blocks and delimiters balance.
- IDs and selectors are unique in the inserted destination.
- `styles` and `css` round-trip through CSS Mode.
- Links, dynamic tags, media, and query parameters are valid.
- No thick rounded surfaces or unsupported proof.
- Pattern works with realistic content and non-ideal states.
- Import/registration source and installed result were both verified.
