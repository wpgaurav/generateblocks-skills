---
title: Dynamic Tags (canonical catalog)
description: Every dynamic tag in GenerateBlocks free 2.4 + Pro 2.7 with exact source-verified syntax, options, and usage in block markup. Replaces all older tag documentation.
---

# Dynamic Tags — Canonical Reference

Source-verified against GenerateBlocks 2.4.0 (`includes/dynamic-tags/`) and
GB Pro 2.7.0 (`includes/extend/dynamic-tags/`); matches the official docs at
learn.generatepress.com (canonical for v2 — docs.generateblocks.com is v1
only). **If another file in this repo shows a different tag syntax, this file
wins.**

## 1. Syntax — get this exactly right

```
{{tag_name first_option:value|second_option:value|third_option:value}}
```

- **A single SPACE separates the tag name from the options string.**
- **Pipes (`|`) separate options from each other.**
- **A colon (`:`) separates each option key from its value.**
- **No quotes around values. Ever.** Values run until the next unescaped pipe
  or the closing `}}`. Spaces and commas inside values are fine.
- Escape a literal pipe or colon inside a value with a backslash: `\|` `\:`.

```
{{post_title}}                              ← no options
{{post_title link:post}}                    ← one option
{{post_excerpt length:20}}                  ← numeric value, no quotes
{{post_date dateFormat:F j, Y}}             ← value containing spaces + comma
{{term_list tax:category|sep:, }}           ← two options, pipe-separated
{{featured_image size:large|key:url}}       ← two options
{{post_meta key:price}}                     ← meta key
{{post_meta key:repeater.0.subfield}}       ← dot notation for nested values
```

**WRONG forms that silently fail** (the tag renders as literal text on the
frontend — no recovery error, just `{{...}}` visible to visitors):

```
{{post_meta key="price"}}          ← quoted values don't exist
{{post_url}}                       ← no such tag; use {{post_permalink}}
{{featured_image_url size="large"}}← no such tag; use {{featured_image size:large}}
{{post_terms taxonomy="category"}} ← no such tag; use {{term_list tax:category}}
{{post_meta|key:price}}            ← pipe directly after tag name; must be a space
{{acf field="..."}}                ← no acf tag; use {{post_meta key:field_name}}
```

The matcher regex is `/\{{(tag_names)(\s+[^}]+)?}}/` — whitespace after the
tag name is mandatory before any options (`find_matches()` in
`includes/dynamic-tags/class-register-dynamic-tag.php`; unchanged in 2.4,
which only added `preg_quote()` around tag names).

## 2. Where tags work

Tags are replaced at render time (priority-10 `render_block` filter) in these
blocks only: `generateblocks/element`, `text`, `media`, `shape`, `query`,
`looper`, `loop-item`, `query-page-numbers`.

| Place | Example |
|---|---|
| Text `content` | `"content":"{{post_title link:post}}"` + same string in the HTML body |
| Element/text `htmlAttributes.href` | `"htmlAttributes":{"href":"{{post_permalink}}"}` |
| Media `htmlAttributes.src` / `alt` | `"htmlAttributes":{"src":"{{featured_image size:large}}","alt":"{{featured_image key:alt}}"}` |
| `css` string (e.g. background-image) | `background-image:url({{featured_image size:full}})` |
| Query `query` values | `"post__not_in":["current"]` (Pro magic value, not a `{{}}` tag) |

Tags do NOT work in core blocks (`core/paragraph`, `core/image`, ...).

Since 2.4, tags are **stripped to empty inside `on*` event-handler
attributes** (`onclick`, `onmouseover`, ...) and `srcdoc` — see §10. Don't
put tags there.

**JSON escaping still applies.** A tag inside a JSON attribute value follows
the normal recovery rules: if the tag value contains `--`, `&`, `<`, `>`, use
the five substitutions (rare — tag syntax avoids these characters by design).
The same tag string must appear identically in the JSON attribute and the
rendered HTML body.

## 3. Context resolution

How a tag decides which post/user/term it reads from, in priority order:

1. Explicit `id:123` option on the tag.
2. **Loop context** — inside a `loop-item`, tags resolve against the current
   loop post automatically. This is what makes query loops work.
3. The current post / queried object (`get_the_ID()`,
   `get_queried_object_id()`, `get_current_user_id()`).

No setup needed inside loops — `{{post_title}}` in a loop-item just works.

## 4. Free tags (GenerateBlocks 2.4)

### Post

