<?php
defined('ABSPATH') || exit;

function wp_loft_booking_deactivate() {
    wp_clear_scheduled_hook('wp_loft_booking_check_token_refresh');
    wp_clear_scheduled_hook('wp_loft_booking_sync_units');
}
register_deactivation_hook(dirname(__FILE__, 3) . '/wp-loft-booking-plugin.php', 'wp_loft_booking_deactivate');

function wp_loft_booking_drop_tables() {
    global $wpdb;
    $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}loft_virtual_keys");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}loft_units");
    $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
}
register_deactivation_hook(dirname(__FILE__, 3) . '/wp-loft-booking-plugin.php', 'wp_loft_booking_drop_tables');