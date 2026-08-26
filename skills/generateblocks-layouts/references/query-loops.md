# GenerateBlocks Query Loops Skill

> **Legacy core/query reference.** Use this only when the user explicitly
> requests WordPress `core/query`. For GenerateBlocks V2 Query/Looper/Loop Item,
> use `query-block.md`. Styling examples below predate CSS Mode parity and the
> current anti-slop gate; reuse the query structure, then rebuild styles through
> `css-mode.md`, `responsive.md`, and `design-quality.md`.

Build dynamic, query-based layouts with GenerateBlocks and WordPress Query blocks.

## When to Use This Skill

- Displaying lists of posts, products, or custom post types
- Building blog grids, archives, and related posts sections
- Creating filterable content layouts
- Implementing pagination and load more functionality

## Query Loop Fundamentals

### WordPress Query Block

The WordPress Query block (`core/query`) provides the foundation for dynamic content loops. GenerateBlocks elements style the output.

### Basic Structure

```html
<!-- wp:query {"queryId":1,"query":{...}} -->
<div class="wp-block-query">

    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
        <!-- Individual post template using GenerateBlocks -->
    <!-- /wp:post-template -->

    <!-- Optional: Pagination -->
    <!-- wp:query-pagination /-->

    <!-- Optional: No results -->
    <!-- wp:query-no-results -->
        <!-- Content when no posts found -->
    <!-- /wp:query-no-results -->

</div>
<!-- /wp:query -->
```

## Query Parameters

### Core Query Attributes

```json
{
  "queryId": 1,
  "query": {
    "perPage": 12,
    "pages": 0,
    "offset": 0,
    "postType": "post",
    "order": "desc",
    "orderBy": "date",
    "author": "",
    "search": "",
    "exclude": [],
    "sticky": "",
    "inherit": false,
    "taxQuery": null,
    "parents": []
  }
}
```

### Parameter Reference

| Parameter | Type | Description |
|-----------|------|-------------|
| `perPage` | number | Posts per page (default: site setting) |
| `pages` | number | Max pages to show (0 = unlimited) |
| `offset` | number | Skip first N posts |
| `postType` | string | Post type slug |
| `order` | string | "asc" or "desc" |
| `orderBy` | string | Sort field |
| `author` | string | Author ID(s) |
| `search` | string | Search query |
| `exclude` | array | Post IDs to exclude |
| `sticky` | string | "exclude", "only", or "" |
| `inherit` | boolean | Inherit from main query |
| `taxQuery` | object | Taxonomy filtering |
| `parents` | array | Parent page IDs |

### Order By Options

```json
{
  "orderBy": "date"           // Publication date (default)
  "orderBy": "modified"       // Last modified date
  "orderBy": "title"          // Alphabetical by title
  "orderBy": "author"         // Author name
  "orderBy": "comment_count"  // Most comments
  "orderBy": "menu_order"     // Menu/page order
  "orderBy": "rand"           // Random order
}
```

### Taxonomy Queries

```json
{
  "taxQuery": {
    "category": {
      "terms": [5, 12],
      "operator": "IN"
    }
  }
}
```

#### Operators

| Operator | Description |
|----------|-------------|
| `IN` | Match any term |
| `NOT IN` | Exclude terms |
| `AND` | Match all terms |
| `EXISTS` | Has any term in taxonomy |
| `NOT EXISTS` | Has no terms in taxonomy |

### Multiple Taxonomy Conditions

```json
{
  "taxQuery": {
    "category": {
      "terms": [5],
      "operator": "IN"
    },
    "post_tag": {
      "terms": [10, 20],
      "operator": "IN"
    }
  }
}
```

## Post Template Layouts

### Grid Layout

```html
<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
    <!-- Content -->
<!-- /wp:post-template -->
```

### Flex Layout

```html
<!-- wp:post-template {"layout":{"type":"flex","flexWrap":"wrap"}} -->
    <!-- Content -->
<!-- /wp:post-template -->
```

### List Layout (Default)

```html
<!-- wp:post-template -->
    <!-- Content -->
<!-- /wp:post-template -->
```

### Custom Spacing

```html
<!-- wp:post-template {"style":{"spacing":{"blockGap":"2rem"}}} -->
    <!-- Content -->
<!-- /wp:post-template -->
```

## Common Query Patterns

### Blog Post Grid (3 Columns)

