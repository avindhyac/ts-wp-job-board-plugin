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
