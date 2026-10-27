<?php
/**
 * Deprecated CSS output API compatibility.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Preserve the public API of the former generated-file CSS class.
 *
 * Active local CSS output is owned by GenerateBlocks_Inline_CSS.
 *
 * @deprecated 2.5.0 Use GenerateBlocks_Inline_CSS.
 */
class GenerateBlocks_Enqueue_CSS {
	/**
	 * Instance.
	 *
	 * @access private
	 * @var object Instance
	 * @since 0.1
	 */
	private static $instance;

	/**
	 * Initiator.
	 *
	 * @since 0.1
	 * @return object Initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor retained for compatibility.
	 */
	public function __construct() {}

	/**
	 * Check whether rendered blocks can process CSS safely.
	 *
	 * @deprecated 2.5.0 Use GenerateBlocks_Inline_CSS::can_process_block_css().
	 *
	 * @return bool Whether rendered block CSS can be processed.
	 */
	public static function can_enqueue() {
		return GenerateBlocks_Inline_CSS::can_process_block_css();
	}

	/**
	 * Register frontend CSS output through the active implementation.
	 *
	 * @deprecated 2.5.0 Use GenerateBlocks_Inline_CSS::register_output().
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		GenerateBlocks_Inline_CSS::get_instance()->register_output();
	}

	/**
	 * Add current local block CSS through the active implementation.
	 *
	 * @deprecated 2.5.0 Use GenerateBlocks_Inline_CSS::print_inline_css().
	 *
	 * @return void
	 */
	public function print_inline_css() {
		GenerateBlocks_Inline_CSS::get_instance()->print_inline_css();
	}

	/*
	 * -------------------------------------------------------------------------
	 * Removed generated-file API — fatal-prevention stubs.
	 *
	 * These public methods shipped in stable releases. They remain inert so an
	 * external caller cannot fatal after local block CSS became inline-only.
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Stub for the removed generated-file page ID resolver.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return false
	 */
	public function page_id() {
		return false;
	}

	/**
	 * Return the only supported local CSS output mode.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return string Always `inline`.
	 */
	public function mode() {
		return 'inline';
	}

	/**
	 * Preserve the former callback's rendered-block CSS readiness signal.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * Generated-file lookup and enqueueing are intentionally not performed.
	 *
	 * @return void
	 */
	public function enqueue_dynamic_css() {
		GenerateBlocks_Inline_CSS::start_css_output();
	}

	/**
	 * Stub for the removed generated-file builder.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return false
	 */
	public function make_css() {
		return false;
	}

	/**
	 * Stub for the removed generated-file writability check.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return false
	 */
	public function can_write() {
		return false;
	}

	/**
	 * Stub for the removed generated-file path resolver.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @param string $target Former path or URI target.
	 * @return string Always empty.
	 */
	public function file( $target = 'path' ) {
		unset( $target );

		return '';
	}

	/**
	 * Stub for the removed generated-file option registration.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return void
	 */
	public function add_options() {}

	/**
	 * Stub for the removed generated-file post invalidation callback.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @param int   $post_id Current post ID.
	 * @param mixed $post    Current post.
	 * @return int Current post ID.
	 */
	public function post_update_option( $post_id, $post ) {
		unset( $post );

		return $post_id;
	}

	/**
	 * Stub for the removed reusable-block invalidation callback.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @param int   $post_id Current post ID.
	 * @param mixed $post    Current post.
	 * @return int Current post ID.
	 */
	public function wp_block_update( $post_id, $post ) {
		unset( $post );

		return $post_id;
	}

	/**
	 * Stub for the removed generated-file validity check.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return false
	 */
	public function needs_update() {
		return false;
	}

	/**
	 * Stub for the removed generated-file throttle update.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return void
	 */
	public function update_saved_time() {}

	/**
	 * Pass through widget settings without invalidating generated files.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @param mixed $instance Widget settings.
	 * @return mixed Unmodified widget settings.
	 */
	public function force_file_regen_on_widget_save( $instance ) {
		return $instance;
	}

	/**
	 * Stub for the removed Customizer invalidation callback.
	 *
	 * @deprecated 2.5.0 Local block CSS is always inline.
	 *
	 * @return void
	 */
	public function force_file_regen_on_customizer_save() {}
}
