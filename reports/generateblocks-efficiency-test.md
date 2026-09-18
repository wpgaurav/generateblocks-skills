# GenerateBlocks efficiency test

September 18, 2026. WordPress 7.1.1, PHP 8.4, GenerateBlocks 2.5.0-beta.1 and
Pro 2.8.0-beta.1. Tests ran locally in WordPress Studio.

The instruction restructuring described below was subsequently completed; see
[instruction-efficiency-optimization.md](instruction-efficiency-optimization.md)
for current counts and the later About-page corrections.

**Verdict:** local block styling remains a reasonable default. On this small
page its browser cost was modest, while shared styling reduced repeated saved
data. The clearest avoidable cost is the amount of instruction text the skills
ask an assistant to read before producing a layout.

## Skill efficiency

These are exact word counts from the routed GenerateBlocks files, not model
token counts. They exclude project instructions, tool schemas, conversation,
external design skills, source inspection, and generated output. Reads are
deduplicated within each route.

| Workflow | Files | Words |
|---|---:|---:|
| Mandatory entry, router, recovery rules, and styling-scope guidance | 4 | 6,609 |
| Responsive static layout with a supplied design | 8 | 11,446 |
| Responsive static layout with a new design | 9 | 12,422 |
| Responsive query with a supplied design | 8 | 12,489 |
| Opt-in shared beta system with a new design | 11 | 15,205 |
| Entire layout entry/reference library | 29 | 39,013 |

The reference library is deliberately not a normal load target. The problem is
that even its routed paths remain large. The entrypoint, router, and recovery
guide repeat block rules, output requirements, and decision shortcuts. Recovery
rules alone contain 3,131 words and are required on every task.

All four entrypoints were also measured separately, before their references:

| Skill entrypoint | Words | Characters |
|---|---:|---:|
| Layouts | 1,997 | 14,256 |
| HTML conversion | 2,433 | 24,792 |
| Elementor conversion | 2,261 | 22,997 |
| Figma conversion | 2,568 | 23,779 |

Converters contain substantial embedded code/examples, so word counts alone
understate their size. Moving conditional examples out of entrypoints is another
candidate for optimization; it was not done during this measurement.

Helper execution is already cheap:

- Preflight: **29.6ms** median for the shared page and **30.8ms** for the local
  page, including a fresh Python process each time; seven measured runs after
  one warm-up per fixture.
- All **10** focused tooling tests passed.
- The local variant reloaded in the actual editor as **67 valid blocks**, saved
  successfully, and retained byte-identical raw content.
- Native local conversion/selector discovery/CSS compilation/serialization took
  **5.46ms** median over ten measured runs after two warm-ups. Parsing and
  serializing existing shared markup took **1.19ms**. These perform different
  amounts of work and are not measurements of AI generation latency.

No repeated LLM trial, model-token billing measurement, or model success-rate
benchmark was performed. Instruction size identifies a likely context-cost
problem; it does not by itself prove a proportional speed or quality change.

## Website efficiency

