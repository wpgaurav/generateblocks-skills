<?php
/**
 * The Libraries class file.
 *
 * @package GenerateBlocks\Pattern_Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for handling with libraries.
 *
 * @since 1.9.0
 */
class GenerateBlocks_Query_Utils extends GenerateBlocks_Singleton {

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'generateblocks/v1',
			'/get-wp-query',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_wp_query' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);

		register_rest_route(
			'generateblocks/v1',
			'/get-user-query',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_user_query' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}


	/**
	 * Gets posts and returns an array of WP_Post objects.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function get_user_query( WP_REST_Request $request ) {
		$args = $request->get_param( 'args' ) ?? [];

		if ( ! is_array( $args ) ) {
			$args = [];
		}

		$args['number'] = min( max( 1, absint( $args['number'] ?? 150 ) ), 150 );
		$args['paged']  = max( 1, (int) ( $args['paged'] ?? 1 ) );
		$args['fields'] = 'all';

		$can_list_users     = current_user_can( 'list_users' );
		$can_manage_options = current_user_can( 'manage_options' );

		// Tightly allowlist the query args for users without list_users.
		//
		// The only caller of this route is the editor's SelectUser control (the
		// Query Loop author filter and the dynamic-tag user picker). It sends just
		// number/paged and filters the returned list client-side — it never sends
		// search/search_columns/include. Left ungated, those args flow straight
		// into WP_User_Query and turn the count/returned IDs into a private-column
		// oracle: a Contributor could set search_columns => ['user_email'] (or
		// 'user_login') with a wildcard search + include => [ target ] and read
		// back total/matched IDs to reconstruct another user's email or login,
		// even though those fields are stripped from the response body. An empty
		// search_columns is not safe either — core defaults an "@"-containing
		// search to user_email. Allowlisting to the args the feature actually uses
		// removes all attacker-controlled search/targeting before the query runs
		// (this also drops meta_query/role/capability, so the explicit unsets are
		// no longer needed).
		if ( ! $can_list_users ) {
			$args = array_intersect_key(
				$args,
				array_flip( [ 'number', 'paged', 'fields' ] )
			);

			$args['has_published_posts'] = true;
		}

		$number    = $args['number'];
		$query     = new WP_User_Query( $args );
		$max_pages = (int) ceil( $query->get_total() / $number );

		// Filter sensitive data from user objects while maintaining structure.
		$users = array_map(
			function ( $user ) use ( $can_manage_options, $can_list_users ) {
				// Always remove critical security fields.
				unset( $user->data->user_pass );
				unset( $user->data->user_activation_key );

				if ( ! $can_manage_options ) {
					// Remove sensitive values for non-admin users.
					unset( $user->data->user_login );
					unset( $user->data->user_email );

					// Remove capability data to prevent role enumeration.
					unset( $user->caps );
					unset( $user->allcaps );
				}

				if ( ! $can_list_users ) {
					unset( $user->roles );
					unset( $user->cap_key );
				}

				return $user;
			},
			$query->get_results()
		);

		return rest_ensure_response(
			[
				'users'     => $users,
				'total'     => $query->get_total(),
				'max_pages' => $max_pages,
			]
		);
	}

	/**
	 * Gets posts and returns an array of WP_Post objects.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function get_wp_query( $request ) {
		$args           = $request->get_param( 'args' );
		$args           = is_array( $args ) ? $args : [];
		$page           = $args['paged'] ?? $request->get_param( 'page' ) ?? 1;
		$page           = is_scalar( $page ) ? (int) $page : 1;
		$page           = max( 1, $page );
		$attributes     = $request->get_param( 'attributes' ) ?? [];
		$current_post   = $request->get_param( 'postId' ) ?? null;
		$current_author = $request->get_param( 'authorId' ) ?? null;
		$context        = $request->get_param( 'context' ) ?? [];
		$query_type     = $request->get_param( 'queryType' ) ?? GenerateBlocks_Block_Query::TYPE_WP_QUERY;
		$current        = [
			'post_id'   => $current_post,
			'author_id' => $current_author,
		];

		/**
		 * Filter the args for get-wp-query calls before they're passed to get_wp_query_args.
		 *
		 * @param array $args The WP_Query args to parse.
		 * @param array $props Additional filter properties.
		 *
		 * @return array $args The optimized WP_Query args array.
		 */
		$args = apply_filters(
			'generateblocks_rest_get_wp_query_args',
			$args,
			[
				'page'       => $page,
				'attributes' => $attributes,
				'context'    => $context,
				'current'    => $current,
				'query_type' => $query_type,
			]
		);

		$posts_cap      = generateblocks_get_query_loop_editor_posts_cap();
		$posts_per_page = $args['posts_per_page'] ?? get_option( 'posts_per_page', 10 );

		if (
			! is_scalar( $posts_per_page ) ||
			! is_numeric( $posts_per_page ) ||
			(int) $posts_per_page < 1 ||
			(int) $posts_per_page > $posts_cap
		) {
			$posts_per_page = $posts_cap;
		}

		$args['posts_per_page'] = (int) $posts_per_page;

		$query = new WP_Query(
			self::get_wp_query_args(
				$args,
				$page,
				$attributes,
				null,
				$current
			)
		);

		// Filter sensitive data in-place so consumers still receive the full WP_Query object.
		$query->posts = array_map(
			function ( $post ) {
				$requires_password = ! empty( $post->post_password ) && post_password_required( $post );
				$can_read_post     = current_user_can( 'read_post', $post->ID );
				$can_bypass_pw     = current_user_can( 'edit_post', $post->ID ) || get_current_user_id() === (int) $post->post_author;

				unset( $post->post_password );
				unset( $post->guid );
				unset( $post->post_content_filtered );

				if ( ! $can_read_post || ( $requires_password && ! $can_bypass_pw ) ) {
					unset( $post->post_content );
					unset( $post->post_excerpt );
				}

				return $post;
			},
			$query->posts
		);

		return rest_ensure_response( $query );
	}

