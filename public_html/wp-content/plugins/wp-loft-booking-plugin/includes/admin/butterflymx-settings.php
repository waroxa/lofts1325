<?php
defined('ABSPATH') || exit;



function wp_loft_booking_register_butterflymx_settings() {
    register_setting('wp_loft_booking_butterflymx_settings', 'butterflymx_client_id');
    register_setting('wp_loft_booking_butterflymx_settings', 'butterflymx_client_secret');
    register_setting('wp_loft_booking_butterflymx_settings', 'butterflymx_building_id');
    register_setting('wp_loft_booking_butterflymx_settings', 'butterflymx_environment');
    register_setting('wp_loft_booking_butterflymx_settings', 'butterflymx_token_v3');
    register_setting('wp_loft_booking_butterflymx_settings', 'butterflymx_token_v4');

    add_settings_section('butterflymx_main', 'ButterflyMX API Settings', 'butterflymx_section_text', 'wp_loft_booking_butterflymx_settings');

    add_settings_field('butterflymx_client_id', 'Client ID', 'butterflymx_client_id_input', 'wp_loft_booking_butterflymx_settings', 'butterflymx_main');
    add_settings_field('butterflymx_client_secret', 'Client Secret', 'butterflymx_client_secret_input', 'wp_loft_booking_butterflymx_settings', 'butterflymx_main');
    add_settings_field('butterflymx_building_id', 'Building ID', 'butterflymx_building_id_input', 'wp_loft_booking_butterflymx_settings', 'butterflymx_main');
    add_settings_field('butterflymx_environment', 'Environment', 'butterflymx_environment_input', 'wp_loft_booking_butterflymx_settings', 'butterflymx_main');
}
add_action('admin_init', 'wp_loft_booking_register_butterflymx_settings');

function butterflymx_section_text() {
    echo '<p>Configure your ButterflyMX API settings below.</p>';
}

function butterflymx_client_id_input() {
    $client_id = get_option('butterflymx_client_id');
    echo '<input type="text" name="butterflymx_client_id" value="' . esc_attr($client_id) . '" class="regular-text" required />';
}

function butterflymx_client_secret_input() {
    $client_secret = get_option('butterflymx_client_secret');
    echo '<input type="text" name="butterflymx_client_secret" value="' . esc_attr($client_secret) . '" class="regular-text" required />';
}

function butterflymx_building_id_input() {
    $building_id = get_option('butterflymx_building_id');
    echo '<input type="text" name="butterflymx_building_id" value="' . esc_attr($building_id) . '" class="regular-text" required />';
}

function butterflymx_environment_input() {
    $environment = get_option('butterflymx_environment', 'sandbox');
    echo '<select name="butterflymx_environment" id="butterflymx_environment">
            <option value="sandbox" ' . selected($environment, 'sandbox', false) . '>Sandbox</option>
            <option value="production" ' . selected($environment, 'production', false) . '>Production</option>
          </select>';
}

function wp_loft_booking_butterflymx_settings_page() {
    echo '<div class="wrap"><h1>ButterflyMX API Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('wp_loft_booking_butterflymx_settings');
    do_settings_sections('wp_loft_booking_butterflymx_settings');
    submit_button();
    echo '</form>';

    // Check if functions exist before calling
    $auth_url_v3 = function_exists('wp_loft_booking_get_authorization_url') ? wp_loft_booking_get_authorization_url('v3') : '#';
    $auth_url_v4 = function_exists('wp_loft_booking_get_authorization_url') ? wp_loft_booking_get_authorization_url('v4') : '#';

    echo '<p><a href="' . esc_url($auth_url_v3) . '" class="button-primary">Authorize v3 API</a></p>';
    echo '<p><a href="' . esc_url($auth_url_v4) . '" class="button-primary">Authorize v4 API</a></p>';

    echo '<h3>Paste Authorization Code for v3:</h3>';
    echo '<form method="post" action="">';
    echo '<input type="text" name="authorization_code_v3" id="authorization_code_v3" style="width: 100%;" />';
    echo '<p><input type="submit" name="submit_code_v3" class="button-primary" value="Exchange Code for v3 Token"></p>';
    echo '</form>';

    echo '<h3>Paste Authorization Code for v4:</h3>';
    echo '<form method="post" action="">';
    echo '<input type="text" name="authorization_code_v4" id="authorization_code_v4" style="width: 100%;" />';
    echo '<p><input type="submit" name="submit_code_v4" class="button-primary" value="Exchange Code for v4 Token"></p>';
    echo '</form>';

    $token_v3 = get_option('butterflymx_token_v3', 'Not available');
    $token_v4 = get_option('butterflymx_token_v4', 'Not available');

    echo '<h3>Stored Tokens</h3>';
    echo '<p><strong>v3 Token:</strong> ' . esc_html($token_v3) . '</p>';
    echo '<p><strong>v4 Token:</strong> ' . esc_html($token_v4) . '</p>';

    echo '<h3>Available Buildings</h3>';
    $buildings = function_exists('wp_loft_booking_get_buildings') ? wp_loft_booking_get_buildings() : [];
    if (is_array($buildings) && !empty($buildings)) {
        echo '<ul>';
        foreach ($buildings as $building) {
            if (isset($building['id']) && isset($building['name'])) {
                echo '<li><strong>ID:</strong> ' . esc_html($building['id']) . ' - <strong>Name:</strong> ' . esc_html($building['name']) . '</li>';
            }
        }
        echo '</ul>';
    } else {
        echo '<p>No buildings available or error retrieving data.</p>';
    }
    echo '</div>';
}

