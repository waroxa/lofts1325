<?php
/*
Plugin Name: WP Loft Booking Plugin
Plugin URI: https://loft1325.com
Description: Custom booking plugin for managing room reservations and virtual keys.
Version: 1.0
Author: Maria Garcia
Author URI: https://loft1325.com
License: GPL2
*/

defined('ABSPATH') || exit;

// Include all necessary files
require_once plugin_dir_path(__FILE__) . 'includes/database/db-setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/database/db-cleanup.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/branches.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/lofts.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/bookings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/loft-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/butterflymx-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/payment-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/tenants.php';
require_once plugin_dir_path(__FILE__) . 'includes/integrations/butterflymx.php';
require_once plugin_dir_path(__FILE__) . 'includes/integrations/booking-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/integrations/amelia_hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/booking-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/search-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/display-results.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/loft-types-display.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron/cron-jobs.php';
require_once plugin_dir_path(__FILE__) . 'includes/calendar/google-calendar.php';
require_once plugin_dir_path(__FILE__) . 'includes/calendar/google-oauth-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/calendar/cleaning-calendar.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/keychains.php';




// Enqueue scripts and styles
function wp_loft_booking_enqueue_scripts() {
    wp_enqueue_style('custom-loft-styles', plugin_dir_url(__FILE__) . 'assets/css/custom-loft-style.css');
    wp_enqueue_script('custom-loft-script', plugin_dir_url(__FILE__) . 'assets/js/custom-loft-script.js', ['jquery'], '1.0', true);
    wp_localize_script('custom-loft-script', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
}
add_action('wp_enqueue_scripts', 'wp_loft_booking_enqueue_scripts');

function wp_loft_booking_enqueue_admin_scripts() {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_script('custom-loft-script', plugin_dir_url(__FILE__) . 'assets/js/custom-loft-script.js', ['jquery'], '1.0', true);
    wp_enqueue_style('custom-loft-styles', plugin_dir_url(__FILE__) . 'assets/css/custom-loft-style.css');
}
add_action('admin_enqueue_scripts', 'wp_loft_booking_enqueue_admin_scripts');