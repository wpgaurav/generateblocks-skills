# GenerateBlocks Responsive Design Skill

> **LEGACY FILE — patterns here predate the hardened serialization rules.**
> Markup examples may use old class order, unsorted CSS, and descendant
> selectors. They also use `max-width:768px` as Mobile and often place queries
> only in `css`; neither is the current native 2.4.1 contract. Use
> `responsive.md` for current patterns; treat this file as historical reference
> only and never copy its markup directly.

Handle responsive design, breakpoints, and device-specific layouts in GenerateBlocks.

## When to Use This Skill

- Building layouts that adapt to different screen sizes
- Configuring breakpoints and responsive behavior
- Implementing mobile-first or desktop-first designs
- Managing visibility and layout changes per device

## Responsive Fundamentals

### GenerateBlocks Breakpoints

GenerateBlocks uses three default breakpoints:

| Device | Breakpoint | Description |
|--------|------------|-------------|
| Desktop | > 1024px | Large screens |
| Tablet | 768px - 1024px | Medium screens |
| Mobile | < 768px | Small screens |

### Breakpoint Configuration

In GenerateBlocks settings (GenerateBlocks > Settings):

```php
<?php
// Filter to customize breakpoints
add_filter( 'generateblocks_breakpoints', function( $breakpoints ) {
    return array(
        'tablet' => 1024,  // Tablet starts below this
        'mobile' => 768,   // Mobile starts below this
    );
} );
```

## Responsive Styles in Blocks

### Desktop-First Approach (Default)

Define base styles for desktop, override for smaller screens:

```html
<!-- wp:generateblocks/element {"uniqueId":"resp1","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"repeat(4, minmax(0, 1fr))","gap":"2rem","padding":"4rem 2rem"},"css":".gb-element-resp1{display:grid;grid-template-columns:repeat(4, minmax(0, 1fr));gap:2rem;padding:4rem 2rem}@media(max-width:1024px){.gb-element-resp1{grid-template-columns:repeat(2, minmax(0, 1fr));gap:1.5rem;padding:3rem 1.5rem}}@media(max-width:768px){.gb-element-resp1{grid-template-columns:1fr;gap:1rem;padding:2rem 1rem}}"} -->
<div class="gb-element gb-element-resp1">
    <!-- Content -->
</div>
<!-- /wp:generateblocks/element -->
```

### Mobile-First Approach

Define base styles for mobile, enhance for larger screens:

```html
<!-- wp:generateblocks/element {"uniqueId":"resp2","tagName":"div","styles":{"display":"flex","flexDirection":"column","gap":"1rem","padding":"1.5rem"},"css":".gb-element-resp2{display:flex;flex-direction:column;gap:1rem;padding:1.5rem}@media(min-width:768px){.gb-element-resp2{flex-direction:row;gap:2rem;padding:2rem}}@media(min-width:1024px){.gb-element-resp2{gap:3rem;padding:3rem}}"} -->
<div class="gb-element gb-element-resp2">
    <!-- Content -->
</div>
<!-- /wp:generateblocks/element -->
```

## Common Responsive Patterns

### Responsive Grid

```html
<!-- wp:generateblocks/element {"uniqueId":"grid1","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"repeat(4, minmax(0, 1fr))","gap":"1.5rem"},"css":".gb-element-grid1{display:grid;grid-template-columns:repeat(4, minmax(0, 1fr));gap:1.5rem}@media(max-width:1024px){.gb-element-grid1{grid-template-columns:repeat(2, minmax(0, 1fr))}}@media(max-width:768px){.gb-element-grid1{grid-template-columns:1fr}}"} -->
<div class="gb-element gb-element-grid1">
    <!-- Grid items -->
</div>
<!-- /wp:generateblocks/element -->
```

### Grid Column Span Adjustments

```html
<!-- Featured card: 2x2 on desktop, 2x1 on tablet, full width on mobile -->
<!-- wp:generateblocks/element {"uniqueId":"feat1","tagName":"div","styles":{"gridColumn":"span 2","gridRow":"span 2"},"css":".gb-element-feat1{grid-column:span 2;grid-row:span 2}@media(max-width:1024px){.gb-element-feat1{grid-column:span 2;grid-row:span 1}}@media(max-width:768px){.gb-element-feat1{grid-column:span 1;grid-row:span 1}}"} -->
<div class="gb-element gb-element-feat1">
    <!-- Featured content -->
</div>
<!-- /wp:generateblocks/element -->
```

### Responsive Flexbox Direction

