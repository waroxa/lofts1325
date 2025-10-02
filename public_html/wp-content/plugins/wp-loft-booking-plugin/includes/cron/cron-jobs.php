<?php
defined('ABSPATH') || exit;

function wp_loft_booking_schedule_token_refresh() {
    if (!wp_next_scheduled('wp_loft_booking_check_token_refresh')) {
        wp_schedule_event(time(), 'hourly', 'wp_loft_booking_check_token_refresh');
    }
}
add_action('wp_loft_booking_check_token_refresh', 'wp_loft_booking_check_token_refresh');
register_activation_hook(dirname(__FILE__, 3) . '/wp-loft-booking-plugin.php', 'wp_loft_booking_schedule_token_refresh');

function wp_loft_booking_check_token_refresh() {
    $current_time = time();

    // Check and refresh the v3 token
    $v3_expires = get_option('butterflymx_token_v3_expires', 0);
    if ($v3_expires <= $current_time + 300) {
        error_log('[ButterflyMX] Attempting to refresh v3 token...');
        $v3_refreshed = wp_loft_booking_refresh_code_token('v3');
        if ($v3_refreshed) {
            error_log('[ButterflyMX] v3 token refreshed successfully.');
        } else {
            error_log('[ButterflyMX] Failed to refresh v3 token.');
        }
    } else {
        error_log('[ButterflyMX] v3 token is still valid until: ' . date('Y-m-d H:i:s', $v3_expires));
    }

    // Check and refresh the v4 token
    $v4_expires = get_option('butterflymx_token_v4_expires', 0);
    if ($v4_expires <= $current_time + 300) {
        error_log('[ButterflyMX] Attempting to refresh v4 token...');
        $v4_refreshed = wp_loft_booking_refresh_code_token('v4');
        if ($v4_refreshed) {
            error_log('[ButterflyMX] v4 token refreshed successfully.');
        } else {
            error_log('[ButterflyMX] Failed to refresh v4 token.');
        }
    } else {
        error_log('[ButterflyMX] v4 token is still valid until: ' . date('Y-m-d H:i:s', $v4_expires));
    }
}

function wp_loft_booking_schedule_unit_sync() {
    if (!wp_next_scheduled('wp_loft_booking_sync_units')) {
        wp_schedule_event(time(), 'hourly', 'wp_loft_booking_sync_units');
    }
}
add_action('wp_loft_booking_sync_units', 'wp_loft_booking_sync_units');
register_activation_hook(dirname(__FILE__, 3) . '/wp-loft-booking-plugin.php', 'wp_loft_booking_schedule_unit_sync');

// 1️⃣ Add custom cron schedule (e.g., every 15 minutes)
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_15_minutes'] = [
        'interval' => 15 * 60, // 15 minutes in seconds
        'display'  => __('Every 15 Minutes')
    ];
    return $schedules;
});

// 2️⃣ Schedule cron event on plugin activation
register_activation_hook(dirname(__FILE__, 3) . '/wp-loft-booking-plugin.php', function () {
    if (!wp_next_scheduled('wp_loft_booking_cron_sync')) {
        wp_schedule_event(time(), 'every_15_minutes', 'wp_loft_booking_cron_sync');
    }
});

// 3️⃣ Clear cron on plugin deactivation
register_deactivation_hook(dirname(__FILE__, 3) . '/wp-loft-booking-plugin.php', function () {
    wp_clear_scheduled_hook('wp_loft_booking_cron_sync');
});

// 4️⃣ Main cron hook
add_action('wp_loft_booking_cron_sync', function () {
    error_log("⏰ Running cron: syncing tenants, keychains, and units");

    if (function_exists('wp_loft_booking_full_sync')) {
        wp_loft_booking_full_sync();
    } else {
        // Fallback to individual calls if the helper is unavailable.
        if (function_exists('wp_loft_booking_fetch_and_save_tenants')) {
            wp_loft_booking_run_safely('wp_loft_booking_fetch_and_save_tenants');
        }

        $keychain_result = function_exists('wp_loft_booking_trigger_keychains_page_sync')
            ? wp_loft_booking_trigger_keychains_page_sync()
            : false;

        if ($keychain_result instanceof WP_Error) {
            error_log('[WP Loft Booking] Cron keychain sync failed: ' . $keychain_result->get_error_message());
        } elseif ($keychain_result) {
            error_log('🔑 Cron: keychains synced successfully via keychains_page_function.');
        }

        if (function_exists('wp_loft_booking_sync_units')) {
            wp_loft_booking_run_safely('wp_loft_booking_sync_units');
        }
    }

    error_log("✅ Cron sync completed");
});
