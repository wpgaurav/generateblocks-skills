---
title: SVG Icons Reference
description: Shape block patterns and inline SVG techniques for GenerateBlocks
---

# SVG Icons Reference

Three approaches for icons in GenerateBlocks V2:

1. **Shape Block** - `generateblocks/shape` for standalone SVG icons
2. **Inline SVG** - SVG inside text blocks (buttons, links)
3. **Icon Fonts** - CSS icon classes (md-icon-*)

---

## 1. Shape Block (`generateblocks/shape`)

Best for: Standalone icons, decorative elements, complex SVGs.

### Two Approaches

**Approach 1: `styles.svg` object** (for complex SVG styling)

Shape blocks use `styles.svg` for SVG-specific properties (fill, stroke, width, height, color). The plugin generates `.gb-shape-{id} svg{...}` CSS from this object.

```html
<!-- wp:generateblocks/shape {"uniqueId":"icon1","styles":{"display":"inline-flex","svg":{"fill":"currentColor","height":"1.5rem","width":"1.5rem"}},"css":".gb-shape-icon1{display:inline-flex}.gb-shape-icon1 svg{fill:currentColor;height:1.5rem;width:1.5rem}","className":"gb-shape"} -->
<span class="gb-shape-icon1 gb-shape">
    <svg viewBox="0 0 24 24">
        <path d="..."/>
    </svg>
</span>
<!-- /wp:generateblocks/shape -->
```

**Approach 2: Simple styles** (for small inline icons)

Use width/height/color on the wrapper and put SVG attributes inline. No `styles.svg` needed.

```html
<!-- wp:generateblocks/shape {"uniqueId":"check1","styles":{"width":"20px","height":"20px","color":"#10b981"},"css":".gb-shape-check1{color:#10b981;height:20px;width:20px}","className":"gb-shape"} -->
<span class="gb-shape-check1 gb-shape"><svg stroke-linejoin="round" stroke-linecap="round" stroke-width="3" stroke="currentColor" fill="none" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
<!-- /wp:generateblocks/shape -->
```

Both patterns are valid. Use `styles.svg` when you need the plugin to generate separate SVG CSS rules. Use simple styles for quick inline icons.

### CSS Targeting Pattern

```css
/* Wrapper span - layout (alphabetically sorted) */
.gb-shape-icon1 {
    display: inline-flex;
}

/* SVG element - from styles.svg (alphabetically sorted) */
.gb-shape-icon1 svg {
    fill: currentColor;
    height: 1.5rem;
    width: 1.5rem;
}
```

### CSS Variables in Styles

Use unicode escapes for `--` in JSON: `\u002d\u002d`

```json
{
  "styles": {
    "svg": {
      "color": "var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002drose, #F43F5E)"
    }
  }
}
```

### Examples

**Arrow Icon:**
```html
<!-- wp:generateblocks/shape {"uniqueId":"arrow1","styles":{"display":"inline-flex","svg":{"fill":"none","height":"1rem","stroke":"currentColor","width":"1rem"}},"css":".gb-shape-arrow1{display:inline-flex}.gb-shape-arrow1 svg{fill:none;height:1rem;stroke:currentColor;width:1rem}","className":"gb-shape"} -->
<span class="gb-shape-arrow1 gb-shape">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M5 12h14M12 5l7 7-7 7"/>
    </svg>
</span>
<!-- /wp:generateblocks/shape -->
```

**Filled Icon with Color:**
```html
<!-- wp:generateblocks/shape {"uniqueId":"heart1","styles":{"display":"inline-flex","svg":{"fill":"currentColor","height":"2rem","width":"2rem","color":"#c0392b"}},"css":".gb-shape-heart1{display:inline-flex}.gb-shape-heart1 svg{color:#c0392b;fill:currentColor;height:2rem;width:2rem}","className":"gb-shape"} -->
<span class="gb-shape-heart1 gb-shape">
    <svg viewBox="0 0 24 24">
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/>
    </svg>
</span>
<!-- /wp:generateblocks/shape -->
```

**Stroke Icon (Lucide style):**
```html
<!-- wp:generateblocks/shape {"uniqueId":"check1","styles":{"display":"inline-flex","svg":{"fill":"none","height":"1.25rem","stroke":"currentColor","strokeLinecap":"round","strokeLinejoin":"round","strokeWidth":"2.5","width":"1.25rem","color":"#22c55e"}},"css":".gb-shape-check1{display:inline-flex}.gb-shape-check1 svg{color:#22c55e;fill:none;height:1.25rem;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:2.5;width:1.25rem}","className":"gb-shape"} -->
<span class="gb-shape-check1 gb-shape">
    <svg viewBox="0 0 24 24" fill="none">
        <polyline points="20 6 9 17 4 12"/>
    </svg>
</span>
<!-- /wp:generateblocks/shape -->
```

**Icon with Background:**
```html
<!-- wp:generateblocks/shape {"uniqueId":"star1","styles":{"alignItems":"center","backgroundColor":"#f5f5f3","borderRadius":"0.75rem","color":"#c0392b","display":"flex","height":"3rem","justifyContent":"center","width":"3rem","svg":{"fill":"currentColor","height":"1.5rem","width":"1.5rem"}},"css":".gb-shape-star1{align-items:center;background-color:#f5f5f3;border-radius:0.75rem;color:#c0392b;display:flex;height:3rem;justify-content:center;width:3rem}.gb-shape-star1 svg{fill:currentColor;height:1.5rem;width:1.5rem}","className":"gb-shape"} -->
<span class="gb-shape-star1 gb-shape">
    <svg viewBox="0 0 24 24">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="currentColor"/>
    </svg>
</span>
<!-- /wp:generateblocks/shape -->
```

