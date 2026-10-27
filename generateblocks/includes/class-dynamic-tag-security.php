<?php
/**
 * Dynamic tag security utilities.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// The enforcement spine this class registers its dynamic-data rule with. Required
// here so the delegating methods below can never load without it, regardless of
// include order.
if ( ! class_exists( 'GenerateBlocks_Save_Gate' ) ) {
	require_once __DIR__ . '/class-save-gate.php';
}

/**
 * Class Dynamic_Tag_Security
 *
 * ARCHITECTURE — two independent layers guard dynamic data:
 *
 * 1. Render-time taint (PRIMARY, authoritative). Every post save stamps or
 *    clears a taint meta recording whether the content was last changed by a user
 *    who may not author dynamic data. At render time, any tainted frame suppresses
 *    dynamic tags for the whole descendant render tree. This layer is the security
 *    boundary and must hold entirely on its own, except for the unframed render
 *    paths documented below.
 *
 * 2. Save gate (convenience/fallback). Blocks restricted users from persisting
 *    dynamic-data markers in the first place, so a live page's tags are never
 *    silently rendered inert by an untrusted save landing taint on the post. Sites
 *    may disable it via `generateblocks_enforce_dynamic_data_save_gate` without
 *    weakening layer 1.
 *
 *    Enforcement lives in GenerateBlocks_Save_Gate — this class registers the
 *    'dynamic_data' rule with it and keeps thin delegating wrappers for its
 *    public save-gate methods. The gate's contract: a restricted user never
 *    persists NEW marker content, and never INCREASES the exposure of existing
 *    marker content (see the no-new-exposure exemption documented on the gate).
 *
 * KNOWN UNFRAMED RENDER PATHS:
 *
 * Legacy v1 dynamic-background CSS:
 * The pre-render CSS collector runs before `the_content` and flattens the host
 * post, reusable blocks, widgets, and integration-owned content into one attribute
 * pool. The original content source is therefore unavailable when a legacy dynamic
 * background URL resolves. For this one path, the save gate is the authoritative
 * authoring control. Inferring ownership from the global post would incorrectly
 * suppress trusted widget, Element, template, and Global Style CSS on a tainted
 * host. Sites that disable `generateblocks_enforce_dynamic_data_save_gate` also
 * opt out of this legacy-path protection. Output-time taint for this path requires
 * a future source-aware CSS collector; do not add a host-wide source fallback.
 *
 * Core block templates and template parts:
 * Core renders these sources through `do_blocks()` without a balanced hook that
 * carries the owning wp_template/wp_template_part post ID. Their saves still pass
 * through the gate. Do not infer ownership from the queried post: that would make
 * trusted site headers and footers inherit an Author post's taint. Renderers we
 * control should use render_with_content_source() around their existing render call.
 */
class GenerateBlocks_Dynamic_Tag_Security extends GenerateBlocks_Singleton {
	const UNTRUSTED_DYNAMIC_CONTENT_META_KEY = '_generateblocks_untrusted_dynamic_content';

	// Compatibility alias for the content-provenance marker's historical public name.
	const SAVED_VERSION_META_KEY = GenerateBlocks_Content_Version::META_KEY;

	// The first GB release that ships the event-handler gate. A post stamped BELOW this
	// version was last saved by a pre-gate GB and keeps legacy on* resolution; a missing
	// or invalid stamp is NOT proof of a pre-gate save, so it strips (fail closed).
	const EVENT_HANDLER_GATE_VERSION = '2.4.0-alpha.1';

	const SAVE_GATE_RULE_ID = 'dynamic_data';

	const CORE_BLOCK_SOURCE_FRAME_KEY = '__generateblocks_dynamic_tag_source_frame_id';

	const DISALLOWED_KEYS = [
		'post_password',
		'password',
		'user_pass',
		'user_activation_key',
	];

	/**
	 * Stack of known content-source frames.
	 *
	 * @var array<int, array{post_id:int, untrusted:bool, known:bool}>
	 */
	private static $source_stack = [];

	/**
	 * Stack of active core/block source frames.
	 *
	 * @var array<int, array{id:int, ref:int}>
	 */
	private static $render_block_source_frames = [];

	/**
	 * Monotonic ID used to match a render_block callback to its source frame.
	 *
	 * @var int
	 */
	private static $render_block_source_frame_id = 0;

	/**
	 * Per-request cache for post taint checks, keyed per blog.
	 *
	 * @var array<string, bool>
	 */
	private static $untrusted_content_cache = [];

	/**
	 * Per-request cache for event-handler gate checks, keyed per blog.
	 *
	 * @var array<string, bool>
	 */
	private static $event_handler_gate_cache = [];

	/**
	 * Whether the current REST dispatch is core's block-renderer endpoint.
	 *
	 * @var bool
	 */
	private static $is_block_renderer_rest_request = false;

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Priority 0: everything else on this hook (pattern preview caches and other
		// save-time renderers) must observe the taint already stamped. The stamp reads
		// only the committed post row and the current user, so nothing is gained by
		// running later. Hooks that fire earlier in the save flow (save_post,
		// rest_after_insert_{type}) still precede it — renderers there must declare a
		// source frame instead of relying on ordering.
		add_action( 'wp_after_insert_post', [ $this, 'update_untrusted_dynamic_content_meta' ], 0, 4 );
		add_action( 'rest_api_init', [ $this, 'register_dynamic_data_disabled_rest_field' ] );
		add_action( 'admin_notices', [ $this, 'maybe_render_dynamic_data_disabled_notice' ] );
		add_filter( 'the_content', [ $this, 'push_the_content_source' ], -PHP_INT_MAX );
		add_filter( 'the_content', [ $this, 'pop_the_content_source' ], PHP_INT_MAX );
		add_filter( 'render_block_data', [ $this, 'push_core_block_source' ], 10, 3 );
		add_filter( 'render_block', [ $this, 'pop_core_block_source' ], PHP_INT_MAX, 3 );
		add_filter( 'rest_pre_dispatch', [ $this, 'capture_block_renderer_rest_request' ], 1, 3 );

		// Save-gate enforcement (REST pre-insert, autosaves, wp_insert_post_data,
		// wp_insert_attachment_data) lives in GenerateBlocks_Save_Gate; this class
		// only contributes the dynamic-data rule.
		$this->register_save_gate_rule();
	}

