<?php
/**
 * The retrieve metadata and options.
 *
 * @package GenerateBlocks\Meta_Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for handling dynamic tags.
 *
 * @since 2.0.0
 */
class GenerateBlocks_Meta_Handler extends GenerateBlocks_Singleton {

	/**
	 * Back-compat proxy so external code can keep referencing this constant.
	 */
	const DISALLOWED_KEYS = GenerateBlocks_Dynamic_Tag_Security::DISALLOWED_KEYS;

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register class REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {

		register_rest_route(
			'generateblocks/v1',
			'/meta/get-post-meta',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_post_meta_rest' ],
				'permission_callback' => function () {
					return GenerateBlocks_Dynamic_Tag_Security::user_can_author_dynamic_data();
				},
			]
		);

		register_rest_route(
			'generateblocks/v1',
			'/meta/get-user-meta',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_user_meta_rest' ],
				'permission_callback' => function () {
					return GenerateBlocks_Dynamic_Tag_Security::user_can_author_dynamic_data();
				},
			]
		);

		register_rest_route(
			'generateblocks/v1',
			'/meta/get-term-meta',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_term_meta_rest' ],
				'permission_callback' => function () {
					return GenerateBlocks_Dynamic_Tag_Security::user_can_author_dynamic_data();
				},
			]
		);

		register_rest_route(
			'generateblocks/v1',
			'/meta/get-option',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_option_rest' ],
				'permission_callback' => function ( $request ) {
					if ( ! GenerateBlocks_Dynamic_Tag_Security::user_can_author_dynamic_data() ) {
						return false;
					}

					// Only allow users who can edit posts to access options.
					if ( ! current_user_can( 'edit_posts' ) ) {
						return false;
					}

					// Admins can access all options.
					if ( current_user_can( 'manage_options' ) ) {
						return true;
					}

					$allowed_keys = apply_filters(
						'generateblocks_allowed_option_keys_rest_api',
						[
							'siteurl',
							'blogname',
							'blogdescription',
							'home',
							'time_format',
							'user_count',
						]
					);

					if ( ! is_array( $allowed_keys ) ) {
						return false;
					}

					$key = $request->get_param( 'key' ) ?? '';

					if ( ! is_string( $key ) ) {
						return false;
					}

					// Allow access to allowed keys.
					if ( in_array( $key, $allowed_keys, true ) ) {
						return true;
					}

					// Fallback: check parent key for dot notation.
					if ( strpos( $key, '.' ) !== false ) {
						$parent_key = trim( explode( '.', $key )[0] );

						if ( '' !== $parent_key && in_array( $parent_key, $allowed_keys, true ) ) {
							return true;
						}
					}

					return false;
				},
			]
		);
	}

	/**
	 * Check to see if a value if array-like and if so, get the provided property from it.
	 *
	 * @param mixed $value The value to check the property against.
	 * @param mixed $property The property to retrieve from the value if it exists.
	 * @param bool  $single_only If true, only return value if it's a string-like value.
	 * @param bool  $gate_objects If true, block reads off non-public dereferenced WP_Post objects.
	 * @return mixed The $property value if it exists, otherwise the $value.
	 */
	public static function maybe_get_property( $value, $property, $single_only = true, $gate_objects = false ) {
		if ( ! $property
			&& ! $single_only
			&& ( is_array( $value ) || is_object( $value ) )
		) {
			return $gate_objects ? self::scrub_dereferenced_value( $value ) : $value;
		}

		// Never surface a field that is already forbidden as a direct meta key through a
		// dot-path walk. A relational field can return full WP_Post/WP_User objects, so
		// `related_posts.0.post_password` or `team_member.data.user_pass` must fail the same
		// way `post_password` / `user_pass` fail as top-level reads.
		if ( is_string( $property ) && in_array( $property, self::DISALLOWED_KEYS, true ) ) {
			return '';
		}

		// Gate reads off a dereferenced post object (only when the caller opts in — see
		// get_meta()). A dynamic-tag dot-path can land on a WP_Post an ACF relational field
		// returned; unlike a loop item (already query-gated), that pointer lives in mutable
		// post meta the save-time validator never sees, while the rendered output is
		// public/cacheable. Do not expose draft/private/password-protected/non-viewable posts.
		if ( $gate_objects ) {
			if ( $value instanceof WP_Post && ! self::dereferenced_post_is_public( $value ) ) {
				return '';
			}
		}

		if ( is_array( $value ) ) {
			return $value[ $property ] ?? $value;
		} elseif ( is_object( $value ) ) {
			// WP_User resolves a lowercase `id` through a deprecated ->id path; normalize to the
			// canonical ID so a byline read gets the same value without emitting the deprecation.
			if ( 'id' === $property && $value instanceof WP_User ) {
				$property = 'ID';
			}

			return $value->$property ?? $value;
		}

		// Return the value if it's not an array or object.
		return $value;
	}

	/**
	 * Whether a WP_Post reached by dereferencing a dynamic-tag pointer may be exposed on the
	 * public frontend.
	 *
	 * It must be published, not password-protected, and of a publicly viewable post type.
	 * Viewer caps are deliberately NOT consulted: the pointer (an ACF field value) is mutable
	 * independently of the validated content string, and the rendered output is public and
	 * cacheable, so readability cannot depend on who is viewing.
	 *
	 * @param mixed $post The dereferenced value (expected WP_Post).
	 * @return bool
	 */
	protected static function dereferenced_post_is_public( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( 'publish' !== $post->post_status ) {
			return false;
		}

		if ( '' !== (string) $post->post_password ) {
			return false;
		}

		if ( function_exists( 'is_post_type_viewable' ) ) {
			return (bool) is_post_type_viewable( $post->post_type );
		}

		$post_type = function_exists( 'get_post_type_object' ) ? get_post_type_object( $post->post_type ) : null;

		return ! empty( $post_type->public );
	}

	/**
	 * Recursively strip a dereferenced structure down to data we can render publicly.
	 *
	 * The reader (get_value()) returns a whole array/object when single_only is false and the
	 * sub-key resolves to empty — e.g. a Query Loop reading an ACF relationship field, or the REST
	 * meta endpoint called with singleOnly=false. Keep existing public/user/plain data intact,
	 * but drop non-public WP_Post objects and blank keys that are forbidden as direct meta reads.
	 *
	 * @param mixed $value The dereferenced value to sanitize.
	 * @return mixed The sanitized value.
	 */
	protected static function scrub_dereferenced_value( $value ) {
		if ( $value instanceof WP_Post ) {
			return self::dereferenced_post_is_public( $value ) ? $value : '';
		}

		if ( is_object( $value ) ) {
			if ( ! self::is_scrubbable_object( $value ) ) {
				return $value;
			}

			return self::scrub_disallowed_properties( $value );
		}

		$sanitized = [];

		foreach ( $value as $key => $item ) {
			if ( is_string( $key ) && in_array( $key, self::DISALLOWED_KEYS, true ) ) {
				$sanitized[ $key ] = '';
				continue;
			}

			if ( $item instanceof WP_Post ) {
				if ( self::dereferenced_post_is_public( $item ) ) {
					$sanitized[ $key ] = $item;
				}

				continue;
			}

			$sanitized[ $key ] = self::is_array_or_object( $item ) ? self::scrub_dereferenced_value( $item ) : $item;
		}

		return $sanitized;
	}

	/**
	 * Whether an object can be safely copied into a sanitized structural return value.
	 *
	 * @param object $value Object to check.
	 * @return bool Whether the object can be scrubbed.
	 */
	protected static function is_scrubbable_object( $value ) {
		return $value instanceof stdClass || ( $value instanceof WP_User && 'WP_User' === get_class( $value ) );
	}

	/**
	 * Blank disallowed property names while preserving the original object shape.
	 *
	 * Only plain objects and WP_User objects are sanitized here. Other plugin/service objects
	 * are left untouched by scrub_dereferenced_value() to avoid invoking unknown object behavior.
	 *
	 * @param WP_User|stdClass $value Object to sanitize.
	 * @return WP_User|stdClass Sanitized object.
	 */
	protected static function scrub_disallowed_properties( $value ) {
		$sanitized = $value instanceof WP_User ? clone $value : new stdClass();

		foreach ( get_object_vars( $value ) as $key => $item ) {
			if ( in_array( $key, self::DISALLOWED_KEYS, true ) ) {
				$sanitized->$key = '';
				continue;
			}

			if ( $item instanceof WP_Post ) {
				$sanitized->$key = self::dereferenced_post_is_public( $item )
					? $item
					: '';
				continue;
			}

			if ( self::is_array_or_object( $item ) ) {
				$sanitized->$key = self::scrub_dereferenced_value( $item );
				continue;
			}

			$sanitized->$key = $item;
		}

		return $sanitized;
	}

	/**
	 * Check if a value is an array or object.
	 *
	 * @param mixed $value The value to check.
	 * @return bool If the value is an array or object.
	 */
	public static function is_array_or_object( $value ) {
		return is_array( $value ) || is_object( $value );
	}

	/**
	 * Recursive or single value retrieval.
	 *
	 * @param string     $key The key from the parent value for retrieval.
	 * @param string|int $parent_value The parent value to check the key against.
	 * @param bool       $single_only If true, only return value if it's a string-like value.
	 * @param string     $fallback The fallback value if the return value is empty.
	 * @param bool       $gate_objects If true, block reads off non-public dereferenced WP_Post objects.
	 * @return string
	 */
	public static function get_value( $key, $parent_value, $single_only = true, $fallback = '', $gate_objects = false ) {
		// Stop here if the key is empty, and not "0".
		if ( empty( $key ) && ! is_numeric( $key ) ) {
			if ( $single_only ) {
				$parent_value = self::is_array_or_object( $parent_value ) ? $fallback : (string) $parent_value;

				return '' !== $parent_value ? $parent_value : $fallback;
			}

			if ( ! self::is_array_or_object( $parent_value ) ) {
				return $fallback;
			}

			// single_only=false returns the whole structure. When the caller opted into object
			// gating (get_meta), an ACF relational pre-value may have made this a WP_Post or a
			// list of them — sanitize so a Query Loop or REST meta read (singleOnly=false)
			// can't surface a private post's content. Ungated callers (loop items, already
			// query-gated) keep the raw value.
			return $gate_objects ? self::scrub_dereferenced_value( $parent_value ) : $parent_value;
		}

		$parts     = explode( '.', $key );
		$sub_value = self::maybe_get_property( $parent_value, $parts[0], $single_only, $gate_objects );

		if ( self::is_array_or_object( $sub_value ) ) {
			return self::get_value(
				implode( '.', array_slice( $parts, 1 ) ),
				$sub_value,
				$single_only,
				$fallback,
				$gate_objects
			);
		}

		// Coerce simple values to strings.
		$value = (string) $sub_value;

		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Resolve a meta key to the exact parent key get_meta() reads.
	 *
	 * The reader splits on ".", trims each segment, and reads the first one, so every
	 * protected-meta decision — here and in the save-time validators — must be made
	 * against this same resolved value. Centralising it keeps the validators and the
	 * reader from drifting: a raw check let "{{post_meta key: _secret}}" pass validation
	 * (is_protected_meta() sees the leading space and returns false) while get_meta()
	 * trimmed the key back to the protected "_secret" before reading it.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed $key The raw meta key, optionally dot-notated.
	 * @return string The trimmed first segment actually read.
	 */
	public static function resolve_meta_key_parent( $key ) {
		if ( ! is_string( $key ) ) {
			return '';
		}

		$parts = array_map( 'trim', explode( '.', $key ) );

		return $parts[0];
	}

	/**
	 * Get a meta value.
	 *
	 * @param string|int $id The id of the entity to fetch meta from.
	 * @param string     $key The meta key to fetch. May include one or more sub keys separated by a period.
	 * @param bool       $single_only If true, only return value if it's a string-like value.
	 * @param string     $callable Function name to call. Should be a native WordPress function (ex: get_post_meta).
	 * @param string     $fallback The fallback value to show if the returned value is empty.
	 * @return string|array|object The returned value or an empty string if not found.
	 */
	public static function get_meta( $id, $key, $single_only = true, $callable = null, $fallback = '' ) {
		if ( ! is_string( $callable ) || ! function_exists( $callable ) || ! is_string( $key ) ) {
			return '';
		}

		$key_parts   = array_map( 'trim', explode( '.', $key ) );
		$parent_name = self::resolve_meta_key_parent( $key );

		if ( empty( $key ) || in_array( $parent_name, self::DISALLOWED_KEYS, true ) ) {
			return '';
		}

		/**
		 * Allow a filter to set this meta value using some
		 * custom setter function (such as get_field in ACF). If this value returns
		 * something we can skip calling get_post_meta for it and return the value instead.
		 *
		 * @since 2.0.0
		 *
		 * @param string|null $pre_value The pre-filtered value, or null if unset.
		 * @param int         $id The entity ID used to fetch the meta value.
		 * @param string      $key The meta key to fetch.
		 * @param string      $callable Function name to call. Should be a native WordPress function (ex: get_post_meta).
		 * @param bool        $single_only If true, only return value if it's a string-like value.
		 */
		$pre_value = apply_filters(
			'generateblocks_get_meta_pre_value',
			null,
			$id,
			$key,
			$callable,
			$single_only
		);

		if ( is_numeric( $id ) ) {
			$meta = $pre_value ? $pre_value : call_user_func( $callable, $id, $parent_name, true );
		} else {
			$meta = $pre_value ? $pre_value : call_user_func( $callable, $parent_name );
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && ! current_user_can( 'manage_options' ) ) {
			if ( 'get_user_meta' === $callable && is_numeric( $id ) ) {
				if ( self::should_restrict_user_meta_access( $id, $key ) ) {
					return '';
				}
			}

			if ( 'get_post_meta' === $callable ) {
				if ( is_numeric( $id ) && ! current_user_can( 'read_post', (int) $id ) ) {
					return '';
				}

				if ( is_protected_meta( $parent_name, 'post' ) ) {
					return '';
				}
			}

			if ( 'get_term_meta' === $callable ) {
				if ( is_protected_meta( $parent_name, 'term' ) ) {
					return '';
				}
			}
		}

		// Some user meta is stored as user data.
		// If we're looking for user meta and can't find it, let's check for user data as well.
		if ( ! $meta && 'get_user_meta' === $callable ) {
			$meta = get_the_author_meta( $parent_name, $id );
		}

		$meta = apply_filters(
			'generateblocks_get_meta_object',
			$meta,
			$id,
			$key,
			$callable
		);

		// Only send the sub key(s) through. If they're empty this will return the value of $meta.
		array_shift( $key_parts );
		$sub_key = implode( '.', $key_parts );
		// Gate post-object dereferences: a pre-value filter (ACF) may have turned this meta value
		// into a WP_Post (relationship/post-object field). Walking a dot-path sub-key into it can
		// disclose a private post's content, and the pointer is mutable independently of the
		// validated content string — so gate post objects to publicly renderable posts here.
		// Plain scalar/array/user meta is unaffected except for explicitly disallowed keys.
		$value = self::get_value( $sub_key, $meta, $single_only, $fallback, true );

		/**
		 * Filter the result of get_value for entity meta.
		 *
		 * @since 2.0.0
		 * @param string|int $id The ID of the entity to fetch meta from.
		 * @param string     $key The meta key to fetch. May include one or more sub keys separated by a period.
		 * @param bool       $single_only If true, only return value if it's a string-like value.
		 * @param string     $callable Function name to call. Should be a native WordPress function (ex: get_post_meta).
		 */
		return apply_filters( 'generateblocks_get_meta_value', $value, $id, $key, $single_only, $callable );
	}

	/**
	 * Get the post meta.
	 *
	 * @param string|int $id The id of the post to fetch meta from.
	 * @param string     $key The meta key to fetch. May include one or more sub keys separated by a period.
	 * @param bool       $single_only If true, only return value if it's a string-like value.
	 * @return string|array|object The returned value or an empty string if not found.
	 */
	public static function get_post_meta( $id, $key, $single_only = true ) {
		return self::get_meta( $id, $key, $single_only, 'get_post_meta' );
	}

	/**
	 * Rest handler for get_post_meta
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object.
	 */
	public function get_post_meta_rest( $request ) {
		$id          = (int) $request->get_param( 'id' );
		$single_only = true;

		// Verify user can read this post.
		if ( ! current_user_can( 'read_post', $id ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to read this post.', 'generateblocks' ),
				array( 'status' => 403 )
			);
		}

		$post = get_post( $id );

		if ( $post ) {
			$requires_password = ! empty( $post->post_password ) && post_password_required( $post );
			$can_bypass_pw     = current_user_can( 'edit_post', $post->ID ) || get_current_user_id() === (int) $post->post_author;

			if ( $requires_password && ! $can_bypass_pw ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to read meta for this password-protected post.', 'generateblocks' ),
					array( 'status' => 403 )
				);
			}
		}

		$key = $request->get_param( 'key' );

		// Block protected meta keys (WordPress convention for private/internal meta).
		// Resolve the key exactly as get_meta() will read it, so a leading-space key
		// (" _secret") cannot slip past this check and then resolve to "_secret".
		if ( is_string( $key ) && is_protected_meta( self::resolve_meta_key_parent( $key ), 'post' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access protected meta fields.', 'generateblocks' ),
				array( 'status' => 403 )
			);
		}

		if ( 'false' === $request->get_param( 'singleOnly' ) ) {
			$single_only = false;
		}

		return rest_ensure_response( self::get_post_meta( $id, $key, $single_only ) );
	}


	/**
	 * Get the user meta.
	 *
	 * @param string|int $id The id of the user to fetch meta from.
	 * @param string     $key The meta key to fetch. May include one or more sub keys separated by a period.
	 * @param bool       $single_only If true, only return value if it's a string-like value.
	 * @return string|array|object The returned value or an empty string if not found.
	 */
	public static function get_user_meta( $id, $key, $single_only = true ) {
		return self::get_meta( $id, $key, $single_only, 'get_user_meta' );
	}

	/**
	 * Determine if user meta access should be restricted.
	 *
	 * This checks whether security restrictions should apply based on:
	 * - User capabilities (list_users bypasses all restrictions)
	 * - Whether user is viewing their own data (allowed)
	 * - Whether the field is in the safe list
	 *
	 * @since 2.2.0
	 *
	 * @param int    $user_id The user ID being accessed.
	 * @param string $key     The meta key being accessed.
	 * @return bool True if access should be restricted, false if allowed.
	 */
	public static function should_restrict_user_meta_access( $user_id, $key ) {
		$current_user_id = get_current_user_id();
		$is_privileged   = current_user_can( 'list_users' );
		$is_viewing_own  = (int) $user_id === $current_user_id && $current_user_id > 0;

		// Privileged users or viewing own data - no restrictions.
		if ( $is_privileged || $is_viewing_own ) {
			return false;
		}

		// Check if field is in safe list.
		$parent_key = '';
		if ( is_string( $key ) ) {
			$parent_key = trim( explode( '.', $key )[0] );
		}

		$safe_keys = [];

		if (
			class_exists( 'GenerateBlocks_Dynamic_Tag_Security' ) &&
			method_exists( 'GenerateBlocks_Dynamic_Tag_Security', 'get_safe_user_meta_keys' )
		) {
			$safe_keys = GenerateBlocks_Dynamic_Tag_Security::get_safe_user_meta_keys();
		}

		// If key is in safe list, allow access.
		if ( $parent_key && in_array( $parent_key, $safe_keys, true ) ) {
			return false;
		}

		/**
		 * Filter whether to restrict user_meta access.
		 *
		 * WARNING: Returning false disables security restrictions and may
		 * expose sensitive user data to all visitors. Only disable if you
		 * have implemented your own access controls.
		 *
		 * @since 2.1.4
		 *
		 * @param bool $should_restrict   Whether to apply restrictions.
		 * @param int  $user_id           The user ID being accessed.
		 * @param int  $current_user_id   Current user ID (0 if logged out).
		 */
		return apply_filters(
			'generateblocks_restrict_user_meta_access',
			true,
			$user_id,
			$current_user_id
		);
	}

	/**
	 * Rest handler for get_user_meta
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object.
	 */
	public function get_user_meta_rest( $request ) {
		$requested_id = (int) $request->get_param( 'id' );
		$current_id   = get_current_user_id();

		$id = $requested_id ? $requested_id : $current_id;

		if ( ! $id ) {
			return rest_ensure_response(
				new WP_Error(
					'invalid_user_id',
					__( 'A valid user ID is required.', 'generateblocks' ),
					array( 'status' => 400 )
				)
			);
		}

		// Require list_users capability to access other users' meta.
		if ( $id !== $current_id && ! current_user_can( 'list_users' ) ) {
			return rest_ensure_response(
				new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to access this user\'s meta.', 'generateblocks' ),
					array( 'status' => rest_authorization_required_code() )
				)
			);
		}

		$key         = $request->get_param( 'key' );
		$single_only = true;

		// Block protected meta keys (WordPress convention for private/internal meta).
		if ( is_string( $key ) && is_protected_meta( self::resolve_meta_key_parent( $key ), 'user' ) ) {
			return rest_ensure_response(
				new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to access protected meta fields.', 'generateblocks' ),
					array( 'status' => rest_authorization_required_code() )
				)
			);
		}

		// Check if access should be restricted (preview/draft context and safe list).
		if ( self::should_restrict_user_meta_access( $id, $key ) ) {
			return rest_ensure_response(
				new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to view this user meta field.', 'generateblocks' ),
					array( 'status' => rest_authorization_required_code() )
				)
			);
		}

		if ( 'false' === $request->get_param( 'singleOnly' ) ) {
			$single_only = false;
		}

		return rest_ensure_response( self::get_user_meta( $id, $key, $single_only ) );
	}

	/**
	 * Get the term meta.
	 *
	 * @param string|int $id The id of the term to fetch meta from.
	 * @param string     $key The meta key to fetch. May include one or more sub keys separated by a period.
	 * @param bool       $single_only If true, only return value if it's a string-like value.
	 * @return string|array|object The returned value or an empty string if not found.
	 */
	public static function get_term_meta( $id, $key, $single_only = true ) {
		return self::get_meta( $id, $key, $single_only, 'get_term_meta' );
	}

	/**
	 * Rest handler for get_term_meta
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object.
	 */
	public function get_term_meta_rest( $request ) {
		$id          = (int) $request->get_param( 'id' );
		$key         = $request->get_param( 'key' );
		$single_only = true;

		if ( ! $id ) {
			return rest_ensure_response(
				new WP_Error(
					'invalid_term_id',
					__( 'A valid term ID is required.', 'generateblocks' ),
					[ 'status' => 400 ]
				)
			);
		}

		$term = get_term( $id );

		if ( ! $term || is_wp_error( $term ) ) {
			return rest_ensure_response(
				new WP_Error(
					'term_not_found',
					__( 'Term not found.', 'generateblocks' ),
					[ 'status' => 404 ]
				)
			);
		}

		$can_manage_term = current_user_can( 'edit_term', $term->term_id );
		$can_edit_posts  = current_user_can( 'edit_posts' );

		if ( ! $can_manage_term && ! $can_edit_posts ) {
			return rest_ensure_response(
				new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to read this term.', 'generateblocks' ),
					[ 'status' => 403 ]
				)
			);
		}

		if ( is_string( $key ) && is_protected_meta( self::resolve_meta_key_parent( $key ), 'term' ) ) {
			return rest_ensure_response(
				new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to access protected term meta fields.', 'generateblocks' ),
					[ 'status' => 403 ]
				)
			);
		}

		if ( 'false' === $request->get_param( 'singleOnly' ) ) {
			$single_only = false;
		}

		return rest_ensure_response( self::get_term_meta( $id, $key, $single_only ) );
	}

	/**
	 * Get an option's value.
	 *
	 * @param string $key The meta key to fetch. May include one or more sub keys separated by a period.
	 * @param bool   $single_only If true, only return value if it's a string-like value.
	 * @return string|array|object The returned value or an empty string if not found.
	 */
	public static function get_option( $key, $single_only = true ) {
		return self::get_meta( 'option', $key, $single_only, 'get_option' );
	}

	/**
	 * Rest handler for get_option
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object.
	 */
	public function get_option_rest( $request ) {
		$key         = $request->get_param( 'key' );
		$single_only = true;

		if ( 'false' === $request->get_param( 'singleOnly' ) ) {
			$single_only = false;
		}

		return rest_ensure_response( self::get_option( $key, $single_only ) );
	}
}

GenerateBlocks_Meta_Handler::get_instance()->init();
