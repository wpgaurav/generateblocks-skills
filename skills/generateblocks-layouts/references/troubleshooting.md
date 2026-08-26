---
title: Troubleshooting Guide
description: Debug recipes, chunking strategies, and error recovery for complex GenerateBlocks layouts.
---

# Troubleshooting Guide

For the **catalog of every recovery cause and its exact fix**, see
`recovery-rules.md`. This file is for debug strategies, chunking, and the
recipes you reach for after a failure.

## When you hit "Attempt Recovery"

1. **Re-read `recovery-rules.md`** — every known cause is in there. Walk
   the §7 pre-flight checklist against your output.
2. **Bisect.** Comment out the back half of the file, save, see if recovery
   still happens. Halve again. The fastest way to find the bad block.
3. **Look at the editor's "click to attempt recovery" diff.** When you click
   the button, the editor shows the block markup it expected vs what's in
   the post. The mismatch is your bug.
4. **Check the JSON `--` escapes first.** This is the most common silent
   failure.
5. **Check `styles`/`css` parity.** A transition, selector, or at-rule in the
   CSS cache needs an equivalent structured styles branch. Repair the pair;
   do not blindly strip valid CSS Mode selectors.
6. **Check selector/at-rule depth.** CSS Mode supports one selector level plus
   one `@media`, `@supports`, or `@container` level. Flatten deeper selectors.

### Solutions

**1. Simplify CSS Attribute**

Split complex CSS into multiple blocks instead of one massive string:

```css
/* Instead of this (500+ chars) */
.gb-element-id{prop1;prop2;prop3;...many more...}.gb-element-id:hover{...}.gb-element-id::before{...}@media{...}

/* Break into smaller chunks by nesting elements */
```

**2. Use Chunked Generation**

For sections with 20+ blocks, generate in chunks:

```
Chunk 1: Section wrapper + header (3-5 blocks)
Chunk 2: First row of cards (4-6 blocks)
Chunk 3: Second row of cards (4-6 blocks)
Chunk 4: Footer/CTA area (2-4 blocks)
```

**3. Escape Special Characters**

In `css` attribute, escape:
- Single quotes: Use `'` not `"`
- Content property: `content:''` or `content:'→'`
- URLs: Encode special characters

**4. Validate JSON Before Output**

Ensure:
- All quotes are properly escaped
- No trailing commas
- Brackets match

---

## Chunking Strategy for Complex Layouts

### Planning Phase

1. **Map the structure** - List all components before coding
2. **Identify nesting levels** - Max 4-5 levels deep
3. **Group related blocks** - Cards, stats, etc.
4. **Estimate block count** - Plan chunks if >20 blocks

### Example: Services Section (50+ blocks)

```
Section: Services
├── Container (sect001)
│   ├── Inner (sect002)
│   │   ├── Trust Block (trust001-trust010) → CHUNK 1
│   │   ├── Header (head001-head003) → CHUNK 2
│   │   └── Grid (grid001) → CHUNK 3 wrapper
│   │       ├── Featured Card (feat001-feat008) → CHUNK 4
│   │       ├── Cards 1-4 (card001-card016) → CHUNK 5
│   │       └── Cards 5-8 (card017-card032) → CHUNK 6
```

### Chunked Output Format

**Chunk 1: Trust Block**
```html
<!-- CHUNK 1: Trust Block -->
<!-- wp:generateblocks/element {"uniqueId":"trust1"...} -->
...
<!-- /wp:generateblocks/element -->
<!-- END CHUNK 1 -->
```

**Chunk 2: Header**
```html
<!-- CHUNK 2: Header -->
<!-- wp:generateblocks/element {"uniqueId":"head1"...} -->
...
<!-- /wp:generateblocks/element -->
<!-- END CHUNK 2 -->
```

### Assembly

After generating all chunks, combine in order with proper nesting.

---

## Common Syntax Errors

### Missing Closing Comments

```html
<!-- WRONG -->
<!-- wp:generateblocks/text {"uniqueId":"txt1"} -->
<p class="gb-text">Text</p>
<!-- Missing closing comment -->

<!-- CORRECT -->
<!-- wp:generateblocks/text {"uniqueId":"txt1"} -->
<p class="gb-text">Text</p>
<!-- /wp:generateblocks/text -->
```

### Mismatched Block Types

```html
<!-- WRONG -->
<!-- wp:generateblocks/element {"uniqueId":"elem1"} -->
<div>Content</div>
<!-- /wp:generateblocks/text -->  <!-- Wrong type -->

<!-- CORRECT -->
<!-- wp:generateblocks/element {"uniqueId":"elem1"} -->
<div>Content</div>
<!-- /wp:generateblocks/element -->
```

### Invalid JSON

```json
// WRONG - trailing comma
{"uniqueId":"id1","styles":{"padding":"1rem",}}

// CORRECT
{"uniqueId":"id1","styles":{"padding":"1rem"}}
```

