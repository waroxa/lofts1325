<?php
defined('ABSPATH') || exit;

function wp_loft_booking_admin_menu() {
    add_menu_page('Loft Booking', 'Lofts 1325', 'manage_options', 'wp_loft_booking', 'wp_loft_booking_dashboard', 'dashicons-building', 6);
    add_submenu_page('wp_loft_booking', 'Manage Branches', 'Branches', 'manage_options', 'wp_loft_booking_branches', 'wp_loft_booking_branches_page');
    add_submenu_page('wp_loft_booking', 'Manage Lofts', '🚪🛋️ Lofts', 'manage_options', 'wp_loft_booking_lofts', 'wp_loft_booking_lofts_page');
    add_submenu_page('wp_loft_booking', 'Manage Bookings', '🛎️ Bookings', 'manage_options', 'wp_loft_booking_bookings', 'wp_loft_booking_bookings_page');
    add_submenu_page('wp_loft_booking', 'ButterflyMX Settings', 'ButterflyMX Settings', 'manage_options', 'wp_loft_booking_butterflymx_settings', 'wp_loft_booking_butterflymx_settings_page');
    add_submenu_page('wp_loft_booking', 'Tenants', '👨 Tenants', 'manage_options', 'tenants', 'tenants_page_function');
    add_submenu_page('wp_loft_booking', 'Keychains', '🔑🗝️ Keychains', 'manage_options', 'wp_loft_booking_keychains', 'keychains_page_function');
    add_menu_page('Loft Types', 'Loft Types', 'manage_options', 'wp_loft_booking_loft_types', '', '', 7);
    add_submenu_page('wp_loft_booking', 'Loft Types', 'All Loft Types', 'manage_options', 'loft-types', 'wp_loft_types_admin_page');
    add_submenu_page('wp_loft_booking', 'Add/Edit Loft Type', 'Add Loft Type', 'manage_options', 'add-edit-loft-type', 'wp_add_edit_loft_type_page');
    add_submenu_page('wp_loft_booking', 'Manual Token Refresh', 'Token Refresh', 'manage_options', 'wp_loft_booking_manual_token_refresh', 'wp_loft_booking_manual_token_refresh_page');
    add_submenu_page(
    'wp_loft_booking',
    '🗓️ Loft Bookings Calendar',
    '🗓️ Loft Bookings Calendar',
    'manage_options',
    'loft-booking-google-calendar',
    'loft_booking_google_calendar_page');
    add_submenu_page(
    null, // 👈 null para que no aparezca en el menú
    'Google OAuth Callback',
    '', // sin título en menú
    'manage_options',
    'loft-booking-google-auth',
    'loft_booking_handle_google_auth'
);
    add_submenu_page(
    'wp_loft_booking',
    'Loft Cleaning Schedule',
    '🧼🧹 Cleaning Calendar',
    'manage_options',
    'loft-booking-cleaning-calendar',
    'loft_booking_cleaning_calendar_page'
);



}
add_action('admin_menu', 'wp_loft_booking_admin_menu');

function wp_loft_booking_dashboard() {
    echo '<div class="wrap"><h1>Loft Booking Dashboard</h1><p>Welcome to the Loft Booking Plugin! Use the menu to manage branches, lofts, and bookings.</p></div>';
}