```html
<!-- wp:query {"queryId":1,"query":{"perPage":9,"postType":"post","order":"desc","orderBy":"date"}} -->
<div class="wp-block-query">

    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3},"style":{"spacing":{"blockGap":"2rem"}}} -->

        <!-- wp:generateblocks/text {"uniqueId":"post1","tagName":"a","styles":{"display":"flex","flexDirection":"column","backgroundColor":"#ffffff","border":"1px solid #e5e5e5","borderRadius":"1rem","overflow":"hidden","textDecoration":"none","height":"100%"},"css":".gb-text-post1{display:flex;flex-direction:column;background-color:#ffffff;border:1px solid #e5e5e5;border-radius:1rem;overflow:hidden;text-decoration:none;height:100%;transition:all 0.3s}.gb-text-post1:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,0.12);border-color:#0073aa}"} -->
        <a class="gb-text gb-text-post1">

            <!-- wp:post-featured-image {"isLink":false,"aspectRatio":"16/9"} /-->

            <!-- wp:generateblocks/element {"uniqueId":"post2","tagName":"div","styles":{"padding":"1.5rem","display":"flex","flexDirection":"column","gap":"0.75rem","flex":"1"},"css":".gb-element-post2{padding:1.5rem;display:flex;flex-direction:column;gap:0.75rem;flex:1}"} -->
            <div class="gb-element gb-element-post2">

                <!-- wp:generateblocks/text {"uniqueId":"post3","tagName":"span","styles":{"fontSize":"0.75rem","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.05em","color":"#0073aa"},"css":".gb-text-post3{font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#0073aa}"} -->
                <span class="gb-text gb-text-post3">
                    <!-- wp:post-terms {"term":"category"} /-->
                </span>
                <!-- /wp:generateblocks/text -->

                <!-- wp:post-title {"isLink":false,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700","lineHeight":"1.3"},"color":{"text":"#0a0a0a"}}} /-->

                <!-- wp:post-excerpt {"excerptLength":18,"style":{"color":{"text":"#666666"}}} /-->

                <!-- wp:generateblocks/element {"uniqueId":"post4","tagName":"div","styles":{"marginTop":"auto","display":"flex","justifyContent":"space-between","alignItems":"center","fontSize":"0.875rem","color":"#888888"},"css":".gb-element-post4{margin-top:auto;display:flex;justify-content:space-between;align-items:center;font-size:0.875rem;color:#888888}"} -->
                <div class="gb-element gb-element-post4">
                    <!-- wp:post-date {"format":"M j, Y"} /-->
                </div>
                <!-- /wp:generateblocks/element -->

            </div>
            <!-- /wp:generateblocks/element -->

        </a>
        <!-- /wp:generateblocks/text -->

    <!-- /wp:post-template -->

    <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->

    <!-- wp:query-no-results -->
        <!-- wp:generateblocks/text {"uniqueId":"nores1","tagName":"p","styles":{"textAlign":"center","padding":"3rem","color":"#666666"},"css":".gb-text-nores1{text-align:center;padding:3rem;color:#666666}"} -->
        <p class="gb-text gb-text-nores1">No posts found.</p>
        <!-- /wp:generateblocks/text -->
    <!-- /wp:query-no-results -->

</div>
<!-- /wp:query -->
```

### Featured Post + Grid

```html
<!-- wp:group -->
<div class="wp-block-group">

    <!-- Featured Post (First) -->
    <!-- wp:query {"queryId":1,"query":{"perPage":1,"postType":"post","order":"desc","orderBy":"date"}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->

            <!-- wp:generateblocks/element {"uniqueId":"feat1","tagName":"article","styles":{"display":"grid","gridTemplateColumns":"minmax(0, 1fr) minmax(0, 1fr)","gap":"2rem","marginBottom":"3rem"},"css":".gb-element-feat1{display:grid;grid-template-columns:minmax(0, 1fr) minmax(0, 1fr);gap:2rem;margin-bottom:3rem}@media(max-width:767px){.gb-element-feat1{grid-template-columns:1fr}}"} -->
            <article class="gb-element gb-element-feat1">
                <!-- wp:post-featured-image {"aspectRatio":"16/10","style":{"border":{"radius":"1rem"}}} /-->

                <!-- wp:generateblocks/element {"uniqueId":"feat2","tagName":"div","styles":{"display":"flex","flexDirection":"column","justifyContent":"center","gap":"1rem"},"css":".gb-element-feat2{display:flex;flex-direction:column;justify-content:center;gap:1rem}"} -->
                <div class="gb-element gb-element-feat2">
                    <!-- wp:post-terms {"term":"category"} /-->
                    <!-- wp:post-title {"style":{"typography":{"fontSize":"2rem","fontWeight":"700"}}} /-->
                    <!-- wp:post-excerpt {"excerptLength":30} /-->
                    <!-- wp:post-date /-->
                </div>
                <!-- /wp:generateblocks/element -->
            </article>
            <!-- /wp:generateblocks/element -->

        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->

    <!-- Remaining Posts Grid (Skip first) -->
    <!-- wp:query {"queryId":2,"query":{"perPage":6,"offset":1,"postType":"post","order":"desc","orderBy":"date"}} -->
    <div class="wp-block-query">
        <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
            <!-- Post card template -->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->

</div>
<!-- /wp:group -->
```