```html
<!-- wp:generateblocks/element {"uniqueId":"flex1","tagName":"div","styles":{"display":"flex","flexDirection":"row","gap":"2rem","alignItems":"center"},"css":".gb-element-flex1{display:flex;flex-direction:row;gap:2rem;align-items:center}@media(max-width:768px){.gb-element-flex1{flex-direction:column;gap:1.5rem;text-align:center}}"} -->
<div class="gb-element gb-element-flex1">
    <!-- Flex items -->
</div>
<!-- /wp:generateblocks/element -->
```

### Responsive Typography

```html
<!-- wp:generateblocks/text {"uniqueId":"h1001","tagName":"h1","styles":{"fontSize":"3.5rem","lineHeight":"1.1","marginBottom":"1.5rem"},"css":".gb-text-h1001{font-size:3.5rem;line-height:1.1;margin-bottom:1.5rem}@media(max-width:1024px){.gb-text-h1001{font-size:2.75rem}}@media(max-width:768px){.gb-text-h1001{font-size:2rem;margin-bottom:1rem}}"} -->
<h1 class="gb-text gb-text-h1001">Responsive Heading</h1>
<!-- /wp:generateblocks/text -->
```

### Fluid Typography with Clamp

```html
<!-- wp:generateblocks/text {"uniqueId":"fluid1","tagName":"h1","styles":{"fontSize":"clamp(2rem, 5vw, 3.5rem)","lineHeight":"1.1"},"css":".gb-text-fluid1{font-size:clamp(2rem, 5vw, 3.5rem);line-height:1.1}"} -->
<h1 class="gb-text gb-text-fluid1">Fluid Typography</h1>
<!-- /wp:generateblocks/text -->
```

**Clamp formula:** `clamp(min, preferred, max)`
- `min`: Minimum size (e.g., 2rem)
- `preferred`: Viewport-relative size (e.g., 5vw)
- `max`: Maximum size (e.g., 3.5rem)

### Responsive Spacing

```html
<!-- wp:generateblocks/element {"uniqueId":"space1","tagName":"section","styles":{"padding":"6rem 2rem"},"css":".gb-element-space1{padding:6rem 2rem}@media(max-width:1024px){.gb-element-space1{padding:4rem 1.5rem}}@media(max-width:768px){.gb-element-space1{padding:3rem 1rem}}"} -->
<section class="gb-element gb-element-space1">
    <!-- Section content -->
</section>
<!-- /wp:generateblocks/element -->
```

### Fluid Spacing with Clamp

```html
<!-- wp:generateblocks/element {"uniqueId":"fluid2","tagName":"section","styles":{"padding":"clamp(2rem, 8vw, 6rem) clamp(1rem, 4vw, 2rem)"},"css":".gb-element-fluid2{padding:clamp(2rem, 8vw, 6rem) clamp(1rem, 4vw, 2rem)}"} -->
<section class="gb-element gb-element-fluid2">
    <!-- Content with fluid padding -->
</section>
<!-- /wp:generateblocks/element -->
```

## Visibility Controls

### Hide on Specific Devices

```html
<!-- Desktop only (hide on tablet and mobile) -->
<!-- wp:generateblocks/element {"uniqueId":"desk1","tagName":"div","styles":{"display":"block"},"css":".gb-element-desk1{display:block}@media(max-width:1024px){.gb-element-desk1{display:none}}"} -->
<div class="gb-element gb-element-desk1">
    Desktop only content
</div>
<!-- /wp:generateblocks/element -->

<!-- Tablet and up (hide on mobile) -->
<!-- wp:generateblocks/element {"uniqueId":"tab1","tagName":"div","styles":{"display":"block"},"css":".gb-element-tab1{display:block}@media(max-width:768px){.gb-element-tab1{display:none}}"} -->
<div class="gb-element gb-element-tab1">
    Tablet and desktop content
</div>
<!-- /wp:generateblocks/element -->

<!-- Mobile only (hide on tablet and desktop) -->
<!-- wp:generateblocks/element {"uniqueId":"mob1","tagName":"div","styles":{"display":"none"},"css":".gb-element-mob1{display:none}@media(max-width:768px){.gb-element-mob1{display:block}}"} -->
<div class="gb-element gb-element-mob1">
    Mobile only content
</div>
<!-- /wp:generateblocks/element -->
```

### Swap Content by Device