	/**
	 * Helper function that optimizes the query attribute from the Query block.
	 *
	 * @param array          $args The WP_Query args to parse.
	 * @param int            $page  Current query's page.
	 * @param array          $attributes The query block's attributes. Used for reference in the filters.
	 * @param WP_Block|array $block The current block.
	 * @param array          $current Array of current entities (post, author, etc.).
	 *
	 * @return array $query_args The optimized WP_Query args array.
	 */
	public static function get_wp_query_args( $args = [], $page = 1, $attributes = [], $block = null, $current = [] ) {
		$current_post_id   = $current['post_id'] ?? get_the_ID();
		$current_author_id = $current['author_id'] ?? get_the_author_meta( 'ID' );

		// Set up our pagination.
		if ( ! isset( $args['paged'] ) && -1 < (int) $page ) {
			$args['paged'] = $page;
		}

		if ( isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ) {
			if ( count( $args['tax_query'] ) > 1 ) {
				$args['tax_query']['relation'] = 'AND';
			}
		}

		// Sticky posts handling.
		if ( isset( $args['stickyPosts'] ) ) {

			if ( 'ignore' === $args['stickyPosts'] ) {
				$args['ignore_sticky_posts'] = true;
			}

			if ( 'exclude' === $args['stickyPosts'] ) {
				$sticky_posts = get_option( 'sticky_posts' );
				$post_not_in  = isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] ) ? $args['post__not_in'] : array();
				$args['post__not_in'] = array_merge( $sticky_posts, $post_not_in );
			}

			if ( 'only' === $args['stickyPosts'] ) {
				$sticky_posts = get_option( 'sticky_posts' );
				$args['ignore_sticky_posts'] = true;
				$args['post__in'] = $sticky_posts;
			}

			unset( $args['stickyPosts'] );
		}

		// Ensure offset works correctly with pagination.
		$posts_per_page = (int) ( $args['posts_per_page'] ?? get_option( 'posts_per_page', -1 ) );
		if (
			isset( $args['posts_per_page'] ) &&
			is_numeric( $args['posts_per_page'] ) &&
			$posts_per_page > -1
		) {

			$offset = 0;

			if (
				isset( $args['offset'] ) &&
				is_numeric( $args['offset'] )
			) {
				$offset = absint( $args['offset'] );
			}

			$args['offset']         = ( $posts_per_page * ( $page - 1 ) ) + $offset;
			$args['posts_per_page'] = $posts_per_page;
		}

		$post_status = $args['post_status'] ?? 'publish';
		if (
			'publish' !== $post_status &&
			! current_user_can( 'read_private_posts' )
		) {
			// If the user can't read private posts, we'll force the post status to be public.
			$args['post_status'] = 'publish';
		}

		$date_query = $args['date_query'] ?? false;

		if ( is_array( $date_query ) ) {
			$args['date_query'] = array_map(
				function ( $query ) {
					$query = array_filter(
						$query,
						function ( $value ) {
							return ! empty( $value );
						}
					);
					return $query;
				},
				$args['date_query'] ?? []
			);
		}

		/**
		 * Legacy filter for v1 query args compatibility.
		 *
		 * @deprecated 2.0.0.
		 *
		 * @param array           $query_args The query arguments.
		 * @param array           $attributes An array of block attributes.
		 * @param WP_Block|object $block The block instance.
		 *
		 * @return array The modified query arguments.
		 */
		$args = apply_filters(
			'generateblocks_query_loop_args',
			$args,
			$attributes,
			null === $block ? new stdClass() : $block
		);

		/**
		 * Filter the final calculated query args.
		 *
		 * @param array @query_args The array of args for the WP_Query.
		 * @param array $attributes The block attributes.
		 * @param WP_Block|array $block The current block.
		 * @param array $current The current entities (post, author, etc.).
		 *
		 * @return $args The modified query arguments.
		 */
		return apply_filters(
			'generateblocks_query_wp_query_args',
			$args,
			$attributes,
			null === $block ? new stdClass() : $block,
			[
				'post_id'   => $current_post_id,
				'author_id' => $current_author_id,
			]
		);
	}

	/**
	 * Normalize the tax query attributes to be used in the WP_Query
	 *
	 * @param array  $raw_tax_query Tax query.
	 * @param string $operator Tax operator.
	 *
	 * @return array|array[]
	 */
	public static function normalize_tax_query_attributes( $raw_tax_query, $operator = 'IN' ) {
		return array_map(
			function ( $tax ) use ( $operator ) {
				return array(
					'taxonomy' => $tax['taxonomy'],
					'field'    => 'term_id',
					'terms'    => $tax['terms'],
					'operator' => $operator,
					'include_children' => isset( $tax['includeChildren'] ) ? $tax['includeChildren'] : true,
				);
			},
			$raw_tax_query
		);
	}
}

GenerateBlocks_Query_Utils::get_instance()->init();
