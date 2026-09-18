---
title: Elementor structure and widget mapping
description: Flatten unnecessary wrappers while retaining the source layout, data, accessibility, and interactions.
---

# Elementor migration map

Read this for unfamiliar widgets or wrapper behavior. The layout skill owns
GenerateBlocks serialization; this map describes source interpretation only.

## Wrapper reduction

An old Elementor hierarchy often resembles:

```text
section → container → column → widget-wrap → widget → widget-container → heading
```

When the wrappers add no separate behavior, the equivalent can be:

```text
section → content rail → heading
```

Keep extra layers when they actually own grid/flex layout, positioning, clipping,
anchor targets, conditional visibility, or script behavior. Modern Elementor
Flexbox Containers differ from old section/column markup: inspect the source
rather than applying one flattening recipe to both.

## Widget choices

| Elementor content | Destination |
|---|---|
| Heading | Text with the original semantic heading level |
| Text editor | Text/core prose; preserve inline links, lists, and emphasis |
| Button, with or without icon | element `a` with Text child; Shape for an icon |
| Real form submit/control | actual form semantics; do not convert it into an anchor |
| Image | Media; core/image when a static caption is required |
| Icon/Icon Box | Shape plus Text in the minimum useful element structure |
| Image Box | Media/Text with a layout-owning element |
| Spacer | remove only when its spacing can be expressed by the owning layout |
| Divider | core/separator or a meaningful local border |
| Gallery/video/audio/embed/table/quote/code | corresponding core block |
| Posts/Loop widget | V2 Query/Looper/Loop-Item; preserve filters, ordering, pagination, and empty state |
| Accordion/toggle | native Pro accordion; a core/details alternative needs equivalent behavior |
| Tabs | native Pro tabs; disclosures are not an equivalent default |
| Carousel/slider | native Pro carousel or the existing approved runtime |
| Forms | retain the working form system or explicitly migrate configuration and integrations |
| Lottie/text-path/custom widget | inspect its actual runtime; disclose unsupported behavior |

Interactive features require the matching layout reference and native editor
serialization. Do not promise functionality from a visual block replacement.

## Measured styles, not class-name guesses

Inspect resolved values for container width, column gap, alignment, typography,
and breakpoints. Names such as `elementor-column-gap-default` do not establish a
universal numeric gap. Use local `styles` and compiled `css` unless the user
explicitly opted in to shared styling.

For a measured two-column layout with 24px gap that stacks at 767px:

```json
{
  "display": "grid",
  "gridTemplateColumns": "repeat(2,minmax(0,1fr))",
  "gap": "24px",
  "@media (max-width:767px)": {"gridTemplateColumns": "1fr"}
}
```

That is a style input, not serialized markup. Preserve deliberate custom
boundaries. “Hidden on tablet” is not automatically `max-width:1024px`, which
also hides on mobile: inspect the source device range before translating it.

Replace icon-font glyphs with the equivalent approved SVG only when the icon
and its accessible label are known. Do not substitute arbitrary decorative icons.

## Completion evidence

Inventory source widgets before migration and reconcile them afterward. Check
content, forms, query results, links, anchors, mobile visibility, and keyboard
behavior. Measure DOM/CSS and timing before claiming performance gains; fewer
wrappers do not prove zero render blocking, faster paint, or reduced CLS.

If source styling is absent, inspect the destination design language. Do not use
a remembered “GeneratePress palette” or a historical GT theme snapshot as current
evidence. Any necessary redesign goes through the layout design-quality gate.
