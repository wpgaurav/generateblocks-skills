<?php
/**
 * The save gate: capability-based save restrictions for post content.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class GenerateBlocks_Save_Gate
 *
 * A single enforcement spine for every "this user may not save that content"
 * rule. The gate owns the save entry points and their shared exemption logic;
 * rules supply only their detection predicate, capability check, and message.
 * A rule registered here inherits coverage of every entry point below without
 * knowing they exist — which is the point: entry-point coverage is proven once,
 * here, instead of re-discovered by each feature.
 *
 * ENTRY POINTS — every path core uses to persist post content:
 *
 * - rest_pre_insert_{post_type} for REST creates/updates (the block editor).
 * - rest_dispatch_request for Gutenberg autosaves, which core dispatches before
 *   pre-insert errors can propagate.
 * - wp_insert_post_data for classic editor, XML-RPC, and programmatic saves.
 * - wp_insert_attachment_data because core routes attachment saves through it,
 *   and an attachment's "description" field is its post_content.
 *
 * THE NO-NEW-EXPOSURE EXEMPTION (shared by every rule): a save whose content is
 * byte-identical to the stored row authored nothing, so it is exempt as long as
 * it also does not move the post to a more exposed status (draft/pending →
 * publish/future/private, private → publish), strip the post's password, change
 * its parent (an inherit-status attachment's effective exposure IS its parent's
 * status), or change its post type. This lets restricted users edit
 * titles/slugs/terms/meta of trusted-authored restricted posts, and unpublish
 * or trash them — but never newly publish or newly expose one.
 *
 * RULES — registered via register_rule() with this shape:
 *
 *     [
 *         'id'         => 'dynamic_data',            // Unique slug.
 *         'applies'    => callable( $content, $context ): bool,
 *                         // Whether $content contains material this rule
 *                         // restricts. $context = [ 'post_id', 'post_type' ].
 *         'user_can'   => callable(): bool,          // Whether the current user
 *                         // may author that material.
 *         'message'    => callable(): string,        // User-facing block message,
 *                         // resolved at block time (or a plain string).
 *         'error_code' => 'my_error_code',           // WP_Error code on block.
 *         'enforced'   => callable( $context ): bool, // Optional. Rule-specific
 *                         // enforcement toggle (e.g. a legacy filter).
 *         'exempt'     => callable( $content, $context ): bool, // Optional.
 *                         // Rule-specific exemption checked after the shared
 *                         // no-new-exposure exemption fails — for rules that
 *                         // can prove a finer-grained save safe (e.g. a diff
 *                         // that shows the restricted material is unchanged).
 *     ]
 *
 * Partner plugins register rules directly on plugins_loaded or later, behind a
 * class_exists( 'GenerateBlocks_Save_Gate' ) check — there is no registration
 * action to miss. Re-registering an id replaces that rule.
 *
 * CONTRACT: the gate is normally a convenience/authoring layer, not a security
 * boundary on its own. Saves can predate a rule or arrive with the gate disabled
 * by filter, so rules need an authoritative guard at output time (the dynamic-data
 * rule's is normally the render-time taint in GenerateBlocks_Dynamic_Tag_Security;
 * a kses-stripped field's is kses itself). The documented exceptions are render
 * paths that do not expose source ownership: legacy v1 dynamic-background CSS and
 * Core's direct wp_template/wp_template_part `do_blocks()` pipelines. This gate is
 * the authoritative authoring control for those paths. Disabling a rule's
 * enforcement also opts out of any protection it owns.
 *
 * @since 2.4.0
 */
class GenerateBlocks_Save_Gate extends GenerateBlocks_Singleton {

	/**
	 * Registered rules, keyed by rule id, in registration order.
	 *
	 * @var array<string, array>
	 */
	private $rules = [];

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_filters' ] );
		add_filter( 'rest_dispatch_request', [ $this, 'validate_autosave_rest_request' ], 10, 4 );