### Category-Filtered Posts

```html
<!-- wp:query {"queryId":3,"query":{"perPage":4,"postType":"post","taxQuery":{"category":{"terms":[5],"operator":"IN"}}}} -->
<div class="wp-block-query">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":4}} -->
        <!-- Post cards -->
    <!-- /wp:post-template -->
</div>
<!-- /wp:query -->
```

### Exclude Category

```html
<!-- wp:query {"queryId":4,"query":{"perPage":12,"postType":"post","taxQuery":{"category":{"terms":[10],"operator":"NOT IN"}}}} -->
<div class="wp-block-query">
    <!-- Posts excluding category ID 10 -->
</div>
<!-- /wp:query -->
```

### Custom Post Type Query

```html
<!-- wp:query {"queryId":5,"query":{"perPage":8,"postType":"product","orderBy":"menu_order","order":"asc"}} -->
<div class="wp-block-query">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":4}} -->

        <!-- wp:generateblocks/text {"uniqueId":"prod1","tagName":"a","styles":{"display":"flex","flexDirection":"column","padding":"1.5rem","backgroundColor":"#ffffff","borderRadius":"1rem","textDecoration":"none"},"css":".gb-text-prod1{display:flex;flex-direction:column;padding:1.5rem;background-color:#ffffff;border-radius:1rem;text-decoration:none;transition:all 0.3s}.gb-text-prod1:hover{box-shadow:0 8px 30px rgba(0,0,0,0.1)}"} -->
        <a class="gb-text gb-text-prod1">
            <!-- wp:post-featured-image {"aspectRatio":"1"} /-->
            <!-- wp:post-title {"isLink":false} /-->
            <!-- Custom fields via dynamic content -->
        </a>
        <!-- /wp:generateblocks/text -->

    <!-- /wp:post-template -->
</div>
<!-- /wp:query -->
```

### Related Posts (Same Category)

```html
<!-- wp:query {"queryId":6,"query":{"perPage":3,"postType":"post","exclude":[{"current":true}],"taxQuery":{"category":{"terms":[],"include":["current"]}}}} -->
<div class="wp-block-query">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
        <!-- Related post cards -->
    <!-- /wp:post-template -->
</div>
<!-- /wp:query -->
```

**Note:** Related posts with "current" requires GenerateBlocks Pro or custom filtering.

## Advanced Query Patterns

### Posts with Main + Sidebar

```html
<!-- wp:generateblocks/element {"uniqueId":"layout1","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"1fr 300px","gap":"3rem"},"css":".gb-element-layout1{display:grid;grid-template-columns:1fr 300px;gap:3rem}@media(max-width:1024px){.gb-element-layout1{grid-template-columns:1fr}}"} -->
<div class="gb-element gb-element-layout1">

    <!-- Main Content -->
    <!-- wp:generateblocks/element {"uniqueId":"main1","tagName":"main"} -->
    <main class="gb-element">

        <!-- wp:query {"queryId":7,"query":{"perPage":10,"postType":"post"}} -->
        <div class="wp-block-query">
            <!-- wp:post-template {"style":{"spacing":{"blockGap":"2rem"}}} -->
                <!-- Post list items -->
            <!-- /wp:post-template -->

            <!-- wp:query-pagination /-->
        </div>
        <!-- /wp:query -->

    </main>
    <!-- /wp:generateblocks/element -->

    <!-- Sidebar -->
    <!-- wp:generateblocks/element {"uniqueId":"side1","tagName":"aside","styles":{"position":"sticky","top":"2rem","alignSelf":"start"},"css":".gb-element-side1{position:sticky;top:2rem;align-self:start}@media(max-width:1024px){.gb-element-side1{position:static}}"} -->
    <aside class="gb-element gb-element-side1">
        <!-- Sidebar widgets -->
    </aside>
    <!-- /wp:generateblocks/element -->

</div>
<!-- /wp:generateblocks/element -->
```