| Tag | Options | Returns |
|---|---|---|
| `{{post_title}}` | — | Post title |
| `{{post_excerpt}}` | `length` (words, default 55), `useTheme` (bool), `pre` (text before read-more), `readMore` (link text) | Excerpt |
| `{{post_permalink}}` | — | Permalink URL |
| `{{post_date}}` | `type` (`''` published / `modified` / `modifiedOnly`), `dateFormat` (PHP date format) | Formatted date |
| `{{featured_image}}` | `key` (`url` default / `id` / `alt` / `caption` / `description`), `size` (image size slug, default `full`) | Featured image data |
| `{{post_meta}}` | `key` (required; dot notation for nested/array values) | Meta value (ACF-aware with Pro) |
| `{{comments_count}}` | `none`, `single`, `multiple` (`%` = count) | Comment count text |
| `{{comments_url}}` | — | URL to comments section |
| `{{term_list}}` | `tax` (required, e.g. `category`, `post_tag`), `sep` (default `, `), `link` (bool) | Term names, optionally linked |
| `{{previous_posts_page_url}}` | — | Previous page URL in a paginated query |
| `{{next_posts_page_url}}` | — | Next page URL in a paginated query |

### Author

| Tag | Options | Returns |
|---|---|---|
| `{{author_meta}}` | `key` (required: `display_name`, `description`, `user_email`, any user meta) | Author field |
| `{{author_archives_url}}` | — | Author archive URL |
| `{{author_avatar_url}}` | `size` (px, default 96), `default`, `forceDefault`, `rating` | Avatar URL |

### Media

| Tag | Options | Returns |
|---|---|---|
| `{{media}}` | `id` (attachment ID), `key` (`url` / `id` / `alt` / `caption` / `description`) | Any attachment's data |

## 5. Universal post-processing options

These work on (almost) every tag, chained with pipes, applied to the output:

| Option | Example | Effect |
|---|---|---|
| `link:type` | `{{post_title link:post}}` | Wraps output in `<a>`. Types: `post`, `comments`, `author_archive`, `post_meta,meta_key`, `author_meta,meta_key` |
| `trunc:n[,words]` | `{{post_title trunc:40}}`, `{{post_excerpt trunc:20,words}}` | Truncate to n chars (or words) |
| `case:x` | `{{post_title case:upper}}` | `lower`, `upper`, `title` |
| `trim[:side]` | `{{post_meta key:x|trim}}` | Strip whitespace (`left`/`right`/both) |
| `replace:a,b` | `{{post_title replace:Old,New}}` | String replace (escape literal comma as `\,`) |
| `wpautop` | `{{post_meta key:bio|wpautop}}` | Wrap in paragraphs like the classic editor |
| `id:n` | `{{post_title id:123}}` | Read from a specific post/user/term instead of context |

Combined: `{{post_title case:upper|trunc:40|link:post}}`

**"Required to render"**: tags also support a required flag (set via the tag
modal UI; serialized into the tag options). When a required tag resolves
empty, the **entire block is removed** from output — the official way to hide
a wrapper when its data is missing. Don't hand-guess its serialized form;
insert once via the UI and copy, or use the `:empty` CSS fallback from
`conditions.md` §4.

## 6. Pro tags (GB Pro 2.7)

### Archive / site / options

| Tag | Options | Returns |
|---|---|---|
| `{{archive_title}}` | `id` (term ID / post type / page ID override) | Archive title (term, post type, author, search) |
| `{{archive_description}}` | — | Archive description |
| `{{site_title}}` | — | Site name |
| `{{site_tagline}}` | — | Site tagline |
| `{{site_url}}` | — | Home URL |
| `{{site_logo_url}}` | — | Customizer logo URL |
| `{{current_year}}` | — | 4-digit year (e.g. footer copyright) |
| `{{option}}` | `key` (required, dot notation OK), `default` (fallback) | WP option / ACF options-page field. Non-admins limited to safe keys + ACF fields |

### Term / user meta

| Tag | Options | Returns |
|---|---|---|
| `{{term_meta}}` | `key` (required, dot notation), `id` (term override) | Term meta (ACF-aware). Default context: current queried term |
| `{{user_meta}}` | `key` (required, dot notation), `id` (user override) | User meta (ACF-aware). Default context: logged-in user |

### Loops (Pro looper extensions)

