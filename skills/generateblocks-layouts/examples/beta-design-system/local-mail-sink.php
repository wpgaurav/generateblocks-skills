<?php
/** Local fixture only: capture simulated mail without sending it. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_filter( 'pre_wp_mail', function ( $result, $atts ) {
    if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), [ 'localhost', '127.0.0.1', '::1' ], true ) ) { return $result; }
    update_option( 'gb_beta_kit_mail_count', (int) get_option( 'gb_beta_kit_mail_count', 0 ) + 1, false );
    return true;
}, 10, 2 );