### Masonry-Style Grid

```html
<!-- wp:query {"queryId":8,"query":{"perPage":12,"postType":"post"}} -->
<div class="wp-block-query">

    <!-- wp:generateblocks/element {"uniqueId":"masonry1","tagName":"div","styles":{"columnCount":"3","columnGap":"1.5rem"},"css":".gb-element-masonry1{column-count:3;column-gap:1.5rem}@media(max-width:1024px){.gb-element-masonry1{column-count:2}}@media(max-width:767px){.gb-element-masonry1{column-count:1}}"} -->
    <div class="gb-element gb-element-masonry1">

        <!-- wp:post-template -->

            <!-- wp:generateblocks/element {"uniqueId":"masonry2","tagName":"article","styles":{"breakInside":"avoid","marginBottom":"1.5rem","backgroundColor":"#ffffff","borderRadius":"1rem","overflow":"hidden"},"css":".gb-element-masonry2{break-inside:avoid;margin-bottom:1.5rem;background-color:#ffffff;border-radius:1rem;overflow:hidden}"} -->
            <article class="gb-element gb-element-masonry2">
                <!-- wp:post-featured-image /-->
                <!-- wp:generateblocks/element {"uniqueId":"masonry3","tagName":"div","styles":{"padding":"1.5rem"},"css":".gb-element-masonry3{padding:1.5rem}"} -->
                <div class="gb-element gb-element-masonry3">
                    <!-- wp:post-title /-->
                    <!-- wp:post-excerpt /-->
                </div>
                <!-- /wp:generateblocks/element -->
            </article>
            <!-- /wp:generateblocks/element -->

        <!-- /wp:post-template -->

    </div>
    <!-- /wp:generateblocks/element -->

</div>
<!-- /wp:query -->
```

### Horizontal Scroll Cards

```html
<!-- wp:query {"queryId":9,"query":{"perPage":8,"postType":"post"}} -->
<div class="wp-block-query">

    <!-- wp:generateblocks/element {"uniqueId":"scroll1","tagName":"div","styles":{"display":"flex","gap":"1.5rem","overflowX":"auto","paddingBottom":"1rem","scrollSnapType":"x mandatory"},"css":".gb-element-scroll1{display:flex;gap:1.5rem;overflow-x:auto;padding-bottom:1rem;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch}.gb-element-scroll1::-webkit-scrollbar{height:6px}.gb-element-scroll1::-webkit-scrollbar-thumb{background:#ccc;border-radius:3px}"} -->
    <div class="gb-element gb-element-scroll1">

        <!-- wp:post-template -->

            <!-- wp:generateblocks/element {"uniqueId":"scroll2","tagName":"article","styles":{"minWidth":"300px","scrollSnapAlign":"start","flexShrink":"0"},"css":".gb-element-scroll2{min-width:300px;scroll-snap-align:start;flex-shrink:0}"} -->
            <article class="gb-element gb-element-scroll2">
                <!-- Card content -->
            </article>
            <!-- /wp:generateblocks/element -->

        <!-- /wp:post-template -->

    </div>
    <!-- /wp:generateblocks/element -->

</div>
<!-- /wp:query -->
```

## Pagination Styles

### Standard Pagination

```html
<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"0.5rem"}}} -->
    <!-- wp:query-pagination-previous {"label":"← Previous"} /-->
    <!-- wp:query-pagination-numbers /-->
    <!-- wp:query-pagination-next {"label":"Next →"} /-->
<!-- /wp:query-pagination -->
```

### Simple Prev/Next

```html
<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
    <!-- wp:query-pagination-previous /-->
    <!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->
```

### Styled Pagination

```html
<!-- wp:generateblocks/element {"uniqueId":"pag1","tagName":"nav","styles":{"display":"flex","justifyContent":"center","gap":"0.5rem","marginTop":"3rem"},"css":".gb-element-pag1{display:flex;justify-content:center;gap:0.5rem;margin-top:3rem}.gb-element-pag1 .wp-block-query-pagination-numbers{display:flex;gap:0.25rem}.gb-element-pag1 a,.gb-element-pag1 span{padding:0.5rem 1rem;border-radius:0.25rem;text-decoration:none}.gb-element-pag1 a{background:#f0f0f0;color:#333}.gb-element-pag1 a:hover{background:#0073aa;color:#fff}.gb-element-pag1 .current{background:#0073aa;color:#fff}"} -->
<nav class="gb-element gb-element-pag1">
    <!-- wp:query-pagination -->
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->
</nav>
<!-- /wp:generateblocks/element -->
```

