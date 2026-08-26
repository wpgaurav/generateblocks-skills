---
title: Query / Looper / Loop-Item (V2 dynamic content)
description: How to build dynamic post lists, archives, related posts, ACF repeater loops, and pagination with the GenerateBlocks V2 Query block family. Source-verified against free 2.4 + Pro 2.7.
---

# Query Block (V2)

GenerateBlocks V2 ships its own query primitives. These are different from
WordPress core's `core/query` + `core/post-template` and from the legacy
`generateblocks/query-loop` block. Use these V2 blocks for any new dynamic
content.

The five blocks form a strict hierarchy:

```
generateblocks/query                        ← runs the WP_Query, holds query args
├── generateblocks/looper                   ← iterates results, ONE per query
│   └── generateblocks/loop-item            ← per-post template, ONE per looper
│       └── [any blocks: text, media, element, shape...]
├── generateblocks/query-no-results         ← optional empty state
└── generateblocks/query-page-numbers       ← optional pagination UI
```

`looper`, `query-no-results`, and `query-page-numbers` MUST be inside a `query`
ancestor (enforced via `ancestor` in block.json). `loop-item` MUST be the only
child type inside a `looper` (`allowedBlocks`).

---

## 1. The five blocks (verified against block.json)

### 1.1 `generateblocks/query`

| Attribute | Default | Notes |
|---|---|---|
| `uniqueId` | `""` | id-class `gb-query-{uniqueId}` auto-injected when `styles` non-empty |
| `tagName` | `""` | Enum: `div`, `section`, `article`, `aside`, `header`, `footer`, `nav`, `main` |
| `styles` / `css` / `globalClasses` / `htmlAttributes` | | Standard (see `block-types.md`) |
| `queryType` | `"WP_Query"` | Free: `WP_Query` only. Pro adds `post_meta` and `option` (loop over arrays — see §6) |
| `paginationType` | `"standard"` | Enum: `standard` (page reload) or `instant` (Interactivity-API router; enqueues `looper.js`, adds `data-gb-router-region`) |
| `query` | `{}` | WP_Query args object — see §2 |
| `inheritQuery` | `false` | `true` = use the global `$wp_query` (archive templates). `query` is ignored but still emit `"query":{}` |
| `showTemplateSelector` | `false` | Editor-only |

Provides context to all descendants: `generateblocks/query`,
`generateblocks/queryId` (= uniqueId), `generateblocks/inheritQuery`,
`generateblocks/paginationType`, `generateblocks/queryType`.

### 1.2 `generateblocks/looper`

| Attribute | Notes |
|---|---|
| `uniqueId`, `styles`, `css`, `globalClasses`, `htmlAttributes` | Standard |
| `tagName` | Enum: `div`, `section`, `article`, `aside`, `header`, `footer`, `nav`, `main`, `ul`, `ol` |

This is the grid/flex container — put `display:grid` + `gap` here.
`allowedBlocks: ["generateblocks/loop-item"]` — exactly one loop-item child.

### 1.3 `generateblocks/loop-item`

| Attribute | Notes |
|---|---|
| `uniqueId`, `styles`, `css`, `globalClasses`, `htmlAttributes` | Standard |
| `tagName` | Enum: `div`, `li`, `a`, `article`, `section`, `aside` |

The per-post template, rendered once per result. When `queryType` is
`WP_Query`, WordPress post classes (`post-123 type-post ...`) are auto-injected
into the rendered tag server-side via `WP_HTML_Tag_Processor`
(`includes/blocks/class-loop-item.php:41`) — do NOT write them into your markup.

Each iteration receives context: `postId`, `postType`,
`generateblocks/loopIndex` (1-based), `generateblocks/loopItem` (the post, or
the array item for Pro `post_meta`/`option` loops).

### 1.4 `generateblocks/query-no-results`

**No attributes at all** — emit `<!-- wp:generateblocks/query-no-results -->`
with inner blocks and the closing comment. Renders its inner blocks only when
the query found zero posts. Place as a sibling after the `looper`.

### 1.5 `generateblocks/query-page-numbers`

