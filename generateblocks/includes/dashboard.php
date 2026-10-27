<?php
/**
 * Our admin Dashboard.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'admin_menu', 'generateblocks_register_dashboard', 9 );
/**
 * Register our Dashboard page.
 *
 * @since 0.1
 */
function generateblocks_register_dashboard() {
	$dashboard = add_menu_page(
		__( 'GenerateBlocks', 'generateblocks' ),
		__( 'GenerateBlocks', 'generateblocks' ),
		'manage_options',
		'generateblocks',
		'generateblocks_do_dashboard'
	);

	$show_upgrade_menu = apply_filters(
		'generateblocks_show_upgrade_menu',
		! defined( 'GENERATEBLOCKS_PRO_VERSION' )
	);

	if ( $show_upgrade_menu ) {
		add_submenu_page(
			'generateblocks',
			__( 'Upgrade', 'generateblocks' ),
			__( 'Upgrade', 'generateblocks' ),
			'manage_options',
			'generateblocks-upgrade',
			'generateblocks_do_dashboard'
		);
	}

	add_action( "admin_print_styles-$dashboard", 'generateblocks_enqueue_dashboard_scripts' );
}

/**
 * Add our Dashboard-specific scripts.
 *
 * @since 1.0.0
 */
function generateblocks_enqueue_dashboard_scripts() {
	$assets_file = GENERATEBLOCKS_DIR . 'dist/dashboard.asset.php';
	$compiled_assets = file_exists( $assets_file )
		? require $assets_file
		: false;

	$assets =
		isset( $compiled_assets['dependencies'] ) &&
		isset( $compiled_assets['version'] )
		? $compiled_assets
		: [
			'dependencies' => [],
			'version' => filemtime( GENERATEBLOCKS_DIR . 'dist/dashboard.js' ),
		];

	wp_enqueue_script(
		'generateblocks-dashboard',
		GENERATEBLOCKS_DIR_URL . 'dist/dashboard.js',
		$assets['dependencies'],
		$assets['version'],
		true
	);

	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'generateblocks-dashboard', 'generateblocks' );
	}

	wp_localize_script(
		'generateblocks-dashboard',
		'generateblocksDashboard',
		array(
			'gpImage'       => esc_url( GENERATEBLOCKS_DIR_URL ) . 'assets/images/generatepress-sites.png',
			'gpLogo'        => esc_url( GENERATEBLOCKS_DIR_URL ) . 'assets/images/generatepress.svg',
			'gbVersion'     => GENERATEBLOCKS_VERSION,
			'gbpVersion'    => defined( 'GENERATEBLOCKS_PRO_VERSION' )
				? GENERATEBLOCKS_PRO_VERSION
				: false,
		)
	);

	wp_enqueue_style(
		'generateblocks-dashboard',
		GENERATEBLOCKS_DIR_URL . 'dist/dashboard.css',
		array( 'wp-components' ),
		GENERATEBLOCKS_VERSION
	);
}

/**
 * Get a list of our Dashboard pages.
 *
 * @since 1.2.0
 */
function generateblocks_get_dashboard_pages() {
	return apply_filters(
		'generateblocks_dashboard_screens',
		array(
			'generateblocks_page_generateblocks-settings',
		)
	);
}

add_filter( 'admin_body_class', 'generateblocks_set_admin_body_classes' );
/**
 * Add admin body classes when needed.
 *
 * @since 1.2.0
 * @param string $classes The existing classes.
 */
function generateblocks_set_admin_body_classes( $classes ) {
	$dashboard_pages = generateblocks_get_dashboard_pages();
	$current_screen = get_current_screen();

	if ( $current_screen && in_array( $current_screen->id, $dashboard_pages ) ) {
		$classes .= ' generateblocks-dashboard-page';
	}

	// The Settings screen is always rendered with the GeneratePress design-system
	// page frame, by GenerateBlocks or by the companion plugin that owns it. The
	// class is added server-side so the frame's spacing applies before React mounts.
	if ( $current_screen && 'generateblocks_page_generateblocks-settings' === $current_screen->id ) {
		$classes .= ' gb-admin-page-active';
	}

	return $classes;
}

