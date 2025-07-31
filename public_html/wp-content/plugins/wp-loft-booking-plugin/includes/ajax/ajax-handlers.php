<?php
defined('ABSPATH') || exit;

function wp_loft_booking_submit() {
    // Handle booking submission logic (to be implemented based on your requirements)
    wp_send_json_success('Booking submitted successfully.');
}
add_action('wp_ajax_wp_loft_booking_submit', 'wp_loft_booking_submit');
add_action('wp_ajax_nopriv_wp_loft_booking_submit', 'wp_loft_booking_submit');

function wp_loft_booking_get_units() {
    global $wpdb;
    $branch_id = intval($_POST['branch_id']);

    // Fetch units with no active bookings for the selected branch
    $units = $wpdb->get_results($wpdb->prepare("
        SELECT u.id, u.unit_name 
        FROM {$wpdb->prefix}loft_units u
        LEFT JOIN {$wpdb->prefix}loft_virtual_keys vk ON u.id = vk.unit_id
        WHERE u.branch_id = %d
        AND (vk.expiration_date IS NULL OR vk.expiration_date < NOW())
    ", $branch_id));

    wp_send_json($units);
}
add_action('wp_ajax_wp_loft_booking_get_units', 'wp_loft_booking_get_units');
add_action('wp_ajax_nopriv_wp_loft_booking_get_units', 'wp_loft_booking_get_units');


function wp_loft_booking_sync_units() {
    global $wpdb;

    error_log("🚨 ENTERED wp_loft_booking_sync_units()");

    $units_table = $wpdb->prefix . 'loft_units';
    $keychains_table = $wpdb->prefix . 'loft_keychains';

    $token = get_option('butterflymx_access_token_v4');
    $environment = get_option('butterflymx_environment', 'sandbox');
    $api_base_url = ($environment === 'production') ? "https://api.butterflymx.com/v4" : "https://api.na.sandbox.butterflymx.com/v4";

    error_log("🔄 Starting sync with token: $token");
    error_log("🌐 Using API base URL: $api_base_url");

    // Clear all loft_units
    $wpdb->query("DELETE FROM $units_table");

    $now = current_time('mysql');
    $new_units_count = 0;
    $summary = ['SIMPLE' => 0, 'DOUBLE' => 0, 'PENTHOUSE' => 0];

    // Fetch all valid keychains
    $active_keys = $wpdb->get_results("SELECT name, valid_until FROM $keychains_table WHERE valid_until >= '$now'");
    $active_key_data = array_map(function($row) {
        // Normalize keychain name: uppercase, trim, enforce space before "("
        $normalized_name = strtoupper(trim($row->name));
        $normalized_name = preg_replace('/\s*\(/', ' (', $normalized_name); // ensure space before "("
        return [
            'name' => $normalized_name,
            'valid_until' => $row->valid_until
        ];
    }, $active_keys);

    // Fetch all virtual keys and log solos
    $solo_virtual_units = [];
    $response_vk = wp_remote_get("{$api_base_url}/virtual_keys", [
        'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json']
    ]);
    if (!is_wp_error($response_vk)) {
        $vk_data = json_decode(wp_remote_retrieve_body($response_vk), true);
        if (isset($vk_data['data']) && is_array($vk_data['data'])) {
            foreach ($vk_data['data'] as $vk) {
                if (empty($vk['keychain_id']) && !empty($vk['unit_id'])) {
                    $solo_virtual_units[$vk['unit_id']] = true;
                    error_log("🟡 SOLO VIRTUAL KEY: " . json_encode($vk));
                }
            }
        }
    }

    // Fetch branches
    $branches = $wpdb->get_results("SELECT id, building_id FROM {$wpdb->prefix}loft_branches WHERE building_id IS NOT NULL");

    foreach ($branches as $branch) {
        $response = wp_remote_get("{$api_base_url}/units?q[building_id_eq]={$branch->building_id}", [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json']
        ]);

        if (is_wp_error($response)) {
            error_log("❌ Error fetching units: " . $response->get_error_message());
            continue;
        }

        $units_data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($units_data['data']) || !is_array($units_data['data'])) continue;

        foreach ($units_data['data'] as $unit) {
            // Get and normalize unit name
            $unit_name = sanitize_text_field($unit['label'] ?? 'Unknown');
            $unit_name = preg_replace('/\s*\(/', ' (', $unit_name); // enforce space before "("
            $unit_name = strtoupper(trim($unit_name)); // trim + uppercase for consistency

            $unit_id_api = intval($unit['id']);
            $unit_name_upper = $unit_name;
            $status = 'available'; // Default to available
            $available_until = null;

            // 🚫 Block if solo virtual key exists
            if (!empty($solo_virtual_units[$unit_id_api])) {
                $status = 'unavailable';
                error_log("🚫 UNIT BLOCKED FROM AVAILABLE DUE TO SOLO KEY: $unit_name");
            }
            // Skip anything not containing "LOFT"
            elseif (stripos($unit_name_upper, 'LOFT') === false) {
                $status = 'unavailable';
            }
            // Skip if not one of the allowed types
            elseif (
                stripos($unit_name_upper, 'SIMPLE') === false &&
                stripos($unit_name_upper, 'DOUBLE') === false &&
                stripos($unit_name_upper, 'PENTHOUSE') === false
            ) {
                $status = 'unavailable';
            }
            // Otherwise, check active keychains
            else {
                preg_match('/LOFT\s*(\d+)/i', $unit_name_upper, $match);
                $unit_number = $match[1] ?? null;
                if ($unit_number) {
                    foreach ($active_key_data as $key) {
                        if (preg_match('/^LOFT\s*' . preg_quote($unit_number, '/') . '(\s|\(|$)/i', $key['name'])) {
                            $status = 'occupied';
                            $available_until = date('Y-m-d H:i:s', strtotime($key['valid_until']));
                            error_log("📛 MARKED OCCUPIED: $unit_name | UNTIL: $available_until | KEYCHAIN: {$key['name']}");
                            break;
                        }
                    }
                } else {
                    $status = 'unavailable';
                }
            }

            // Update counts only for AVAILABLE lofts
            if ($status === 'available') {
                if (stripos($unit_name_upper, 'SIMPLE') !== false) $summary['SIMPLE']++;
                elseif (stripos($unit_name_upper, 'DOUBLE') !== false) $summary['DOUBLE']++;
                elseif (stripos($unit_name_upper, 'PENTHOUSE') !== false) $summary['PENTHOUSE']++;
            }

            $result = $wpdb->insert(
                $units_table,
                [
                    'branch_id'          => $branch->id,
                    'unit_name'          => $unit_name,
                    'status'             => $status,
                    'availability_until' => $available_until,
                    'unit_id_api'        => $unit_id_api
                ],
                ['%d', '%s', '%s', '%s', '%d']
            );

            if ($result === false) {
                error_log("❌ INSERT FAILED: $unit_name — " . $wpdb->last_error);
            } else {
                error_log("✅ INSERTED: $unit_name | STATUS: $status | UNTIL: " . ($available_until ?? 'N/A'));
                $new_units_count++;
            }
        }
    }

    // Update post_meta with only available counts
    update_post_meta(10773, 'nd_booking_meta_box_qnt', $summary['SIMPLE']);    // SIMPLE
    update_post_meta(13803, 'nd_booking_meta_box_qnt', $summary['DOUBLE']);    // DOUBLE
    update_post_meta(13804, 'nd_booking_meta_box_qnt', $summary['PENTHOUSE']); // PENTHOUSE

    error_log("✅ FINAL SYNC (ONLY AVAILABLE): SIMPLE={$summary['SIMPLE']}, DOUBLE={$summary['DOUBLE']}, PENTHOUSE={$summary['PENTHOUSE']}");

    wp_send_json_success("✅ Sync completed with $new_units_count units.");
}




