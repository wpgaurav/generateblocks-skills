<?php
/**
 * Deprecated functions.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Return the only supported local CSS output mode.
 *
 * This callback is no longer registered because the corresponding filter is
 * not part of inline-only output. It remains callable for compatibility.
 *
 * @since 1.0
 * @deprecated 2.5.0 Local block CSS is always inline.
 *
 * @param string $method Former CSS output method.
 * @return string Always `inline`.
 */
function generateblocks_set_css_print_method( $method ) {
	unset( $method );

	return 'inline';
}

/**
 * Redirect to the Dashboard page on single plugin activation.
 *
 * @since 0.1
 * @deprecated 2.0.0
 */
function generateblocks_dashboard_redirect() {
	$do_redirect = apply_filters( 'generateblocks_do_activation_redirect', get_option( 'generateblocks_do_activation_redirect', false ) );

	if ( $do_redirect ) {
		delete_option( 'generateblocks_do_activation_redirect' );
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=generateblocks' ) ) );
		exit;
	}
}

/**
 * Adds a redirect option during plugin activation on non-multisite installs.
 *
 * @since 0.1
 * @deprecated 2.0.0
 *
 * @param bool $network_wide Whether or not the plugin is being network activated.
 */
function generateblocks_do_activate( $network_wide = false ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to do a redirect. False positive.
	if ( ! $network_wide && ! isset( $_GET['activate-multi'] ) ) {
		update_option( 'generateblocks_do_activation_redirect', true );
	}
}

/**
 * Output permissions for use in admin objects.
 *
 * @deprecated 2.1.0
 * @return void
 */
function generateblocks_admin_head_scripts() {
	$permissions = apply_filters(
		'generateblocks_permissions',
		[
			'isAdminUser'       => current_user_can( 'manage_options' ),
			'canEditPosts'      => current_user_can( 'edit_posts' ),
			'isGbProActive'     => is_plugin_active( 'generateblocks-pro/plugin.php' ),
			'isGpPremiumActive' => is_plugin_active( 'gp-premium/gp-premium.php' ),
		]
	);

	$permission_object = wp_json_encode( $permissions );

	printf(
		'<script>
				const gbPermissions = %s;
				Object.freeze( gbPermissions );
		</script>',
		$permission_object // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