add_action( 'in_admin_header', 'generateblocks_do_dashboard_headers' );
/**
 * Add our Dashboard headers.
 *
 * @since 1.3.0
 */
function generateblocks_do_dashboard_headers() {
	$dashboard_pages = generateblocks_get_dashboard_pages();
	$current_screen = get_current_screen();

	if ( ! $current_screen || ! in_array( $current_screen->id, $dashboard_pages ) ) {
		return;
	}

	// The Settings screen renders the design-system page header itself, whether
	// GenerateBlocks or a companion plugin owns it. This header remains for the
	// screens that have not adopted the design-system frame.
	if ( 'generateblocks_page_generateblocks-settings' === $current_screen->id ) {
		return;
	}

	generateblocks_do_dashboard_header();
}

add_action( 'admin_enqueue_scripts', 'generateblocks_enqueue_global_dashboard_scripts' );
/**
 * Add our scripts to the page.
 *
 * @since 0.1
 */
function generateblocks_enqueue_global_dashboard_scripts() {
	wp_enqueue_style(
		'generateblocks-dashboard-global',
		GENERATEBLOCKS_DIR_URL . 'assets/css/dashboard-global.css',
		array(),
		GENERATEBLOCKS_VERSION
	);

	$dashboard_pages = generateblocks_get_dashboard_pages();
	$current_screen = get_current_screen();

	// Shared dashboard chrome: the fallback page header, wp-admin layout
	// adjustments, and the legacy panel styles companion plugins still use.
	// The Settings app's own stylesheet is enqueued by GenerateBlocks_Settings.
	if ( $current_screen && in_array( $current_screen->id, $dashboard_pages ) ) {
		wp_enqueue_style(
			'generateblocks-settings-build',
			GENERATEBLOCKS_DIR_URL . 'dist/dashboard-chrome.css',
			array( 'wp-components' ),
			GENERATEBLOCKS_VERSION
		);
	}
}

/**
 * Build our Dashboard menu.
 *
 * Retained for backwards compatibility and the `generateblocks_dashboard_tabs`
 * filter. The dashboard header no longer renders in-header page navigation —
 * those links live in the WordPress admin sidebar.
 */
function generateblocks_dashboard_navigation() {
	$screen = get_current_screen();

	$tabs = apply_filters(
		'generateblocks_dashboard_tabs',
		array(
			'settings'  => array(
				'name'  => __( 'Settings', 'generateblocks' ),
				'url'   => admin_url( 'admin.php?page=generateblocks-settings' ),
				'class' => 'generateblocks_page_generateblocks-settings' === $screen->id ? 'active' : '',
			),
		)
	);

	if ( ! defined( 'GENERATEBLOCKS_PRO_VERSION' ) ) {
		$tabs['pro'] = array(
			'name'  => __( 'Get Pro', 'generateblocks' ),
			'url'   => 'https://generatepress.com/blocks/',
			'class' => '',
		);
	}

	// Don't print any markup if we only have one tab.
	if ( count( $tabs ) === 1 ) {
		return;
	}
	?>
	<div class="gblocks-navigation">
		<?php
		foreach ( $tabs as $tab ) {
			printf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				esc_url( $tab['url'] ),
				esc_attr( $tab['class'] ),
				esc_html( $tab['name'] )
			);
		}
		?>
	</div>
	<?php
}

/**
 * Get the HTML tags and attributes allowed in the dashboard header icon.
 *
 * @since 2.4.0
 *
 * @return array Allowed HTML for wp_kses().
 */