## Inheriting Main Query

Use `"inherit": true` to inherit the main archive/search query:

```html
<!-- wp:query {"queryId":10,"query":{"inherit":true}} -->
<div class="wp-block-query">
    <!-- Template uses main query (category, tag, search results, etc.) -->
</div>
<!-- /wp:query -->
```

### When to Use Inherit

| Template | Inherit |
|----------|---------|
| Archive templates | `true` |
| Category/tag pages | `true` |
| Search results | `true` |
| Custom page sections | `false` |
| Homepage featured | `false` |

## Custom Query Filters (PHP)

### Modify Query Parameters

```php
<?php
/**
 * Modify query block queries
 */
function theme_modify_query_block( $query, $block, $page ) {
    // Only modify specific query
    if ( isset( $block->context['queryId'] ) && $block->context['queryId'] === 5 ) {
        // Add meta query
        $query['meta_query'] = array(
            array(
                'key'     => 'featured',
                'value'   => '1',
                'compare' => '=',
            ),
        );
    }

    return $query;
}
add_filter( 'query_loop_block_query_vars', 'theme_modify_query_block', 10, 3 );
```

### Add Custom Ordering

```php
<?php
function theme_custom_query_order( $query, $block, $page ) {
    if ( isset( $block->context['queryId'] ) && $block->context['queryId'] === 6 ) {
        $query['meta_key'] = 'popularity';
        $query['orderby']  = 'meta_value_num';
        $query['order']    = 'DESC';
    }

    return $query;
}
add_filter( 'query_loop_block_query_vars', 'theme_custom_query_order', 10, 3 );
```

### Exclude Current Post

```php
<?php
function theme_exclude_current_post( $query, $block, $page ) {
    if ( is_singular() ) {
        $query['post__not_in'] = array( get_the_ID() );
    }

    return $query;
}
add_filter( 'query_loop_block_query_vars', 'theme_exclude_current_post', 10, 3 );
```

## Best Practices

### 1. Use Meaningful Query IDs

Give each query a unique ID to target with filters:

```html
<!-- Blog posts query -->
<!-- wp:query {"queryId":100,"query":{...}} -->

<!-- Related posts query -->
<!-- wp:query {"queryId":101,"query":{...}} -->

<!-- Featured products query -->
<!-- wp:query {"queryId":200,"query":{...}} -->
```

### 2. Optimize Performance

- Limit `perPage` to reasonable numbers
- Use `offset` sparingly (affects performance)
- Consider caching for complex queries
- Avoid `orderBy: "rand"` on large datasets

### 3. Handle Empty States

Always include `query-no-results`:

```html
<!-- wp:query-no-results -->
    <!-- wp:generateblocks/text {"tagName":"p"} -->
    <p class="gb-text">No posts match your criteria. Try adjusting your filters.</p>
    <!-- /wp:generateblocks/text -->
<!-- /wp:query-no-results -->
```

### 4. Responsive Grid Columns

```html
<!-- wp:post-template {"layout":{"type":"grid","columnCount":4}} -->
```

Add CSS overrides:

```css
@media(max-width:1024px){.wp-block-post-template{grid-template-columns:repeat(2, minmax(0, 1fr))!important}}
@media(max-width:767px){.wp-block-post-template{grid-template-columns:1fr!important}}
```

### 5. Lazy Load Images

Featured images lazy load by default. Ensure custom images include:

```html
<img loading="lazy" ... />
```

## Troubleshooting

### Posts Not Showing

1. Check `perPage` is not 0
2. Verify `postType` exists
3. Check taxonomy terms exist
4. Ensure posts are published
5. Check `exclude` array isn't blocking posts

### Wrong Posts Showing

1. Verify `inherit` setting
2. Check taxonomy query operators
3. Look for conflicting PHP filters
4. Verify `offset` value

### Pagination Not Working

1. Ensure `queryId` is unique per page
2. Check `pages` parameter
3. Verify sufficient posts exist
4. Test without custom JavaScript

### Performance Issues

1. Reduce `perPage`
2. Remove `orderBy: "rand"`
3. Simplify taxonomy queries
4. Add caching
5. Check for n+1 query problems in dynamic content