	/**
	 * Register the dynamic-data rule with the save gate.
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	protected function register_save_gate_rule() {
		if ( ! class_exists( 'GenerateBlocks_Save_Gate' ) ) {
			return;
		}

		GenerateBlocks_Save_Gate::get_instance()->register_rule(
			[
				'id'         => self::SAVE_GATE_RULE_ID,
				'enforced'   => static function ( $context ) {
					/**
					 * Whether to enforce the dynamic-data save gate.
					 *
					 * @since 2.4.0
					 *
					 * @param bool  $enforce Whether to enforce the save gate.
					 * @param array $context Save context.
					 */
					return (bool) apply_filters( 'generateblocks_enforce_dynamic_data_save_gate', true, $context );
				},
				'user_can'   => [ __CLASS__, 'user_can_author_dynamic_data' ],
				'applies'    => static function ( $content ) {
					return self::should_validate_content( $content, false );
				},
				'message'    => static function () {
					return self::get_restricted_dynamic_content_message();
				},
				'error_code' => 'generateblocks_restricted_dynamic_content',
			]
		);
	}

	/**
	 * Determine if a given piece of content should be scanned for dynamic tag usage.
	 *
	 * @since 2.2.0
	 *
	 * @param string $content    The post content to evaluate.
	 * @param bool   $is_slashed Whether the content uses WordPress slashing. Default true.
	 * @return bool True when validation should proceed.
	 */
	public static function should_validate_content( $content, $is_slashed = true ) {
		$content = self::normalize_serialized_content( $content, $is_slashed );

		if ( '' === $content ) {
			return false;
		}

		$has_dynamic_tag_syntax = false !== strpos( $content, '{{' );
		$has_dynamic_attributes = false;

		if ( class_exists( 'GenerateBlocks_Dynamic_Content' ) && method_exists( 'GenerateBlocks_Dynamic_Content', 'content_has_dynamic_attribute_markers' ) ) {
			$has_dynamic_attributes = (bool) GenerateBlocks_Dynamic_Content::content_has_dynamic_attribute_markers( $content, false );
		}

		// Legacy v1 dynamic attributes (headline/button/image/container) render dynamic content
		// without a {{ tag and are not in the dynamic-tag block allow-list below. They must be
		// validated whenever present — independently of {{ — otherwise a stray {{ in unrelated
		// content (e.g. a paragraph) flips the branch into the allow-list path, finds no allowed
		// dynamic-tag block, and skips validation, bypassing the unreadable-post reference guard
		// and the legacy meta-key checks.
		if ( $has_dynamic_attributes ) {
			return true;
		}

		if ( ! $has_dynamic_tag_syntax ) {
			return (bool) apply_filters( 'generateblocks_force_validate_content', false, $content );
		}

		$block_names = self::get_dynamic_tag_enabled_blocks();

		if ( empty( $block_names ) ) {
			return false;
		}

		foreach ( $block_names as $block_name ) {
			if ( function_exists( 'has_block' ) && has_block( $block_name, $content ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'generateblocks_force_validate_content', false, $content );
	}

	/**
	 * Get safe user meta keys accessible without list_users capability.
	 *
	 * @since 2.1.4
	 *
	 * @return array<string>
	 */
	public static function get_safe_user_meta_keys() {
		$safe_keys = [
			'description',
			'first_name',
			'last_name',
			'nickname',
			'display_name',
			'user_nicename',
			'user_url',
			'locale',
			'show_admin_bar_front',
		];

		$safe_keys = apply_filters( 'generateblocks_safe_user_meta_keys', $safe_keys );

		return array_values( array_unique( array_filter( $safe_keys, 'is_string' ) ) );
	}

	/**
	 * Get the list of blocks that support GenerateBlocks dynamic tags.
	 *
	 * Mirrors the filter used when replacing tags so integrations stay in sync.
	 *
	 * @since 2.2.0
	 *
	 * @return array<int, string>
	 */
	protected static function get_dynamic_tag_enabled_blocks() {
		$blocks = [];

		if ( class_exists( 'GenerateBlocks_Dynamic_Tags' ) ) {
			$dynamic_tags = GenerateBlocks_Dynamic_Tags::get_instance();

			if ( method_exists( $dynamic_tags, 'get_allowed_blocks' ) ) {
				$blocks = $dynamic_tags->get_allowed_blocks();
			}
		}

		if ( ! is_array( $blocks ) ) {
			return [];
		}

		return array_values(
			array_filter(
				$blocks,
				function ( $block_name ) {
					return is_string( $block_name ) && '' !== $block_name;
				}
			)
		);
	}

	/**
	 * Track whether this REST request is core's server-side block renderer.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed  $response Response to replace the requested version with. Default false.
	 * @param object $server   REST server instance.
	 * @param object $request  Request used to generate the response.
	 * @return mixed Unchanged response.
	 */
	public function capture_block_renderer_rest_request( $response, $server, $request ) {
		unset( $server );

		self::$is_block_renderer_rest_request = false;

		if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
			return $response;
		}

		$route = $request->get_route();

		self::$is_block_renderer_rest_request = is_string( $route ) &&
			(bool) preg_match( '#^/wp/v2/block-renderer(?:/|$)#', $route );

		return $response;
	}

	/**
	 * Whether the current user is resolving client-supplied block-renderer output
	 * without the trusted dynamic-data authoring capability.
	 *
	 * @since 2.4.0
	 *
	 * @return bool
	 */
	public static function is_non_trusted_block_renderer_rest_request() {
		return self::$is_block_renderer_rest_request && ! self::user_can_author_dynamic_data();
	}

	/**
	 * Normalize serialized block content for validation.
	 *
	 * Internal validation callers pass unslashed content. WordPress's slashed
	 * wp_insert_post_data boundary is normalized once by GenerateBlocks_Save_Gate
	 * before it reaches them. Unslashing again would corrupt valid JSON escape
	 * sequences (for example, `\u0075seDynamicData`) before parse_blocks() can
	 * decode them. The default preserves the existing one-argument public API.
	 *
	 * @since 2.2.0
	 *
	 * @param mixed $content    Content string.
	 * @param bool  $is_slashed Whether the content uses WordPress slashing. Default true.
	 * @return string Normalized content string.
	 */
	public static function normalize_serialized_content( $content, $is_slashed = true ) {
		if ( ! is_string( $content ) ) {
			return '';
		}

		if ( $is_slashed ) {
			$content = function_exists( 'wp_unslash' ) ? wp_unslash( $content ) : stripslashes( $content );
		}

		return trim( $content );
	}

	/**
	 * Whether the current user may author dynamic-data references that read
	 * data at render time.
	 *
	 * @return bool
	 */
	public static function user_can_author_dynamic_data() {
		$default = current_user_can( 'unfiltered_html' ) || current_user_can( 'manage_options' );

		/**
		 * SECURITY: returning true lets this user author dynamic tags whose output may
		 * disclose protected meta, other users' meta, options, and cross-post data.
		 * Grant only to fully trusted roles.
		 *
		 * @since 2.4.0
		 *
		 * @param bool $can_author Whether the current user may author dynamic data.
		 */
		return (bool) apply_filters( 'generateblocks_user_can_author_dynamic_data', $default );
	}

	/**
	 * Determine whether a prospective save should be blocked for dynamic-data markers.
	 *
	 * Delegates to the save gate's decision layer for the dynamic-data rule.
	 *
	 * @since 2.4.0
	 *
	 * @param string $content   Incoming post content, already unslashed by the caller.
	 * @param int    $post_id   Existing post ID, or 0 for a new post.
	 * @param string $post_type Post type being saved.
	 * @return bool True when the save should be blocked.
	 */
	public static function save_is_restricted( $content, $post_id, $post_type ) {
		return GenerateBlocks_Save_Gate::get_instance()->save_is_restricted( $content, $post_id, $post_type, self::SAVE_GATE_RULE_ID );
	}

	/**
	 * Register REST pre-insert save gate filters for REST-exposed post types.
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	public function register_dynamic_data_save_gate_rest_filters() {
		GenerateBlocks_Save_Gate::get_instance()->register_rest_filters();
	}

	/**
	 * Block REST saves that introduce dynamic-data markers for restricted users.
	 *
	 * Delegates to the save gate, which enforces every registered rule.
	 *
	 * @since 2.4.0
	 *
	 * @param object $prepared_post An object representing the post prepared for the database.
	 * @param object $request       Request object.
	 * @return object|WP_Error The prepared post, or a restriction error.
	 */
	public function validate_dynamic_data_rest_save( $prepared_post, $request ) {
		return GenerateBlocks_Save_Gate::get_instance()->validate_rest_save( $prepared_post, $request );
	}

	/**
	 * Intercept Gutenberg autosave REST requests before core bypasses pre-insert errors.
	 *
	 * The trailing parameters are optional because the 2.2.x version of this public
	 * method took ( $response, $server, $request ) — a legacy three-argument caller
	 * must degrade to a no-op pass-through, never fatal on arity.
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
		return GenerateBlocks_Save_Gate::get_instance()->validate_autosave_rest_request( $response, $request, $route, $handler );
	}

	/**
	 * Catch classic/editor/programmatic saves that bypass REST pre-insert checks.
	 *
	 * Delegates to the save gate, which enforces every registered rule.
	 *
	 * @since 2.4.0
	 *
	 * @param array $data                Slashed post data.
	 * @param array $postarr             Sanitized post array.
	 * @param array $unsanitized_postarr Original unsanitized post array.
	 * @param bool  $update              Whether this is an update.
	 * @return array Slashed post data.
	 */
	public function validate_dynamic_data_insert_post_data( $data, $postarr, $unsanitized_postarr = null, $update = null ) {
		return GenerateBlocks_Save_Gate::get_instance()->validate_insert_post_data( $data, $postarr, $unsanitized_postarr, $update );
	}

	/**
	 * Get the reusable restricted dynamic-content save message.
	 *
	 * @since 2.4.0
	 *
	 * @return string Error message.
	 */
	protected static function get_restricted_dynamic_content_message() {
		return __( 'This content contains dynamic data, which your account doesn\'t have permission to save. Ask an administrator to make this change for you, or remove the dynamic data and try again.', 'generateblocks' );
	}

	/**
	 * Update the post-level dynamic-data taint after a post save.
	 *
	 * @param int     $post_id     Post ID.
	 * @param WP_Post $post        Post object.
	 * @param bool    $update      Whether this is an existing post being updated.
	 * @param WP_Post $post_before Previous post object.
	 * @return void
	 */
	public function update_untrusted_dynamic_content_meta( $post_id, $post, $update, $post_before ) {
		unset( $update );

		$post_id = absint( $post_id );

		if ( ! $post_id || ! is_object( $post ) ) {
			return;
		}

		// Skip all revisions, including autosaves. Only the published post row is rendered on
		// the frontend, so only it carries taint. Autosaves must NOT be processed here: core
		// redirects autosave-revision metadata to the parent, so clearing/marking an autosave's
		// taint would silently clear or flip the parent's taint (e.g. a clean autosave of a
		// tainted post would un-taint it). Untrusted marker autosaves are already blocked at the
		// save gate, so there is nothing to attribute to an autosave anyway.
		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
			return;
		}

		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
			return;
		}

		$user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;

		if ( ! $user_id ) {
			return;
		}

		$content = isset( $post->post_content ) && is_string( $post->post_content ) ? $post->post_content : '';

		if ( self::user_can_author_dynamic_data() ) {
			self::clear_untrusted_dynamic_content( $post_id );
			return;
		}

		// A restricted user's save that did not change the content did not author anything,
		// so it must not change the taint. This covers save paths that bypass the save gate
		// entirely — wp_publish_post()-style status flips fire wp_after_insert_post without
		// any insert-data filter — and keeps a no-op touch (trash, status change) from
		// flipping a trusted author's taint state in either direction. Trusted users are
		// handled above: their unchanged re-save still clears (the documented recovery flow).
		if (
			is_object( $post_before ) &&
			isset( $post_before->post_content ) &&
			is_string( $post_before->post_content ) &&
			$post_before->post_content === $content
		) {
			return;
		}

		self::mark_untrusted_dynamic_content( $post_id, $user_id );
	}

	/**
	 * Whether the given post has been saved by an untrusted dynamic-data author.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function post_has_untrusted_dynamic_content( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		$cache_key = self::get_untrusted_content_cache_key( $post_id );

		if ( isset( self::$untrusted_content_cache[ $cache_key ] ) ) {
			return self::$untrusted_content_cache[ $cache_key ];
		}

		if ( ! function_exists( 'get_post_meta' ) ) {
			self::$untrusted_content_cache[ $cache_key ] = false;
			return false;
		}

		$value = get_post_meta( $post_id, self::UNTRUSTED_DYNAMIC_CONTENT_META_KEY, true );

		self::$untrusted_content_cache[ $cache_key ] = ! empty( $value );

		return self::$untrusted_content_cache[ $cache_key ];
	}

	/**
	 * Get the innermost known source post ID for the current dynamic-tag render.
	 *
	 * @since 2.4.0
	 *
	 * @return int Source post ID, or 0 when no known source frame is active.
	 */
	public static function get_current_source_post_id() {
		if ( empty( self::$source_stack ) ) {
			return 0;
		}

		for ( $i = count( self::$source_stack ) - 1; $i >= 0; $i-- ) {
			$frame = self::$source_stack[ $i ];

			if ( ! is_array( $frame ) || empty( $frame['known'] ) ) {
				continue;
			}

			$post_id = isset( $frame['post_id'] ) ? absint( $frame['post_id'] ) : 0;

			if ( $post_id ) {
				return $post_id;
			}
		}

		return 0;
	}

	/**
	 * Whether a post's last save was stamped by a GB version below the event-handler gate.
	 *
	 * Legacy on* resolution requires positive evidence of a pre-gate save: the version
	 * stamp must EXIST and be below the gate. A missing, empty, or non-string stamp
	 * returns false (strip). Every post that can carry a dynamic tag was saved by
	 * GB >= 2.0, which always writes the stamp on save, so an absent stamp means a
	 * non-standard save path (a no-user programmatic save, stripped meta) — not an
	 * old site — and must not fail open.
	 *
	 * @since 2.4.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool True when the saved GB version proves a pre-gate save.
	 */
	public static function post_saved_before_event_handler_gate( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		$cache_key = self::get_untrusted_content_cache_key( $post_id );

		if ( isset( self::$event_handler_gate_cache[ $cache_key ] ) ) {
			return self::$event_handler_gate_cache[ $cache_key ];
		}

		$version = function_exists( 'get_post_meta' )
			? get_post_meta( $post_id, self::SAVED_VERSION_META_KEY, true )
			: '';

		self::$event_handler_gate_cache[ $cache_key ] = is_string( $version ) &&
			'' !== $version &&
			version_compare( $version, self::EVENT_HANDLER_GATE_VERSION, '<' );

		return self::$event_handler_gate_cache[ $cache_key ];
	}

	/**
	 * Whether the current source post predates the event-handler dynamic-tag gate.
	 *
	 * True ONLY when the innermost known source post carries a saved-GB-version stamp
	 * below the gate — positive evidence of a pre-gate save. Everything else strips:
	 * a known source with no stamp, AND unknown provenance (no known frame — widgets,
	 * hook-rendered content). Unknown-source content can never prove a pre-gate save,
	 * so it gets the safe default; the opt-in
	 * generateblocks_allow_dynamic_data_in_event_handlers filter remains the escape
	 * hatch for a site that must keep such a handler resolving.
	 *
	 * @since 2.4.0
	 *
	 * @return bool True when the current source should keep legacy event-handler output.
	 */
	public static function current_source_post_predates_event_handler_gate() {
		$post_id = self::get_current_source_post_id();

		return $post_id > 0 && self::post_saved_before_event_handler_gate( $post_id );
	}

	/**
	 * Build the per-request taint cache key for a post.
	 *
	 * Post IDs repeat across blogs, so on multisite a request that renders
	 * same-ID posts from more than one blog (switch_to_blog aggregators) must
	 * never be served another blog's cached verdict — scope the key by blog.
	 *
	 * @since 2.4.0
	 *
	 * @param int $post_id Post ID.
	 * @return string Cache key.
	 */
	protected static function get_untrusted_content_cache_key( $post_id ) {
		$blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;

		return $blog_id . ':' . absint( $post_id );
	}

	/**
	 * Mark a post's dynamic content as untrusted.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return void
	 */
	protected static function mark_untrusted_dynamic_content( $post_id, $user_id ) {
		if ( ! function_exists( 'update_post_meta' ) ) {
			return;
		}

		$value = [
			'user_id' => (int) $user_id,
			'time'    => time(),
		];

		update_post_meta( $post_id, self::UNTRUSTED_DYNAMIC_CONTENT_META_KEY, $value );
		self::$untrusted_content_cache[ self::get_untrusted_content_cache_key( $post_id ) ] = true;
	}

	/**
	 * Clear the untrusted marker from a post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	protected static function clear_untrusted_dynamic_content( $post_id ) {
		if ( function_exists( 'delete_post_meta' ) ) {
			delete_post_meta( $post_id, self::UNTRUSTED_DYNAMIC_CONTENT_META_KEY );
		}

		self::$untrusted_content_cache[ self::get_untrusted_content_cache_key( $post_id ) ] = false;
	}

	/**
	 * Expose the dynamic-data taint state to the block editor.
	 *
	 * Read-only edit-context field; the editor uses it to show a persistent
	 * notice while a post's dynamic data is disabled and to detect the save
	 * transitions that set or clear the taint.
	 *
	 * @return void
	 */
	public function register_dynamic_data_disabled_rest_field() {
		if ( ! function_exists( 'register_rest_field' ) || ! function_exists( 'get_post_types' ) ) {
			return;
		}

		$post_types = get_post_types(
			[
				'show_in_rest' => true,
			],
			'names'
		);

		foreach ( $post_types as $post_type ) {
			register_rest_field(
				$post_type,
				'generateblocks_dynamic_data_disabled',
				[
					'get_callback' => [ __CLASS__, 'get_dynamic_data_disabled_rest_field' ],
					'schema'       => [
						'description' => __( 'Whether GenerateBlocks dynamic data is disabled for this post: it was last saved by a restricted user and its saved content contains dynamic-data markers or embeds reusable blocks that may carry them.', 'generateblocks' ),
						'type'        => 'boolean',
						'readonly'    => true,
						'context'     => [ 'edit' ],
					],
				]
			);

			register_rest_field(
				$post_type,
				'generateblocks_content_has_dynamic_markers',
				[
					'get_callback' => [ __CLASS__, 'get_content_has_dynamic_markers_rest_field' ],
					'schema'       => [
						'description' => __( 'Whether the saved post content contains GenerateBlocks dynamic-data markers (dynamic tags or legacy dynamic attributes).', 'generateblocks' ),
						'type'        => 'boolean',
						'readonly'    => true,
						'context'     => [ 'edit' ],
					],
				]
			);
		}
	}

	/**
	 * REST get_callback for the dynamic-data disabled field.
	 *
	 * True only when the post is tainted AND its saved content can carry dynamic
	 * data — directly via dynamic markers, or indirectly via an embedded reusable
	 * block whose own content may hold dynamic tags (a tainted host suppresses its
	 * whole descendant render tree). Every restricted-user save stamps taint —
	 * including plain static posts — and reporting those would put a permanent
	 * "dynamic data disabled" banner on every post a restricted author touches.
	 *
	 * @param array $post_data Prepared post response data.
	 * @return bool
	 */
	public static function get_dynamic_data_disabled_rest_field( $post_data ) {
		$post_id = isset( $post_data['id'] ) ? absint( $post_data['id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		if ( ! self::post_has_untrusted_dynamic_content( $post_id ) ) {
			return false;
		}

		$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;

		if ( ! is_object( $post ) || ! isset( $post->post_content ) || ! is_string( $post->post_content ) ) {
			return false;
		}

		return self::should_validate_content( $post->post_content, false ) || self::content_embeds_reusable_blocks( $post->post_content );
	}

	/**
	 * Whether saved content embeds a reusable block (synced pattern) reference.
	 *
	 * A `core/block` ref carries no dynamic markers itself, but its referenced
	 * content may — and a tainted host suppresses that content's dynamic data.
	 * Used to decide whether a tainted post warrants the "dynamic data disabled"
	 * notice even though its own content is static.
	 *
	 * @since 2.4.0
	 *
	 * @param string $content Saved post content.
	 * @return bool
	 */
	protected static function content_embeds_reusable_blocks( $content ) {
		return is_string( $content ) && function_exists( 'has_block' ) && has_block( 'core/block', $content );
	}

	/**
	 * REST get_callback for the dynamic-markers field.
	 *
	 * Uses the same marker detection as the save-time taint hook so the editor's
	 * re-enable notice can never drift from what the server actually taints —
	 * legacy v1 dynamic attributes carry no `{{` for the client to sniff.
	 *
	 * @param array $post_data Prepared post response data.
	 * @return bool
	 */
	public static function get_content_has_dynamic_markers_rest_field( $post_data ) {
		$post_id = isset( $post_data['id'] ) ? absint( $post_data['id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;

		if ( ! is_object( $post ) || ! isset( $post->post_content ) || ! is_string( $post->post_content ) ) {
			return false;
		}

		return self::should_validate_content( $post->post_content, false );
	}

	/**
	 * Show a warning on the classic post edit screen while dynamic data is disabled.
	 *
	 * The block editor renders its own persistent notice from the REST field, so
	 * this only covers non-block-editor edit screens.
	 *
	 * @return void
	 */
	public function maybe_render_dynamic_data_disabled_notice() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! is_object( $screen ) || empty( $screen->base ) || 'post' !== $screen->base ) {
			return;
		}

		$post = function_exists( 'get_post' ) ? get_post() : null;

		if ( ! is_object( $post ) || empty( $post->ID ) ) {
			return;
		}

		if ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) {
			return;
		}

		if ( ! self::post_has_untrusted_dynamic_content( $post->ID ) ) {
			return;
		}

		// Same gate as the REST field: only warn when the saved content can carry
		// dynamic data, so tainted-but-static posts stay quiet.
		if ( ! isset( $post->post_content ) || ! is_string( $post->post_content ) ) {
			return;
		}

		if ( self::should_validate_content( $post->post_content, false ) ) {
			$message = __( 'Dynamic data on this page is disabled because it was last saved by a user who cannot author dynamic data. Ask an administrator or another trusted user to re-save this page to restore it.', 'generateblocks' );
		} elseif ( self::content_embeds_reusable_blocks( $post->post_content ) ) {
			// The page's own content is static; anything disabled lives inside the
			// embedded reusable blocks, so hedge the wording accordingly.
			$message = __( 'This page was last saved by a user who cannot author dynamic data, so any dynamic data inside its embedded patterns will not display. Ask an administrator or another trusted user to re-save this page to restore it.', 'generateblocks' );
		} else {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Push a source frame around the current post's own the_content render.
	 *
	 * The frame is attributed to the global $post unconditionally. Core rewrites
	 * the string before this filter runs (more/teaser span injection, nextpage
	 * splits), so matching the filtered content against stored post_content is
	 * not reliable — a tainted post containing a core/more block would dodge the
	 * match and fail open. Fail closed instead: the_content output while a post
	 * is the current post belongs to that post.
	 *
	 * @param string $content Content being filtered.
	 * @return string
	 */
	public function push_the_content_source( $content ) {
		global $post;

		$post_id = is_object( $post ) && isset( $post->ID ) ? absint( $post->ID ) : 0;

		if ( $post_id ) {
			self::push_content_source( $post_id );

			// Fail closed on previews. When an untrusted user previews their own
			// draft, core (_set_preview) swaps their unsaved autosave into
			// $post->post_content. That autosave never passed the save gate, so its
			// dynamic tags would otherwise resolve against the still-clean parent's
			// frame and disclose data. Treat the untrusted previewer's swapped-in
			// content as untrusted so the render-time taint layer suppresses it on
			// its own — independently of the save gate. Trusted users are exempt:
			// they may author dynamic data, so their previews are never gated.
			if ( self::is_untrusted_autosave_preview( $post_id ) ) {
				self::mark_current_frame_untrusted();
			}
		} else {
			self::push_unknown_content_source();
		}

		return $content;
	}

	/**
	 * Whether this request is an untrusted user previewing their own autosave of
	 * the given post.
	 *
	 * Core's preview flow (_set_preview) replaces the queried post's content with
	 * the current user's autosave, which bypasses the save gate. This lets the
	 * render-time taint layer fail closed on that swapped-in content when the
	 * previewer cannot author dynamic data — the taint layer's independent guard
	 * for the preview path, needed when the save gate is disabled by filter.
	 *
	 * @since 2.4.0
	 *
	 * @param int $post_id Post being rendered.
	 * @return bool
	 */
	protected static function is_untrusted_autosave_preview( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		if ( ! function_exists( 'is_preview' ) || ! is_preview() ) {
			return false;
		}

		// Only the previewed post gets the autosave swap; secondary content on the
		// same page keeps resolving normally.
		if ( ! function_exists( 'get_queried_object_id' ) || absint( get_queried_object_id() ) !== $post_id ) {
			return false;
		}

		// Trusted users may author dynamic data; their previews are never gated.
		if ( self::user_can_author_dynamic_data() ) {
			return false;
		}

		// No autosave means core renders the last-saved content, which already
		// passed the save gate (or predates it) — nothing extra to fail closed on.
		// Mirror core's _set_preview(), which swaps in wp_get_post_autosave( $post->ID )
		// — the newest autosave from ANY user, not just the current one. Scoping this to
		// the current user's autosave would miss another (equally untrusted) user's marker
		// autosave that core actually swaps in, so query it the same way core does.
		if ( ! function_exists( 'wp_get_post_autosave' ) ) {
			return false;
		}

		return ! empty( wp_get_post_autosave( $post_id ) );
	}

	/**
	 * Force the current source frame to be treated as untrusted.
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	protected static function mark_current_frame_untrusted() {
		if ( empty( self::$source_stack ) ) {
			return;
		}

		$index = count( self::$source_stack ) - 1;

		self::$source_stack[ $index ]['untrusted'] = true;
	}

	/**
	 * Pop the source frame pushed for the current the_content render.
	 *
	 * @param string $content Content being filtered.
	 * @return string
	 */
	public function pop_the_content_source( $content ) {
		self::pop_content_source();

		return $content;
	}

	/**
	 * Push a frame when core/block begins rendering a referenced wp_block post.
	 *
	 * @param array         $parsed_block Parsed block.
	 * @param array         $source_block Unmodified source block.
	 * @param WP_Block|null $parent_block Parent block.
	 * @return array
	 */
	public function push_core_block_source( $parsed_block, $source_block, $parent_block ) {
		unset( $source_block, $parent_block );

		if ( ! self::is_core_block_with_ref( $parsed_block ) ) {
			return $parsed_block;
		}

		$ref      = absint( $parsed_block['attrs']['ref'] );
		$frame_id = ++self::$render_block_source_frame_id;

		self::push_content_source( $ref );

		$parsed_block[ self::CORE_BLOCK_SOURCE_FRAME_KEY ] = $frame_id;
		self::$render_block_source_frames[]                = [
			'id'  => $frame_id,
			'ref' => $ref,
		];

		return $parsed_block;
	}

	/**
	 * Pop a core/block source frame after the block finishes rendering.
	 *
	 * @param string   $block_content Block content.
	 * @param array    $block         Parsed block.
	 * @param WP_Block $instance      Block instance.
	 * @return string
	 */
	public function pop_core_block_source( $block_content, $block, $instance ) {
		unset( $instance );

		if ( ! self::is_core_block_with_ref( $block ) || empty( $block[ self::CORE_BLOCK_SOURCE_FRAME_KEY ] ) ) {
			return $block_content;
		}

		if ( empty( self::$render_block_source_frames ) ) {
			return $block_content;
		}

		$frame    = end( self::$render_block_source_frames );
		$frame_id = absint( $block[ self::CORE_BLOCK_SOURCE_FRAME_KEY ] );
		$ref      = absint( $block['attrs']['ref'] );

		if (
			! is_array( $frame ) ||
			empty( $frame['id'] ) ||
			empty( $frame['ref'] ) ||
			absint( $frame['id'] ) !== $frame_id ||
			absint( $frame['ref'] ) !== $ref
		) {
			return $block_content;
		}

		array_pop( self::$render_block_source_frames );
		self::pop_content_source();

		return $block_content;
	}

	/**
	 * Render arbitrary content while declaring its post_content source.
	 *
	 * @param int      $post_id  Source post ID.
	 * @param callable $callback Callback to execute inside the source frame.
	 * @return mixed
	 */
	public static function render_with_content_source( $post_id, $callback ) {
		if ( ! is_callable( $callback ) ) {
			return '';
		}

		self::push_content_source( $post_id );

		try {
			return call_user_func( $callback );
		} finally {
			self::pop_content_source();
		}
	}

	/**
	 * Whether any active source frame should suppress dynamic data.
	 *
	 * @return bool
	 */
	public static function should_suppress_dynamic_data() {
		if ( empty( self::$source_stack ) ) {
			return false;
		}

		foreach ( self::$source_stack as $frame ) {
			if ( ! empty( $frame['untrusted'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace every registered dynamic tag in content with an empty string.
	 *
	 * @param string      $content    Rendered block content.
	 * @param string|null $block_html Optional source block HTML used to find tags.
	 * @return string
	 */
	public static function replace_dynamic_tags_with_empty( $content, $block_html = null ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}

		$block_html = is_string( $block_html ) ? $block_html : $content;

		if ( false === strpos( $block_html, '{{' ) ) {
			return $content;
		}

		if (
			! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ||
			! method_exists( 'GenerateBlocks_Register_Dynamic_Tag', 'find_matches' ) ||
			! method_exists( 'GenerateBlocks_Register_Dynamic_Tag', 'get_tags' )
		) {
			return $content;
		}

		$matches      = GenerateBlocks_Register_Dynamic_Tag::find_matches(
			$block_html,
			GenerateBlocks_Register_Dynamic_Tag::get_tags()
		);
		$replacements = [];

		foreach ( $matches as $match ) {
			$full_tag = isset( $match[0] ) ? (string) $match[0] : '';

			if ( '' === $full_tag ) {
				continue;
			}

			// Always strip the bare tag. A matching protocol-prefixed occurrence may
			// add a second, longer key below, but must not hide this form elsewhere.
			$replacements[ $full_tag ] = '';

			if ( method_exists( 'GenerateBlocks_Register_Dynamic_Tag', 'maybe_prepend_protocol' ) ) {
				$full_tag = GenerateBlocks_Register_Dynamic_Tag::maybe_prepend_protocol( $block_html, $full_tag );
			}

			$replacements[ $full_tag ] = '';
		}

		if ( empty( $replacements ) ) {
			return $content;
		}

		if ( method_exists( 'GenerateBlocks_Register_Dynamic_Tag', 'replace_tags_in_content' ) ) {
			return GenerateBlocks_Register_Dynamic_Tag::replace_tags_in_content( $content, $replacements );
		}

		return strtr( $content, $replacements );
	}

	/**
	 * Push a known source frame.
	 *
	 * @param int $post_id Source post ID.
	 * @return void
	 */
	protected static function push_content_source( $post_id ) {
		$post_id = absint( $post_id );

		self::$source_stack[] = [
			'post_id'   => $post_id,
			'untrusted' => $post_id ? self::post_has_untrusted_dynamic_content( $post_id ) : false,
			'known'     => true,
		];
	}

	/**
	 * Push a neutral frame when the_content runs with no known source post.
	 *
	 * The neutral frame resolves dynamic tags only when no parent frame is tainted.
	 * A tainted parent still suppresses descendants.
	 *
	 * @return void
	 */
	protected static function push_unknown_content_source() {
		self::$source_stack[] = [
			'post_id'   => 0,
			'untrusted' => false,
			'known'     => false,
		];
	}

	/**
	 * Pop the current source frame.
	 *
	 * @return void
	 */
	protected static function pop_content_source() {
		if ( ! empty( self::$source_stack ) ) {
			array_pop( self::$source_stack );
		}
	}

	/**
	 * Whether a parsed block is a reusable block reference.
	 *
	 * @param mixed $block Parsed block.
	 * @return bool
	 */
	protected static function is_core_block_with_ref( $block ) {
		return is_array( $block ) &&
			isset( $block['blockName'] ) &&
			'core/block' === $block['blockName'] &&
			! empty( $block['attrs']['ref'] ) &&
			0 < absint( $block['attrs']['ref'] );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Removed 2.2.x validation API — fatal-prevention stubs.
	 *
	 * The signature/regex save-validation engine shipped in 2.2.0 was replaced
	 * in 2.4.0 by the post-source taint model and the dynamic-data save gate.
	 * These public methods shipped in stable releases, so no-op stubs remain to
	 * guarantee an external caller can never fatal on update. Nothing in
	 * GenerateBlocks, Pro, or Cloud calls them; real enforcement lives in the
	 * 2.4.0 engine regardless of what these return.
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Stub for the removed 2.2.x content validator.
	 *
	 * @deprecated 2.4.0 No-op stub; see the save gate (save_is_restricted()).
	 *
	 * @param string $content Serialized post content.
	 * @return true
	 */
	public static function validate_content( $content ) {
		unset( $content );

		return true;
	}

	/**
	 * Stub for the removed 2.2.x signature-aware content validator.
	 *
	 * @deprecated 2.4.0 No-op stub; see the save gate (save_is_restricted()).
	 *
	 * @param string $content             Serialized post content.
	 * @param array  $existing_signatures Ignored.
	 * @return true
	 */
	public static function validate_content_with_existing_signatures( $content, $existing_signatures = [] ) {
		unset( $content, $existing_signatures );

		return true;
	}

	/**
	 * Stub for the removed 2.2.x signature collector.
	 *
	 * @deprecated 2.4.0 No-op stub; the signature model no longer exists.
	 *
	 * @param string $content Serialized post content.
	 * @return array Always empty.
	 */
	public static function get_restricted_reference_signatures( $content ) {
		unset( $content );

		return [];
	}

	/**
	 * Stub for the removed 2.2.x user-meta rule toggle.
	 *
	 * @deprecated 2.4.0 No-op stub; see user_can_author_dynamic_data().
	 *
	 * @return false
	 */
	public static function should_validate_user_meta_fields() {
		return false;
	}

	/**
	 * Stub for the removed 2.2.x user meta key validator.
	 *
	 * @deprecated 2.4.0 No-op stub; meta keys are gated at render by GenerateBlocks_Meta_Handler.
	 *
	 * @param string $field_name The meta key.
	 * @return true
	 */
	public static function validate_user_meta_field_name( $field_name ) {
		unset( $field_name );

		return true;
	}

	/**
	 * Stub for the removed 2.2.x post meta key validator.
	 *
	 * @deprecated 2.4.0 No-op stub; meta keys are gated at render by GenerateBlocks_Meta_Handler.
	 *
	 * @param string $field_name The meta key.
	 * @return true
	 */
	public static function validate_post_meta_field_name( $field_name ) {
		unset( $field_name );

		return true;
	}

	/**
	 * Stub for the removed 2.2.x term meta key validator.
	 *
	 * @deprecated 2.4.0 No-op stub; meta keys are gated at render by GenerateBlocks_Meta_Handler.
	 *
	 * @param string $field_name The meta key.
	 * @return true
	 */
	public static function validate_term_meta_field_name( $field_name ) {
		unset( $field_name );

		return true;
	}

	/**
	 * Stub for the removed 2.2.x wp_insert_post_data callback.
	 *
	 * @deprecated 2.4.0 Pass-through; the 2.4.0 engine hooks its own enforcement.
	 *
	 * @param mixed $data    Post data.
	 * @param mixed $postarr Raw post data.
	 * @return mixed Unmodified $data.
	 */
	public function validate_content_before_save( $data, $postarr = [] ) {
		unset( $postarr );

		return $data;
	}

	/**
	 * Stub for the removed 2.2.x REST pre-insert callback.
	 *
	 * @deprecated 2.4.0 Pass-through; the 2.4.0 engine hooks its own enforcement.
	 *
	 * @param mixed $prepared_post Post object being prepared.
	 * @param mixed $request       REST request.
	 * @return mixed Unmodified $prepared_post.
	 */
	public function validate_content_rest( $prepared_post, $request = null ) {
		unset( $request );

		return $prepared_post;
	}

	/**
	 * Stub for the removed 2.2.x REST filter registration.
	 *
	 * @deprecated 2.4.0 No-op stub; the 2.4.0 engine registers its own filters.
	 *
	 * @return void
	 */
	public function register_rest_validation_for_post_types() {}
}

GenerateBlocks_Dynamic_Tag_Security::get_instance()->init();

if ( ! function_exists( 'generateblocks_user_can_author_dynamic_data' ) ) {
	/**
	 * Whether the current user may author dynamic-data references that read
	 * restricted data. Thin global wrapper around the class method so themes and
	 * partner plugins have a stable entry point.
	 *
	 * @since 2.4.0
	 *
	 * @return bool
	 */
	function generateblocks_user_can_author_dynamic_data() {
		return GenerateBlocks_Dynamic_Tag_Security::user_can_author_dynamic_data();
	}
}
