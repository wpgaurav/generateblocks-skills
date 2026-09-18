<?php
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'style.css' );
} );
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'gb-kit-canvas', get_stylesheet_uri(), [], '1.0.0' );
} );