add_action('wp_ajax_wp_loft_booking_sync_units', 'wp_loft_booking_sync_units');


function test_room_counts_from_loft_units() {
    global $wpdb;

    $units_table = $wpdb->prefix . 'loft_units';

    $units = $wpdb->get_results("SELECT unit_name, status FROM {$units_table}");

    $simple = 0;
    $double = 0;
    $penthouse = 0;

    foreach ($units as $unit) {
        $title = strtoupper($unit->unit_name);
        $status = strtolower($unit->status);

        if (stripos($title, '(SIMPLE)') !== false && $status === 'available') {
            $simple++;
        }

        if (stripos($title, '(DOUBLE)') !== false && $status === 'available') {
            $double++;
        }

        if (stripos($title, '(PENTHOUSE)') !== false && $status === 'available') {
            $penthouse++;
        }
    }

    echo "<div style='background: #fff3cd; padding:10px; border:1px solid #ffeeba; margin:15px 0;'>
        <strong>Found:</strong><br>
        Simple Lofts Available: {$simple}<br>
        Double Lofts Available: {$double}<br>
        Penthouse Lofts Available: {$penthouse}
    </div>";
}


function update_room_quantities_after_loft_sync() {
    global $wpdb;

    $units_table = $wpdb->prefix . 'loft_units';
    $units = $wpdb->get_results("SELECT unit_name, status FROM {$units_table}");

    $simple_count = 0;
    $double_count = 0;
    $penthouse_count = 0;

    foreach ($units as $unit) {
        $title = strtoupper(trim($unit->unit_name));
        $status = strtolower(trim($unit->status));

        if ($status !== 'available') {
            continue;
        }

        if (preg_match('/\( *SIMPLE *\)/i', $title)) {
            $simple_count++;
        } elseif (preg_match('/\( *DOUBLE *\)/i', $title)) {
            $double_count++;
        } elseif (preg_match('/\( *PENTHOUSE *\)/i', $title)) {
            $penthouse_count++;
        }
    }

    update_post_meta(10773, 'nd_booking_meta_box_qnt', $simple_count);    // SIMPLE
    update_post_meta(13803, 'nd_booking_meta_box_qnt', $double_count);    // DOUBLE
    update_post_meta(13804, 'nd_booking_meta_box_qnt', $penthouse_count); // PENTHOUSE

    error_log("✅ FINAL SYNC: SIMPLE=$simple_count, DOUBLE=$double_count, PENTHOUSE=$penthouse_count");
}







