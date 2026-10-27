<?php
/**
 * Our settings page.
 *
 * @package GenerateBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Build our settings page.
 */
class GenerateBlocks_Settings {
	/**
	 * Instance.
	 *
	 * @access private
	 * @var object Instance
	 */
	private static $instance;

	/**
	 * Initiator.
	 *
	 * @return object initialized object of class.
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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_menu', array( $this, 'remove_parent_duplicate' ), 999 );
	}

	/**
	 * Add our Dashboard menu item.
	 *
	 * A companion plugin can register and render the Settings screen instead;
	 * see generateblocks_should_register_settings_page().
	 */
	public function add_menu() {
		if ( ! generateblocks_should_register_settings_page() ) {
			return;
		}

		$settings = add_submenu_page(
			'generateblocks',
			__( 'Settings', 'generateblocks' ),
			__( 'Settings', 'generateblocks' ),
			'manage_options',
			'generateblocks-settings',
			array( $this, 'settings_page' ),
			1
		);

		// Keep released companion plugins' submenu positions unchanged.
		$this->remove_parent_duplicate();

		add_action( "admin_print_styles-$settings", array( $this, 'enqueue_styles' ) );
		add_action( "admin_print_scripts-$settings", array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Remove the parent menu's duplicate entry.
	 *
	 * WordPress inserts the parent as the first submenu entry when the first
	 * child is added, which duplicates the Settings link. Free removes it
	 * immediately; the late callback also covers companion-owned screens.
	 *
	 * @since 2.5.0
	 */
	public function remove_parent_duplicate() {
		remove_submenu_page( 'generateblocks', 'generateblocks' );
	}

	/**
	 * Enqueue the Settings screen stylesheet.
	 *
	 * It carries the GeneratePress design-system styles, so it is only enqueued
	 * when GenerateBlocks renders the screen itself.
	 *
	 * @since 2.5.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'generateblocks-settings',
			GENERATEBLOCKS_DIR_URL . 'dist/settings.css',
			array( 'wp-components' ),
			GENERATEBLOCKS_VERSION
		);
	}

	/**
	 * Enqueue our scripts.
	 */
	public function enqueue_scripts() {
		$generateblocks_deps = array( 'wp-api', 'wp-i18n', 'wp-components', 'wp-element', 'wp-api-fetch' );

		$show_container_width = generateblocks_should_set_container_width();
		$use_customizer_container_width = generateblocks_use_customizer_container_width();

		$assets_file = GENERATEBLOCKS_DIR . 'dist/settings.asset.php';
		$compiled_assets = file_exists( $assets_file )
			? require $assets_file
			: false;

		$assets =
			isset( $compiled_assets['dependencies'] ) &&
			isset( $compiled_assets['version'] )
			? $compiled_assets
			: [
				'dependencies' => $generateblocks_deps,
				'version' => filemtime( GENERATEBLOCKS_DIR . 'dist/settings.js' ),
			];

		wp_enqueue_script(
			'generateblocks-settings',
			GENERATEBLOCKS_DIR_URL . 'dist/settings.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'generateblocks-settings', 'generateblocks' );
		}

		wp_localize_script(
			'generateblocks-settings',
			'generateBlocksSettings',
			array(
				'settings' => wp_parse_args(
					get_option( 'generateblocks', array() ),
					generateblocks_get_option_defaults()
				),
				'gpContainerWidth' => $use_customizer_container_width ? generate_get_option( 'container_width' ) : false,
				'gpContainerWidthLink' => $use_customizer_container_width ?
				add_query_arg(
					rawurlencode( 'autofocus[control]' ),
					rawurlencode( 'generate_settings[container_width]' ),
					wp_customize_url()
				) :
					false,
				'showContainerWidthSetting' => $show_container_width,
				'useV1Blocks' => generateblocks_use_v1_blocks(),
				'hasPro' => defined( 'GENERATEBLOCKS_PRO_VERSION' ),
			)
		);
	}

	/**
	 * Add settings container.
	 *
	 * Retained for backwards compatibility. The Settings app mounts into the
	 * container printed by settings_page() and no longer uses this hook.
	 *
	 * @since 1.2.0
	 */
	public function add_settings_container() {
		echo '<div id="gblocks-block-default-settings"></div>';
	}

	/**
	 * Add blocks version settings container.
	 *
	 * Retained for backwards compatibility. The block version controls are
	 * part of the Settings app and no longer use this hook.
	 *
	 * @since 2.0.0
	 */
	public function add_blocks_version_settings_container() {
		echo '<div id="gblocks-blocks-version-settings"></div>';
	}

	/**
	 * Output our Dashboard HTML.
	 *
	 * The Settings app renders first. Companion plugins that still print their
	 * own controls through `generateblocks_settings_area` render below it.
	 *
	 * @since 0.1
	 */
	public function settings_page() {
		?>
			<div class="wrap gblocks-dashboard-wrap gblocks-settings-wrap">
				<div id="gblocks-settings-dashboard"></div>

				<?php if ( false !== has_action( 'generateblocks_settings_area' ) ) : ?>
					<div class="generateblocks-settings-area gblocks-settings-extensions">
						<?php do_action( 'generateblocks_settings_area' ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php
	}
}

GenerateBlocks_Settings::get_instance();
