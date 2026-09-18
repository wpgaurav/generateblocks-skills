---
title: Local styling by default; shared styling by request
description: Prompt once before introducing shared Global Styles or Design Tokens, then follow the user's explicit choice.
---

# Styling scope

Default to local block `styles` and matching compiled `css`. Shared Global
Styles and Design Tokens are optional, even for repeated components, full sites,
conversions, and performance work.

When a build or conversion introduces styling and the user has not already
chosen its scope, prompt once:

> Would you like shared Global Styles, Design Tokens, or both for reuse across
> pages? Otherwise I'll keep this layout's styling local.

Offer local styling as the default. Explain the benefit and that shared edits
can affect other pages. Continue independent work and local styling while
waiting; no answer does not authorize shared styling. If the user already
explicitly requested the shared features for this task, proceed within that
scope without asking again. A request for Global Styles alone does not also
request new tokens, and vice versa.

Before explicit opt-in, do not:

- create, import, or modify shared Global Style or Design Token records;
- promote local declarations into a shared system;
- newly assign `globalClasses` or introduce token dependencies in generated
  blocks as a shared styling strategy;
- import a pattern or run a starter that adds shared styles/tokens implicitly.

You may inspect existing styles/tokens, inherit the theme's normal styling, and
preserve references already present in the source or target. A targeted edit
does not require removing an existing shared system. Keep new styling local
unless the user chooses otherwise.

An ordinary request to build a website, convert a design, optimize CSS, or use
the beta is not by itself an opt-in to shared styling. Read-only explanations
and audits do not need this prompt. The beta kit is an explicit shared-system
example, not the default starting point for every layout.