```html
<!-- Desktop navigation -->
<!-- wp:generateblocks/element {"uniqueId":"nav1","tagName":"nav","styles":{"display":"flex"},"css":".gb-element-nav1{display:flex}@media(max-width:1024px){.gb-element-nav1{display:none}}"} -->
<nav class="gb-element gb-element-nav1">
    <!-- Full navigation -->
</nav>
<!-- /wp:generateblocks/element -->

<!-- Mobile hamburger menu -->
<!-- wp:generateblocks/element {"uniqueId":"nav2","tagName":"nav","styles":{"display":"none"},"css":".gb-element-nav2{display:none}@media(max-width:1024px){.gb-element-nav2{display:block}}"} -->
<nav class="gb-element gb-element-nav2">
    <!-- Mobile menu -->
</nav>
<!-- /wp:generateblocks/element -->
```

## Layout Transformations

### Two-Column to Stacked

```html
<!-- wp:generateblocks/element {"uniqueId":"twocol1","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"minmax(0, 1fr) minmax(0, 1fr)","gap":"3rem","alignItems":"center"},"css":".gb-element-twocol1{display:grid;grid-template-columns:minmax(0, 1fr) minmax(0, 1fr);gap:3rem;align-items:center}@media(max-width:768px){.gb-element-twocol1{grid-template-columns:1fr;gap:2rem}}"} -->
<div class="gb-element gb-element-twocol1">

    <!-- wp:generateblocks/element {"uniqueId":"twocol2","tagName":"div"} -->
    <div class="gb-element">
        <!-- Left content -->
    </div>
    <!-- /wp:generateblocks/element -->

    <!-- wp:generateblocks/element {"uniqueId":"twocol3","tagName":"div"} -->
    <div class="gb-element">
        <!-- Right content -->
    </div>
    <!-- /wp:generateblocks/element -->

</div>
<!-- /wp:generateblocks/element -->
```

### Reverse Order on Mobile

```html
<!-- wp:generateblocks/element {"uniqueId":"rev1","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"minmax(0, 1fr) minmax(0, 1fr)","gap":"2rem"},"css":".gb-element-rev1{display:grid;grid-template-columns:minmax(0, 1fr) minmax(0, 1fr);gap:2rem}@media(max-width:768px){.gb-element-rev1{grid-template-columns:1fr}}.gb-element-rev1>:first-child{order:1}@media(max-width:768px){.gb-element-rev1>:first-child{order:2}}.gb-element-rev1>:last-child{order:2}@media(max-width:768px){.gb-element-rev1>:last-child{order:1}}"} -->
<div class="gb-element gb-element-rev1">
    <!-- First child: Image (appears second on mobile) -->
    <!-- Second child: Text (appears first on mobile) -->
</div>
<!-- /wp:generateblocks/element -->
```

### Sidebar to Full Width

```html
<!-- wp:generateblocks/element {"uniqueId":"side1","tagName":"div","styles":{"display":"grid","gridTemplateColumns":"1fr 300px","gap":"3rem"},"css":".gb-element-side1{display:grid;grid-template-columns:1fr 300px;gap:3rem}@media(max-width:1024px){.gb-element-side1{grid-template-columns:1fr}}"} -->
<div class="gb-element gb-element-side1">

    <!-- Main content -->
    <!-- wp:generateblocks/element {"uniqueId":"side2","tagName":"main"} -->
    <main class="gb-element">
        <!-- Content -->
    </main>
    <!-- /wp:generateblocks/element -->

    <!-- Sidebar -->
    <!-- wp:generateblocks/element {"uniqueId":"side3","tagName":"aside","styles":{"position":"sticky","top":"2rem"},"css":".gb-element-side3{position:sticky;top:2rem}@media(max-width:1024px){.gb-element-side3{position:static}}"} -->
    <aside class="gb-element gb-element-side3">
        <!-- Sidebar content -->
    </aside>
    <!-- /wp:generateblocks/element -->

</div>
<!-- /wp:generateblocks/element -->
```

## Responsive Images

### Aspect Ratio Preservation

```html
<!-- wp:generateblocks/media {"uniqueId":"img1","htmlAttributes":{"src":"image.jpg","alt":"Description"},"styles":{"width":"100%","height":"auto","aspectRatio":"16/9","objectFit":"cover"},"css":".gb-media-img1{width:100%;height:auto;aspect-ratio:16/9;object-fit:cover}@media(max-width:768px){.gb-media-img1{aspect-ratio:4/3}}"} -->
<img class="gb-media gb-media-img1" src="image.jpg" alt="Description" />
<!-- /wp:generateblocks/media -->
```

### Responsive Image Sizes

