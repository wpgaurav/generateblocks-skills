<?php
/**
 * Handles frontend inline block CSS output.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Output page-local block CSS inline.
 *
 * @since 2.5.0
 */
class GenerateBlocks_Inline_CSS extends GenerateBlocks_Singleton {
	/**
	 * Whether the normal frontend CSS output pass has started.
	 *
	 * Render callbacks can run before this pass when another plugin calls
	 * do_blocks() early. Ignoring those calls prevents discarded renders from
	 * marking block CSS as already processed before the real page render.
	 *
	 * @var bool
	 */
	private static $css_output_started = false;

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_output' ) );
	}

	/**
	 * Mark the normal frontend CSS output pass as started.
	 *
	 * Public only so the deprecated GenerateBlocks_Enqueue_CSS facade can
	 * preserve its former enqueue_dynamic_css() behavior.
	 *
	 * @internal
	 */
	public static function start_css_output() {
		self::$css_output_started = true;
	}

	/**
	 * Check whether render callbacks can process block CSS safely.
	 *
	 * Block themes render template blocks before the normal stylesheet action,
	 * so their render callbacks must be allowed to collect CSS immediately.
	 *
	 * @return bool Whether rendered block CSS can be processed.
	 */
	public static function can_process_block_css() {
		return self::$css_output_started || ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() );
	}

	/**
	 * Register frontend CSS output.
	 */
	public function register_output() {
		$dynamic_css_priority = apply_filters( 'generateblocks_dynamic_css_priority', 25 );

		add_action( 'wp_enqueue_scripts', array( $this, 'print_inline_css' ), $dynamic_css_priority );
	}

	/**
	 * Add current local block CSS inline in the document head.
	 */
	public function print_inline_css() {
		self::start_css_output();

		// Build CSS from the current content and any registered integrations.
		generateblocks_get_dynamic_css();

		$css = generateblocks_get_frontend_block_css();

		if ( ! is_string( $css ) ) {
			return;
		}

		$css .= generateblocks_get_container_width_css();

		if ( '' === $css ) {
			return;
		}

		// Add a handle for WordPress and integrations to attach inline styles to.
		wp_register_style( 'generateblocks', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'generateblocks' );

		$css = html_entity_decode( $css, ENT_QUOTES, 'UTF-8' );

		wp_add_inline_style(
			'generateblocks',
			wp_strip_all_tags( $css ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}

GenerateBlocks_Inline_CSS::get_instance()->init();
