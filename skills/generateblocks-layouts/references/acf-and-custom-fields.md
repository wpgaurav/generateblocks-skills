---
title: ACF & custom fields with GenerateBlocks
description: Using ACF (and other custom field providers) in GenerateBlocks — field output via meta tags, image/link/group fields, repeater loops with the Looper, options pages, term/user fields, conditional output.
---

# ACF & Custom Fields

GenerateBlocks reads custom fields through its **meta dynamic tags**. With GB
Pro + ACF active, Pro's ACF bridge
(`generateblocks-pro/includes/extend/dynamic-tags/class-acf.php`, filter
`generateblocks_get_meta_pre_value`) makes the meta tags ACF-aware: values
come through ACF's `get_field()` pipeline with return formats applied.

**There is no `{{acf}}` tag.** Everything goes through `{{post_meta}}`,
`{{term_meta}}`, `{{user_meta}}`, `{{option}}` — exact syntax rules in
`dynamic-tags.md` (space after tag name, `key:value`, pipes, no quotes).

## 1. Field-type cheat sheet (post context)

| ACF field type | Return format to set | Tag |
|---|---|---|
| Text / Textarea / Number / Email | — | `{{post_meta key:field_name}}` |
| Wysiwyg | — | `{{post_meta key:field_name|wpautop}}` |
| Image | **URL** | `{{post_meta key:field_name}}` into media `src` |
| Image | Array/ID | `{{post_meta key:field_name.url}}` (array) — or switch the field to URL return |
| Link (array) | Array | href: `{{post_meta key:field_name.url}}`, label: `{{post_meta key:field_name.title}}` |
| URL | — | `{{post_meta key:field_name}}` in `htmlAttributes.href` |
| Select / Radio | Value | `{{post_meta key:field_name}}` |
| True/False | — | resolves `1`/empty — pair with `:empty` CSS or Pro conditions |
| Group | — | `{{post_meta key:group_name.subfield}}` |
| Repeater (single row) | — | `{{post_meta key:repeater.0.subfield}}` (0-based row index) |
| Repeater (loop all rows) | — | Pro Looper with `queryType:"post_meta"` — §4 |
| Relationship / Post Object | Post ID | `{{post_meta key:field_name}}` = ID; query it via meta, or display via `{{post_title id:...}}` only with a literal ID — for full related-post cards use a query loop with `meta_query` |
| Taxonomy | Term ID | combine with `{{term_meta}}`/`{{term_list}}` as appropriate |

Dot notation digs into any array/object value, ACF or not
(`includes/class-meta-handler.php`). Always use the **field name**, never the
field label or the `field_xxxxx` key.

## 2. Common patterns

### ACF image (return format: URL)

```html
<!-- wp:generateblocks/media {"uniqueId":"prod2","tagName":"img","styles":{"width":"100%","borderRadius":"0.75rem"},"css":".gb-media-prod2{border-radius:0.75rem;width:100%}","htmlAttributes":{"src":"{{post_meta key:product_image}}","alt":"{{post_title}}"},"className":"gb-media"} -->
<img class="gb-media-prod2 gb-media" src="{{post_meta key:product_image}}" alt="{{post_title}}"/>
<!-- /wp:generateblocks/media -->
```

### ACF link field (return format: array) → CTA button

```html
<!-- wp:generateblocks/element {"uniqueId":"prod3","tagName":"a","styles":{"display":"inline-block","backgroundColor":"#c0392b","color":"#ffffff","padding":"0.75rem 1.5rem","borderRadius":"0.375rem"},"css":".gb-element-prod3{background-color:#c0392b;border-radius:0.375rem;color:#ffffff;display:inline-block;padding:0.75rem 1.5rem}","htmlAttributes":{"href":"{{post_meta key:cta_link.url}}"},"className":"gb-element"} -->
<a class="gb-element-prod3 gb-element" href="{{post_meta key:cta_link.url}}">
    <!-- wp:generateblocks/text {"uniqueId":"prod4","tagName":"span","content":"{{post_meta key:cta_link.title}}","styles":{},"css":"","className":"gb-text"} -->
    <span class="gb-text-prod4 gb-text">{{post_meta key:cta_link.title}}</span>
    <!-- /wp:generateblocks/text -->
</a>
<!-- /wp:generateblocks/element -->
```

### Price with prefix text

Mix static text and tags freely inside one text block's content:

```json
"content":"From ${{post_meta key:price}} / month"
```

### Hide the wrapper when the field is empty

Free: `:empty` on the block's own selector (allowed exception):

```json
"css":".gb-text-prod5:empty{display:none}.gb-text-prod5{color:#5c5c5c}"
```

Pro: a Conditions post on Post Meta — see `conditions.md`.

## 3. Beyond posts: options, terms, users (Pro tags)

```
{{option key:company_phone}}                ← ACF options page field
{{option key:footer_settings.address}}      ← options group subfield
{{term_meta key:category_color}}            ← ACF field on the queried term
{{user_meta key:job_title}}                 ← ACF field on the user
{{user_meta key:job_title|id:7}}            ← specific user
```

Capability limits apply for visitors: options are whitelisted +
ACF-detected keys (Pro 2.7 also allow-lists `option` keys per-user at save
time); `_`-prefixed (protected) meta never resolves publicly. Since free
2.4, dot-path lookups that dereference into a **non-public post**
(draft/private/password-protected) return empty, and
`post_password`/`user_pass`-type keys are always blocked. If a tag works
logged-in but not logged-out, this is why.