		// PHP_INT_MAX: the gate must inspect the content that will actually be persisted,
		// after any other plugin's filter has mutated it. Attachments need their own hook —
		// core routes them through wp_insert_attachment_data instead, and an attachment's
		// "description" field is its post_content.
		add_filter( 'wp_insert_post_data', [ $this, 'validate_insert_post_data' ], PHP_INT_MAX, 4 );
		add_filter( 'wp_insert_attachment_data', [ $this, 'validate_insert_post_data' ], PHP_INT_MAX, 4 );
	}

	/**
	 * Register a save-gate rule.
	 *
	 * See the class doc block for the rule shape. Registering an id that already
	 * exists replaces that rule. A malformed rule is rejected so a broken
	 * registration can never half-enforce.
	 *
	 * @since 2.4.0
	 *
	 * @param array $rule Rule definition.
	 * @return bool Whether the rule was registered.
	 */
	public function register_rule( $rule ) {
		$valid = is_array( $rule ) &&
			isset( $rule['id'] ) && is_string( $rule['id'] ) && '' !== $rule['id'] &&
			isset( $rule['applies'] ) && is_callable( $rule['applies'] ) &&
			isset( $rule['user_can'] ) && is_callable( $rule['user_can'] ) &&
			isset( $rule['error_code'] ) && is_string( $rule['error_code'] ) && '' !== $rule['error_code'] &&
			isset( $rule['message'] ) && ( is_callable( $rule['message'] ) || is_string( $rule['message'] ) ) &&
			( ! isset( $rule['enforced'] ) || is_callable( $rule['enforced'] ) ) &&
			( ! isset( $rule['exempt'] ) || is_callable( $rule['exempt'] ) );

		if ( ! $valid ) {
			if ( function_exists( '_doing_it_wrong' ) ) {
				_doing_it_wrong(
					__METHOD__,
					'Save-gate rules require a non-empty id and error_code, callable applies and user_can, and a callable or string message.',
					'2.4.0'
				);
			}

			return false;
		}

		$this->rules[ $rule['id'] ] = $rule;

		return true;
	}

	/**
	 * Whether a rule id is registered.
	 *
	 * @since 2.4.0
	 *
	 * @param string $rule_id Rule id.
	 * @return bool
	 */
	public function has_rule( $rule_id ) {
		return is_string( $rule_id ) && isset( $this->rules[ $rule_id ] );
	}

	/**
	 * Determine whether a prospective save is restricted, before exemptions.
	 *
	 * This is the pure decision layer — enforcement toggles, user context, the
	 * rule's capability check, and its content predicate. The per-save
	 * exemptions (byte-identical content, no new exposure) are applied by the
	 * entry points, not here.
	 *
	 * @since 2.4.0
	 *
	 * @param string $content   Incoming post content, already unslashed by the caller.
	 * @param int    $post_id   Existing post ID, or 0 for a new post.
	 * @param string $post_type Post type being saved.
	 * @param string $rule_id   Optional. Restrict the check to one rule id;
	 *                          empty checks every registered rule.
	 * @return bool True when the save is restricted.
	 */
	public function save_is_restricted( $content, $post_id, $post_type, $rule_id = '' ) {
		$context = self::build_context( $post_id, $post_type );

		foreach ( $this->rules as $id => $rule ) {
			if ( '' !== $rule_id && $rule_id !== $id ) {
				continue;
			}

			if ( $this->rule_restricts_save( $rule, $content, $context ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register REST pre-insert save gate filters for REST-exposed post types.
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	public function register_rest_filters() {
		if ( ! function_exists( 'get_post_types' ) ) {
			return;
		}

		$post_types = get_post_types(
			[
				'show_in_rest' => true,
			],
			'names'
		);

		foreach ( $post_types as $post_type ) {
			add_filter( "rest_pre_insert_{$post_type}", [ $this, 'validate_rest_save' ], 10, 2 );
		}
	}

	/**
	 * Block REST saves that introduce restricted content for restricted users.
	 *
	 * @since 2.4.0
	 *
	 * @param object $prepared_post An object representing the post prepared for the database.
	 * @param object $request       Request object.
	 * @return object|WP_Error The prepared post, or a restriction error.
	 */
	public function validate_rest_save( $prepared_post, $request ) {
		unset( $request );

		if ( ! is_object( $prepared_post ) ) {
			return $prepared_post;
		}

		$post_id   = ! empty( $prepared_post->ID ) ? absint( $prepared_post->ID ) : 0;
		$post_type = isset( $prepared_post->post_type ) && is_string( $prepared_post->post_type ) ? $prepared_post->post_type : '';

		// Fall back to the stored content for content-less updates so a re-save/publish of a
		// restricted post is still gated on the post's real content.
		$content = self::resolve_effective_post_content( $prepared_post, $post_id );

		if ( '' === $content ) {
			return $prepared_post;
		}

		// Core only sets a field on the prepared object when the request submitted it; an unset
		// field keeps the stored value, which is_no_new_exposure_save() expresses as null. This
		// exemption also subsumes a status-only trash: it matches the stored content and steps
		// exposure down (exempt), while restricted content smuggled in alongside a trash status
		// fails the byte match and stays blocked.
		$new_status   = isset( $prepared_post->post_status ) && is_string( $prepared_post->post_status ) ? $prepared_post->post_status : null;
		$new_password = isset( $prepared_post->post_password ) && is_string( $prepared_post->post_password ) ? $prepared_post->post_password : null;
		$new_parent   = isset( $prepared_post->post_parent ) ? absint( $prepared_post->post_parent ) : null;
		$new_type     = '' !== $post_type ? $post_type : null;

		$blocking_rule = $this->get_blocking_rule(
			$content,
			self::build_context( $post_id, $post_type ),
			static function () use ( $content, $post_id, $new_status, $new_password, $new_parent, $new_type ) {
				return self::is_no_new_exposure_save( $content, $post_id, $new_status, $new_password, $new_parent, $new_type );
			}
		);

		if ( $blocking_rule ) {
			return self::get_rule_error( $blocking_rule );
		}

		return $prepared_post;
	}

	/**
	 * Intercept Gutenberg autosave REST requests before core bypasses pre-insert errors.
	 *
	 * The trailing parameters are optional so a legacy three-argument caller
	 * degrades to a no-op pass-through instead of fataling on arity.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed  $response Response to replace the requested version with. Default null.
	 * @param object $request  Request used to generate the response.
	 * @param string $route    Matched route.
	 * @param array  $handler  Route handler used for the request.
	 * @return mixed Either the original response or a WP_Error to halt dispatch.
	 */
	public function validate_autosave_rest_request( $response, $request = null, $route = '', $handler = null ) {
		unset( $handler );

		if ( null !== $response ) {
			return $response;
		}

		if ( ! is_object( $request ) || ! method_exists( $request, 'get_method' ) || 'POST' !== $request->get_method() ) {
			return $response;
		}

		if ( ! is_string( $route ) || false === strpos( $route, '/autosaves' ) || ! preg_match( '#/autosaves/?$#', $route ) ) {
			return $response;
		}

		$content = self::get_rest_request_content_param( $request );

		if ( null === $content ) {
			return $response;
		}

		// Resolve the parent post from the concrete request path so rule callbacks receive
		// real context (post_id/post_type), letting a site scope a rule per post type for
		// autosaves too. The $route argument is the registered pattern and carries no literal ID.
		$post_id   = self::get_autosave_route_parent_id( $request );
		$post_type = $post_id && function_exists( 'get_post_type' ) ? get_post_type( $post_id ) : '';
		$post_type = is_string( $post_type ) ? $post_type : '';

		$blocking_rule = $this->get_blocking_rule(
			$content,
			self::build_context( $post_id, $post_type ),
			static function () use ( $content, $post_id ) {
				// A byte-identical autosave persists nothing new, and an autosave can never
				// change the parent row's status or password, so no exposure check is needed.
				// Template autosaves resolve no numeric parent (post_id 0) and stay blocked.
				return self::content_matches_stored( $content, $post_id );
			}
		);

		if ( $blocking_rule ) {
			return self::get_rule_error( $blocking_rule );
		}

		return $response;
	}

	/**
	 * Catch classic/editor/programmatic saves that bypass REST pre-insert checks.
	 *
	 * Hooked on both wp_insert_post_data and wp_insert_attachment_data: core routes
	 * attachment saves (media modal / edit-media screen description = post_content)
	 * through the latter.
	 *
	 * @since 2.4.0
	 *
	 * @param array $data                Slashed post data.
	 * @param array $postarr             Sanitized post array.
	 * @param array $unsanitized_postarr Original unsanitized post array.
	 * @param bool  $update              Whether this is an update.
	 * @return array Slashed post data.
	 */
	public function validate_insert_post_data( $data, $postarr, $unsanitized_postarr = null, $update = null ) {
		unset( $unsanitized_postarr, $update );

		if ( ! is_array( $data ) ) {
			return $data;
		}

		$is_revision          = isset( $data['post_type'] ) && 'revision' === $data['post_type'];
		$is_autosave_revision = $is_revision && self::is_autosave_revision_data( $data, $postarr );

		if ( $is_revision && ! $is_autosave_revision ) {
			return $data;
		}

		// For updates, core has already merged the stored row into $data, so $data['post_content']
		// is the content that will actually be persisted (submitted or unchanged). Gate on it
		// directly — there is no reliable "was content submitted?" signal at this layer. Note
		// sanitize_post() (kses for restricted users) has already run on $data here, so a stored
		// row holding kses-hostile trusted markup won't byte-match below and the exemption fails
		// closed to the full gate.
		$content   = self::unslash_post_content( isset( $data['post_content'] ) ? $data['post_content'] : '' );
		$post_id   = $is_autosave_revision && isset( $data['post_parent'] ) ? absint( $data['post_parent'] ) : ( isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0 );
		$post_type = isset( $data['post_type'] ) && is_string( $data['post_type'] ) ? $data['post_type'] : '';

		if ( $is_autosave_revision && $post_id && function_exists( 'get_post_type' ) ) {
			$parent_post_type = get_post_type( $post_id );

			if ( is_string( $parent_post_type ) ) {
				$post_type = $parent_post_type;
			}
		}

		if ( $is_autosave_revision ) {
			// A byte-identical autosave persists nothing new, and an autosave never changes
			// the parent row's status or password, so no exposure check applies. Compare
			// against the PARENT's stored content ($post_id is the parent here).
			$is_generically_exempt = static function () use ( $content, $post_id ) {
				return self::content_matches_stored( $content, $post_id );
			};
		} else {
			$new_status   = isset( $data['post_status'] ) && is_string( $data['post_status'] ) ? $data['post_status'] : '';
			$new_password = isset( $data['post_password'] ) && is_string( $data['post_password'] ) ? $data['post_password'] : '';
			$new_parent   = isset( $data['post_parent'] ) ? absint( $data['post_parent'] ) : null;
			$new_type     = '' !== $post_type ? $post_type : null;

			// Content unchanged and no exposure increase: metadata edits, status step-downs,
			// and trash (wp_trash_post() re-saves the stored content unchanged) all pass. A
			// trash request that also rewrites the content fails the byte match and stays
			// gated, as does any transition toward publish/future, a password removal, a
			// parent change, or a post-type change.
			$is_generically_exempt = static function () use ( $content, $post_id, $new_status, $new_password, $new_parent, $new_type ) {
				return self::is_no_new_exposure_save( $content, $post_id, $new_status, $new_password, $new_parent, $new_type );
			};
		}

		$blocking_rule = $this->get_blocking_rule(
			$content,
			self::build_context( $post_id, $post_type ),
			$is_generically_exempt
		);

		if ( $blocking_rule ) {
			$message = self::get_rule_message( $blocking_rule );
			$is_ajax_request = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();
			$uses_json_handler = ! $is_ajax_request &&
				function_exists( 'wp_is_json_request' ) &&
				wp_is_json_request();

			// AJAX takes precedence over JSON in wp_die(). Only the JSON handler
			// encodes this message verbatim; every other path receives escaped HTML.
			if ( ! $uses_json_handler ) {
				$message = esc_html( $message );
			}

			wp_die(
				$message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above for the HTML die path; the JSON die handler encodes verbatim.
				'',
				[
					'response'  => 403,
					'back_link' => true,
				]
			);
		}

		return $data;
	}

	/**
	 * Find the first registered rule that both restricts and is not exempted
	 * from a prospective save.
	 *
	 * The shared exemption is rule-independent — content byte-identical to the
	 * stored row authored nothing under ANY rule — so it is evaluated once,
	 * lazily, when the first rule restricts, and short-circuits every rule. A
	 * rule's own 'exempt' callback only skips that rule.
	 *
	 * @since 2.4.0
	 *
	 * @param string   $content               Incoming post content, already unslashed.
	 * @param array    $context               Save context ('post_id', 'post_type').
	 * @param callable $is_generically_exempt Lazy evaluator for the entry point's
	 *                                        shared exemption.
	 * @return array|null The blocking rule, or null when the save may proceed.
	 */
	private function get_blocking_rule( $content, $context, $is_generically_exempt ) {
		$generic_exempt = null;

		foreach ( $this->rules as $rule ) {
			if ( ! $this->rule_restricts_save( $rule, $content, $context ) ) {
				continue;
			}

			if ( null === $generic_exempt ) {
				$generic_exempt = (bool) call_user_func( $is_generically_exempt );
			}

			if ( $generic_exempt ) {
				return null;
			}

			if ( isset( $rule['exempt'] ) && call_user_func( $rule['exempt'], $content, $context ) ) {
				continue;
			}

			return $rule;
		}

		return null;
	}

	/**
	 * Whether a single rule restricts a prospective save, before exemptions.
	 *
	 * @since 2.4.0
	 *
	 * @param array  $rule    Rule definition.
	 * @param string $content Incoming post content, already unslashed.
	 * @param array  $context Save context ('post_id', 'post_type').
	 * @return bool True when the rule restricts the save.
	 */
	private function rule_restricts_save( $rule, $content, $context ) {
		/**
		 * Whether to enforce a save-gate rule.
		 *
		 * @since 2.4.0
		 *
		 * @param bool   $enforce Whether to enforce the rule.
		 * @param string $rule_id Rule id.
		 * @param array  $context Save context.
		 */
		$enforce = apply_filters( 'generateblocks_enforce_save_gate_rule', true, $rule['id'], $context );

		if ( ! $enforce ) {
			return false;
		}

		if ( isset( $rule['enforced'] ) && ! call_user_func( $rule['enforced'], $context ) ) {
			return false;
		}

		// No user context (cron, WP-CLI, other system saves) is never gated: there is no
		// author to attribute the content to, and each rule's output-time guard is the
		// authoritative layer for content that reaches the database anyway.
		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;

		if ( ! $user_id ) {
			return false;
		}

		if ( call_user_func( $rule['user_can'] ) ) {
			return false;
		}

		return (bool) call_user_func( $rule['applies'], $content, $context );
	}

	/**
	 * Build the save context passed to rule callbacks and filters.
	 *
	 * @since 2.4.0
	 *
	 * @param int    $post_id   Existing post ID, or 0 for a new post.
	 * @param string $post_type Post type being saved.
	 * @return array Save context.
	 */
	protected static function build_context( $post_id, $post_type ) {
		return [
			'post_id'   => absint( $post_id ),
			'post_type' => is_string( $post_type ) ? $post_type : '',
		];
	}

	/**
	 * Resolve a rule's user-facing block message.
	 *
	 * @since 2.4.0
	 *
	 * @param array $rule Rule definition.
	 * @return string Block message.
	 */
	protected static function get_rule_message( $rule ) {
		$message = isset( $rule['message'] ) ? $rule['message'] : '';

		if ( is_callable( $message ) ) {
			$message = call_user_func( $message );
		}

		return is_string( $message ) ? $message : '';
	}

	/**
	 * Build the WP_Error returned when a rule blocks a save.
	 *
	 * @since 2.4.0
	 *
	 * @param array $rule Rule definition.
	 * @return WP_Error Restriction error.
	 */
	protected static function get_rule_error( $rule ) {
		return new WP_Error(
			$rule['error_code'],
			self::get_rule_message( $rule ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Whether the given content is identical to the stored post's content.
	 *
	 * The anchor of the no-new-exposure exemption: a save whose content matches the stored
	 * row byte-for-byte authored nothing. A new post (post_id 0) has no stored row, so any
	 * content is treated as changed, and any mutation another filter (or kses) applied to
	 * the incoming content fails the match — the exemption fails closed to the full gate.
	 *
	 * @since 2.4.0
	 *
	 * @param string $content Unslashed content that will be persisted.
	 * @param int    $post_id Existing post ID, or 0 for a new post.
	 * @return bool True when the content matches the stored row.
	 */
	protected static function content_matches_stored( $content, $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! function_exists( 'get_post' ) ) {
			return false;
		}

		$stored = get_post( $post_id );

		if ( ! is_object( $stored ) || ! isset( $stored->post_content ) || ! is_string( $stored->post_content ) ) {
			return false;
		}

		return $stored->post_content === $content;
	}

	/**
	 * Whether a restricted save may pass the gate because it persists the stored content
	 * unchanged AND does not increase how exposed that content is.
	 *
	 * The gate blocks restricted users from AUTHORING restricted content. A save whose
	 * content is byte-identical to the stored row authored nothing — but it can still
	 * newly expose trusted-authored restricted content by moving the post to a more
	 * public status (draft → publish/future, private → publish), by removing its
	 * password, by reparenting it, or by changing its post type. Those transitions stay
	 * blocked; everything else (metadata edits, status step-downs, trash) passes. A new
	 * post has no stored row and is never exempt.
	 *
	 * @since 2.4.0
	 *
	 * @param string      $content      Unslashed content that will be persisted.
	 * @param int         $post_id      Existing post ID, or 0 for a new post.
	 * @param string|null $new_status   Status being saved, or null when the save leaves it unchanged.
	 * @param string|null $new_password Password being saved, or null when the save leaves it unchanged.
	 * @param int|null    $new_parent   Parent ID being saved, or null when the save leaves it unchanged.
	 * @param string|null $new_type     Post type being saved, or null when the save leaves it unchanged.
	 * @return bool True when the save is exempt from the gate.
	 */
	protected static function is_no_new_exposure_save( $content, $post_id, $new_status = null, $new_password = null, $new_parent = null, $new_type = null ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! self::content_matches_stored( $content, $post_id ) ) {
			return false;
		}

		$stored = function_exists( 'get_post' ) ? get_post( $post_id ) : null;

		if ( ! is_object( $stored ) ) {
			return false;
		}

		$old_status   = isset( $stored->post_status ) && is_string( $stored->post_status ) ? $stored->post_status : '';
		$old_password = isset( $stored->post_password ) && is_string( $stored->post_password ) ? $stored->post_password : '';
		$old_parent   = isset( $stored->post_parent ) ? absint( $stored->post_parent ) : 0;
		$old_type     = isset( $stored->post_type ) && is_string( $stored->post_type ) ? $stored->post_type : '';

		// A parent change can raise EFFECTIVE exposure while the literal status stays put:
		// core resolves an inherit-status attachment's visibility through its parent, and
		// treats an unattached attachment as published — so reparenting a draft-attached
		// restricted attachment to a published post (or to 0) newly exposes it. A post-type
		// change can likewise move content from a non-public type into a public one.
		// Neither is provable-safe from here, so both fail closed to the full gate —
		// exactly where every such save landed before this exemption existed.
		if ( null !== $new_parent && absint( $new_parent ) !== $old_parent ) {
			return false;
		}

		if ( null !== $new_type && $new_type !== $old_type ) {
			return false;
		}

		// Removing the password from a password-protected post newly exposes its rendered
		// content even though status and content are unchanged. Setting or changing a
		// password only ever narrows exposure.
		if ( null !== $new_password && '' !== $old_password && '' === $new_password ) {
			return false;
		}

		if ( null === $new_status ) {
			$new_status = $old_status;
		}

		return ! self::status_transition_increases_exposure( $old_status, $new_status );
	}

	/**
	 * Whether a status transition makes a post's rendered content more publicly exposed.
	 *
	 * Identical statuses (including unknown/custom ones) never increase exposure. For a
	 * real transition, unknown statuses fail closed on both sides: an unrecognized NEW
	 * status ranks fully public (blocked unless the post already was), an unrecognized
	 * OLD status ranks unexposed.
	 *
	 * @since 2.4.0
	 *
	 * @param string $old_status Stored post status.
	 * @param string $new_status Status being saved.
	 * @return bool True when the transition increases exposure.
	 */
	protected static function status_transition_increases_exposure( $old_status, $new_status ) {
		if ( (string) $new_status === (string) $old_status ) {
			return false;
		}

		return self::get_status_exposure_rank( $new_status, 3 ) > self::get_status_exposure_rank( $old_status, 1 );
	}

	/**
	 * Rank how exposed a post status makes rendered content.
	 *
	 * 3 = world ('future' counts: cron flips it to 'publish' with no user context, so it
	 * must rank at scheduling time), 2 = privileged viewers only, 1 = not rendered on the
	 * frontend.
	 *
	 * @since 2.4.0
	 *
	 * @param string $status       Post status.
	 * @param int    $unknown_rank Fail-closed rank for statuses not in the map.
	 * @return int Exposure rank.
	 */
	protected static function get_status_exposure_rank( $status, $unknown_rank ) {
		$ranks = [
			'publish'    => 3,
			'future'     => 3,
			'private'    => 2,
			'draft'      => 1,
			'pending'    => 1,
			'trash'      => 1,
			'auto-draft' => 1,
			'inherit'    => 1,
		];

		return $ranks[ $status ] ?? $unknown_rank;
	}

	/**
	 * Extract the parent post ID from an autosave REST request's concrete path.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed $request WP_REST_Request instance.
	 * @return int Parent post ID, or 0 when it cannot be determined.
	 */
	protected static function get_autosave_route_parent_id( $request ) {
		if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
			return 0;
		}

		$route = $request->get_route();

		if ( ! is_string( $route ) || ! preg_match( '#/(\d+)/autosaves/?$#', $route, $matches ) ) {
			return 0;
		}

		return absint( $matches[1] );
	}

	/**
	 * Normalize the REST content parameter shape used by post and autosave routes.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed $request WP_REST_Request instance.
	 * @return string|null Content string when present, otherwise null.
	 */
	protected static function get_rest_request_content_param( $request ) {
		if ( ! is_object( $request ) || ! method_exists( $request, 'get_param' ) ) {
			return null;
		}

		$content = $request->get_param( 'content' );

		if ( is_string( $content ) ) {
			return $content;
		}

		if ( is_array( $content ) && isset( $content['raw'] ) && is_string( $content['raw'] ) ) {
			return $content['raw'];
		}

		return null;
	}

	/**
	 * Unslash incoming post content without trimming it.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed $content Incoming post content.
	 * @return string Unslashed content.
	 */
	protected static function unslash_post_content( $content ) {
		if ( ! is_string( $content ) ) {
			return '';
		}

		return function_exists( 'wp_unslash' ) ? wp_unslash( $content ) : stripslashes( $content );
	}

	/**
	 * Whether post data represents a core autosave revision.
	 *
	 * @since 2.4.0
	 *
	 * @param array $data    Slashed post data.
	 * @param array $postarr Sanitized post array.
	 * @return bool True when the row is an autosave revision.
	 */
	protected static function is_autosave_revision_data( $data, $postarr = [] ) {
		if ( ! is_array( $data ) || ! isset( $data['post_type'] ) || 'revision' !== $data['post_type'] ) {
			return false;
		}

		$post_name   = isset( $data['post_name'] ) && is_string( $data['post_name'] ) ? $data['post_name'] : '';
		$post_parent = isset( $data['post_parent'] ) ? absint( $data['post_parent'] ) : 0;

		if ( $post_parent && self::is_autosave_revision_name( $post_name, $post_parent ) ) {
			return true;
		}

		$revision_id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;

		if ( $revision_id && function_exists( 'wp_is_post_autosave' ) ) {
			return (bool) wp_is_post_autosave( $revision_id );
		}

		return false;
	}

	/**
	 * Whether a revision slug matches core's autosave naming convention.
	 *
	 * @since 2.4.0
	 *
	 * @param string $post_name   Revision post_name.
	 * @param int    $post_parent Parent post ID.
	 * @return bool True when this is an autosave slug.
	 */
	protected static function is_autosave_revision_name( $post_name, $post_parent ) {
		$post_parent = absint( $post_parent );

		if ( ! $post_parent || ! is_string( $post_name ) ) {
			return false;
		}

		return 0 === strpos( $post_name, $post_parent . '-autosave' );
	}

	/**
	 * Resolve the content that a REST save will actually persist.
	 *
	 * Core only sets post_content on the prepared object when the request included a
	 * content field, so a set value means "submitted" and an unset value means the
	 * stored content will be kept. Fall back to the stored content in the latter case
	 * so content-less updates are still gated on the post's real content.
	 *
	 * @since 2.4.0
	 *
	 * @param object $prepared_post Prepared post object.
	 * @param int    $post_id       Existing post ID, or 0 for a new post.
	 * @return string Effective content to validate.
	 */
	protected static function resolve_effective_post_content( $prepared_post, $post_id ) {
		if ( is_object( $prepared_post ) && isset( $prepared_post->post_content ) && is_string( $prepared_post->post_content ) ) {
			return $prepared_post->post_content;
		}

		$post_id = absint( $post_id );

		if ( $post_id && function_exists( 'get_post' ) ) {
			$stored = get_post( $post_id );

			if ( is_object( $stored ) && isset( $stored->post_content ) && is_string( $stored->post_content ) ) {
				return $stored->post_content;
			}
		}

		return '';
	}
}

GenerateBlocks_Save_Gate::get_instance()->init();
