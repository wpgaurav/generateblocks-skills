> Superseded implementation: the initial Scripts Manager conversion below was corrected to native blocks only. See [native-only-correction.md](native-only-correction.md).

# About-page test of the compact GenerateBlocks skill

The compact skill completed a real About-page conversion after native validation
and targeted corrections. This demonstrates that it can handle this page; it is
not a controlled old-guide-versus-new-guide experiment or proof of equal success
on every future task.

## Targets and installation

- Source: [published About page](https://gauravtiwari.org/about/), ID **16260**.
- Review: [private draft preview](https://gauravtiwari.org/?page_id=1178400&preview=true), ID **1178400**.
- Editor: [edit the test draft](https://gauravtiwari.org/wp-admin/post.php?post=1178400&action=edit).
- Target template: `premium-builder.php`; active theme stylesheet: `md-new`.
- Actual active plugins: GenerateBlocks **2.5.0-beta.1**, Pro **2.8.0-beta.1**.
- All four local skills were installed through the installer and verified against
  source. Findings from this test were incorporated and installed again; standalone
  archives and their generated dependencies were rebuilt and checked.

The source was an 84,585-byte inline `gt-page-block/page-block`, not existing
GenerateBlocks markup. Authenticated raw storage was authoritative; the initial
web-search rendering was older than that stored source.

The final authenticated check confirmed that the published source content and
its Scripts Manager fields were unchanged. The test copy remains a draft.

## Conversion scope

The user chose local styling. A fresh-context worker used the installed compact
HTML conversion skill, its authoring contract/router, and the additional block,
CSS, SVG, and native schema details required by this page. It did not use prior
reports, old skill snapshots, browser tools, or production write access.

The result contains **346 native blocks**:

| Block | Count |
|---|---:|
| GenerateBlocks Element | 163 |
| GenerateBlocks Text | 174 |
| GenerateBlocks Shape | 1 |
| GenerateBlocks Media | 1 |
| Core Details | 7 |

There are no Page Blocks or Custom HTML blocks in the candidate. Complex
structural lists use GB ol/ul/li elements to preserve their child layouts and
tab/reveal hooks. No new Global Styles or Design Token records were created.

The original 33,844-byte CSS and 10,604-byte JavaScript were retained exactly in
the draft's page-specific Scripts Manager fields. A 964-byte page-only adapter
handles necessary native-block differences. Content and structure are editable
as blocks; most styling and custom behavior remain in those local code fields,
not in GenerateBlocks visual style controls. Editor execution/preview of those
assets remains off.

## Findings and fixes

The first pass was not sufficient on its own, despite reporting valid blocks:

1. **RichText dropped five empty decorative i elements.** These formed two
   product illustrations. They were rebuilt as native empty Element blocks with
   a scoped class and six copied selector rules. Their dimensions, borders,
   backgrounds, and pseudo-elements now match the original ornaments.
2. **Empty styles objects changed during server saves.** The site serialized
   `styles:{}` into `styles:[]`. Omitting unused styles restored byte-exact raw
   transport. Preflight now rejects explicit empty or wrong-type styles, with a
   regression test covering omission, empty objects, and arrays.
3. **The theme styled native Details differently.** Additional padding, margins,
   flow spacing, and a focus radius changed the FAQ. Narrow draft-only overrides
   restore the measured original appearance, including an expanded, focused FAQ.

The skill's compact contract and detailed references now cover empty styles,
intentional fragment links needed by scripts, and empty formatting tags that
can disappear despite native block validity. Relevant old examples were corrected.

These are observed conversion/serialization pitfalls. Without an equivalent
full-guide control, this test cannot attribute them specifically to shortening
the instructions. Keeping the native validation and readback steps proved useful:
they caught failures that preflight and recipe inspection alone missed.

## Verified result

- **346 blocks**, zero native validation errors, successful real editor save,
  and no unsaved editor changes.
- **80,977 bytes** of generated post content survived authenticated readback and
  the editor save byte-for-byte. This number excludes the separate CSS/JS fields
  and is not a total-size or speed comparison with the original Page Block.
- **43 headings, 27 links, 39 source IDs, one portrait, 12 sections, and seven FAQ
  disclosures** retained. Source text and hooks were checked, including the two
  native time elements and the SVG.
- **172 content/control elements** matched checked text, 16 computed CSS
  properties, and dimensions within 0.2px at light-mode widths **375, 768, and
  1440px**, plus dark-mode widths **375 and 1440px**. Page heights matched and
  there was no horizontal overflow.
- Five ornamental elements matched their original measured styling and dimensions.
- Problem tabs passed click and ArrowUp keyboard checks. The process interface
  passed Next, End-key navigation, and the expected step counter/button labels.
- FAQ keyboard opening and expanded/focused layout matched the source.
- Manual reduced-motion controls switched to `motion:off` / `aria-pressed:true`;
  OS reduced-motion behavior was exercised during the stable layout comparisons.
- No draft browser console errors were observed during these checks.
- **11 tooling tests** passed; all four installed skills, validators, archive
  members, generated dependencies, and `.skill`/`.zip` parity were verified.

This was structured DOM/style/behavior verification, not a screenshot approval,
complete accessibility audit, factual review of the biography, or exhaustive
test of every pointer-animation frame. The scope preserves the supplied page
rather than redesigning its content or brand language.

## Evidence

`verification.json` contains the consolidated results. Detailed source snapshots,
recipe/generator, native HTML, preflight output, per-page script snapshots, editor
proof, and comparison data live in `output/about-effectiveness/`.

The live page's final raw-content SHA-256 remained:

`136daf13809d12930dd52909f5228da62add28efd2892392c638d541a0536cf3`

The preview requires the site's authorized signed-in session. No public replacement
or publication was performed.
