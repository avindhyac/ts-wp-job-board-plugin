<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wjb_enqueue_assets() {
    // Google Fonts — Manrope (matches site-wide brand type).
    wp_enqueue_style(
        'wjb-fonts',
        'https://fonts.googleapis.com/css2?family=Manrope:ital,wght@0,400;0,500;0,600;0,700;0,800;1,700;1,800&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'wjb-styles',
        WJB_URL . 'assets/job-board.css',
        array( 'wjb-fonts' ),
        '1.2.0'
    );

    wp_enqueue_script(
        'wjb-scripts',
        WJB_URL . 'assets/job-board.js',
        array(),
        '1.2.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'wjb_enqueue_assets' );

// Preconnect to the Google Fonts hosts for faster font loading.
function wjb_resource_hints( $hints, $relation ) {
    if ( 'preconnect' === $relation ) {
        $hints[] = 'https://fonts.googleapis.com';
        $hints[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
    }
    return $hints;
}
add_filter( 'wp_resource_hints', 'wjb_resource_hints', 10, 2 );
