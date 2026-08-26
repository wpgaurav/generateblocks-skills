---
title: Conditional display (GB Pro Conditions + alternatives)
description: Block-level conditions (gblocks_condition CPT + gbBlockCondition), form field conditions, menu item conditions, and free-plugin alternatives including GeneratePress Elements display rules.
---

# Conditional Display

Four different systems show/hide things conditionally. Pick by scope:

| Scope | System | Requires |
|---|---|---|
| One block, any rule type | **GB Pro Block Conditions** | Pro 2.4+ |
| Form field based on another field | **Form field `conditions`** | Pro 2.6+ |
| WP menu item | **Menu item conditions** | Pro 2.4+ |
| Whole template/section placement | **GeneratePress Elements display rules** | GP Premium |
| Device-only visibility | CSS media queries | free |

## 1. GB Pro Block Conditions (the real model)

**A condition is a saved post, not inline rules.** Conditions live in the
`gblocks_condition` CPT — singular; the REST base is `gblocks-conditions`
(Dashboard → GenerateBlocks → Conditions) — where you build the rule set in
a UI. A block then references the condition post by ID:

```json
"gbBlockCondition":123,
"gbBlockConditionInvert":false
```

- `gbBlockCondition` — integer post ID of a **published** condition post.
  Unpublished/missing condition → block renders normally (fail-open).
- `gbBlockConditionInvert` — `true` flips the result (render when the
  condition is NOT met).
- Evaluated on a `render_block` filter
  (`generateblocks-pro/includes/extend/block-conditions.php:26`) —
  server-side, before output. Loop-aware (current post context is passed).
- The attributes are injected into **every block type** via
  `register_block_type_args`, so they work on core blocks too, not just GB.

> Older docs in this repo showed `"gbBlockCondition":[{"type":"userRole",...}]`
> — that inline-array format does not exist. It's a post ID.

### Available condition rule types (built in the Conditions UI)

`location` (page/post/archive targeting), `query-arg` (URL params), `user-role`,
`date-time` (scheduling), `device` (desktop/tablet/mobile), `referrer`,
`post-meta`, `user-meta`, `cookie`, `language`, `options`, `author`
(`generateblocks-pro/includes/conditions/conditions/`).

Post/user meta operators: Exists, Does Not Exist, Equals, Contains, Does Not
Contain, Greater/Less Than, plus Has Value / Has No Value (Pro 2.5+) — this
is the clean "show only if ACF field is filled" answer.

### Workflow for "show this block only to logged-in admins"

1. Create a condition post: rule `User Role is administrator`. Publish. Note ID.
2. Add to the target block's JSON: `"gbBlockCondition":456`.
3. Conditions can't be fully hand-authored — the rule set is post meta
   (`_gb_conditions`) managed by the UI. Author the *reference*, build the
   *rules* in the dashboard (or via REST:
   `includes/conditions/class-conditions-rest.php`).

## 2. Form field conditions (Pro 2.6)

Inline on the `form-field` block — this one IS an inline array:

```json
"conditions":[{"field":"topic","operator":"is","value":"support"}]
```

Operators: `is`, `isnot`, `isempty`, `isnotempty`. Hidden fields are excluded
from processing server-side. See `pro-forms.md` §5.

## 3. Menu item conditions (Pro 2.4)

Same Conditions posts, attached to WP menu items
(`includes/extend/menu-item-conditions.php`, attribute `gbMenuItemCondition`).
Configure in Appearance → Menus.

## 4. Free-plugin alternatives

### "Has value" rendering — automatic

A dynamic tag that resolves to empty renders empty. A text block whose entire
content is one empty tag outputs an empty element — usually harmless. If an
empty wrapper would break layout (e.g. a styled badge), prefer the `:empty`
CSS pseudo-class in the block's own `css`:

```json
"css":".gb-text-badge1:empty{display:none}.gb-text-badge1{...}"
```

(`:empty` on the block's own selector is allowed — it's not a descendant
selector.)

### Device visibility — media queries

```json
"styles":{"display":"none","@media (max-width:767px)":{"display":"block"}}
```

### Template-level conditions — GeneratePress Elements

When the user runs GeneratePress + GP Premium (true for gauravtiwari.org),
section-level "show on these pages / categories / user states" belongs in a
**GP Element with Display Rules**, not in block conditions. See
`template-authoring.md`.

## 5. Decision shortcuts

- "Hide price if field empty" → `:empty` CSS or accept empty render (free).
- "Members-only section" → Pro condition post (User Role) or GP Element
  display rules if it's a whole template section.
- "Different hero on mobile" → two blocks + media-query display toggling
  (free), or device condition (Pro) to avoid rendering both.
  Note: CSS hiding still ships the markup; Pro conditions remove it
  server-side — better for heavy sections.
- "Seasonal banner" → Pro condition (Date/Time) — it has scheduling.
