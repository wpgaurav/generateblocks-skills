<?php
/**
 * Handles option changes on plugin updates.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Process option updates if necessary.
 */
class GenerateBlocks_Dynamic_Content {
	/**
	 * Class instance.
	 *
	 * @access private
	 * @var $instance Class instance.
	 */
	private static $instance;

	/**
	 * For the excerpt we need to keep track of ids to prevent infinite loops.
	 *
	 * @var array $source_ids The current post id.
	 */
	private static $source_ids = [];

	/**
	 * Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'generateblocks_defaults', array( $this, 'add_block_defaults' ) );
		add_filter( 'generateblocks_background_image_url', array( $this, 'set_dynamic_background_image' ), 10, 2 );
		add_filter( 'generateblocks_button_count', array( $this, 'update_button_count' ), 10, 3 );
	}

	/**
	 * Get the requested dynamic content.
	 *
	 * @param array    $attributes The block attributes.
	 * @param WP_Block $block Block instance.
	 */
	public static function get_content( $attributes, $block ) {
		$content = '';

		if ( self::should_block_block_renderer_attribute_resolution( $attributes ) ) {
			return '';
		}

		switch ( $attributes['dynamicContentType'] ) {
			case 'post-title':
				$content = self::get_post_title( $attributes );
				break;

			case 'post-excerpt':
				$content = self::get_post_excerpt( $attributes );
				// Once we have the excerpt content we are safe to clear the source ids.
				// By doing so we avoid empty content for subsequent calls.
				self::$source_ids = [];
				break;

			case 'post-date':
				$content = self::get_post_date( $attributes );
				break;

			case 'post-meta':
				$content = self::get_post_meta( $attributes );
				break;

			case 'comments-number':
				$content = self::get_comments_number( $attributes );
				break;

			case 'terms':
				$content = self::get_terms( $attributes );
				break;

			case 'author-meta':
				$content = self::get_author_meta( $attributes );
				break;

			case 'author-email':
				$content = self::get_user_data( self::get_source_author_id( $attributes ), 'user_email' );
				break;

			case 'author-name':
				$content = self::get_user_data( self::get_source_author_id( $attributes ), 'display_name' );
				break;

			case 'author-nickname':
				$content = self::get_user_data( self::get_source_author_id( $attributes ), 'nickname' );
				break;

			case 'author-first-name':
				$content = self::get_user_data( self::get_source_author_id( $attributes ), 'first_name' );
				break;

			case 'author-last-name':
				$content = self::get_user_data( self::get_source_author_id( $attributes ), 'last_name' );
				break;

			case 'pagination-numbers':
				$content = self::get_paginate_links( $attributes, $block );
				break;

			case 'featured-image':
				$content = self::get_dynamic_image( $attributes, $block );
				break;

			case 'caption':
				$content = self::get_image_caption( $attributes, $block );
				break;

			case 'alt-text':
				$content = self::get_image_alt_text( $attributes, $block );
				break;

			case 'image-description':
				$content = self::get_image_description( $attributes, $block );
				break;
		}

		return apply_filters(
			'generateblocks_dynamic_content_output',
			$content,
			$attributes,
			$block
		);
	}

	/**
	 * Get the requested post title.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_post_title( $attributes ) {
		return get_the_title( self::get_source_id( $attributes ) );
	}

	/**
	 * Get the post excerpt
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return string
	 */
	public static function get_post_excerpt( $attributes ) {
		$source_id = self::get_source_id( $attributes );
		$unique_id = $attributes['uniqueId'];

		// This prevents endless loops by not rendering excerpts within themselves.
		if ( ! $source_id || ( isset( self::$source_ids[ $unique_id ] ) && $source_id === self::$source_ids[ $unique_id ] ) ) {
			return '';
		}

		self::$source_ids[ $unique_id ] = $source_id;

		$filter_excerpt_length = function ( $length ) use ( $attributes ) {
			return isset( $attributes['excerptLength'] ) ? $attributes['excerptLength'] : $length;
		};

		add_filter(
			'excerpt_length',
			$filter_excerpt_length,
			100
		);

		if ( isset( $attributes['useDefaultMoreLink'] ) && ! $attributes['useDefaultMoreLink'] ) {
			$filter_more_text = function () use ( $attributes ) {
				if ( empty( $attributes['customMoreLinkText'] ) ) {
					return ' ...';
				}

				return apply_filters(
					'generateblocks_dynamic_excerpt_more_link',
					sprintf(
						' ... <a class="gb-dynamic-read-more" href="%1$s" aria-label="%3$s">%2$s</a>',
						esc_url( get_permalink( get_the_ID() ) ),
						wp_kses_post( $attributes['customMoreLinkText'] ),
						sprintf(
							/* translators: Aria-label describing the read more button */
							_x( 'More on %s', 'more on post title', 'generateblocks' ),
							the_title_attribute( 'echo=0' )
						)
					)
				);
			};

			add_filter(
				'excerpt_more',
				$filter_more_text,
				100
			);
		}

		$excerpt = get_the_excerpt( $source_id );

		if ( isset( $filter_excerpt_length ) ) {
			remove_filter(
				'excerpt_length',
				$filter_excerpt_length,
				100
			);
		}

		if ( isset( $filter_more_text ) ) {
			remove_filter(
				'excerpt_more',
				$filter_more_text,
				100
			);
		}

		return $excerpt;
	}