| Tag | Options | Returns |
|---|---|---|
| `{{loop_index}}` | `zeroBased` (bool) | 1-based iteration counter (numbered lists, stagger delays) |
| `{{loop_item}}` | `key` (property path, dot notation), `fallback` | Current item value when looping arrays/repeaters (queryType `post_meta`/`option`) — see `acf-and-custom-fields.md` |

### Adjacent posts (Pro `source` option)

Pro adds a `source` option to post tags for prev/next navigation:

```
{{post_title source:next-post}}
{{post_permalink source:previous-post}}
{{post_title source:next-post|inSameTerm:true|sameTermTaxonomy:category}}
{{featured_image source:previous-post|size:medium}}
```

## 7. ACF and custom field providers

There is **no `{{acf}}` tag**. Pro hooks ACF into the meta tags via the
`generateblocks_get_meta_pre_value` filter (`includes/extend/dynamic-tags/class-acf.php`),
so ACF fields use the standard meta tags with ACF return-format handling:

```
{{post_meta key:my_acf_field}}              ← post field (get_field equivalent)
{{post_meta key:my_group.subfield}}         ← group sub-field
{{post_meta key:my_repeater.0.title}}       ← repeater row 0, sub-field title
{{term_meta key:my_acf_field}}              ← term field
{{user_meta key:my_acf_field}}              ← user field
{{option key:my_options_page_field}}        ← ACF options page
```

Full ACF patterns (image fields, links, repeaters with the Looper,
conditional output): see `acf-and-custom-fields.md`.

## 8. Worked examples (markup-ready)

### Title linked to post, inside a loop-item

```html
<!-- wp:generateblocks/text {"uniqueId":"card2","tagName":"h3","content":"{{post_title link:post}}","styles":{"fontSize":"1.25rem","fontWeight":"700"},"css":".gb-text-card2{font-size:1.25rem;font-weight:700}","className":"gb-text"} -->
<h3 class="gb-text-card2 gb-text">{{post_title link:post}}</h3>
<!-- /wp:generateblocks/text -->
```

### Dynamic featured image (inside a loop-item)

`mediaId` stays `0`/omitted for dynamic images — the `src` tag does the work.
The plugin only adds srcset/width/height when `mediaId` points at a real
attachment, which a loop image doesn't.

```html
<!-- wp:generateblocks/media {"uniqueId":"card3","tagName":"img","styles":{"width":"100%","objectFit":"cover","aspectRatio":"16/9"},"css":".gb-media-card3{aspect-ratio:16/9;object-fit:cover;width:100%}","htmlAttributes":{"src":"{{featured_image size:large}}","alt":"{{featured_image key:alt}}","loading":"lazy"},"className":"gb-media"} -->
<img class="gb-media-card3 gb-media" src="{{featured_image size:large}}" alt="{{featured_image key:alt}}" loading="lazy"/>
<!-- /wp:generateblocks/media -->
```

### Card wrapper linking to the post

```html
<!-- wp:generateblocks/element {"uniqueId":"card1","tagName":"a","styles":{"display":"flex","flexDirection":"column"},"css":".gb-element-card1{display:flex;flex-direction:column}","htmlAttributes":{"href":"{{post_permalink}}"},"className":"gb-element"} -->
<a class="gb-element-card1 gb-element" href="{{post_permalink}}">
    <!-- inner blocks -->
</a>
<!-- /wp:generateblocks/element -->
```

### Meta row: date + categories

```html
<!-- wp:generateblocks/text {"uniqueId":"card4","tagName":"p","content":"{{post_date dateFormat:M j, Y}} · {{term_list tax:category|sep:, }}","styles":{"fontSize":"0.875rem","color":"#5c5c5c"},"css":".gb-text-card4{color:#5c5c5c;font-size:0.875rem}","className":"gb-text"} -->
<p class="gb-text-card4 gb-text">{{post_date dateFormat:M j, Y}} · {{term_list tax:category|sep:, }}</p>
<!-- /wp:generateblocks/text -->
```

### Footer copyright (Pro)

```html
<!-- wp:generateblocks/text {"uniqueId":"foot1","tagName":"p","content":"© {{current_year}} {{site_title}}. All rights reserved.","styles":{"fontSize":"0.875rem"},"css":".gb-text-foot1{font-size:0.875rem}","className":"gb-text"} -->
<p class="gb-text-foot1 gb-text">© {{current_year}} {{site_title}}. All rights reserved.</p>
<!-- /wp:generateblocks/text -->
```

### Prev/next post navigation (Pro)

