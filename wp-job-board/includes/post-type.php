<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wjb_register_post_type() {
    $labels = array(
        'name'               => 'Job Listings',
        'singular_name'      => 'Job Listing',
        'menu_name'          => 'Job Board',
        'add_new'            => 'Add New Job',
        'add_new_item'       => 'Add New Job Listing',
        'edit_item'          => 'Edit Job Listing',
        'new_item'           => 'New Job Listing',
        'view_item'          => 'View Job Listing',
        'search_items'       => 'Search Jobs',
        'not_found'          => 'No jobs found',
        'not_found_in_trash' => 'No jobs found in trash',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-id-alt',
        'supports'           => array( 'title', 'editor' ),
        'has_archive'        => false,
        'rewrite'            => false,
    );

    register_post_type( 'job_listing', $args );
}
add_action( 'init', 'wjb_register_post_type' );