	/**
	 * Get the requested post date.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_post_date( $attributes ) {
		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return;
		}

		$updated_time = get_the_modified_time( 'U', $id );
		$published_time = get_the_time( 'U', $id ) + 1800;

		$post_date = sprintf(
			'<time class="entry-date published" datetime="%1$s">%2$s</time>',
			esc_attr( get_the_date( 'c', $id ) ),
			esc_html( get_the_date( '', $id ) )
		);

		$is_updated_date = isset( $attributes['dateType'] ) && 'updated' === $attributes['dateType'];

		if ( ! empty( $attributes['dateReplacePublished'] ) || $is_updated_date ) {
			if ( $updated_time > $published_time ) {
				$post_date = sprintf(
					'<time class="entry-date updated-date" datetime="%1$s">%2$s</time>',
					esc_attr( get_the_modified_date( 'c', $id ) ),
					esc_html( get_the_modified_date( '', $id ) )
				);
			} elseif ( $is_updated_date ) {
				// If we're showing the updated date but no updated date exists, don't display anything.
				return '';
			}
		}

		return $post_date;
	}

	/**
	 * Get the requested post meta.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_post_meta( $attributes ) {
		if ( isset( $attributes['metaFieldName'] ) ) {
			$meta_value = get_post_meta( self::get_source_id( $attributes ), $attributes['metaFieldName'], true );
			$value = (
				is_string( $meta_value ) ||
				is_integer( $meta_value ) ||
				is_float( $meta_value )
			) ? $meta_value : '';

			add_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );
			$value = wp_kses_post( $value );
			remove_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );

			$filtered = apply_filters(
				'generateblocks_dynamic_content_post_meta',
				$value,
				self::get_source_id( $attributes ),
				$attributes
			);

			// A filter (e.g. the ACF integration in Pro) can replace the sanitized value with
			// raw field output, so sanitize again when the value changed. Non-string results
			// (ACF image IDs consumed by get_dynamic_image_id()) pass through untouched, and
			// an unchanged value skips the second pass entirely.
			if ( is_string( $filtered ) && $filtered !== $value ) {
				add_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );
				$filtered = wp_kses_post( $filtered );
				remove_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );
			}

			return $filtered;
		}
	}

	/**
	 * Get the requested author meta.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_author_meta( $attributes ) {
		if ( isset( $attributes['metaFieldName'] ) ) {
			$id = self::get_source_id( $attributes );

			if ( ! $id ) {
				return;
			}

			$author_id = get_post_field( 'post_author', $id );

			if ( ! $author_id ) {
				return;
			}

			$value = self::get_user_data( $author_id, $attributes['metaFieldName'] );

			// Author meta is rendered into the page body unescaped by the consumer, so sanitize
			// it here. Mirror get_post_meta(): allow the same iframe embeds, strip scripts/handlers.
			if ( is_string( $value ) ) {
				add_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );
				$value = wp_kses_post( $value );
				remove_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );
			}

			$filtered = apply_filters(
				'generateblocks_dynamic_content_author_meta',
				$value,
				$author_id,
				$attributes
			);

			// Same re-sanitize as get_post_meta(): a filter (e.g. the ACF integration in Pro)
			// can replace the sanitized value with raw field output. Unchanged or non-string
			// values pass through untouched.
			if ( is_string( $filtered ) && $filtered !== $value ) {
				add_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );
				$filtered = wp_kses_post( $filtered );
				remove_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Content', 'expand_allowed_html' ], 10, 2 );
			}

			return $filtered;
		}
	}

	/**
	 * Get the number of comments.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_comments_number( $attributes ) {
		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return;
		}

		if ( ! isset( $attributes['noCommentsText'] ) ) {
			$attributes['noCommentsText'] = __( 'No comments', 'generateblocks' );
		}

		if ( ! post_password_required( $id ) && ( comments_open( $id ) || get_comments_number( $id ) ) ) {
			if ( '' === $attributes['noCommentsText'] && get_comments_number( $id ) < 1 ) {
				return $attributes['noCommentsText'];
			}

			$comments_text = get_comments_number_text(
				$attributes['noCommentsText'],
				! empty( $attributes['singleCommentText'] ) ? $attributes['singleCommentText'] : __( '1 comment', 'generateblocks' ),
				! empty( $attributes['multipleCommentsText'] ) ? $attributes['multipleCommentsText'] : __( '% comments', 'generateblocks' )
			);

			return $comments_text;
		} else {
			return $attributes['noCommentsText'];
		}
	}

	/**
	 * Get a list of terms.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_terms( $attributes ) {
		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return;
		}

		$is_button = isset( $attributes['isButton'] );
		$taxonomy = isset( $attributes['termTaxonomy'] ) ? $attributes['termTaxonomy'] : 'category';
		$terms = get_the_terms( $id, $taxonomy );
		$link_type = isset( $attributes['dynamicLinkType'] ) ? $attributes['dynamicLinkType'] : '';

		if ( is_wp_error( $terms ) ) {
			return;
		}

		$term_items = array();

		foreach ( (array) $terms as $index => $term ) {
			if ( ! isset( $term->name ) ) {
				continue;
			}

			if ( $is_button ) {
				$term_items[ $index ] = array(
					'content' => wp_kses_post( $term->name ),
					'attributes' => array(
						'class' => 'post-term-item post-term-' . $term->slug,
					),
				);
			} else {
				$term_items[ $index ] = sprintf(
					'<span class="post-term-item term-%2$s">%1$s</span>',
					wp_kses_post( $term->name ),
					esc_attr( $term->slug )
				);
			}

			if ( 'term-archives' === $link_type ) {
				$term_link = get_term_link( $term, $taxonomy );

				if ( ! is_wp_error( $term_link ) ) {
					if ( $is_button ) {
						$term_items[ $index ]['attributes']['href'] = esc_url( get_term_link( $term, $taxonomy ) );
					} else {
						$term_items[ $index ] = sprintf(
							'<span class="post-term-item term-%3$s"><a href="%1$s">%2$s</a></span>',
							esc_url( get_term_link( $term, $taxonomy ) ),
							wp_kses_post( $term->name ),
							esc_attr( $term->slug )
						);
					}
				}
			}
		}

		if ( empty( $term_items ) ) {
			return '';
		}

		$sep = isset( $attributes['termSeparator'] ) ? $attributes['termSeparator'] : ', ';
		$term_output = $is_button ? $term_items : implode( $sep, $term_items );

		return $term_output;
	}

	/**
	 * Get the pagination numbers.
	 *
	 * @param array    $attributes The block attributes.
	 * @param WP_Block $block Block instance.
	 */
	public static function get_paginate_links( $attributes, $block ) {
		$page_key = isset( $block->context['generateblocks/queryId'] ) ? 'query-' . $block->context['generateblocks/queryId'] . '-page' : 'query-page';
		$page     = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ]; // phpcs:ignore -- No data processing happening.
		$max_page = isset( $block->context['generateblocks/query']['pages'] ) ? (int) $block->context['generateblocks/query']['pages'] : 0;

