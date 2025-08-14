<?php
/**
 * Plugin Name: Simplified Food Fitness
 * Description: Provides client dashboard and profile management for Simplified Food Fitness.
 * Version: 0.1.0
 * Author: Loft1325
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Plugin constants.
define( 'SFF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SFF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include shortcode functionality.
require_once SFF_PLUGIN_DIR . 'includes/shortcodes.php';

// Enqueue plugin styles.
function sff_enqueue_assets() {
    wp_enqueue_style( 'sff-styles', SFF_PLUGIN_URL . 'assets/css/sff-styles.css', array(), '0.1.0' );
}
add_action( 'wp_enqueue_scripts', 'sff_enqueue_assets' );