| Attribute | Notes |
|---|---|
| `uniqueId`, `styles`, `css`, `globalClasses`, `htmlAttributes` | Standard |
| `tagName` | Enum: `div`, `section`, `nav` |
| `midSize` | Number of page links each side of current (default `3`) |

Body is an **empty element** — the server fills it via `paginate_links()`.
Page links use the query-scoped URL param `?query-{uniqueId}-page=N`, so two
queries on one page paginate independently. Sibling of `looper`, never a child.

---

## 2. The `query` args object

Native WP_Query keys, passed through `GenerateBlocks_Query_Utils::get_wp_query_args()`
(`includes/class-query-utils.php`). Use real WP_Query names:

```json
"query":{
    "post_type":"post",
    "posts_per_page":6,
    "order":"DESC",
    "orderby":"date",
    "post_status":"publish",
    "ignore_sticky_posts":true,
    "offset":0,
    "tax_query":[{"taxonomy":"category","field":"slug","terms":["wordpress","seo"],"operator":"IN","includeChildren":true}],
    "meta_query":[{"key":"_featured","value":"1","compare":"="}],
    "date_query":[{"after":"2025-01-01","before":"2025-12-31"}]
}
```

Notes verified in source:

- **`offset` composes with pagination** — the plugin computes
  `offset = posts_per_page × (page − 1) + offset`, so a manual offset
  doesn't break page 2+.
- **Multiple `tax_query` clauses get `relation: AND`** automatically.
- **`post_status` other than `publish`** is forced back to `publish` for
  visitors who can't `read_private_posts`.
- **`stickyPosts`** (GB-specific key): `"ignore"` → `ignore_sticky_posts:true`,
  `"exclude"` → merges sticky IDs into `post__not_in`, `"only"` → only sticky.
- `posts_per_page: -1` = all posts (no pagination).

## 3. Pro magic values — relationship queries

With GB Pro active, the string `"current"` resolves at render time inside
these keys (`generateblocks-pro/includes/extend/query/class-query.php`):

| Key | `"current"` means |
|---|---|
| `post__not_in` | Exclude the current post — **the related-posts essential** |
| `post__in` | Only the current post |
| `author__in` / `author__not_in` | Current post's author |
| `post_parent__in` / `post_parent__not_in` | Current post's parent |
| `tax_query[].terms` | Current post's terms in that taxonomy |

### Related posts (same category, current post excluded)

```json
"query":{
    "post_type":"post",
    "posts_per_page":3,
    "post__not_in":["current"],
    "tax_query":[{"taxonomy":"category","field":"term_id","terms":["current"],"operator":"IN"}]
}
```

Without Pro there are no magic values — related-posts sections need Pro, or
hardcoded term IDs.

---

## 4. Dynamic content inside loop-item

Use dynamic tags — **exact syntax in `dynamic-tags.md`** (space after tag
name, pipe-separated options, no quotes). The high-frequency loop patterns:

| Need | Tag |
|---|---|
| Title | `{{post_title}}` or self-linking `{{post_title link:post}}` |
| Permalink (for element `<a>` href) | `{{post_permalink}}` |
| Excerpt | `{{post_excerpt length:25}}` |
| Date | `{{post_date dateFormat:M j, Y}}` |
| Featured image URL | `{{featured_image size:large}}` |
| Featured image alt | `{{featured_image key:alt}}` |
| Categories/tags | `{{term_list tax:category|sep:, }}` |
| Custom field / ACF | `{{post_meta key:field_name}}` |
| Author | `{{author_meta key:display_name}}` |
| Item number | `{{loop_index}}` (Pro) |

### Loop featured image → `generateblocks/media`

`core/image` cannot resolve loop context — inside loops always use
`generateblocks/media` with a tag in `src` and `mediaId` omitted:

```html
<!-- wp:generateblocks/media {"uniqueId":"grid4","tagName":"img","styles":{"width":"100%","aspectRatio":"16/9","objectFit":"cover"},"css":".gb-media-grid4{aspect-ratio:16/9;object-fit:cover;width:100%}","htmlAttributes":{"src":"{{featured_image size:large}}","alt":"{{featured_image key:alt}}","loading":"lazy"},"className":"gb-media"} -->
<img class="gb-media-grid4 gb-media" src="{{featured_image size:large}}" alt="{{featured_image key:alt}}" loading="lazy"/>
<!-- /wp:generateblocks/media -->
```