```json
// WRONG - unescaped quotes in content
{"css":".class{content:"text"}"}

// CORRECT - use single quotes
{"css":".class{content:'text'}"}
```

---

## CSS Debugging

### CSS Not Applying

1. **Check unique ID matches** - Class must match `uniqueId`
2. **Verify minification** - No line breaks in `css` attribute
3. **Check selector format** - `.gb-{type}-{uniqueId}`

```css
/* Element block: */
.gb-element-elem1{...}

/* Text block: */
.gb-text-text1{...}

/* Media block: */
.gb-media-img1{...}

/* Shape block: */
.gb-shape-icon1{...}
```

### Hover Not Working

1. Put the transition in base `styles` and the state under `&:hover` or
   `&:focus-visible`.
2. Compile those same branches into `css`; a CSS-only hover is not durable.
3. Parent-hover, child, and pseudo-element behavior is valid when represented
   as one structured selector such as `&:hover > .child` or `&::after`.
4. Do not depend on hover for access to mobile content.

### Responsive Not Working

1. **Check breakpoint order** - Desktop first, then tablet, then mobile
2. **Verify the installed query** - native Mobile is `@media (max-width:767px)`
3. **Check both `styles` and `css`** - the at-rule belongs in both layers
4. **Inspect specificity before `!important`** - fix ownership/order first

---

## Dynamic Data Failures (2.4+ security model)

Free GB 2.4 added a capability-based security model for dynamic tags. Three
new failure recipes (full model: `dynamic-tags.md` §10):

### Every dynamic tag on a page renders empty

1. Check who last saved the post. If they lack `unfiltered_html` /
   `manage_options`, the post is taint-flagged
   (`_generateblocks_untrusted_dynamic_content` meta) and ALL its dynamic
   tags render empty on the frontend.
2. Fix: re-save the post from a trusted (admin) account — the flag clears.
3. To widen who counts as trusted, use the
   `generateblocks_user_can_author_dynamic_data` filter.

### Save rejected (403) when pasting markup with tags

The save gate blocks untrusted users from saving content that **adds**
dynamic tags. Paste and save with a trusted account; removing existing tags
is always allowed.

### Tag inside an event-handler attribute renders empty

2.4 strips dynamic tags from `on*` attributes (`onclick`, ...) and `srcdoc`.
Don't put tags there — move the logic to real attributes or CSS.

---

## Nesting Issues

### Maximum Nesting Depth

Keep nesting to 4-5 levels max:

```
section (1)
  └── container (2)
        └── grid (3)
              └── card (4)
                    └── content (5) ← MAX
```

### Breaking Deep Nesting

Instead of:
```html
<section>
  <div>
    <div>
      <div>
        <div>
          <div>Content</div>  <!-- Too deep -->
        </div>
      </div>
    </div>
  </div>
</section>
```

Flatten structure:
```html
<section>
  <div class="container">
    <div class="grid">
      <div class="card">Content</div>
    </div>
  </div>
</section>
```

---

## Performance Issues

### Too Many Blocks

**Symptoms:** Slow editor, lag when editing

**Solutions:**
1. Combine related text into single blocks
2. Use reusable patterns/synced patterns
3. Consider query loops for repeated content

### Large CSS Strings

**Symptoms:** Large page size, slow rendering

**Solutions:**
1. Remove redundant properties
2. Use shorthand CSS (`padding` instead of `padding-top/right/bottom/left`)
3. Extract common styles to global classes

---

## Validation Checklist

Before finalizing complex layouts:

- [ ] All blocks have unique IDs
- [ ] All opening comments have matching closings
- [ ] JSON is valid (no trailing commas, proper escaping)
- [ ] CSS selectors match unique IDs
- [ ] Media queries are in correct order
- [ ] Nesting depth ≤ 5 levels
- [ ] No orphaned blocks (all within containers)

---

## Quick Fixes

### Block Not Rendering

```html
<!-- Check: Is content between comments? -->
<!-- wp:generateblocks/text {"uniqueId":"txt1"} -->
<p class="gb-text gb-text-txt1">Content HERE</p>
<!-- /wp:generateblocks/text -->
```

### Styles Not Applying

```html
<!-- Check: Does class match uniqueId? -->
<!-- wp:generateblocks/element {"uniqueId":"box1","css":".gb-element-box1{...}"} -->
<div class="gb-element-box1 gb-element">...</div>  <!-- box001 matches -->
<!-- /wp:generateblocks/element -->
```

### Hover Breaking Layout

Hover states and transitions belong in the `styles` object and the compiled
`css` cache. CSS Mode supports pseudo-elements and parent/child selectors as
one-level structured branches. If the cache contains a state that `styles`
does not, the next editor save can remove it.

---

## Getting Help

If issues persist:

1. **Test single block** - Isolate the problematic block
2. **Validate JSON** - Use online JSON validator
3. **Check browser console** - Look for JS errors
4. **Compare with working example** - Use examples folder as reference
