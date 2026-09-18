<?php
/** Run with WP-CLI eval-file --use-include on a new, disposable localhost site only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }
$host = wp_parse_url( home_url(), PHP_URL_HOST );
if ( ! in_array( $host, [ 'localhost', '127.0.0.1', '::1' ], true ) ) {
    WP_CLI::error( 'This fixture is restricted to localhost.' );
}
if ( get_option( 'gb_beta_kit_fixture' ) ) {
    WP_CLI::error( 'Fixture already exists. Use its saved IDs; do not duplicate it.' );
}
if ( ! defined( 'GENERATEBLOCKS_PRO_VERSION' ) || ! defined( 'GENERATEBLOCKS_VERSION' ) ) {
    WP_CLI::error( 'Activate both supplied GenerateBlocks betas first.' );
}
if ( ! is_file( __DIR__ . '/local-mail-sink.php' ) ) {
    WP_CLI::error( 'Run eval-file with --use-include so sibling files resolve.' );
}
$sink_dir = WP_CONTENT_DIR . '/mu-plugins';
wp_mkdir_p( $sink_dir );
if ( ! copy( __DIR__ . '/local-mail-sink.php', $sink_dir . '/gb-beta-kit-mail-sink.php' ) ) {
    WP_CLI::error( 'Could not install the local mail sink.' );
}
wp_set_current_user( 1 );
update_option( 'blog_public', '0' );
update_option( 'generateblocks', array_merge( (array) get_option( 'generateblocks', [] ), [ 'enable_forms' => true, 'google_fonts' => false ] ) );
switch_theme( 'gb-beta-kit-canvas' );
$page = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Fieldwork: a reusable product-page kit', 'post_name' => 'fieldwork', 'post_author' => 1 ], true );
if ( is_wp_error( $page ) ) { WP_CLI::error( $page ); }
$posts = [];
foreach ( [
    'Start with the decisions that repeat' => 'Color, spacing, and typography belong in a shared system. Keep the unusual composition local to its page.',
    'Build a page from useful sections' => 'Reuse a comparison, a question and answer, or a signup section. Each pattern should solve a specific content job.'
] as $title => $content ) {
    $posts[] = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $title, 'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $content ) . '</p><!-- /wp:paragraph -->', 'post_excerpt' => $content, 'post_author' => 1 ] );
}
update_option( 'gb_beta_kit_fixture', [ 'pageId' => $page, 'postIds' => $posts ] );
WP_CLI::line( wp_json_encode( [ 'pageId' => $page, 'postIds' => $posts, 'wordpress' => get_bloginfo( 'version' ), 'free' => GENERATEBLOCKS_VERSION, 'pro' => GENERATEBLOCKS_PRO_VERSION ] ) );