## 4. Looping an ACF repeater (Pro)

The Query block's Pro `queryType:"post_meta"` loops any array meta — which is
exactly what an ACF repeater is. Structure is the normal query family:

```html
<!-- wp:generateblocks/query {"uniqueId":"team1","tagName":"section","styles":{"paddingTop":"4rem","paddingBottom":"4rem"},"css":".gb-query-team1{padding-bottom:4rem;padding-top:4rem}","queryType":"post_meta","paginationType":"standard","query":{"meta_key_id":"current","meta_key":"team_members","posts_per_page":-1},"className":"gb-query"} -->
<section class="gb-query-team1 gb-query">
    <!-- wp:generateblocks/looper {"uniqueId":"team2","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"repeat(3,minmax(0,1fr))","gap":"2rem","@media (max-width:767px)":{"gridTemplateColumns":"1fr"}},"css":".gb-looper-team2{display:grid;gap:2rem;grid-template-columns:repeat(3,minmax(0,1fr))}@media (max-width:767px){.gb-looper-team2{grid-template-columns:1fr}}","className":"gb-looper"} -->
    <div class="gb-looper-team2 gb-looper">
        <!-- wp:generateblocks/loop-item {"uniqueId":"team3","tagName":"div","styles":{"backgroundColor":"#f5f5f3","borderRadius":"1rem","padding":"2rem"},"css":".gb-loop-item-team3{background-color:#f5f5f3;border-radius:1rem;padding:2rem}","className":"gb-loop-item"} -->
        <div class="gb-loop-item-team3 gb-loop-item">
            <!-- wp:generateblocks/text {"uniqueId":"team4","tagName":"h3","content":"{{loop_item key:name}}","styles":{"fontSize":"1.25rem"},"css":".gb-text-team4{font-size:1.25rem}","className":"gb-text"} -->
            <h3 class="gb-text-team4 gb-text">{{loop_item key:name}}</h3>
            <!-- /wp:generateblocks/text -->
            <!-- wp:generateblocks/text {"uniqueId":"team5","tagName":"p","content":"{{loop_item key:role|fallback:Team member}}","styles":{"color":"#5c5c5c"},"css":".gb-text-team5{color:#5c5c5c}","className":"gb-text"} -->
            <p class="gb-text-team5 gb-text">{{loop_item key:role|fallback:Team member}}</p>
            <!-- /wp:generateblocks/text -->
        </div>
        <!-- /wp:generateblocks/loop-item -->
    </div>
    <!-- /wp:generateblocks/looper -->
</section>
<!-- /wp:generateblocks/query -->
```

Key points:

- `query.meta_key` = repeater field name; `meta_key_id:"current"` reads it
  from the current post (or pass a post ID; for an options-page repeater use
  `queryType:"option"` with the option key).
- Inside the loop, **`{{loop_item key:subfield}}`** reads the current row;
  `{{loop_index}}` numbers rows. The post tags (`{{post_title}}` etc.) still
  refer to the parent post.
- Nested row values: `{{loop_item key:image.url}}` for an image subfield with
  array return.
- Pagination works (`posts_per_page` + array slicing) but for repeaters you
  usually want `-1`.

## 5. ACF fields in query filters

`meta_query` works with ACF values like any meta:

```json
"query":{"post_type":"property","posts_per_page":9,"meta_query":[{"key":"bedrooms","value":"3","compare":">="}],"orderby":"meta_value_num","meta_key":"price","order":"ASC"}
```

(ACF stores scalar field values as plain post meta — text/number/select
filter cleanly; relationship/repeater fields store serialized data and don't
`meta_query` well.)

## 6. Other field providers

- **Meta Box / Pods / custom `register_post_meta`** — scalar values stored as
  real post meta render with `{{post_meta key:...}}` (the free meta handler
  reads raw meta; dot notation digs into arrays). No return-format magic —
  what's in the DB is what renders. Caveats: Meta Box has **no first-class
  integration** (no field picker; cloneable/group fields store shapes that
  may not dot-traverse cleanly), and fields not exposed to REST won't preview
  in the editor — they still render on the frontend. Native Meta Box support
  is on the GB roadmap (announced for 2026, not shipped as of Pro 2.7).
- **ACF without Pro** — free GB still reads the raw meta value via
  `{{post_meta}}`: scalars are fine; image-ID fields render the ID, not a URL.
  For image fields on free, set ACF return format to URL.
- If a provider stores JSON/serialized arrays, dot notation traverses them the
  same way.

## 7. Troubleshooting

| Symptom | Cause |
|---|---|
| Tag renders the attachment ID instead of a URL | ACF image return format is ID/array → use URL return or `.url` |
| Empty on frontend, value exists in admin | Protected key (`_` prefix), wrong context (term vs post), or capability gating on user/option tags |
| Repeater loop renders nothing | `queryType` not `post_meta`, wrong `meta_key`, meta value isn't an array, or Pro missing |
| Repeater previews blank in editor, works on frontend | Meta key not REST-exposed — editor preview needs REST | 
| `{{loop_item key:x}}` literal text on page | Pro missing (it's a Pro tag) or used outside a looper |
| Field renders raw array text | Pointed at the repeater/group itself — point at a subfield with dot notation |
| ALL tags on the page suddenly empty | 2.4 taint model — post last saved by an untrusted user; trusted re-save fixes it (`dynamic-tags.md` §10) |
| Dot-path into a related post stopped working | The related post is draft/private/password-protected — 2.4 blocks dereferencing non-public posts |
