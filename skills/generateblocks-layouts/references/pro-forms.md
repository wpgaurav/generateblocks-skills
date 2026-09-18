---
title: GB Pro Forms (2.6+)
description: The GenerateBlocks Pro forms system — block hierarchy, field types, validation, conditional fields, integrations (email, webhook, Mailchimp, Kit, MailerLite, ActiveCampaign, Brevo, Turnstile), submissions storage.
---

# GB Pro Forms

Requires **GB Pro 2.6+** (stable since Pro 2.6.1). Forms are an **opt-in
module** — enable under GenerateBlocks → Settings first. A first-class
system: form posts (CPT), form blocks, server-side processing, integrations,
and a submissions table. Spam protection is layered: honeypot, token
freshness, origin check, optional Turnstile.

Since Pro 2.7, editing forms requires the `edit_others_posts` capability
(Editors and above) — adjust via the `generateblocks_form_capability`
filter. Contributors/Authors can no longer author form posts.

## 1. Architecture — how forms actually work

1. A form lives in a **`gblocks_form` post** (Dashboard → GenerateBlocks →
   Forms). Its block content defines the fields; its `_gb_form` post meta
   holds the config (actions, integrations, notifications).
2. The form is embedded anywhere with the tiny **`generateblocks-pro/form-render`**
   block: `{"formId":123}`.
3. On submit, the server **re-derives the field schema from the saved form
   post content** (`includes/form/class-form-schema-builder.php`) — not from
   whatever was posted. Spoofed fields are dropped.
4. Registered actions run: email / confirmation email / webhook / email-signup
   integrations.
5. Submissions are stored in the custom table `wp_gbp_form_submissions`
   (max 500 per form, 180-day retention, GDPR export/erase wired up).

## 2. Recommended workflow

The tested Pro 2.8 REST base is `/wp/v2/gblocks-forms` (hyphenated plural),
not the `gblocks_form` post-type name. Config lives in `meta._gb_form.config`.
Discover the installed post type's `rest_base` before writing. The bundled beta
kit demonstrates native form serialization and a localhost-only mail sink.

**Build the form's processing config in the editor UI; author the field
markup as blocks.** The config (where emails go, integrations, Turnstile) is
post meta you can't express in block markup. So:

1. Create the form post (Forms → Add New) and set actions/integrations in the UI.
2. The field layout inside it is normal block markup — you can hand-author it.
3. Embed with `form-render` wherever needed:

```html
<!-- wp:generateblocks-pro/form-render {"formId":123} /-->
```

(Self-closing — `form-render` has no inner content.)

## 3. Block hierarchy

```
generateblocks-pro/form                  ← <form> wrapper (inside the gblocks_form post)
└── generateblocks-pro/form-field        ← one per field; holds fieldType/fieldName/isRequired
    ├── generateblocks-pro/form-field-label    ← <label>/<legend>, supports icon
    └── generateblocks-pro/form-field-control  ← the <input>/<textarea>/<select>
[submit button: generateblocks/text with tagName:"button" + htmlAttributes {"type":"submit"}]
```

### Attribute schemas (verified against block.json)

**`generateblocks-pro/form`**
`uniqueId, tagName, styles, css, globalClasses, htmlAttributes, showTemplateSelector`

**`generateblocks-pro/form-field`** (ancestor: form)
`uniqueId, styles, css, globalClasses, htmlAttributes, fieldType, tagName, fieldName, isRequired, matchesField, conditions`

- `fieldType` enum: `text`, `email`, `url`, `tel`, `number`, `hidden`,
  `textarea`, `select`, `checkbox`, `radio`, `checkbox-group`
- `fieldName` — the POST key; required, unique within the form
- `isRequired` — boolean, enforced server-side too
- `matchesField` — name of another field whose value must match (confirm-email)
- `conditions` — conditional visibility, see §5

**`generateblocks-pro/form-field-label`** (parent: form-field)
`uniqueId, styles, css, globalClasses, htmlAttributes, tagName, content, icon, iconLocation`

**`generateblocks-pro/form-field-control`** (ancestor: form-field)
`uniqueId, styles, css, globalClasses, htmlAttributes, placeholder, rows, options, checkedValue, defaultValue`

- `options` — array of `{label, value, clientId}` for select / radio / checkbox-group
- `rows` — textarea rows (string)
- `defaultValue` — supports dynamic tags (e.g. prefill `{{user_meta key:user_email}}`)
- Allowed extra `htmlAttributes`: `aria-*`, `data-*`, `autocomplete`,
  `autocapitalize`, `enterkeyhint`, `inputmode`, `min`, `max`, `minlength`,
  `maxlength`, `pattern`, `spellcheck`, `step`, `title`. System-managed ones
  (`name`, `id`, `required`, `type`, `value`, ...) are ignored — set them via
  the proper attributes instead.

**`generateblocks-pro/form-render`** — `formId` (integer) only.

## 4. Server-side validation & sanitization (what you get for free)

| fieldType | Sanitizer |
|---|---|
| text | `sanitize_text_field` |
| email | `sanitize_email` |
| url | `esc_url_raw` |
| textarea | `sanitize_textarea_field` |
| tel / number | custom numeric/tel sanitizers |
| checkbox-group | array-aware |

Plus: 10,000-char max per field, required-field enforcement, honeypot +
rate limiting (`class-form-spam.php`), and optional Cloudflare **Turnstile**
(validated server-side when site key + secret are configured; fail-open when
not configured).

## 5. Conditional fields

`conditions` on a form-field shows/hides it based on another field's live
value. Hidden fields are also **excluded server-side** during processing
(`class-form-processor.php` → `resolve_hidden_fields()`), so users can't
submit values for fields they couldn't see.

```json
"conditions":[{"field":"topic","operator":"is","value":"support"}]
```

Operators: `is`, `isnot`, `isempty`, `isnotempty`.

## 6. Actions & integrations (configured in the form UI)

| Action | Notes |
|---|---|
| **Email** | Up to 10 comma-separated recipients, merge-tag subject/body, Reply-To control, HTML template filter `generateblocks_form_render_email_template` |
| **Confirmation email** | Auto-reply to the submitter with merge tags |
| **Webhook** | POST JSON to a URL, basic auth, retry logic |
| **Mailchimp** | API key + audience + merge field mapping |
| **Kit (ConvertKit)** | v4 API, form + custom field mapping |
| **MailerLite** | API key + group mapping |
| **ActiveCampaign** | API URL + key + list |
| **Brevo** | API key + list |

Merge tags in email subject/body reference submitted fields by `fieldName`.

## 7. Hand-authoring caution

Form blocks follow the same serialization rules as every GB block
(`recovery-rules.md` applies in full). Two extra cautions:

- The exact save output of form-field/control blocks (wrapper divs, generated
  `id`/`for` pairs, aria wiring) is produced by the block save functions —
  when hand-authoring a full form, build one field in the editor first, copy
  its serialized markup as the template, then replicate. Don't guess the
  rendered HTML.
- A submit button is a `generateblocks/text` with `tagName:"button"` and
  `"htmlAttributes":{"type":"submit"}` — not a dedicated block.

## 8. When NOT to use GB forms

- Multi-step forms, file uploads, payments → use a dedicated form plugin
  (the GB system has no uploads or payment actions as of 2.7).
- A bare email-capture embedded in body copy where the site already runs
  another form system — keep one form stack per site.