```html
<!-- wp:generateblocks/element {"uniqueId":"nav1","tagName":"a","styles":{"display":"inline-flex"},"css":".gb-element-nav1{display:inline-flex}","htmlAttributes":{"href":"{{post_permalink source:next-post}}","rel":"next"},"className":"gb-element"} -->
<a class="gb-element-nav1 gb-element" href="{{post_permalink source:next-post}}" rel="next">
    <!-- wp:generateblocks/text {"uniqueId":"nav2","tagName":"span","content":"Next: {{post_title source:next-post|trunc:50}}","styles":{},"css":"","className":"gb-text"} -->
    <span class="gb-text-nav2 gb-text">Next: {{post_title source:next-post|trunc:50}}</span>
    <!-- /wp:generateblocks/text -->
</a>
<!-- /wp:generateblocks/element -->
```

## 9. Failure modes (silent — no recovery error)

| Symptom | Cause | Fix |
|---|---|---|
| `{{post_url}}` visible on frontend | Tag name doesn't exist | Use the exact names in this file |
| `{{post_meta key="x"}}` visible | Quoted value breaks option parsing | `{{post_meta key:x}}` |
| Tag renders empty | Key/field has no value, or wrong context | Check field exists on the post; add `id:` to test; for ACF check the field name not the label |
| Tag works in editor preview, empty on frontend | Protected meta (`_`-prefixed) or capability-gated (user meta, options) | Use a non-protected key, or an allowed option key |
| **Every tag on the page renders empty** | 2.4 taint model: post was last saved by a user without dynamic-data permission | Re-save the post with a trusted account (see §10) |
| Save rejected with 403 when adding a tag | 2.4 save gate: only trusted users can save content that adds dynamic tags | Save with a trusted account, or filter `generateblocks_user_can_author_dynamic_data` |
| Tag inside `onclick`/`on*` attribute renders empty | 2.4 strips tags from event-handler attributes and `srcdoc` | Don't put tags in event handlers (opt-in filter exists — §10) |
| ACF field previews blank in editor but renders on frontend | Field not exposed to REST (editor preview uses REST) | Expected — verify on the frontend |
| Tag renders raw inside a core block | Tags only run in GB blocks | Move into `generateblocks/text` |
| Wrong post's data inside a loop | Tag has stray `id:` option | Remove `id:` to use loop context |

## 10. Permissions & security model (2.4)

GenerateBlocks 2.4 replaced the old save-time validation middleware with a
capability model, a save gate, and render-time taint tracking. The old
validation methods are no-op stubs now. What matters when authoring markup:

- **Who counts as trusted:** users with `unfiltered_html` OR
  `manage_options` (filter: `generateblocks_user_can_author_dynamic_data`).
  On standard single-site WP that's Editors and above; on multisite only
  super admins have `unfiltered_html` by default.
- **Save gate** (`includes/class-save-gate.php`): an untrusted user's save
  is rejected (403) if the content **adds** dynamic tags — via REST,
  autosave, classic editor, or programmatic paths. Removing existing tags
  saves fine, as do byte-identical saves that don't increase exposure.
- **Taint model** (`class-dynamic-tag-security.php`): a post whose last save
  came from an untrusted user is stamped with
  `_generateblocks_untrusted_dynamic_content` meta, and **all** dynamic tags
  in it render empty on the frontend until a trusted user re-saves it. This
  is the first thing to check when tags "suddenly stopped rendering".
- **Event handlers:** tags never resolve inside `on*` attributes or
  `srcdoc`. Opt back in (event handlers only) with the
  `generateblocks_allow_dynamic_data_in_event_handlers` filter; `srcdoc`
  is always stripped.
- **Context-aware escaping:** resolved tag output is escaped once, per
  attribute context (`replace_tags_in_content()`): URL attributes (`href`,
  `src`, `action`, `formaction`, `xlink:href`) get `esc_url()` — so
  `javascript:`/`data:` values die — while text/body positions stay
  HTML-capable.
- **Meta lookups:** `post_password`, `password`, `user_pass`, and
  `user_activation_key` are always blocked; `_`-prefixed protected meta is
  blocked (the leading-space bypass is fixed in 2.4); dot-path traversal
  that dereferences into a non-public post (draft/private/password) returns
  empty.
- **Editor previews** for untrusted users come back blank or
  kses-sanitized; the server render is authoritative.

None of this changes tag syntax or markup rules — it changes **who can save
tags and when they render**. Markup this skill emits is unaffected as long
as the person pasting it is a trusted user.
