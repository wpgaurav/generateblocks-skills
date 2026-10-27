<?php
/**
 * Tracks the GenerateBlocks version that last saved supported block content.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Own the saved-content version marker used by compatibility gates.
 */
class GenerateBlocks_Content_Version extends GenerateBlocks_Singleton {
	/**
	 * Historical meta key retained to avoid migrating existing version markers.
	 */
	const META_KEY = '_generateblocks_dynamic_css_version';

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'save_post', array( $this, 'update_saved_version' ), 10, 2 );
	}

	/**
	 * Record which GenerateBlocks version last saved supported block content.
	 *
	 * @param int     $post_id Current post ID.
	 * @param WP_Post $post    Current post.
	 * @return int Current post ID.
	 */
	public function update_saved_version( $post_id, $post ) {
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );

		if ( $is_autosave || $is_revision || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( ! is_object( $post ) || ! isset( $post->post_content ) || ! is_string( $post->post_content ) ) {
			return $post_id;
		}

		$content = $post->post_content;

		if ( false !== strpos( $content, 'wp:generateblocks' ) ) {
			update_post_meta( $post_id, self::META_KEY, sanitize_text_field( GENERATEBLOCKS_VERSION ) );
		} else {
			delete_post_meta( $post_id, self::META_KEY );
		}

		return $post_id;
	}
}

GenerateBlocks_Content_Version::get_instance()->init();
