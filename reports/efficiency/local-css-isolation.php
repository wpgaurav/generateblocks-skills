<?php
/** Local benchmark only: omit shared CSS from the designated local-style page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'wp_enqueue_scripts', function () {
    if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'localhost' ) { return; }
    $page_id = (int) get_option( 'gb_efficiency_local_page_id', 0 );
    if ( $page_id && is_page( $page_id ) ) {
        wp_dequeue_style( 'generateblocks-global' );
    }
}, PHP_INT_MAX );