```html
<!-- wp:generateblocks/media {"uniqueId":"img2","htmlAttributes":{"src":"image.jpg","alt":"Description","srcset":"image-400.jpg 400w, image-800.jpg 800w, image-1200.jpg 1200w","sizes":"(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw"},"styles":{"width":"100%","height":"auto"},"css":".gb-media-img2{width:100%;height:auto}"} -->
<img class="gb-media gb-media-img2" src="image.jpg" alt="Description" srcset="image-400.jpg 400w, image-800.jpg 800w, image-1200.jpg 1200w" sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw" />
<!-- /wp:generateblocks/media -->
```

### Background Image Responsive

```html
<!-- wp:generateblocks/element {"uniqueId":"bg1","tagName":"section","styles":{"backgroundImage":"url(hero-desktop.jpg)","backgroundSize":"cover","backgroundPosition":"center"},"css":".gb-element-bg1{background-image:url(hero-desktop.jpg);background-size:cover;background-position:center}@media(max-width:1024px){.gb-element-bg1{background-image:url(hero-tablet.jpg)}}@media(max-width:768px){.gb-element-bg1{background-image:url(hero-mobile.jpg);background-position:top center}}"} -->
<section class="gb-element gb-element-bg1">
    <!-- Content -->
</section>
<!-- /wp:generateblocks/element -->
```

## Responsive Tables

### Scrollable Table

```html
<!-- wp:generateblocks/element {"uniqueId":"table1","tagName":"div","styles":{"overflowX":"auto"},"css":".gb-element-table1{overflow-x:auto;-webkit-overflow-scrolling:touch}"} -->
<div class="gb-element gb-element-table1">
    <!-- wp:table -->
    <!-- Table content -->
    <!-- /wp:table -->
</div>
<!-- /wp:generateblocks/element -->
```

### Stacked Table on Mobile

```css
/* Apply via global styles or theme CSS */
@media(max-width:768px) {
    .responsive-table table,
    .responsive-table thead,
    .responsive-table tbody,
    .responsive-table th,
    .responsive-table td,
    .responsive-table tr {
        display: block;
    }

    .responsive-table thead {
        display: none;
    }

    .responsive-table tr {
        margin-bottom: 1rem;
        border: 1px solid #e5e5e5;
        border-radius: 0.5rem;
    }

    .responsive-table td {
        padding: 0.75rem;
        text-align: left;
    }

    .responsive-table td::before {
        content: attr(data-label);
        font-weight: 600;
        display: block;
        margin-bottom: 0.25rem;
    }
}
```

## Hero Section Responsive

### Full Example