// Handle authorization code submission with error handling
if (isset($_POST['submit_code_v3'])) {
    $authorization_code_v3 = sanitize_text_field($_POST['authorization_code_v3']);
    if (function_exists('wp_loft_booking_exchange_code_for_token')) {
        $token_v3 = wp_loft_booking_exchange_code_for_token($authorization_code_v3, 'v3');
        if ($token_v3 && !is_wp_error($token_v3)) {
            update_option('butterflymx_token_v3', $token_v3);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>v3 Token updated successfully!</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($token_v3) {
                echo '<div class="notice notice-error"><p>Error exchanging v3 code: ' . esc_html($token_v3) . '</p></div>';
            });
        }
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Function wp_loft_booking_exchange_code_for_token() is missing.</p></div>';
        });
    }
}

if (isset($_POST['submit_code_v4'])) {
    $authorization_code_v4 = sanitize_text_field($_POST['authorization_code_v4']);
    if (function_exists('wp_loft_booking_exchange_code_for_token')) {
        $token_v4 = wp_loft_booking_exchange_code_for_token($authorization_code_v4, 'v4');
        if ($token_v4 && !is_wp_error($token_v4)) {
            update_option('butterflymx_token_v4', $token_v4);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>v4 Token updated successfully!</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($token_v4) {
                echo '<div class="notice notice-error"><p>Error exchanging v4 code: ' . esc_html($token_v4) . '</p></div>';
            });
        }
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Function wp_loft_booking_exchange_code_for_token() is missing.</p></div>';
        });
    }
}

function wp_loft_booking_manual_token_refresh_page() {
    $v3_token = get_option('butterflymx_access_token_v3', 'Not available');
    $v4_token = get_option('butterflymx_access_token_v4', 'Not available');
    $v3_refresh_token = get_option('butterflymx_refresh_token_v3', 'Not available');
    $v4_refresh_token = get_option('butterflymx_refresh_token_v4', 'Not available');

    if (isset($_POST['refresh_tokens']) && check_admin_referer('refresh_tokens_action')) {
        $v3_refreshed = wp_loft_booking_refresh_code_token('v3');
        $v4_refreshed = wp_loft_booking_refresh_code_token('v4');

        if ($v3_refreshed && $v4_refreshed) {
            echo '<div class="updated"><p>Tokens refreshed successfully!</p></div>';
            // Update displayed tokens after refresh
            $v3_token = get_option('butterflymx_access_token_v3', 'Not available');
            $v4_token = get_option('butterflymx_access_token_v4', 'Not available');
            $v3_refresh_token = get_option('butterflymx_refresh_token_v3', 'Not available');
            $v4_refresh_token = get_option('butterflymx_refresh_token_v4', 'Not available');
        } else {
            echo '<div class="error"><p>Failed to refresh one or more tokens. Check the error log for details.</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Manual Token Refresh</h1>';
    echo '<form method="post">';
    wp_nonce_field('refresh_tokens_action');
    echo '<input type="submit" name="refresh_tokens" class="button button-primary" value="Refresh Tokens">';
    echo '</form>';
    echo '<h2>Current v3 Token:</h2><p>' . esc_html($v3_token) . '</p>';
    echo '<h2>Current v4 Token:</h2><p>' . esc_html($v4_token) . '</p>';
    echo '<h2>Stored v3 Refresh Token:</h2><p>' . esc_html($v3_refresh_token) . '</p>';
    echo '<h2>Stored v4 Refresh Token:</h2><p>' . esc_html($v4_refresh_token) . '</p>';
    echo '</div>';
}