### Whole-card link

Either make the loop-item itself an `<a>` (`tagName:"a"` is in its enum), or
use an inner element `<a>`:

```html
<!-- wp:generateblocks/element {"uniqueId":"grid5","tagName":"a","styles":{"display":"block"},"css":".gb-element-grid5{display:block}","htmlAttributes":{"href":"{{post_permalink}}","aria-label":"{{post_title}}"},"className":"gb-element"} -->
<a class="gb-element-grid5 gb-element" href="{{post_permalink}}" aria-label="{{post_title}}">
    <!-- inner blocks -->
</a>
<!-- /wp:generateblocks/element -->
```

---

## 5. Common query recipes

```json
// Inherit archive query (archive.html, category templates, home.html)
"inheritQuery":true,"query":{}

// Custom post type grid
"query":{"post_type":"project","posts_per_page":12,"orderby":"menu_order","order":"ASC"}

// Children of current page (Pro magic value)
"query":{"post_type":"page","post_parent__in":["current"],"posts_per_page":-1,"orderby":"menu_order","order":"ASC"}

// Featured posts only (meta flag)
"query":{"post_type":"post","posts_per_page":4,"meta_query":[{"key":"_featured","value":"1","compare":"="}]}

// More from this author (Pro)
"query":{"post_type":"post","posts_per_page":3,"author__in":["current"],"post__not_in":["current"]}

// Random pick
"query":{"post_type":"post","posts_per_page":1,"orderby":"rand"}
```

---

## 6. Pro: looping arrays — ACF repeaters and options

Pro adds two `queryType` values that loop over array data instead of posts:

```json
"queryType":"post_meta"   // loop a post meta array (ACF repeater)
"queryType":"option"      // loop an option array (ACF options-page repeater)
```

The `query` object for these types:

```json
"query":{
    "meta_key_id":"current",        // post to read meta from ("current" or an ID)
    "meta_key":"team_members",      // the repeater/array field name
    "posts_per_page":10,
    "offset":0
}
```

Inside the loop-item, read fields with `{{loop_item key:...}}` and number rows
with `{{loop_index}}` — full worked repeater example in
`acf-and-custom-fields.md` §4.

Since Pro 2.7, `option` loops (and the `{{option}}` tag) are allow-listed
per user at save time — non-admins can only save option keys already on the
allowed list (filter:
`generateblocks_dynamic_tags_allowed_options_for_current_user`).

---

## 7. Complete worked example

The canonical, copy-pasteable blog grid lives at
**[`examples/layouts/query-blog-grid.html`](../examples/layouts/query-blog-grid.html)** —
query → looper (responsive grid) → loop-item (`article`) with dynamic image,
terms, title, excerpt, read-more, plus no-results and pagination. Copy its
structure rather than rebuilding from memory; every recovery rule and the
canonical tag syntax are already enforced there.

---

## 8. Recovery + correctness rules specific to query blocks

In addition to the global rules in `recovery-rules.md`:

1. **`looper` may only contain `loop-item`** — anything else is rejected.
2. **`loop-item`/`query-no-results`/`query-page-numbers` can't be used outside
   their required ancestors.**
3. **One `loop-item` per `looper`.** It's a template, not a list. Vary
   presentation inside the template, not by adding siblings.
4. **Always emit `"query":{}`** even with `inheritQuery:true`.
5. **`paginationType` defaults to `"standard"`** — `"instant"` enqueues the
   Interactivity-API router; keep `standard` unless asked.
6. **`query-page-numbers` is a sibling of `looper`**, never a child.
7. **No quoted values inside dynamic tags** — `{{featured_image size:large}}`,
   never `size="large"`. Wrong syntax doesn't trigger recovery; it renders
   literally on the frontend, which is worse because it saves fine.
8. **Dynamic images = `generateblocks/media`**, static captioned images =
   `core/image`. The loop is the exception zone where media is mandatory.
9. **Don't write WP post classes on the loop-item** — the server injects them.
10. **`{{currentPostId}}` does not exist.** Use Pro's `"current"` magic value
    in the query args, or hardcode an ID.