---

## 2. Inline SVG in Text Blocks

Best for: Simple inline icons alongside text (badges, labels). For buttons with icons, use `generateblocks/element` wrapping text + shape blocks instead.

### Badge with Icon

```html
<!-- wp:generateblocks/text {"uniqueId":"badge1","tagName":"span","styles":{"display":"inline-flex","alignItems":"center","gap":"0.375rem","padding":"0.375rem 0.75rem","background":"#16a34a","borderRadius":"2rem","fontSize":"0.75rem","fontWeight":"700","textTransform":"uppercase","letterSpacing":"0.03em","color":"white"},"css":".gb-text-badge1{align-items:center;background:#16a34a;border-radius:2rem;color:white;display:inline-flex;font-size:0.75rem;font-weight:700;gap:0.375rem;letter-spacing:0.03em;padding:0.375rem 0.75rem;text-transform:uppercase}.gb-text-badge1 svg{height:0.75rem;width:0.75rem}","className":"gb-text"} -->
<span class="gb-text-badge1 gb-text"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>Available</span>
<!-- /wp:generateblocks/text -->
```

### Action Label with Arrow (inside a linked card)

```html
<!-- wp:generateblocks/text {"uniqueId":"arrow1","tagName":"span","styles":{"display":"flex","alignItems":"center","gap":"0.5rem","fontSize":"0.875rem","fontWeight":"600","color":"#c0392b","svg":{"height":"1rem","transition":"transform .18s ease","width":"1rem"},".gb-element-card1:hover \u0026 svg":{"transform":"translateX(4px)"},"@media (prefers-reduced-motion:reduce)":{"svg":{"transition":"none"}}},"css":".gb-text-arrow1{align-items:center;color:#c0392b;display:flex;font-size:0.875rem;font-weight:600;gap:0.5rem}.gb-text-arrow1 svg{height:1rem;transition:transform .18s ease;width:1rem}.gb-element-card1:hover .gb-text-arrow1 svg{transform:translateX(4px)}@media (prefers-reduced-motion:reduce){.gb-text-arrow1 svg{transition:none}}","className":"gb-text"} -->
<span class="gb-text-arrow1 gb-text">View details<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
<!-- /wp:generateblocks/text -->
```

**Note:** The parent-hover selector and reduced-motion branch live in
`styles` and the compiled `css`. Replace `card1` with the real post-scoped
parent ID.

### Button with Icon (use Element block)

For buttons with icons, use `generateblocks/element` with inner text + shape
blocks. See `examples/svg/icons.html` for a validated pattern.

---

## 3. Icon Fonts (md-icon-*)

Best for: Quick icons using existing CSS icon font.

### Using Icon Font Class

```html
<!-- wp:generateblocks/element {"uniqueId":"iconbox1","tagName":"div","styles":{"width":"3rem","height":"3rem","display":"flex","alignItems":"center","justifyContent":"center","backgroundColor":"#c0392b","borderRadius":"0.75rem","color":"#ffffff","fontSize":"1.5rem"},"css":".gb-element-iconbox1{width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;background-color:#c0392b;border-radius:0.75rem;color:#ffffff;font-size:1.5rem}","className":"gb-element"} -->
<div class="gb-element-iconbox1 gb-element">
    <i class="md-icon-bolt" aria-hidden="true"></i>
</div>
<!-- /wp:generateblocks/element -->
```

### Icon Font in Link

```html
<!-- wp:generateblocks/text {"uniqueId":"social1","tagName":"a","styles":{"width":"2.5rem","height":"2.5rem","display":"flex","alignItems":"center","justifyContent":"center","backgroundColor":"rgba(255,255,255,0.1)","borderRadius":"50%","color":"#ffffff","fontSize":"1.25rem","textDecoration":"none"},"css":".gb-text-social1{align-items:center;background-color:rgba(255,255,255,0.1);border-radius:50%;color:#ffffff;display:flex;font-size:1.25rem;height:2.5rem;justify-content:center;text-decoration:none;width:2.5rem}","className":"gb-text"} -->
<a class="gb-text-social1 gb-text" href="https://twitter.com/handle" aria-label="Follow on Twitter"><i class="md-icon-twitter" aria-hidden="true"></i></a>
<!-- /wp:generateblocks/text -->
```

---

## Common SVG Icons (Copy-Ready)

### Arrow Right
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
```

### Check
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
```

### Star (Filled)
```html
<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
```

### External Link
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
```

### Bolt/Lightning
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
```

### Code/Terminal
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4m-5-9 3 3-3 3M14 14h3"/></svg>
```

### Search
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
```

### Edit/Pencil
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z M15 5l3 3"/></svg>
```

### Mail
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
```

### Menu/Hamburger
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
```

### Close/X
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
```

### Chevron Down
```html
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
```

---

## SVG Best Practices

1. **Always include `viewBox`** - Enables proper scaling
2. **Use `fill="none"` + `stroke`** for line icons
3. **Use `fill="currentColor"`** to inherit text color
4. **Add `aria-hidden="true"`** for decorative icons
5. **Add descriptive `aria-label`** on parent for functional icons
6. **Remove `width`/`height` from SVG** - Control via CSS
7. **Minify paths** - Remove unnecessary whitespace
8. **Use consistent stroke-width** (1.5 or 2 typically)