		global $wp_query;

		if ( isset( $block->context['generateblocks/inheritQuery'] ) && $block->context['generateblocks/inheritQuery'] ) {
			// Take into account if we have set a bigger `max page`
			// than what the query has.
			$total = ! $max_page || $max_page > $wp_query->max_num_pages ? $wp_query->max_num_pages : $max_page;
			$paginate_args = array(
				'prev_next' => false,
				'total' => $total,
			);
			$links = paginate_links( $paginate_args );
		} else {
			$query_args = apply_filters(
				'generateblocks_query_loop_args',
				GenerateBlocks_Query_Loop::get_query_args( $block, $page ),
				$attributes,
				$block
			);

			$block_query = new WP_Query( $query_args );

			// `paginate_links` works with the global $wp_query, so we have to
			// temporarily switch it with our custom query.
			$prev_wp_query = $wp_query;
			$wp_query      = $block_query; // phpcs:ignore -- No way around overwriting core global.
			$total         = ! $max_page || $max_page > $wp_query->max_num_pages ? $wp_query->max_num_pages : $max_page;

			$paginate_args = array(
				'base'      => '%_%',
				'format'    => "?$page_key=%#%",
				'current'   => max( 1, $page ),
				'total'     => $total,
				'prev_next' => false,
			);

			if ( 1 !== $page ) {
				/**
				 * `paginate_links` doesn't use the provided `format` when the page is `1`.
				 * This is great for the main query as it removes the extra query params
				 * making the URL shorter, but in the case of multiple custom queries is
				 * problematic. It results in returning an empty link which ends up with
				 * a link to the current page.
				 *
				 * A way to address this is to add a `fake` query arg with no value that
				 * is the same for all custom queries. This way the link is not empty and
				 * preserves all the other existent query args.
				 *
				 * @see https://developer.wordpress.org/reference/functions/paginate_links/
				 *
				 * The proper fix of this should be in core. Track Ticket:
				 * @see https://core.trac.wordpress.org/ticket/53868
				 *
				 * TODO: After two WP versions (starting from the WP version the core patch landed),
				 * we should remove this and call `paginate_links` with the proper new arg.
				 */
				$paginate_args['add_args'] = array( 'cst' => '' );
			}

			// We still need to preserve `paged` query param if exists, as is used
			// for Queries that inherit from global context.
			$paged = empty( $_GET['paged'] ) ? null : (int) $_GET['paged']; // phpcs:ignore -- No data processing happening.

			if ( $paged ) {
				$paginate_args['add_args'] = array( 'paged' => $paged );
			}

			$links = paginate_links( $paginate_args );
			$wp_query = $prev_wp_query; // phpcs:ignore -- Restoring core global.
			wp_reset_postdata(); // Restore original Post Data.
		}

		$doc = self::load_html( $links );

		if ( ! $doc ) {
			return;
		}

		$data = array();
		$html_nodes = $doc->getElementsByTagName( '*' );

		foreach ( $html_nodes as $index => $node ) {
			$classes = $node->getAttribute( 'class' ) ? $node->getAttribute( 'class' ) : '';

			if ( $node->getAttribute( 'aria-current' ) ) {
				$classes = str_replace( 'current', 'gb-block-is-current', $classes );
			}

			// phpcs:ignore -- DOMDocument doesn't use snake-case.
			if ( 'span' === $node->tagName || 'a' === $node->tagName ) {
				$data[ $index ]['href'] = $node->getAttribute( 'href' ) ? $node->getAttribute( 'href' ) : '';
				$data[ $index ]['aria-current'] = $node->getAttribute( 'aria-current' ) ? $node->getAttribute( 'aria-current' ) : '';
				$data[ $index ]['class'] = $classes;

				// phpcs:ignore -- DOMDocument doesn't use snake-case.
				foreach ( $node->childNodes as $childNode ) {
					$data[ $index ]['content'] = $doc->saveHTML( $childNode );
				}
			}
		}

		$paginate_links = array_values( $data );
		$link_items = array();

		foreach ( (array) $paginate_links as $index => $link ) {
			$link_items[ $index ] = array(
				'content' => $link['content'],
				'attributes' => array(
					'href' => $link['href'],
					'aria-current' => $link['aria-current'],
					'class' => $link['class'],
				),
			);
		}

		if ( empty( $link_items ) ) {
			return '';
		}