```html
<!-- wp:generateblocks/element {"uniqueId":"hero1","tagName":"section","styles":{"minHeight":"90vh","display":"flex","alignItems":"center","justifyContent":"center","padding":"6rem 2rem","background":"linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)","color":"#ffffff","textAlign":"center"},"css":".gb-element-hero1{min-height:90vh;display:flex;align-items:center;justify-content:center;padding:6rem 2rem;background:linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);color:#ffffff;text-align:center}@media(max-width:1024px){.gb-element-hero1{min-height:80vh;padding:4rem 1.5rem}}@media(max-width:768px){.gb-element-hero1{min-height:auto;padding:3rem 1rem}}"} -->
<section class="gb-element gb-element-hero1">

    <!-- wp:generateblocks/element {"uniqueId":"hero2","tagName":"div","styles":{"maxWidth":"900px"},"css":".gb-element-hero2{max-width:900px}@media(max-width:768px){.gb-element-hero2{max-width:100%}}"} -->
    <div class="gb-element gb-element-hero2">

        <!-- wp:generateblocks/text {"uniqueId":"hero3","tagName":"h1","styles":{"fontSize":"4rem","fontWeight":"900","lineHeight":"1.1","marginBottom":"1.5rem"},"css":".gb-text-hero3{font-size:4rem;font-weight:900;line-height:1.1;margin-bottom:1.5rem}@media(max-width:1024px){.gb-text-hero3{font-size:3rem}}@media(max-width:768px){.gb-text-hero3{font-size:2.25rem;margin-bottom:1rem}}"} -->
        <h1 class="gb-text gb-text-hero3">Build Amazing Websites</h1>
        <!-- /wp:generateblocks/text -->

        <!-- wp:generateblocks/text {"uniqueId":"hero4","tagName":"p","styles":{"fontSize":"1.25rem","opacity":"0.9","marginBottom":"2.5rem","maxWidth":"600px","margin":"0 auto 2.5rem"},"css":".gb-text-hero4{font-size:1.25rem;opacity:0.9;margin-bottom:2.5rem;max-width:600px;margin:0 auto 2.5rem}@media(max-width:768px){.gb-text-hero4{font-size:1.125rem;margin-bottom:2rem}}"} -->
        <p class="gb-text gb-text-hero4">Create stunning layouts without writing code. Flexible, lightweight, and powerful.</p>
        <!-- /wp:generateblocks/text -->

        <!-- wp:generateblocks/element {"uniqueId":"hero5","tagName":"div","styles":{"display":"flex","gap":"1rem","justifyContent":"center","flexWrap":"wrap"},"css":".gb-element-hero5{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap}@media(max-width:768px){.gb-element-hero5{flex-direction:column;gap:0.75rem}}"} -->
        <div class="gb-element gb-element-hero5">

            <!-- wp:generateblocks/text {"uniqueId":"hero6","tagName":"a","htmlAttributes":{"href":"#"},"styles":{"padding":"1rem 2rem","backgroundColor":"#e94560","color":"#ffffff","borderRadius":"0.5rem","textDecoration":"none","fontWeight":"600"},"css":".gb-text-hero6{padding:1rem 2rem;background-color:#e94560;color:#ffffff;border-radius:0.5rem;text-decoration:none;font-weight:600;transition:all 0.3s}.gb-text-hero6:hover{background-color:#d63850;transform:translateY(-2px)}@media(max-width:768px){.gb-text-hero6{padding:0.875rem 1.5rem;width:100%}}"} -->
            <a class="gb-text gb-text-hero6" href="#">Get Started</a>
            <!-- /wp:generateblocks/text -->

            <!-- wp:generateblocks/text {"uniqueId":"hero7","tagName":"a","htmlAttributes":{"href":"#"},"styles":{"padding":"1rem 2rem","backgroundColor":"transparent","color":"#ffffff","border":"2px solid #ffffff","borderRadius":"0.5rem","textDecoration":"none","fontWeight":"600"},"css":".gb-text-hero7{padding:1rem 2rem;background-color:transparent;color:#ffffff;border:2px solid #ffffff;border-radius:0.5rem;text-decoration:none;font-weight:600;transition:all 0.3s}.gb-text-hero7:hover{background-color:#ffffff;color:#1a1a2e}@media(max-width:768px){.gb-text-hero7{padding:0.875rem 1.5rem;width:100%}}"} -->
            <a class="gb-text gb-text-hero7" href="#">Learn More</a>
            <!-- /wp:generateblocks/text -->

        </div>
        <!-- /wp:generateblocks/element -->

    </div>
    <!-- /wp:generateblocks/element -->

</section>
<!-- /wp:generateblocks/element -->
```

## Testing Responsive Designs

### Browser DevTools

1. Open Chrome/Firefox DevTools (F12)
2. Toggle device toolbar (Ctrl/Cmd + Shift + M)
3. Test at specific breakpoints:
   - 1440px (large desktop)
   - 1024px (tablet landscape)
   - 768px (tablet portrait)
   - 375px (mobile)

### Common Device Widths

| Device | Width |
|--------|-------|
| iPhone SE | 375px |
| iPhone 12/13 | 390px |
| iPhone 12/13 Pro Max | 428px |
| iPad | 768px |
| iPad Pro 11" | 834px |
| iPad Pro 12.9" | 1024px |
| Laptop | 1366px |
| Desktop | 1920px |

## Best Practices

### 1. Start with Content

Design content first, then make it responsive. Don't hide important content on mobile.

### 2. Use Relative Units

Prefer `rem`, `em`, `%`, `vw`, `vh` over `px`:

```css
/* Good */
font-size: 1.25rem;
padding: 2rem;
width: 100%;

/* Avoid for most cases */
font-size: 20px;
padding: 32px;
width: 400px;
```

### 3. Test Touch Targets

Ensure buttons and links are at least 44x44px on mobile:

```css
@media(max-width:768px) {
    .button {
        min-height: 44px;
        padding: 12px 24px;
    }
}
```

### 4. Consider Performance

- Use responsive images with srcset
- Lazy load below-fold images
- Consider CSS-only solutions over JavaScript

### 5. Maintain Readability

- Line length: 45-75 characters
- Font size: minimum 16px on mobile
- Adequate contrast and spacing

## Troubleshooting

### Styles Not Applying at Breakpoint

1. Check media query syntax
2. Verify breakpoint values match settings
3. Check for specificity conflicts
4. Use `!important` sparingly for overrides

### Layout Breaking on Specific Device

1. Test at exact breakpoint values
2. Check for content overflow
3. Verify flex/grid container settings
4. Look for fixed widths causing issues

### Images Not Scaling

1. Ensure `max-width: 100%` is set
2. Check for inline width attributes
3. Verify container constraints
4. Test aspect-ratio usage