function generateblocks_get_dashboard_header_icon_allowed_html() {
	// Shared stroke attributes so outline icons (matching the React studio icons)
	// survive sanitization alongside filled glyphs like the GeneratePress mark.
	$stroke = array(
		'stroke'           => true,
		'stroke-width'     => true,
		'stroke-linecap'   => true,
		'stroke-linejoin'  => true,
		'stroke-dasharray' => true,
	);

	return array(
		'svg'      => array_merge(
			array(
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
				'role'        => true,
				'aria-hidden' => true,
				'focusable'   => true,
				'class'       => true,
			),
			$stroke
		),
		'g'        => array_merge(
			array(
				'fill'      => true,
				'class'     => true,
				'transform' => true,
			),
			$stroke
		),
		'path'     => array_merge(
			array(
				'd'         => true,
				'fill'      => true,
				'fill-rule' => true,
				'clip-rule' => true,
				'class'     => true,
			),
			$stroke
		),
		'rect'     => array_merge(
			array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
				'fill'   => true,
				'class'  => true,
			),
			$stroke
		),
		'circle'   => array_merge(
			array(
				'cx'    => true,
				'cy'    => true,
				'r'     => true,
				'fill'  => true,
				'class' => true,
			),
			$stroke
		),
		'line'     => array_merge(
			array(
				'x1'    => true,
				'y1'    => true,
				'x2'    => true,
				'y2'    => true,
				'class' => true,
			),
			$stroke
		),
		'polyline' => array_merge(
			array(
				'points' => true,
				'fill'   => true,
				'class'  => true,
			),
			$stroke
		),
		'polygon'  => array_merge(
			array(
				'points' => true,
				'fill'   => true,
				'class'  => true,
			),
			$stroke
		),
	);
}

/**
 * Build our Dashboard header.
 *
 * @since 1.2.0
 */
function generateblocks_do_dashboard_header() {
	$screen    = get_current_screen();
	$screen_id = $screen ? $screen->id : '';

	// This header is the fallback for dashboard screens that have not adopted
	// the GeneratePress design-system page frame (GenerateCloud, earlier
	// GenerateBlocks Pro versions). Its markup and styles mirror the
	// design-system PageHeader so those screens match the ones that have.
	//
	// The GeneratePress mark is the default icon — the same glyph as
	// assets/images/generatepress-mark.svg (the admin menu icon) and the brand
	// mark in the design-system page header. Plugins can swap in their own SVG
	// for a given screen via the filter below.
	$default_icon = '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="19" height="19"><path fill="currentColor" d="m161.74 142.582-34.98-16.311a37.5 37.5 0 1 0-42.608 7.716l58.866 27.45a75 75 0 1 1 31.697-54.907 1 1 0 0 0 .574.993l21.851 10.19a1 1 0 0 0 1.409-.737 100 100 0 1 0-14.528 37.25 1 1 0 0 0-.417-1.449ZM88.67 94.717a12.5 12.5 0 0 1 16.612-6.046 12.5 12.5 0 0 1 6.046 16.612 12.5 12.5 0 0 1-16.612 6.046 12.5 12.5 0 0 1-6.046-16.612"/></svg>';

	/**
	 * Filters the icon shown in the dashboard header tile for the current screen.
	 *
	 * Return SVG markup; it is sanitized with
	 * generateblocks_get_dashboard_header_icon_allowed_html().
	 *
	 * @since 2.4.0
	 *
	 * @param string $default_icon Default SVG markup (the GeneratePress mark).
	 * @param string $screen_id    Current admin screen id.
	 */
	$icon = (string) apply_filters( 'generateblocks_dashboard_header_icon', $default_icon, $screen_id );
	?>
	<div class="gblocks-dashboard-header">
		<div class="gblocks-dashboard-header-title">
			<h1>
				<?php echo wp_kses( $icon, generateblocks_get_dashboard_header_icon_allowed_html() ); ?>
				<?php echo esc_html( get_admin_page_title() ); ?>
			</h1>
		</div>

		<?php if ( ! defined( 'GENERATEBLOCKS_PRO_VERSION' ) ) : ?>
			<div class="gblocks-dashboard-header-actions">
				<a class="gblocks-get-pro" href="https://generatepress.com/blocks/">
					<?php esc_html_e( 'Get Pro', 'generateblocks' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Output our Dashboard HTML.
 *
 * @since 0.1
 */
function generateblocks_do_dashboard() {
	?>
		<div class="wrap gblocks-dashboard-wrap">
			<div id="gblocks-dashboard" />
		</div>
	<?php
}

add_action( 'admin_init', 'generateblocks_do_upgrade_redirect' );
/**
 * Redirect to the sales page when landing on the upgrade page.
 */
function generateblocks_do_upgrade_redirect() {
	if ( empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( 'generateblocks-upgrade' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_redirect( 'https://generatepress.com/blocks' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}
}