Compared the existing [shared-style page](http://localhost:8922/?page_id=6) with
a new [local-style benchmark copy](http://localhost:8922/?page_id=41) on the
same site. The copy resolves token values into local block styles and compiles
them with the installed plugin. It adds no new shared styles or tokens.

A localhost-only MU plugin omits the shared `generateblocks-global` stylesheet
on page 41 so the copy cannot accidentally borrow its styling. The rest of the
site keeps its normal assets. Page 41 uses its own local form (42), preserving
the test mail sink. Both pages retain identical visible copy for comparison,
including the demo's text about shared systems.

### Equivalence gate

Before timing, compared 47 content/control elements at 360, 767, 768, and 1440px.
Normalized visible text, ten computed CSS properties, and rounded element
widths/heights matched at all four widths. Neither page overflowed. The local
accordion also passed keyboard activation. This is a targeted computed-layout
comparison, not a screenshot approval or full accessibility audit.

The local implementation has two extra blocks: a page-level inheritance wrapper
and a wrapper supplying local styles to the core table. This is a practical
equivalent implementation, not a claim that the two DOM trees are identical.

### Three alternating anonymous browser audits per variant

| Metric | Local styling | Existing shared system |
|---|---:|---:|
| Tool-reported total page weight | 88,969 bytes | 84,244 bytes |
| Requests | 8 | 9 |
| DOM elements reported | 137 | 133 |
| Median TTFB | 57ms | 56ms |
| Median FCP | 160ms | 152ms |
| Median LCP | 160ms | 152ms |
| CLS in every run | 0 | 0 |

The local page was **4,725 bytes / 5.6% heavier** but needed one fewer request.
An 8ms LCP difference from three localhost samples is not a persuasive speed
advantage. The tool did not expose controlled browser-cache or throttling
settings; no extra network/CPU throttle or cache manipulation was applied.
These are lab observations, not production performance or field Core Web Vitals.

### Saved markup and CSS

| Measure | Local styling | Existing shared system |
|---|---:|---:|
| Page `content.raw` | 28,909 bytes | 13,117 bytes |
| Form `content.raw` | 2,734 bytes | 1,142 bytes |
| Compiled design CSS | 8,489 bytes across page/form | 4,369 bytes in shared records |
| Additional shared style/token record fields | 0 | 15,940 serialized bytes |

Page markup alone is **2.20 times larger** locally, but comparing only page
markup hides the shared system's up-front storage. Including the form and
serialized shared fields gives **31,643 versus 30,199 bytes** for this one-page
fixture. This is a logical-data comparison, excluding physical database row
overhead, indexes, revisions, snapshots, and caches.

Across multiple similar pages, the shared system's fixed cost is reused. A
simple ten-page model yields 316,430 local bytes versus 158,530 shared bytes,
assuming a separate comparable form per page and one unchanged shared system.
That is a storage illustration, not a measured ten-page site or speed claim.

An accent change affects 17 local declaration values in this page/form, plus
their compiled CSS. The shared equivalent starts with one token value. A script
can update many local values in one operation, so 17 affected values should not
be confused with 17 required manual edits or a measured maintenance time.

## Issues exposed and corrected

1. An older row in `css-patterns.md` still recommended Global Styles for repeated
   components without mentioning opt-in. It now follows the local-default rule.
2. The Pro reference generalized all CSS classes as `gb-{slug}-{id}`. Actual
   accordion child classes use names such as `gb-accordion__toggle-{id}`. The
   documentation now requires native save output for the selector.
3. Deep-cloning native parsed block attributes lost rich-text behavior and
   produced valid but empty blocks. The benchmark now preserves rich-text values;
   the block reference records the failure and requires content comparison.

The first incorrect benchmark candidates were discarded before performance
sampling. All reported size/timing results use the corrected equivalent page.
The corrected references were synced to the installed canonical skills and
the skill bundles rebuilt.

## Recommended next improvement

Reduce the mandatory reading path while preserving the current safety and
serialization checks. Put the compact authoring contract in one place, keep the
router focused on file selection, and load detailed recovery cases by symptom.
Keep beta/shared-system material conditional on the user's explicit choice.

That restructuring was not performed as part of this test. It should be
followed by realistic generation trials before claiming lower token use with
unchanged quality. The current local-style default and shared-style prompt
remain in place.

## Evidence and reproduction

- `generateblocks-efficiency-results.json`: counts, raw helper samples, render
  parity results, medians, and scope limits.
- `efficiency/build-local.js`: native-editor conversion fixture, requiring real
  local record IDs; creates only a local page/form.
- `efficiency/local-css-isolation.php`: local page-specific asset isolation.
- `efficiency/measure.py`: reproduces instruction counts and helper timing from
  the local `output/efficiency/` fixtures.
- `output/efficiency/frontend-runs.json`: all six browser audit responses.
- `output/efficiency/build-result.json`: native build output and timing samples.

The benchmark does not publish to production or change the existing shared
style/token records.
