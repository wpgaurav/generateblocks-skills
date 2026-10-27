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
"gbBlockCondition":"123",
"gbBlockConditionInvert":true
```

- `gbBlockCondition` — the post ID of a **published** condition post, **as a
  string**. Pro registers the attribute as `type: string`; an integer fails type
  validation and the editor silently drops the condition on its next save, so
  the block then renders for everyone (verified on Pro 2.8.0). The native
  serializer writes both attributes after the block's own and before
  `className`; `gb_serialize.ordered()` does the same and stringifies the ID.
  Unpublished/missing condition → block renders normally (fail-open).
- `gbBlockConditionInvert` — `true` flips the result (render when the
  condition is NOT met).
- Evaluated on a `render_block` filter
  (`generateblocks-pro/includes/extend/block-conditions.php`) —
  server-side, before output, with `post_id => get_the_ID()`. GB's looper calls
  `the_post()`, so inside a Query Loop a `post_meta` rule reads **the loop
  item's** meta. To condition a card on another record, wrap it in a one-post
  Query Loop (`post__in:[ID]`); its dynamic tags and conditions then both resolve
  against that record.
- The attributes are injected into **every block type** via
  `register_block_type_args`, so they work on core blocks too, not just GB.

> Older docs in this repo showed `"gbBlockCondition":[{"type":"userRole",...}]`
> — that inline-array format does not exist. It's a post ID.

### Available condition rule types (built in the Conditions UI)

Stored `type` keys use underscores: `location`, `query_arg`, `user_role`,
`date_time`, `device`, `referrer`, `post_meta`, `user_meta`, `cookie`,
`language`, `options`, `author` (`class-conditions-registry.php`). A hyphenated
type such as `post-meta` matches no registered type and always evaluates false.

Post/user meta operators: Exists, Does Not Exist, Equals, Contains, Does Not
Contain, Greater/Less Than, plus Has Value / Has No Value (Pro 2.5+) — this
is the clean "show only if ACF field is filled" answer.

### Stored rule shape (`_gb_conditions` meta)

```json
{"logic":"AND","groups":[
  {"logic":"AND","conditions":[
    {"type":"post_meta","rule":"custom","operator":"equals","value":"bf_active|1"},
    {"type":"post_meta","rule":"custom","operator":"has_value","value":"black_friday_deal"}]},
  {"logic":"OR","conditions":[
    {"type":"post_meta","rule":"custom","operator":"no_value","value":"bf_expires"},
    {"type":"post_meta","rule":"custom","operator":"contains","value":"bf_expires|2026"}]}]}
```

Top-level `logic` combines groups; each group's `logic` combines its conditions.
A custom meta rule's `value` is `meta_key|comparison`. Date rules use
`{"type":"date_time","rule":"current_date","operator":"before|after|between","value":"2026-11-27 00:00:00"}`
(`between` takes `start, end`), in the site timezone. Before referencing a new
record from blocks, test it with
`GenerateBlocks_Pro_Conditions::show( $rules, array( 'post_id' => ID ) )`
against one record that should pass and one that should fail.

### Workflow for "show this block only to logged-in admins"

1. Create a condition post: rule `User Role is administrator`. Publish. Note ID.
2. Add to the target block's JSON: `"gbBlockCondition":"456"`.
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