		return $link_items;
	}

	/**
	 * Get the dynamic image.
	 *
	 * @param array    $attributes The block attributes.
	 * @param WP_Block $block Block instance.
	 */
	public static function get_dynamic_image( $attributes, $block ) {
		$id = self::get_dynamic_image_id( $attributes );

		if ( ! $id ) {
			$id = apply_filters( 'generateblocks_dynamic_image_fallback', '', $attributes, $block );

			// If still empty return.
			if ( ! $id ) {
				return;
			}
		}

		$classes = array(
			'gb-image-' . $attributes['uniqueId'],
			isset( $attributes['className'] ) ? $attributes['className'] : '',
		);

		if ( ! empty( $attributes['align'] ) ) {
			$classes[] = 'align' . $attributes['align'];
		}

		$html_attributes = array(
			'id' => isset( $attributes['anchor'] ) ? $attributes['anchor'] : '',
			'class' => implode( ' ', $classes ),
		);

		$parsed_html_attributes = generateblocks_parse_attr( 'image', $html_attributes, $attributes, $block );
		$parsed_html_attributes = array_map( 'trim', $parsed_html_attributes );
		$parsed_html_attributes = array_filter( $parsed_html_attributes );

		if ( ! empty( $attributes['dynamicContentType'] ) ) {
			if ( 'author-avatar' === $attributes['dynamicContentType'] ) {
				$author_id = self::get_source_author_id( $attributes );
				return get_avatar(
					$author_id,
					$attributes['width'],
					'',
					'',
					$parsed_html_attributes
				);
			}
		}

		if ( $id && ! is_numeric( $id ) ) {
			// Our image ID isn't a number - must be a static URL.
			$html_attributes['src'] = $id;

			return sprintf(
				'<img %s />',
				generateblocks_attr( 'image', $html_attributes, $attributes, $block )
			);
		}

		$dynamic_image = wp_get_attachment_image(
			$id,
			isset( $attributes['sizeSlug'] ) ? $attributes['sizeSlug'] : 'full',
			false,
			$parsed_html_attributes
		);

		if ( ! $dynamic_image ) {
			return '';
		}

		return $dynamic_image;
	}

	/**
	 * Get our source ID.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_source_id( $attributes ) {
		$id = get_the_ID();

		if (
			isset( $attributes['dynamicSource'] ) &&
			'current-post' !== $attributes['dynamicSource'] &&
			isset( $attributes['postId'] )
		) {
			$id = absint( $attributes['postId'] );
		}

		$image_content_types = array( 'caption', 'post-title', 'alt-text', 'image-description' );

		if ( isset( $attributes['dynamicContentType'] ) ) {
			if ( in_array( $attributes['dynamicContentType'], $image_content_types ) ) {
				if ( isset( $attributes['dynamicImage'] ) ) {
					$id = $attributes['dynamicImage'];
				} elseif (
					isset( $attributes['postId'] ) &&
					isset( $attributes['postType'] ) &&
					'attachment' === $attributes['postType']
				) {
					// Use the saved post ID if we're working with a static image.
					$id = absint( $attributes['postId'] );
				}
			}
		}

		return apply_filters(
			'generateblocks_dynamic_source_id',
			$id,
			$attributes
		);
	}

	/**
	 * Get the source post author id.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return int|boolean
	 */
	public static function get_source_author_id( $attributes ) {
		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return false;
		}

		$author_id = get_post_field( 'post_author', $id );

		if ( ! $author_id ) {
			return false;
		}

		return $author_id;
	}

	/**
	 * Get the dynamic image ID.
	 *
	 * @param array $attributes The block attributes.
	 */
	public static function get_dynamic_image_id( $attributes ) {
		if ( self::should_block_block_renderer_attribute_resolution( $attributes ) ) {
			return '';
		}

		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return;
		}

		if ( ! empty( $attributes['dynamicContentType'] ) ) {
			if ( 'post-meta' === $attributes['dynamicContentType'] ) {
				$id = self::get_post_meta( $attributes );
			}

			if ( 'featured-image' === $attributes['dynamicContentType'] ) {
				$id = get_post_thumbnail_id( $id );
			}
		}

		return $id;
	}

	/**
	 * Get the dynamic background image url.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return int|boolean
	 */
	public static function get_dynamic_background_image_url( $attributes ) {
		$id = self::get_dynamic_image_id( $attributes );

		if ( ! $id ) {
			return false;
		}

		if ( empty( $attributes['dynamicContentType'] ) ) {
			return;
		}

		if ( $id && ! is_numeric( $id ) ) {
			// Our image ID isn't a number - must be a static URL.
			return $id;
		}

		$url = wp_get_attachment_image_url(
			$id,
			isset( $attributes['bgImageSize'] ) ? $attributes['bgImageSize'] : 'full'
		);

		return apply_filters(
			'generateblocks_dynamic_background_image_url',
			$url,
			$attributes
		);
	}

	/**
	 * Get our dynamic URL.
	 *
	 * @param array  $attributes The block attributes.
	 * @param object $block The block object.
	 */
	public static function get_dynamic_url( $attributes, $block ) {
		if ( self::should_block_block_renderer_attribute_resolution( $attributes ) ) {
			return '';
		}

		$id = self::get_source_id( $attributes );
		$author_id = get_post_field( 'post_author', $id );
		$link_type = isset( $attributes['dynamicLinkType'] ) ? $attributes['dynamicLinkType'] : '';
		$url = '';

		if ( 'single-post' === $link_type ) {
			$url = get_permalink( $id );
		}

		if ( 'single-image' === $link_type ) {
			if ( ! empty( $attributes['dynamicContentType'] ) ) {
				$image_id = self::get_dynamic_image_id( $attributes );

				if ( $image_id && ! is_numeric( $image_id ) ) {
					// Our image ID isn't a number - must be a static URL.
					return is_string( $image_id ) ? esc_url_raw( $image_id ) : '';
				}
			} else {
				$image_id = ! empty( $attributes['mediaId'] ) ? $attributes['mediaId'] : false;
			}

			if ( $image_id ) {
				$url = wp_get_attachment_url( $image_id );
			} elseif ( ! empty( $attributes['mediaUrl'] ) ) {
				$url = $attributes['mediaUrl'];
			}
		}

		if ( isset( $attributes['linkMetaFieldName'] ) ) {
			if ( 'post-meta' === $link_type ) {
				$url = get_post_meta( $id, $attributes['linkMetaFieldName'], true );

				$url = apply_filters(
					'generateblocks_dynamic_url_post_meta',
					$url,
					self::get_source_id( $attributes ),
					$attributes
				);

				if ( isset( $attributes['linkMetaFieldType'] ) ) {
					$url = $attributes['linkMetaFieldType'] . $url;
				}
			}

			if ( 'author-meta' === $link_type ) {
				$url = self::get_user_data( $author_id, $attributes['linkMetaFieldName'] );

				$url = apply_filters(
					'generateblocks_dynamic_url_author_meta',
					$url,
					$author_id,
					$attributes
				);

				if ( isset( $attributes['linkMetaFieldType'] ) ) {
					$url = $attributes['linkMetaFieldType'] . $url;
				}
			}
		}

		if ( 'author-email' === $link_type ) {
			$url = self::get_user_data( $author_id, 'user_email' );

			if ( isset( $attributes['linkMetaFieldType'] ) ) {
				$url = $attributes['linkMetaFieldType'] . $url;
			}
		}

		if ( 'author-archives' === $link_type ) {
			$url = get_author_posts_url( $author_id );
		}

		if ( 'comments-area' === $link_type ) {
			$url = get_comments_link( $id );
		}

		if ( 'pagination-next' === $link_type ) {
			$page_key = isset( $block->context['generateblocks/queryId'] ) ? 'query-' . $block->context['generateblocks/queryId'] . '-page' : 'query-page';
			$page     = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ]; // phpcs:ignore -- No data processing happening.
			$max_page = isset( $block->context['generateblocks/query']['pages'] ) ? (int) $block->context['generateblocks/query']['pages'] : 0;

			if ( isset( $block->context['generateblocks/inheritQuery'] ) && $block->context['generateblocks/inheritQuery'] ) {
				global $wp_query, $paged;

				if ( ! $max_page || $max_page > $wp_query->max_num_pages ) {
					$max_page = $wp_query->max_num_pages;
				}

				if ( ! $paged ) {
					$paged = 1; // phpcs:ignore -- Need to overrite global here.
				}

				$nextpage = (int) $paged + 1;

				if ( $nextpage <= $max_page ) {
					$url = next_posts( $max_page, false );
				}
			} elseif ( ! $max_page || $max_page > $page ) {
				$query_args = apply_filters(
					'generateblocks_query_loop_args',
					GenerateBlocks_Query_Loop::get_query_args( $block, $page ),
					$attributes,
					$block
				);

				$custom_query           = new WP_Query( $query_args );
				$custom_query_max_pages = (int) $custom_query->max_num_pages;

				if ( $custom_query_max_pages && $custom_query_max_pages !== $page ) {
					$url = esc_url( add_query_arg( $page_key, $page + 1 ) );
				}

				wp_reset_postdata(); // Restore original Post Data.
			}
		}

		if ( 'pagination-prev' === $link_type ) {
			$page_key = isset( $block->context['generateblocks/queryId'] ) ? 'query-' . $block->context['generateblocks/queryId'] . '-page' : 'query-page';
			$page     = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ]; // phpcs:ignore -- No data processing happening.

			if ( isset( $block->context['generateblocks/inheritQuery'] ) && $block->context['generateblocks/inheritQuery'] ) {
				global $paged;

				if ( $paged > 1 ) {
					$url = previous_posts( false );
				}
			} elseif ( 1 !== $page ) {
				$url = esc_url( add_query_arg( $page_key, $page - 1 ) );
			}
		}

		$url = apply_filters(
			'generateblocks_dynamic_url_output',
			$url,
			$attributes,
			$block
		);

		return is_string( $url ) ? esc_url_raw( $url ) : '';
	}

	/**
	 * Get user data.
	 *
	 * @param int         $author_id The ID of the user.
	 * @param string|void $field The field to look up.
	 */
	public static function get_user_data( $author_id, $field ) {
		if ( ! $author_id ) {
			return;
		}

		$data = get_user_meta( $author_id, $field, true );

		if ( ! $data ) {
			$user_data_names = array(
				'user_nicename',
				'user_email',
				'display_name',
			);

			if ( in_array( $field, $user_data_names ) ) {
				$user_data = get_userdata( $author_id );

				if ( $user_data ) {
					switch ( $field ) {
						case 'user_nicename':
							$data = $user_data->user_nicename;
							break;

						case 'user_email':
							$data = $user_data->user_email;
							break;

						case 'display_name':
							$data = $user_data->display_name;
							break;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Run HTML through DOMDocument so we can use parts of it
	 * when needed.
	 *
	 * @param string $content The content to run through DOMDocument.
	 */
	public static function load_html( $content ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return;
		}

		$doc = new DOMDocument();

		// Enable user error handling for the HTML parsing. HTML5 elements aren't
		// supported (as of PHP 7.4) and There's no way to guarantee that the markup
		// is valid anyway, so we're just going to ignore all errors in parsing.
		// Nested heading elements will still be parsed.
		// The lack of HTML5 support is a libxml2 issue:
		// https://bugzilla.gnome.org/show_bug.cgi?id=761534.
		libxml_use_internal_errors( true );

		// Parse the post content into an HTML document.
		// Ensure UTF-8 encoding.
		// https://stackoverflow.com/a/37834812.
		$doc->loadHTML(
			sprintf(
				'<html><head><meta http-equiv="Content-Type" content="text/html; charset=%s"></head><body>%s</body></html>',
				esc_attr( get_bloginfo( 'charset' ) ),
				$content
			)
		);

		// We're done parsing, so we can disable user error handling. This also
		// clears any existing errors, which helps avoid a memory leak.
		libxml_use_internal_errors( false );

		return $doc;
	}

	/**
	 * Extracts the icon element from our content.
	 * This is useful when using icons in dynamic blocks.
	 *
	 * @param string $content The content to search through.
	 */
	public static function get_icon_html( $content ) {
		$doc = self::load_html( $content );

		if ( ! $doc ) {
			return;
		}

		$icon_html = '';
		$html_nodes = $doc->getElementsByTagName( 'span' );

		foreach ( $html_nodes as $node ) {
			if ( 'gb-icon' === $node->getAttribute( 'class' ) ) {
				$icon_html = $doc->saveHTML( $node );
			}
		}

		return $icon_html;
	}

	/**
	 * Get the dynamic image caption.
	 *
	 * @param array  $attributes The block attributes.
	 * @param object $block The block object.
	 */
	public static function get_image_caption( $attributes, $block ) {
		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return;
		}

		return wp_get_attachment_caption( $id );
	}

	/**
	 * Get the image alt text.
	 *
	 * @param array  $attributes The block attributes.
	 * @param object $block The block object.
	 */
	public static function get_image_alt_text( $attributes, $block ) {
		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return '';
		}

		return get_post_meta( $id, '_wp_attachment_image_alt', true );
	}

	/**
	 * Get the image description.
	 *
	 * @param array  $attributes The block attributes.
	 * @param object $block The block object.
	 */
	public static function get_image_description( $attributes, $block ) {
		$id = self::get_source_id( $attributes );

		if ( ! $id ) {
			return '';
		}

		$media  = get_post( $id );
		$status = $media->post_status ?? '';

		if ( 'publish' !== $status && ! current_user_can( 'read_private_posts' ) ) {
			return '';
		}

		return isset( $media ) ? $media->post_content : '';
	}

	/**
	 * Extracts the static content the user has entered.
	 * This is useful when using dynamic links with static content.
	 *
	 * @param string $content The content to search through.
	 */
	public static function get_static_content( $content ) {
		$doc = self::load_html( $content );

		if ( ! $doc ) {
			return;
		}

		$static_content = '';
		$html_nodes = $doc->getElementsByTagName( '*' );

		foreach ( $html_nodes as $node ) {
			$classes = explode( ' ', $node->getAttribute( 'class' ) );

			if (
				in_array( 'gb-button-text', $classes ) ||
				in_array( 'gb-headline-text', $classes ) ||
				in_array( 'gb-block-image', $classes )
			) {
				// Captions are added dynamically in class-image.php, so we can remove
				// the static one here if it exists.
				if ( in_array( 'gb-block-image', $classes ) ) {
					$figcaptions = $node->getElementsByTagName( 'figcaption' );

					if ( ! empty( $figcaptions ) ) {
						foreach ( $figcaptions as $figcaption ) {
							// phpcs:ignore -- DOMDocument doesn't use snake-case.
							$figcaption->parentNode->removeChild( $figcaption );
						}
					}
				}

				// phpcs:ignore -- DOMDocument doesn't use snake-case.
				foreach ( $node->childNodes as $childNode ) {
					$static_content .= $doc->saveHTML( $childNode );
				}
			}
		}

		return $static_content;
	}

	/**
	 * Set our dynamic background image.
	 *
	 * The legacy CSS prepass can call this outside a content-source frame after
	 * combining multiple independently owned sources. Do not infer a source from
	 * the global post here: restricted authoring is enforced by the save gate for
	 * this path, while a host-wide taint would suppress unrelated trusted CSS.
	 *
	 * @param string $url Existing background image URL.
	 * @param array  $settings Block settings.
	 */
	public function set_dynamic_background_image( $url, $settings ) {
		if ( $settings['useDynamicData'] && '' !== $settings['dynamicContentType'] ) {
			$dynamic_image_url = self::get_dynamic_background_image_url( $settings );

			if ( $dynamic_image_url ) {
				$url = $dynamic_image_url;
			}
		}

		return $url;
	}

	/**
	 * Add defaults for our Query settings.
	 *
	 * @param array $defaults Block defaults.
	 */
	public function add_block_defaults( $defaults ) {
		$defaults['container']['useDynamicData'] = false;
		$defaults['container']['dynamicContentType'] = '';
		$defaults['container']['dynamicLinkType'] = '';

		return $defaults;
	}

	/**
	 * Update button count depending on dynamic content.
	 *
	 * @param int    $button_count How many buttons the block container has.
	 * @param array  $attributes The block attributes.
	 * @param object $block The block data.
	 */
	public function update_button_count( $button_count, $attributes, $block ) {
		$inner_blocks = $block->parsed_block['innerBlocks'];

		foreach ( (array) $inner_blocks as $inner_block ) {
			$block_attributes = $inner_block['attrs'];

			// Remove button from count if it has no dynamic content.
			if ( ! empty( $block_attributes['dynamicContentType'] ) && ! self::get_content( $block_attributes, $block ) ) {
				--$button_count;
			}
		}

		return $button_count;
	}

	/**
	 * Expand the wp_kses_post sanitization function to allow iframe HTML tags
	 *
	 * @param array  $tags The allowed tags, attributes, and/or attribute values.
	 * @param string $context Context to judge allowed tags by. Allowed values are 'post'.
	 * @return array
	 */
	public static function expand_allowed_html( $tags, $context ) {
		if ( ! isset( $tags['iframe'] ) ) {
			$tags['iframe'] = [
				'src'             => true,
				'height'          => true,
				'width'           => true,
				'frameborder'     => true,
				'allowfullscreen' => true,
				'title'           => true,
			];
		}

		$tags = apply_filters( 'generateblocks_dynamic_content_allowed_html', $tags, $context );

		return $tags;
	}

	/**
	 * Detect whether legacy v1 dynamic content attributes exist in serialized content.
	 *
	 * @since 2.2.0
	 *
	 * @param string $content    Serialized post content.
	 * @param bool   $is_slashed Whether the content uses WordPress slashing. Default true.
	 * @return bool
	 */
	public static function content_has_dynamic_attribute_markers( $content, $is_slashed = true ) {
		$content = GenerateBlocks_Dynamic_Tag_Security::normalize_serialized_content( $content, $is_slashed );

		if ( '' === $content ) {
			return false;
		}

		$markers = [
			'"useDynamicData":true',
			'"dynamicLinkType":"post-meta"',
			'"dynamicLinkType":"author-meta"',
			'"dynamicContentType":"author-email"',
			'"dynamicLinkType":"author-email"',
		];

		foreach ( $markers as $marker ) {
			if ( false !== strpos( $content, $marker ) ) {
				return true;
			}
		}

		/**
		 * Candidate gate for the expensive parsed fallback below. Every positive from
		 * blocks_have_dynamic_attribute_markers() requires at least one of these attribute
		 * keys in some block's decoded attrs — the referenced-post-ids branch returns empty
		 * without useDynamicData or dynamicLinkType, and the remaining branches read these
		 * keys directly. A decoded JSON key name either appears literally in the serialized
		 * block comment or was written with \u escapes (no other JSON escape produces
		 * letters), so content with none of the key names and no \u sequence cannot decode
		 * into a marker and can skip parse_blocks() entirely. Ordinary posts take this exit;
		 * unusual escaped content merely falls through to the existing slow path.
		 */
		if (
			false === strpos( $content, 'useDynamicData' ) &&
			false === strpos( $content, 'dynamicLinkType' ) &&
			false === strpos( $content, 'dynamicContentType' ) &&
			false === strpos( $content, '\u' )
		) {
			return false;
		}

		/**
		 * The literal markers above are formatting-sensitive: block-comment JSON submitted via
		 * the REST API (e.g. `"useDynamicData" : true`) is not re-serialized until after the
		 * rest_pre_insert validation runs, so a reformatted attribute can slip past the substring
		 * scan. Fall back to inspecting the parsed block attributes so JSON whitespace or key
		 * order cannot evade detection.
		 */
		if (
			function_exists( 'has_blocks' ) && has_blocks( $content ) &&
			function_exists( 'parse_blocks' )
		) {
			return self::blocks_have_dynamic_attribute_markers( parse_blocks( $content ) );
		}

		return false;
	}

	/**
	 * Recursively determine whether any parsed block carries legacy dynamic content attributes.
	 *
	 * Mirrors the markers in content_has_dynamic_attribute_markers() but works on decoded
	 * attributes, so it is immune to the JSON formatting of the serialized block comment.
	 *
	 * @since 2.4.0
	 *
	 * @param array $blocks Parsed block list.
	 * @return bool
	 */
	protected static function blocks_have_dynamic_attribute_markers( $blocks ) {
		foreach ( (array) $blocks as $block ) {
			$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];

			if ( ! empty( $attrs['useDynamicData'] ) ) {
				return true;
			}

			if (
				! empty( $attrs['dynamicLinkType'] ) &&
				in_array( $attrs['dynamicLinkType'], [ 'post-meta', 'author-meta', 'author-email' ], true )
			) {
				return true;
			}

			if ( isset( $attrs['dynamicContentType'] ) && 'author-email' === $attrs['dynamicContentType'] ) {
				return true;
			}

			// A block that points dynamic data or a dynamic link at an explicitly chosen post or
			// attachment is a dynamic-data marker too, even when no other marker is present
			// (e.g. a caption sourced from an attachment postId, a dynamicImage, a single-image
			// mediaId, or dynamicLinkType:single-post). Uses the same resolver the renderer
			// mirrors so marker detection and rendering never drift apart.
			if ( ! empty( self::get_attribute_referenced_post_ids( $attrs ) ) ) {
				return true;
			}

			if (
				! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) &&
				self::blocks_have_dynamic_attribute_markers( $block['innerBlocks'] )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve the explicit post/attachment IDs a block's dynamic data and links would read.
	 *
	 * Mirrors get_source_id() and get_dynamic_url() source selection so marker detection
	 * sees the SAME referenced object the renderer does. The current-post fallback (get_the_ID)
	 * is intentionally not collected — the visitor is already authorized to see the post being
	 * rendered; only an explicitly chosen post/attachment can cross to data the author can't read.
	 *
	 * @since 2.4.0
	 *
	 * @param array $attrs Block attributes.
	 * @return array<int, int> Unique, non-zero referenced IDs.
	 */
	protected static function get_attribute_referenced_post_ids( $attrs ) {
		if ( ! is_array( $attrs ) ) {
			return [];
		}

		$content_type     = isset( $attrs['dynamicContentType'] ) ? (string) $attrs['dynamicContentType'] : '';
		$link_type        = isset( $attrs['dynamicLinkType'] ) ? (string) $attrs['dynamicLinkType'] : '';
		$has_dynamic_data = ! empty( $attrs['useDynamicData'] );

		// Legacy dynamic attributes are mostly inert without useDynamicData: the block render
		// callbacks return static content before calling get_content()/get_dynamic_url(). The
		// one exception is the remove-if-empty guard in
		// GenerateBlocks_Render_Block::filter_rendered_blocks(), which calls get_dynamic_url()
		// whenever dynamicLinkType + dynamicLinkRemoveIfEmpty are set — even with dynamic data
		// off — because existing sites rely on that hiding behavior. That read path must be
		// validated too, or a block saved with useDynamicData off could probe an unreadable
		// post via the rendered/hidden result. Keep this condition in sync with
		// filter_rendered_blocks().
		$reads_link_without_dynamic_data = '' !== $link_type && ! empty( $attrs['dynamicLinkRemoveIfEmpty'] );

		if ( ! $has_dynamic_data && ! $reads_link_without_dynamic_data ) {
			return [];
		}

		// Nothing dynamic renders (and no source id is read) unless a content or link type is set.
		if ( '' === $content_type && '' === $link_type ) {
			return [];
		}

		$source          = isset( $attrs['dynamicSource'] ) ? (string) $attrs['dynamicSource'] : '';
		$explicit_source = '' !== $source && 'current-post' !== $source;
		$post_type       = isset( $attrs['postType'] ) ? (string) $attrs['postType'] : '';
		$image_types     = [ 'caption', 'post-title', 'alt-text', 'image-description' ];
		$ids             = [];

		// Pagination content/link types build their URL from query/page state and ignore the
		// source post, so a stale postId on them is not a reference. Every other source-aware
		// content/link type resolves through get_source_id(); keep this list in sync with the
		// pagination branches of get_content()/get_dynamic_url().
		$query_derived_types = [ 'pagination-numbers', 'pagination-next', 'pagination-prev' ];

		// Mirror get_source_id(): the explicit object the content path reads. For image content
		// types a dynamicImage (or a saved attachment postId) overrides the source REGARDLESS of
		// dynamicSource, which is why a current-post source still reaches another object's data.
		// Content paths are only reachable with useDynamicData on — every block render callback
		// returns static content before get_content() otherwise.
		if ( $has_dynamic_data && '' !== $content_type ) {
			if ( in_array( $content_type, $image_types, true ) ) {
				if ( isset( $attrs['dynamicImage'] ) ) {
					$ids[] = $attrs['dynamicImage'];
				} elseif ( isset( $attrs['postId'] ) && 'attachment' === $post_type ) {
					$ids[] = $attrs['postId'];
				} elseif ( $explicit_source && isset( $attrs['postId'] ) ) {
					$ids[] = $attrs['postId'];
				}
			} elseif (
				$explicit_source &&
				isset( $attrs['postId'] ) &&
				! in_array( $content_type, $query_derived_types, true )
			) {
				$ids[] = $attrs['postId'];
			}
		}

		// Mirror get_dynamic_url(): the explicit object a dynamic link reads. Reached either
		// with useDynamicData on (block render callbacks) or via the remove-if-empty guard in
		// filter_rendered_blocks(), which reads the link with dynamic data off.
		if ( '' !== $link_type ) {
			if ( 'single-image' === $link_type ) {
				if ( '' !== $content_type && isset( $attrs['dynamicImage'] ) ) {
					$ids[] = $attrs['dynamicImage'];
				} elseif ( '' === $content_type && isset( $attrs['mediaId'] ) ) {
					$ids[] = $attrs['mediaId'];
				}
			} elseif (
				$explicit_source &&
				isset( $attrs['postId'] ) &&
				! in_array( $link_type, $query_derived_types, true )
			) {
				// single-post / post-meta / author-* / comments-area resolve via get_source_id();
				// pagination links are excluded above because they ignore postId.
				$ids[] = $attrs['postId'];
			}
		}

		$normalized = [];

		foreach ( $ids as $id ) {
			$id = absint( $id );

			if ( $id ) {
				$normalized[ $id ] = $id;
			}
		}

		return array_values( $normalized );
	}

	/**
	 * Whether legacy attributes should be blanked for the current render source.
	 *
	 * @since 2.4.0
	 *
	 * @param array $attributes Block attributes.
	 * @return bool
	 */
	protected static function should_block_block_renderer_attribute_resolution( $attributes ) {
		unset( $attributes );

		if (
			class_exists( 'GenerateBlocks_Dynamic_Tag_Security' ) &&
			method_exists( 'GenerateBlocks_Dynamic_Tag_Security', 'should_suppress_dynamic_data' ) &&
			GenerateBlocks_Dynamic_Tag_Security::should_suppress_dynamic_data()
		) {
			return true;
		}

		if (
			class_exists( 'GenerateBlocks_Dynamic_Tag_Security' ) &&
			method_exists( 'GenerateBlocks_Dynamic_Tag_Security', 'is_non_trusted_block_renderer_rest_request' ) &&
			GenerateBlocks_Dynamic_Tag_Security::is_non_trusted_block_renderer_rest_request()
		) {
			return true;
		}

		return false;
	}

	/*
	 * -------------------------------------------------------------------------
	 * Removed 2.2.x validation API — fatal-prevention stubs.
	 *
	 * These public methods shipped in stable releases; no-op stubs remain so an
	 * external caller can never fatal on update. See the matching section in
	 * GenerateBlocks_Dynamic_Tag_Security.
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Stub for the removed 2.2.x legacy-attribute validator.
	 *
	 * @deprecated 2.4.0 No-op stub; see the save gate (save_is_restricted()).
	 *
	 * @param string $content Serialized post content.
	 * @return true
	 */
	public static function validate_dynamic_content_attributes( $content ) {
		unset( $content );

		return true;
	}

	/**
	 * Stub for the removed 2.2.x violation collector.
	 *
	 * @deprecated 2.4.0 No-op stub; the violation model no longer exists.
	 *
	 * @param string $content Serialized post content.
	 * @return array Always empty.
	 */
	public static function get_dynamic_attribute_violation_items( $content ) {
		unset( $content );

		return [];
	}
}

GenerateBlocks_Dynamic_Content::get_instance();