function wp_loft_booking_sync_tenants_ajax() {
    wp_loft_booking_fetch_and_save_tenants();
    wp_send_json_success("Tenants synced successfully.");
}

add_action('wp_ajax_wp_loft_booking_sync_tenants', 'wp_loft_booking_sync_tenants_ajax');

add_action('wp_ajax_wp_loft_booking_sync_keychains', 'wp_loft_booking_sync_keychains_handler');

function wp_loft_booking_sync_keychains_handler() {
    $token = get_option('butterflymx_access_token_v3');
    $env = get_option('butterflymx_environment', 'production');
    $base_url = $env === 'production'
        ? "https://api.butterflymx.com/v3"
        : "https://api.na.sandbox.butterflymx.com/v3";

    if (!$token) {
        wp_send_json_error("Missing ButterflyMX token.");
    }
    error_log("🔑 Usando token V3 desde opciones: $token");

    $response = wp_remote_get("$base_url/keychains", [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Content-Type'  => 'application/vnd.api+json'
        ],
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error("Request failed: " . $response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($body['data']) || !is_array($body['data'])) {
        wp_send_json_error("Invalid response format.");
    }

    $result = wp_loft_booking_sync_keychains_only($body['data']);

    if ($result === true) {
        wp_send_json_success("✅ Keychains synced successfully.");
    } else {
        wp_send_json_error("❌ Sync failed internally.");
    }
}

// add_action('wp_ajax_wp_loft_booking_sync_tenants', 'wp_loft_booking_fetch_and_save_tenants');

