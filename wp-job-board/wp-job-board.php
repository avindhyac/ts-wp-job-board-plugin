<?php
/**
 * Plugin Name: WP Job Board
 * Description: A custom job board with admin management and shortcode output. Use [job_board] on any page.
 * Version: 1.1.0
 * Author: Your Agency
 * Text Domain: wp-job-board
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WJB_PATH', plugin_dir_path( __FILE__ ) );
define( 'WJB_URL',  plugin_dir_url( __FILE__ ) );

require_once WJB_PATH . 'includes/post-type.php';
require_once WJB_PATH . 'includes/meta-boxes.php';
require_once WJB_PATH . 'includes/shortcode.php';
require_once WJB_PATH . 'includes/enqueue.php';
require_once WJB_PATH . 'includes/admin-dashboard.php';

// Client portal — must load before template_redirect fires
require_once WJB_PATH . 'includes/portal-settings.php';
require_once WJB_PATH . 'includes/portal-auth.php';
require_once WJB_PATH . 'includes/portal-render.php';
require_once WJB_PATH . 'includes/portal-ajax.php';

// Flush rewrite rules on activation so the portal URL works immediately
register_activation_hook( __FILE__, function () {
    wjb_portal_register_rewrite();
    flush_rewrite_rules();
} );
