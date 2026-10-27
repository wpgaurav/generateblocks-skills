<?php
/**
 * The Register Dynamic Tag class file.
 *
 * @package GenerateBlocks\Dynamic_Tags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for registering dynamic tags.
 *
 * @since 1.9.0
 */
class GenerateBlocks_Register_Dynamic_Tag {
	/**
	 * The tags.
	 *
	 * @var array
	 */
	private static $tags = [];

	/**
	 * Constructor.
	 *
	 * @param array $args The arguments.
	 */
	public function __construct( $args ) {
		if ( ! isset( $args['tag'] ) || ! isset( $args['return'] ) || ! isset( $args['title'] ) ) {
			return;
		}

		if ( ! isset( $args['type'] ) ) {
			$args['type'] = 'post';
		}

		if ( ! isset( $args['supports'] ) || ! is_array( $args['supports'] ) ) {
			$args['supports'] = [];
		}

		self::$tags[ $args['tag'] ] = $args;
	}

	/**
	 * Parse options.
	 *
	 * @param string $options_string The options string.
	 * @param string $tag_name The tag name.
	 * @return array
	 */
	public static function parse_options( $options_string, $tag_name ) {
		$pairs  = $options_string ? preg_split( '/(?<!\\\\)\|/', $options_string, -1, PREG_SPLIT_NO_EMPTY ) : [];
		$result = [
			'tag_name' => $tag_name, // Make it so the tag name is available to us in $options.
		];

		if ( empty( $pairs ) ) {
			return $result;
		}

		foreach ( $pairs as $pair ) {
			$pair = str_replace( [ '\\:', '\\|' ], [ ':', '|' ], $pair );

			if ( generateblocks_str_contains( $pair, ':' ) ) {
				list( $key, $value ) = explode( ':', $pair, 2 );
			} else {
				$key = $pair;
				$value = true; // Default value if no colon is present.
			}

			$result[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Get the tags.
	 *
	 * @return array
	 */
	public static function get_tags() {
		return self::$tags;
	}

	/**
	 * Find matches.
	 *
	 * @param string $content The content.
	 * @param array  $availableTags The available tags.
	 * @return array
	 */
	public static function find_matches( $content, $availableTags ) {
		$tag_names = array_map(
			function ( $tag_name ) {
				return preg_quote( $tag_name, '/' );
			},
			array_keys( $availableTags )
		);
		$pattern = '/\{{(' . implode( '|', $tag_names ) . ')(\s+[^}]+)?}}/';
		preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

		return $matches;
	}

	/**
	 * Replace tags.
	 *
	 * @param string $content The content.
	 * @param array  $block The block.
	 * @param Object $instance The block instance.
	 * @return string
	 */
	public static function replace_tags( $content, $block, $instance ) {
		$block_html = ! empty( $block['innerHTML'] )
			? $block['innerHTML']
			: $content;

		if ( ! generateblocks_str_contains( $block_html, '{{' ) ) {
			return $content;
		}

		// Enforce the post-source taint model at the lowest replacement layer, mirroring
		// how legacy v1 attributes gate inside get_content(). The render_block filter gate
		// only covers callers that go through it; direct callers resolving attribute values
		// (e.g. Pro form-field defaultValue) would otherwise leak inside a suppressed frame.
		if (
			class_exists( 'GenerateBlocks_Dynamic_Tag_Security' ) &&
			method_exists( 'GenerateBlocks_Dynamic_Tag_Security', 'should_suppress_dynamic_data' ) &&
			GenerateBlocks_Dynamic_Tag_Security::should_suppress_dynamic_data()
		) {
			return GenerateBlocks_Dynamic_Tag_Security::replace_dynamic_tags_with_empty( $content, $block_html );
		}

		$matches = self::find_matches( $block_html, self::$tags );

		// Resolve every tag first (keeping the per-tag filters and the required-bail below),
		// then substitute the whole set in one pass via replace_tags_in_content(). Resolving
		// before substituting lets each attribute value be escaped exactly once, after all of
		// its tags are in place: a per-tag escape would let one tag's esc_url() strip the
		// `{{ }}` delimiters off a sibling tag still waiting in the same URL attribute and
		// silently drop it — e.g. href="{{post_permalink}}?ref={{post_meta key:x}}".
		$replacements = [];
		$tag_contexts = [];

		foreach ( $matches as $match ) {
			$tag_name = $match[1] ?? '';

			if ( ! isset( self::$tags[ $tag_name ] ) ) {
				continue;
			}

			$data           = self::$tags[ $tag_name ];
			$full_tag       = $match[0];
			$full_tag       = self::maybe_prepend_protocol( $block_html, $full_tag );
			$options_string = isset( $match[2] ) ? ltrim( $match[2], ' ' ) : '';
			$options        = self::parse_options( $options_string, $tag_name );
			$replacement    = $data['return']( $options, $block, $instance );
			$og_replacement = $replacement;
			$supports       = $data['supports'];
			$required       = ! isset( $options['required'] ) || 'false' !== $options['required'];

			/**
			 * Allow developers to filter the replacement.
			 *
			 * @since 2.0.0
			 *
			 * @param string $replacement The replacement.
			 * @param string $full_tag The full tag.
			 * @param mixed  $content The replacement.
			 * @param array  $block The block.
			 * @param Object $instance The block instance.
			 * @param array  $options The options.
			 * @param array  $supports The supports.
			 */
			$replacement = apply_filters(
				'generateblocks_dynamic_tag_replacement',
				$replacement,
				[
					'tag'      => $tag_name,
					'full_tag' => $full_tag,
					'content'  => $content,
					'block'    => $block,
					'instance' => $instance,
					'options'  => $options,
					'supports' => $supports,
				]
			);

			// If this tag is required for the block to render and there is no replacement, bail.
			if ( $required && ! $replacement ) {
				return '';
			}

			/**
			 * Allow developers to filter the content before dynamic tag replacement.
			 *
			 * @since 2.0.0
			 *
			 * @param string $content The content.
			 * @param string $full_tag The full tag.
			 * @param string $tag The tag.
			 * @param mixed  $replacement The replacement.
			 * @param mixed  $og_replacement The original replacement.
			 * @param array  $block The block.
			 * @param Object $instance The block instance.
			 * @param array  $options The options.
			 * @param array  $supports The supports.
			 */
			$content = apply_filters(
				'generateblocks_before_dynamic_tag_replace',
				$content,
				[
					'full_tag'       => $full_tag,
					'tag'            => $tag_name,
					'replacement'    => $replacement,
					'og_replacement' => $og_replacement,
					'block'          => $block,
					'instance'       => $instance,
					'options'        => $options,
					'supports'       => $supports,
				]
			);

			// Keep the historical effective output for duplicate full tags: the first
			// replacement pass substituted every matching occurrence, so later duplicate
			// matches could still run filters but no longer changed rendered output.
			if ( ! array_key_exists( $full_tag, $replacements ) ) {
				$replacements[ $full_tag ] = (string) $replacement;
				$tag_contexts[ $full_tag ] = [
					'tag'      => $tag_name,
					'full_tag' => $full_tag,
					'type'     => isset( $data['type'] ) ? (string) $data['type'] : '',
					'options'  => $options,
					'block'    => $block,
					'instance' => $instance,
				];
			}
		}

		return self::replace_tags_in_content( $content, $replacements, $tag_contexts );
	}

	/**
	 * Replace one or more dynamic tags within rendered block content, escaping each
	 * attribute value by context exactly once.
	 *
	 * Every tag an attribute value holds is resolved together (via a single strtr pass)
	 * before that value is escaped, so a URL attribute carrying more than one tag —
	 * href="{{a}}{{b}}" — no longer loses each tag after the first: a per-tag escape would
	 * run esc_url() on the value while a sibling tag was still an unresolved `{{ }}` literal,
	 * and esc_url() strips the brace delimiters, leaving nothing for the next pass to match.
	 *
	 * Each resolved attribute value is parked behind a placeholder and escaped by context:
	 * URL attributes (see generateblocks_is_url_attribute()) get do_shortcode() + esc_url(),
	 * which strips javascript:/data: schemes; JavaScript / markup execution sinks (inline
	 * event handlers `on*` and `srcdoc`) have their tags stripped to empty, because the
	 * browser HTML-decodes the attribute before the JS engine / nested document parses it,
	 * so no escaping neutralizes a resolved value there. For back-compat, on* handlers in
	 * content whose innermost known source frame carries a saved-GB-version stamp below 2.4
	 * keep resolving via the generic esc_attr() branch until that source post is re-saved.
	 * Everything else strips — a known source with no stamp AND unknown source frames
	 * (widgets, hook-rendered content): legacy resolution requires positive evidence of a
	 * pre-2.4 save, never the absence of one. srcdoc is always stripped. An on* handler may
	 * still opt
	 * back in via the generateblocks_allow_dynamic_data_in_event_handlers filter, at its own
	 * risk. Every other attribute gets esc_attr(), which neutralizes a quote breakout. We
	 * escape here rather than depend on the priority-20 generateblocks_with_escaped_attributes()
	 * pass, which bails when the block has no htmlAttributes and would leave a dynamic URL
	 * attribute unescaped. The placeholder also stops the body replacement from re-expanding a
	 * self-referential token (a replacement value that itself contains a tag) with raw quotes
	 * inside an already-escaped attribute. Text/body occurrences stay raw so dynamic tags that
	 * intentionally output sanitized HTML continue to render. A tag the parser places in
	 * attribute-name/structure position (bare `<div {{tag}}>`, unquoted, or as a name) has no
	 * value context to escape and no supported use, so it is dropped rather than resolved into
	 * a new attribute.
	 *
	 * @since 2.4.0
	 *
	 * @param string $content      The content to replace tags within.
	 * @param array  $replacements Map of exact dynamic tag string => replacement value.
	 * @param array  $tag_contexts Map of exact dynamic tag string => resolved tag context.
	 * @return string
	 */
	public static function replace_tags_in_content( $content, $replacements, $tag_contexts = [] ) {
		if ( ! is_array( $replacements ) || empty( $replacements ) ) {
			return $content;
		}

		// Normalize to [ full_tag => (string) replacement ] and keep only tags actually present
		// in the content, so the strtr passes below stay cheap and never touch unrelated text.
		$present = [];

		foreach ( $replacements as $full_tag => $replacement ) {
			$full_tag = (string) $full_tag;

			if ( '' === $full_tag || ! generateblocks_str_contains( $content, $full_tag ) ) {
				continue;
			}

			$present[ $full_tag ] = (string) $replacement;
		}

		if ( empty( $present ) ) {
			return $content;
		}

		// Without markup (or the HTML API), there is no attribute context to escape. strtr()
		// substitutes every tag in a single pass, matching the longest tag first and never
		// re-scanning inserted text, so one tag's value cannot spawn another tag.
		if ( ! generateblocks_str_contains( $content, '<' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return strtr( $content, $present );
		}

		$processor     = new WP_HTML_Tag_Processor( $content );
		$escaped_by_ph = []; // placeholder => context-escaped attribute value.
		$updated       = false;

		while ( $processor->next_tag() ) {
			foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
				/*
				 * A dynamic-tag delimiter in an attribute *name* only appears when the HTML
				 * parser saw a tag outside a quoted value: bare (`<div {{tag}}>`), unquoted
				 * (`<div x={{tag}}>`), or in name position (`<div {{tag}}="y">`). In each case
				 * the parser exposes the tag (or a fragment such as `{{post_meta` / `key:e}}`)
				 * as a valueless attribute name. There is no value to escape into, and a raw
				 * substitution there could assemble a new attribute or event handler, so drop
				 * the fragment. Removing any fragment that carries the `{{`/`}}` delimiter
				 * breaks the literal apart so the body replacement below can no longer match
				 * it. Quoted values keep their braces in the value (the name stays clean), so
				 * JSON config / data-* attributes are untouched — this only fires on a tag that
				 * leaked into tag structure.
				 */
				if (
					generateblocks_str_contains( $name, '{{' ) ||
					generateblocks_str_contains( $name, '}}' )
				) {
					// remove_attribute() (unlike set_attribute( …, false )) applies no name
					// gate, so it still strips a fragment whose name carries a character the
					// HTML API forbids in a name — e.g. `&` in `key:a&b}}` — leaving no raw tag
					// for the body replacement below to re-expand.
					if ( $processor->remove_attribute( $name ) ) {
						$updated = true;
					}

					continue;
				}

				$value = $processor->get_attribute( $name );

				if ( ! is_string( $value ) ) {
					continue;
				}

				$has_tag = false;

				foreach ( $present as $full_tag => $replacement ) {
					if ( generateblocks_str_contains( $value, $full_tag ) ) {
						$has_tag = true;
						break;
					}
				}

				if ( ! $has_tag ) {
					continue;
				}

				// We will modify this attribute (to a placeholder, or blank it on failure),
				// so the parsed content must be flushed via get_updated_html() below.
				$updated = true;

				/*
				 * Escape by attribute context, here at substitution time — we do NOT rely on
				 * the priority-20 generateblocks_with_escaped_attributes() pass, which bails
				 * when the block carries no htmlAttributes (e.g. hand-authored / imported
				 * markup), leaving a dynamic URL attribute unescaped. Because we only touch
				 * attributes that contain a tag, the priority-20 pass becomes an idempotent
				 * no-op rather than a dependency.
				 */
				if ( generateblocks_is_url_attribute( $name ) ) {
					// URL attributes: do_shortcode() + esc_url() strips javascript:/data: schemes.
					// do_shortcode() runs first because esc_url() would corrupt an unexpanded
					// shortcode. Resolve every tag first so the value is escaped exactly once.
					$escaped = generateblocks_get_escaped_html_attribute( $name, strtr( $value, $present ) );
				} elseif ( self::is_scripting_context_attribute( $name ) ) {
					/*
					 * Inline event handlers (on*) and `srcdoc` are code / markup execution sinks.
					 * The browser HTML-decodes the attribute value BEFORE the JS engine (or the
					 * nested srcdoc document) parses it, so HTML-attribute escaping is undone
					 * before it matters — esc_attr() and even esc_js() are not a complete defense
					 * (a decoded quote breaks out of a double-quoted JS string, and a value handed
					 * to eval()/setTimeout()/new Function() runs as code regardless of escaping).
					 * There is no safe way to place a resolved dynamic value here, so tags are
					 * stripped to empty by default (the handler stays intact, e.g. track('') —
					 * less breaking than removing the whole attribute). Back-compat: ONLY on*
					 * handlers whose innermost known source post carries a saved-GB-version
					 * stamp below the 2.4 gate keep the legacy generic-attribute esc_attr()
					 * resolve, until that source post is re-saved. A source with no stamp and
					 * unknown-source renders (widgets, hook-rendered content) strip — absence
					 * of the stamp is not evidence of a pre-gate save. srcdoc is never eligible
					 * for this gate and always strips.
					 */
					if ( self::is_event_handler_attribute( $name ) ) {
						if ( self::event_handler_dynamic_data_allowed( $name, $value, $present, $tag_contexts ) ) {
							// Opt-in bridge ONLY (default off, event handlers only, never srcdoc):
							// a site keeping a legacy handler alive while it migrates to the
							// data-attribute pattern. esc_js() each resolved VALUE — so its own quotes
							// are backslash-escaped inside the JS string — but NOT the handler itself;
							// running esc_js() over the whole handler would backslash-escape the
							// author's structural quotes and emit a JS SyntaxError. Then esc_attr() the
							// whole for the attribute: the browser decodes the entities before the JS
							// parse, so the handler's own quoting survives. Best-effort, NOT a guarantee
							// (a value inside a double-quoted JS string can still break out).
							$escaped = esc_attr( strtr( $value, array_map( 'esc_js', $present ) ) );
						} elseif (
							class_exists( 'GenerateBlocks_Dynamic_Tag_Security' ) &&
							method_exists( 'GenerateBlocks_Dynamic_Tag_Security', 'current_source_post_predates_event_handler_gate' ) &&
							GenerateBlocks_Dynamic_Tag_Security::current_source_post_predates_event_handler_gate()
						) {
							// Back-compat gate: on* handlers in content stamped by a pre-2.4 save
							// match the generic attribute branch exactly, so existing saved handlers
							// see the same decoded JavaScript value they did before 2.4.
							$escaped = esc_attr( strtr( $value, $present ) );
						} else {
							// Strip (not resolve) every present tag. Use strtr — as the resolve branch
							// does — because it is single-pass, matches the longest tag first, and never
							// re-scans its own output, so overlapping tag names can't partial-match and a
							// removal can't cascade (unlike sequential str_replace()).
							$escaped = esc_attr( strtr( $value, array_fill_keys( array_keys( $present ), '' ) ) );
						}
					} else {
						// srcdoc is never eligible for the bridge or the back-compat gate — a
						// resolved value here is parsed as a full nested HTML document. Always
						// strip (same single-pass strtr rationale as above).
						$escaped = esc_attr( strtr( $value, array_fill_keys( array_keys( $present ), '' ) ) );
					}
				} else {
					// Every other attribute: esc_attr() neutralizes a quote breakout. Resolve
					// every tag first (strtr is single-pass, longest tag first, no re-scan, so a
					// resolved value containing another tag's literal is not re-expanded).
					$escaped = esc_attr( strtr( $value, $present ) );
				}

				// Park the escaped value behind a placeholder: the body pass below can't then
				// re-expand a self-referential token into it, and set_attribute() (which would
				// re-escape) can't double-encode our already-escaped value. The placeholder is a
				// fixed-length md5 token (esc_url()-safe, so it survives set_attribute() on a URL
				// attribute). Keying it on name + value lets identical attributes share one
				// placeholder, and the fixed length stops any placeholder from being a prefix of
				// another — a variable-length counter previously let "…-1" corrupt "…-10".
				$placeholder = '#generateblocks-dynamic-tag-' . md5( $name . '|' . $value );

				// Only mutate to dodge a literal collision with the original content; identical
				// (name, value) attributes intentionally reuse the same placeholder.
				while ( generateblocks_str_contains( $content, $placeholder ) ) {
					$placeholder .= 'x';
				}

				if ( ! $processor->set_attribute( $name, $placeholder ) ) {
					// The HTML API refused the update — set_attribute() rejects an attribute
					// name containing a forbidden character such as `&` or `<`. Remove the
					// attribute outright (remove_attribute() has no such name gate) so the raw
					// tag isn't left behind for the body replacement below to re-expand into a
					// breakout. Blanking via set_attribute() would fail for the same reason.
					$processor->remove_attribute( $name );
					continue;
				}

				$escaped_by_ph[ $placeholder ] = $escaped;
			}
		}

		if ( $updated ) {
			$content = $processor->get_updated_html();
		}

		// Replace remaining (text/body) occurrences in one strtr pass. This MUST run before the
		// placeholders are expanded: attribute occurrences are now placeholders, so an attribute
		// value cannot gain raw quotes here.
		$content = strtr( $content, $present );

		// Expand the placeholders into their context-escaped attribute values. strtr() replaces
		// every placeholder in a single pass, matching the longest key first and never
		// re-scanning inserted text, so no placeholder or escaped value can disturb another.
		if ( $escaped_by_ph ) {
			$content = strtr( $content, $escaped_by_ph );
		}

		return $content;
	}

	/**
	 * Replace a single dynamic tag within rendered block content.
	 *
	 * Thin, back-compat wrapper around replace_tags_in_content(); it remains the documented
	 * single-tag entry point. Prefer replace_tags_in_content() when substituting several tags
	 * at once so every tag sharing one attribute value is escaped together, exactly once.
	 *
	 * @since 2.3.1
	 *
	 * @param string $content     The content to replace the tag within.
	 * @param string $full_tag    The exact dynamic tag string to replace.
	 * @param mixed  $replacement The replacement value.
	 * @return string
	 */
	public static function replace_tag_in_content( $content, $full_tag, $replacement ) {
		$full_tag = (string) $full_tag;

		if ( '' === $full_tag ) {
			return $content;
		}

		return self::replace_tags_in_content( $content, [ $full_tag => (string) $replacement ] );
	}

	/**
	 * Check if an attribute is an inline JavaScript event handler.
	 *
	 * @since 2.4.0
	 *
	 * @param string $name Attribute name.
	 * @return bool
	 */
	private static function is_event_handler_attribute( $name ) {
		return 1 === preg_match( '/^on[a-z]+$/i', (string) $name );
	}

	/**
	 * Check if an attribute is a JavaScript / markup execution sink.
	 *
	 * Inline event handlers (on*) run their value as JavaScript; `srcdoc` is parsed as a
	 * nested HTML document. In both, the browser HTML-decodes the attribute before that
	 * secondary parse, so HTML-attribute escaping does not neutralize the value. Dynamic
	 * tags are stripped from these rather than resolved.
	 *
	 * @since 2.4.0
	 *
	 * @param string $name Attribute name.
	 * @return bool
	 */
	private static function is_scripting_context_attribute( $name ) {
		return self::is_event_handler_attribute( $name ) || 'srcdoc' === strtolower( (string) $name );
	}

	/**
	 * Whether to keep RESOLVING dynamic tags inside an inline event handler attribute.
	 *
	 * @since 2.4.0
	 *
	 * @param string $name         Attribute name.
	 * @param string $value        Raw attribute value.
	 * @param array  $present      Map of full_tag => replacement present in the content.
	 * @param array  $tag_contexts Map of exact dynamic tag string => resolved tag context.
	 * @return bool
	 */
	private static function event_handler_dynamic_data_allowed( $name, $value, $present, $tag_contexts ) {
		$tags = [];

		foreach ( array_keys( $present ) as $full_tag ) {
			if ( generateblocks_str_contains( $value, $full_tag ) ) {
				$tags[] = $full_tag;
			}
		}

		/**
		 * Whether to keep resolving dynamic tags inside an inline event handler (on*) attribute.
		 *
		 * SECURITY: default false. An inline event handler executes its value as JavaScript, and
		 * the browser HTML-decodes the attribute before the JS engine parses it, so NO server-side
		 * escaping (esc_attr/esc_js) fully prevents a resolved value from breaking out — or from
		 * being executed via a string sink such as eval()/setTimeout()/new Function(). Returning
		 * true re-exposes a stored-XSS class and is only intended as a temporary bridge for a site
		 * migrating a legacy handler to the safe pattern (a `data-*` attribute read by a delegated
		 * listener). This does NOT apply to `srcdoc`, which is always stripped.
		 *
		 * @since 2.4.0
		 *
		 * @param bool  $allow   Whether to keep resolving tags in this event handler. Default false.
		 * @param array $context {
		 *     @type string $attribute Attribute name.
		 *     @type array  $tags      Full dynamic tags present in the attribute value.
		 *     @type array  $contexts  Resolved tag contexts.
		 * }
		 */
		return (bool) apply_filters(
			'generateblocks_allow_dynamic_data_in_event_handlers',
			false,
			[
				'attribute' => $name,
				'tags'      => $tags,
				'contexts'  => $tag_contexts,
			]
		);
	}

	/**
	 * Maybe prepend the protocol to our dynamic tag.
	 * Some core blocks automatically prepend the protocol to URLs, so we need to account for that.
	 * This function checks if the protocol is already prepended and if so, prepends it to the tag so the entire thing is replaced.
	 *
	 * @param string $content The content.
	 * @param string $tag The tag.
	 * @return string
	 */
	public static function maybe_prepend_protocol( $content, $tag ) {
		if ( generateblocks_str_contains( $content, 'http://' . $tag ) ) {
			$tag = 'http://' . $tag;
		}

		if ( generateblocks_str_contains( $content, 'https://' . $tag ) ) {
			$tag = 'https://' . $tag;
		}

		return $tag;
	}


	/**
	 * Get the details of a specific registered tag.
	 *
	 * @param string $tag The dynamic tag used for lookup.
	 * @return array|null The tag details or null if not found.
	 */
	public static function get_tag_details( $tag ) {
		return self::$tags[ $tag ] ?? null;
	}
}
