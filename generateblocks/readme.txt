=== GenerateBlocks ===
Contributors: edge22
Donate link: https://generatepress.com
Tags: blocks, gutenberg, editor, page builder, posts
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 2.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A small collection of lightweight WordPress blocks that can accomplish nearly anything.

== Description ==

Add incredible versatility to your editor without bloating it with tons of one-dimensional blocks. With GenerateBlocks, you can learn a handful of blocks deeply and use them to build anything.

[GenerateBlocks](https://generatepress.com/blocks?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) works hand-in-hand with [GeneratePress](https://generatepress.com/theme?utm_source=wp-repo&utm_medium=link&utm_campaign=generateblocks-readme), but is built to work with any theme.

Looking for more features? Check out [GenerateBlocks Pro](https://generatepress.com/blocks?utm_source=wp-repo&utm_medium=link&utm_campaign=readme).

= Container =

Organize your content into rows and sections. The Container block is the foundation of your content, allowing you to design unique sections for your content.

= Grid =

Create advanced layouts with flexible grids. The Grid block gives you the ability to create any kind of layout you can imagine.

= Text =

Craft text-rich content with advanced typography. Everything from headings to paragraphs - take full control of your text.

= Button =

Drive conversions with beautiful buttons.

= Query =

Build a list of posts from any post type using advanced query parameters. Query post meta and option fields in GenerateBlocks Pro.

= Image =

Add images to your content to make a visual statement.

= Shape =

Add custom SVG shapes to your pages with ease.

= Dynamic Tags =

Use our powerful dynamic tags to display dynamic content inside your blocks.

* Post title
* Post excerpt
* Post permalink
* Post date
* Featured image
* Post meta
* Author meta
* Comment count
* Comments URL
* Author archives URL
* Author avatar URL
* Term list
* Previous posts URL
* Next posts URL
* Media

In GenerateBlocks Pro, you get additional dynamic tags:

* Archive title
* Archive description
* Site option
* Term meta
* User meta
* Current year
* Site title
* Site tagline
* Loop index number
* Loop item

= Performance =

We take performance seriously. Minimal CSS is generated only for the blocks you need, and our HTML structure is as simple as possible while allowing for maximum flexibility.

= Coding standards =

Built to the highest coding standards for security, stability and future compatibility.

= Fully responsive =

Style your blocks for different screen sizes.

* Desktop: @media (min-width:1025px)
* Desktop & Tablet: @media (min-width:768px)
* Tablet: @media (max-width:1024px) and (min-width:768px)
* Tablet & Mobile: @media (max-width:1024px)
* Mobile: @media (max-width:767px)

In GenerateBlocks Pro, you can create as many custom media queries as you need.

= Documentation =

Check out our [documentation](https://learn.generatepress.com/) for more information on the individual blocks and how to use them.

== Installation ==

There's two ways to install GenerateBlocks.

1. Go to "Plugins > Add New" in your Dashboard and search for: GenerateBlocks
2. Download the .zip from WordPress.org, and upload the folder to the `/wp-content/plugins/` directory via FTP.

In most cases, #1 will work fine and is way easier.

== Frequently Asked Questions ==

= How do I add your blocks to my page? =

* Create a new page or post
* Add a new block, and look for the "GenerateBlocks" category
* Choose your block and start building.
* It's always best to start with a Container block.

= What theme should I use? =

GenerateBlocks was built to work hand-in-hand with [GeneratePress](https://generatepress.com). However, it will work with any theme you choose.

= Where do I report security bugs found in this plugin? =

Please report security bugs found in the source code of the GenerateBlocks plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/3910aedb-3fe0-4221-8b1e-ff4e4aea3495). The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Changelog ==

= 2.5.0 =
* Feature: Redesign the Settings page with the GeneratePress design system
* Feature: Allow the global container width to be managed as a Design Token in GenerateBlocks Pro 2.8.0
* Feature: Add the generateblocks_default_at_rules PHP filter to define the at-rule presets offered by the Styles Builder
* Tweak: Always embed page-local block CSS inline and retire its print-method setting, filters, and regeneration endpoint
* Tweak: Update the color picker with support for OKLCH-based color mixing
* Tweak: Open the Pattern Library from the editor toolbar or Options menu instead of the GenerateBlocks sidebar (requires GenerateBlocks Pro 2.8.0 when Pro is active)
* Tweak: Isolate pattern library previews in a sandboxed frame with its own security policy
* Tweak: Enforce the editor Query Loop posts cap server-side
* Tweak: Keep dismissed event handler notices hidden for the rest of the editing session
* Fix: Pattern library crash in cross-origin isolated editors
* Fix: Re-fetch pattern library data automatically when a host's object cache evicts part of the cached set
* Fix: Detect dynamic data markers written with escaped JSON when validating saves
* Fix: Fully strip suppressed dynamic tags when a link-prefixed copy of the same tag exists
* Fix: Event handler notices not appearing for GenerateBlocks Pro blocks
* Fix: Text and Query Page Numbers blocks losing focus when first inserted
* Fix: PHP fatal error when rendering a third-party dynamic tag registered without a supports key
* Fix: Editor ordering block CSS media queries differently than the frontend
* Fix: @supports and @container rules not keeping their authored order in block CSS
* Fix: Custom property names losing their capital letters in block CSS

= 2.4.1 =
* Fix: Editor crash when opening the pattern library on WordPress 7.1 with cross-origin isolation enabled

= 2.4.0 =
* Security: Escape dynamic tag replacements inside HTML attributes
* Feature: Add block inspector slot support for the new Editor Access feature in GenerateBlocks Pro
* Feature: Add content-only and read-only support to our blocks
* Tweak: Users without dynamic data permissions can no longer add or save new dynamic data
* Tweak: Dynamic data no longer renders on pages last saved by a user without dynamic data permissions
* Tweak: Dynamic tags no longer resolve inside inline event handler attributes (onclick, onmouseover, etc.)
* Tweak: Add generateblocks_allow_dynamic_data_in_event_handlers filter
* Tweak: Add editor notices when a save disables or re-enables dynamic data
* Tweak: Improve permissions around dynamic data previews, metadata lookups, and query controls
* Tweak: Refresh the dashboard header design
* Tweak: Raise the minimum supported WordPress version to 6.7

= 2.3.0 =
* Security: Harden legacy dynamic URL output
* Feature: Add support for new Dev Tools feature in Pro
* Feature: Add support for direct CSS editing feature in Pro
* Feature: Add Set/Replace media block toolbar button
* Tweak: Improve Image block selection behavior in editor
* Tweak: Disable core Additional CSS field on GenerateBlocks blocks
* Tweak: Add generateblocks_enable_core_additional_css filter

= 2.2.1 =
* Security: Improve dynamic tag replacement security in the editor

= 2.2.0 =
* Security: Improve REST endpoint access control
* Security: Add dynamic tag validation middleware to stop unsafe meta references during save
* Feature: Add `generateblocks.media.imageAttributes` filter to control Media block auto-populated attributes
* Feature: Add grid-auto-columns field to Styles Builder
* Feature: Add grid-auto-rows field to Styles Builder
* Feature: Add grid-template-areas field to Styles Builder
* Feature: Add grid-area field to Styles Builder
* Feature: Add text-wrap field to Styles Builder
* Feature: Add global CSS values (inherit, initial, revert, revert-layer, unset) to SelectControl components
* Fix: Editor sidebar header alignment
* Fix: Search controls input styles in WordPress 6.9
* Fix: Undo behavior in editor when editing styles
* Fix: Unique ID regeneration when pasting a block before its original
* Fix: Block HTML attribute escaping in WordPress 6.9
* Tweak: Show standard icons in all libraries
* Tweak: Show compound selector symbol in Styles Builder list
* Tweak: Enforce template selector sizing overrides

= 2.1.2 =
* Security: Restricted options REST API endpoint access for contributors/editors

= 2.1.1 =
* Security: Remove sensitive values from REST API response for logged in users
* Fix: Disable image links in the editor
* Fix: Parse shortcodes in URL HTML attribute fields
* Fix: Keep existing image size when changing image in block
* Tweak: Improve image size field loading UI

= 2.1.0 =
* Feature: Enable full iframe editor
* Feature: Improve styles builder indicator dot system
* Feature: Add `static` value to the "Position" control
* Feature: Add `aria-label` field to all blocks
* Feature: Add `inline-grid` option
* Feature: Add inherited values as placeholders
* Fix: Missing "current parent" query parameter
* Fix: Fallback preview support in color picker
* Fix: Inability to type some units in the `UnitControl`
* Fix: Block/pattern preview styles
* Fix: IME issues with multi-select component
* Fix: Border sync button alignment in v1 blocks
* Fix: Single comment count option key in dynamic tags
* Fix: Custom at-rule switching in the editor
* Fix: Font family filter in the styles builder
* Fix: `UnitControl` values starting with a dash
* Fix: Image block selection in WP 6.8
* Fix: Dynamic tag parsing all blocks in Container at once
* Fix: Allow 0 as nested post meta key
* Fix: Ensure block style assets load in wp_head
* Fix: Dimension control tab order
* Fix: Conditional loading of instant pagination script
* Tweak: Improve editor performance
* Tweak: Always show popular user meta fields in dropdown
* Tweak: Add support for device visibility feature in Pro
* Tweak: Add `generateblocks_use_v1_blocks` filter
* Tweak: Improve default styles builder selectors/shortcuts
* Tweak: Load permissions earlier in the editor
* Tweak: Improve block keywords
* Tweak: Sanitize block ID attribute value
* Tweak: Allow the `download` attribute to have a value
* Tweak: Add searching notice to styles builder
* Tweak: Add `generateblocks_block_css` filter
* Tweak: Add `generateblocks_process_block_css` action
* Tweak: Add default `alt` tag to images

= 2.0.2 =
* Fix: Image block selection in WP 6.8

= 2.0.1 =
* Fix: WordPress.org zip package issue

= 2.0.0 =
* Security: Prevent logged-in contributors from querying private post content
* New: All blocks re-written from scratch for better performance and control
* New: Version 1 blocks still exist where used and function normally
* New: Version 1 blocks can be enabled by default with simple filter
* New: Element block - replaces the Container and Grid blocks
* New: Element blocks comes with Container and Grid variations in the block inserter
* New: Text block - replaces the Headline and Button blocks
* New: Text blocks comes with Headline, Text, and Button variations in the block inserter
* New: Query block - replaces the Query Loop block
* New: Looper block - replaces the Grid block inside of the Query block
* New: Loop Item block - replaces the Post Template (Container) in the Query block
* New: No Results block - add content that displays if no Query results are found
* New: Page Numbers block - add page numbers for pagination in your Query block
* New: Shape block - add any SVG shape to your pages
* New: Media block - replaces the Image block
* New: Local blocks now have the full styles builder found in GB Pro Global Styles
* New: Local blocks can now be designed at any breakpoint or nested rule
* New: Filter block design options in the editor based on whether they have a value
* New: Filter block design options in the editor based on whether they're inheriting a value
* New: Dynamic tags system. Insert dynamic data anywhere in your blocks
* New: Multiple dynamic tags can be inserted into a single block
* New: --gb-container-width CSS variable for getting the global max-width
* Performance: Local blocks now generate their CSS and HTML as you build in the editor
* Performance: Blocks are output as static HTML/CSS on the frontend for better performance

Full changelog can be found [here](https://generatepress.com/category/changelog/).
