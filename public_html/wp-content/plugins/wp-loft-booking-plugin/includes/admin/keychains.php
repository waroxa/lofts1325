<?php
defined('ABSPATH') || exit;

function keychains_page_function() {
    global $wpdb;

    $now           = current_time('mysql');
    $kc_table      = $wpdb->prefix . 'loft_keychains';
    $vk_table      = $wpdb->prefix . 'loft_virtual_keys';
    $kc_vk_table   = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $units_table   = $wpdb->prefix . 'loft_units';
    $tenants_table = $wpdb->prefix . 'loft_tenants';

    // Ensure the tables contain the columns we rely on for enriched output.
    if (!function_exists('maybe_add_column')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    maybe_add_column(
        $kc_table,
        'keychain_id',
        "ALTER TABLE $kc_table ADD COLUMN keychain_id INT UNSIGNED DEFAULT NULL AFTER id"
    );

    maybe_add_column(
        $kc_table,
        'people_count',
        "ALTER TABLE $kc_table ADD COLUMN people_count SMALLINT UNSIGNED DEFAULT 0 AFTER valid_until"
    );

    maybe_add_column(
        $kc_table,
        'people_json',
        "ALTER TABLE $kc_table ADD COLUMN people_json LONGTEXT NULL AFTER people_count"
    );

    maybe_add_column(
        $vk_table,
        'key_type',
        "ALTER TABLE $vk_table ADD COLUMN key_type VARCHAR(100) DEFAULT '' AFTER key_status"
    );

    echo '<div class="wrap"><h1>🔑 Keychains</h1>';

    if (isset($_POST['wp_loft_booking_test_keychain'])) {
        check_admin_referer('wp_loft_booking_test_keychain_action');

        $test_result = wp_loft_booking_run_test_keychain_request();

        if (is_wp_error($test_result)) {
            $error_message = esc_html($test_result->get_error_message());
            $error_details = $test_result->get_error_data();

            if (!empty($error_details) && is_array($error_details)) {
                $error_message .= '<br><code>' . esc_html(wp_json_encode($error_details)) . '</code>';
            }

            echo "<div class='notice notice-error'><p>❌ Test keychain request failed: {$error_message}</p></div>";
        } else {
            $keychain_id = isset($test_result['keychain_id']) ? intval($test_result['keychain_id']) : 0;
            $status_code = isset($test_result['status']) ? intval($test_result['status']) : 0;
            echo "<div class='notice notice-success'><p>✅ Test keychain request sent. Status: {$status_code}. Keychain ID: " . esc_html((string) $keychain_id) . '</p></div>';
        }
    }

    if (isset($_POST['sync_keychains'])) {
        $fetched = wp_loft_booking_sync_keychains_from_api();

        if (is_wp_error($fetched)) {
            $error_message = esc_html($fetched->get_error_message());
            echo "<div class='notice notice-error'><p>❌ Failed to sync keychains: {$error_message}</p></div>";
        } elseif (empty($fetched)) {
            echo "<div class='notice notice-warning'><p>⚠️ No keychains were returned by ButterflyMX. Existing keychains were left untouched.</p></div>";
        } else {
            $existing_key_ids = $wpdb->get_col("SELECT key_id FROM $kc_vk_table");
            if (!empty($existing_key_ids)) {
                $placeholders = implode(', ', array_fill(0, count($existing_key_ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM $vk_table WHERE id IN ($placeholders)", $existing_key_ids));
            }

            $wpdb->query("DELETE FROM $kc_vk_table");
            $wpdb->query("DELETE FROM $kc_table");

            $count = 0;

            foreach ($fetched as $kc) {
                $virtual_keys = array_map(
                    static function ($vk) {
                        if (!is_array($vk)) {
                            return [
                                'id'     => $vk,
                            'type'   => '',
                            'status' => '',
                        ];
                    }

                    return $vk;
                },
                $kc['virtual_keys'] ?? []
            );

            $people = array_map(
                static function ($person) {
                    if (!is_array($person)) {
                        return [];
                    }

                    $first = sanitize_text_field($person['first_name'] ?? '');
                    $last  = sanitize_text_field($person['last_name'] ?? '');

                    return [
                        'id'         => sanitize_text_field($person['id'] ?? ''),
                        'type'       => sanitize_text_field($person['type'] ?? ''),
                        'first_name' => $first,
                        'last_name'  => $last,
                        'email'      => sanitize_email($person['email'] ?? ''),
                    ];
                },
                $kc['people'] ?? []
            );

            $normalized_people = array_values(array_filter(
                $people,
                static function ($person) {
                    return !empty($person['first_name']) || !empty($person['last_name']) || !empty($person['email']);
                }
            ));

            $wpdb->insert($kc_table, [
                'keychain_id'  => isset($kc['keychain_id']) ? intval($kc['keychain_id']) : null,
                'tenant_id'    => isset($kc['tenant_id']) && $kc['tenant_id'] ? intval($kc['tenant_id']) : null,
                'unit_id'      => isset($kc['unit_id']) && $kc['unit_id'] ? intval($kc['unit_id']) : null,
                'name'         => sanitize_text_field($kc['name']),
                'valid_from'   => $kc['valid_from'],
                'valid_until'  => $kc['valid_until'],
                'people_count' => count($normalized_people),
                'people_json'  => empty($normalized_people) ? null : wp_json_encode($normalized_people),
            ]);

            $kc_id = $wpdb->insert_id;

            foreach ($virtual_keys as $vk) {
                $vk_id     = sanitize_text_field($vk['id'] ?? '');
                $vk_label  = sanitize_text_field($vk['label'] ?? ($kc['name'] . ' Key'));
                $vk_type   = sanitize_text_field($vk['type'] ?? '');
                $vk_status = sanitize_text_field($vk['status'] ?? 'active');

                if ($vk_type === '') {
                    $vk_type = 'keychain';
                }

                $wpdb->insert($vk_table, [
                    'name'           => $vk_label,
                    'booking_id'     => null,
                    'virtual_key_id' => $vk_id,
                    'pin_code'       => isset($vk['pin_code']) ? sanitize_text_field($vk['pin_code']) : null,
                    'qr_code_url'    => isset($vk['qr_code_url']) ? esc_url_raw($vk['qr_code_url']) : null,
                    'key_status'     => $vk_status ?: 'active',
                    'key_type'       => $vk_type,
                ]);
                $saved_vk_id = $wpdb->insert_id;

                if ($kc_id && $saved_vk_id) {
                    $wpdb->insert($kc_vk_table, [
                        'keychain_id' => $kc_id,
                        'key_id'      => $saved_vk_id
                    ]);
                }
            }

                $count++;
            }

            echo "<div class='updated'><p>✅ Synced $count active keychains.</p></div>";
        }
    }

    echo '<form method="post" style="margin-bottom:12px;">';
    wp_nonce_field('wp_loft_booking_test_keychain_action');
    echo '<input type="hidden" name="wp_loft_booking_test_keychain" value="1" />';
    echo '<input type="submit" class="button" value="🧪 Create Test ButterflyMX Keychain" />';
    echo '</form>';

    echo '<form method="post"><input type="submit" name="sync_keychains" class="button button-primary" value="🔄 Sync Keychains from ButterflyMX" /></form>';

    $per_page = 15;
    $paged    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset   = ($paged - 1) * $per_page;

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $kc_table WHERE valid_from <= %s AND valid_until >= %s",
        $now,
        $now
    ));

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT kc.*, t.first_name, t.last_name, u.unit_name
        FROM $kc_table kc
        LEFT JOIN $tenants_table t ON kc.tenant_id = t.id
        LEFT JOIN $units_table u ON kc.unit_id = u.id
        WHERE kc.valid_from <= %s AND kc.valid_until >= %s
        ORDER BY kc.valid_until DESC
        LIMIT %d OFFSET %d",
        $now,
        $now,
        $per_page,
        $offset
    ));

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Name</th><th>Tenant</th><th>Unit</th><th>People</th><th>Virtual Keys</th><th>Valid From</th><th>Valid Until</th></tr></thead><tbody>';

    foreach ($rows as $kc) {
        $vk_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT vk.name, vk.key_type, vk.key_status, vk.virtual_key_id
            FROM $kc_vk_table kvk
            JOIN $vk_table vk ON kvk.key_id = vk.id
            WHERE kvk.keychain_id = %d
            ORDER BY vk.name ASC",
            $kc->id
        ));

        if (empty($vk_rows)) {
            $vk_html = '<span style="color:gray;">None</span>';
        } else {
            $vk_items = array_map(
                static function ($vk) {
                    $type   = $vk->key_type ? esc_html($vk->key_type) : 'Unknown';
                    $status = $vk->key_status ? esc_html($vk->key_status) : 'n/a';

                    $label_parts = array_filter([
                        esc_html($vk->name),
                        '(' . $type . ')',
                        '[' . $status . ']',
                    ]);

                    return '<li>' . implode(' ', $label_parts) . '<br><code>' . esc_html($vk->virtual_key_id) . '</code></li>';
                },
                $vk_rows
            );

            $vk_html = '<details><summary>' . count($vk_rows) . ' keys</summary><ul>' . implode('', $vk_items) . '</ul></details>';
        }

        $people_data = [];
        if (!empty($kc->people_json)) {
            $decoded = json_decode($kc->people_json, true);
            if (is_array($decoded)) {
                $people_data = array_filter($decoded, static function ($person) {
                    return !empty($person['first_name']) || !empty($person['last_name']);
                });
            }
        }

        if (empty($people_data)) {
            $people_html = '<span style="color:gray;">None</span>';
        } else {
            $people_items = array_map(
                static function ($person) {
                    $name  = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
                    $type  = !empty($person['type']) ? ' — ' . esc_html($person['type']) : '';
                    $email = !empty($person['email']) ? '<br><a href="mailto:' . esc_attr($person['email']) . '">' . esc_html($person['email']) . '</a>' : '';

                    return '<li>' . esc_html($name ?: 'Unnamed') . $type . $email . '</li>';
                },
                $people_data
            );

            $people_html = '<details><summary>' . count($people_data) . ' people</summary><ul>' . implode('', $people_items) . '</ul></details>';
        }

        echo '<tr>';
        echo '<td>' . esc_html($kc->id) . '</td>';
        echo '<td>' . esc_html($kc->name) . '</td>';
        echo '<td>' . esc_html(trim("{$kc->first_name} {$kc->last_name}")) . '</td>';
        echo '<td>' . esc_html($kc->unit_name ?: '❌ None') . '</td>';
        echo '<td>' . $people_html . '</td>';
        echo '<td>' . $vk_html . '</td>';
        echo '<td>' . esc_html($kc->valid_from) . '</td>';
        echo '<td>' . esc_html($kc->valid_until) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    echo paginate_links([
        'base'    => add_query_arg('paged', '%#%'),
        'format'  => '',
        'prev_text' => '« Prev',
        'next_text' => 'Next »',
        'total'   => ceil($total / $per_page),
        'current' => $paged
    ]);

    echo '</div>';
}








function wp_loft_booking_run_test_keychain_request() {
    $environment = wp_loft_booking_get_butterflymx_environment();
    $base_url    = wp_loft_booking_get_butterflymx_base_url($environment);
    $token       = get_butterflymx_access_token('v4');

    if (empty($token)) {
        error_log('❌ Test keychain request failed: Missing ButterflyMX token.');
        return new WP_Error('no_token', 'ButterflyMX access token missing.');
    }

    try {
        $utc_timezone   = new DateTimeZone('UTC');
        $starts_at_dt   = new DateTimeImmutable('now', $utc_timezone);
        $starts_at_dt   = $starts_at_dt->add(new DateInterval('PT10M'));
        $ends_at_dt     = $starts_at_dt->add(new DateInterval('PT22H'));
        $starts_at_iso  = $starts_at_dt->format('Y-m-d\TH:i:s\Z');
        $ends_at_iso    = $ends_at_dt->format('Y-m-d\TH:i:s\Z');
    } catch (Exception $e) {
        error_log('❌ Failed to calculate ButterflyMX test keychain window: ' . $e->getMessage());

        return new WP_Error('date_calculation_failed', 'Unable to calculate keychain validity window.');
    }

    $payload = [
        'keychain' => [
            'name'             => 'LOFT 224 - TESTM',
            'unit_id'          => 1632229,
            'starts_at'        => $starts_at_iso,
            'ends_at'          => $ends_at_iso,
            'recipients'       => ['info@loft1325.com', '+15145537497'],
            'access_point_ids' => [23940, 23941, 23942],
            'device_ids'       => [],
        ],
    ];

    $response = wp_remote_post(
        trailingslashit($base_url) . 'keychains/custom',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($payload, JSON_UNESCAPED_SLASHES),
            'timeout' => 20,
        ]
    );

    if (is_wp_error($response)) {
        error_log('❌ Test keychain request error: ' . $response->get_error_message());
        return new WP_Error('http_request_failed', $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $raw_body    = wp_remote_retrieve_body($response);
    $decoded     = json_decode($raw_body, true);

    if ($status_code >= 300) {
        $message = 'ButterflyMX API error.';

        if (is_array($decoded)) {
            if (!empty($decoded['message'])) {
                $message = $decoded['message'];
            } elseif (!empty($decoded['errors'][0]['messages'])) {
                $message = implode(' ', (array) $decoded['errors'][0]['messages']);
            }
        } elseif (!empty($raw_body)) {
            $message = $raw_body;
        }

        error_log(sprintf('❌ Test keychain request failed (%d): %s', $status_code, $message));

        return new WP_Error(
            'http_error',
            $message,
            [
                'status' => $status_code,
                'body'   => is_null($decoded) ? $raw_body : $decoded,
            ]
        );
    }

    $keychain_id = 0;
    if (isset($decoded['data']['id'])) {
        $keychain_id = (int) $decoded['data']['id'];
    }

    error_log(sprintf('✅ Test keychain request succeeded (%d). Keychain ID: %d', $status_code, $keychain_id));

    return [
        'status'      => $status_code,
        'body'        => $decoded,
        'keychain_id' => $keychain_id,
    ];
}

if (!function_exists('wp_loft_booking_normalize_loft_label')) {
    function wp_loft_booking_normalize_loft_label($label) {
        if (preg_match('/LOFTS?\s*-*\s*([0-9]+)/i', $label, $matches)) {
            return 'LOFT' . $matches[1];
        }

        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $label));

        return $normalized !== '' ? $normalized : null;
    }
}

function wp_loft_booking_sync_keychains_only($keychains) {
    global $wpdb;

    $keychains_table = $wpdb->prefix . 'loft_keychains';
    $virtual_keys_table = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $tenants_table = $wpdb->prefix . 'loft_tenants';
    $units_table = $wpdb->prefix . 'loft_units';

    // 🔥 Delete old data
    $wpdb->query("DELETE FROM $virtual_keys_table");
    $wpdb->query("DELETE FROM $keychains_table");

    $now = new DateTime('now', new DateTimeZone('UTC'));

    foreach ($keychains as $keychain) {
        $attributes = $keychain['attributes'];
        $relationships = $keychain['relationships'];

        $external_tenant = $relationships['tenant']['data']['id'] ?? null;
        $virtual_keys = $relationships['virtual_keys']['data'] ?? [];
        $devices = $relationships['devices']['data'] ?? [];

        // 🚫 Skip if no tenant or no virtual key
        if (!$external_tenant || empty($virtual_keys)) {
            error_log("❌ Skipped keychain with missing tenant ID or virtual keys: " . json_encode($keychain));
            continue;
        }

        // 🚫 Skip if expired
        $valid_until = new DateTime($attributes['ends_at'], new DateTimeZone('UTC'));
        if ($valid_until < $now) {
            error_log("⏱️ Skipped expired keychain: {$attributes['name']} (ends_at: {$attributes['ends_at']})");
            continue;
        }

        $external_tenant_id = intval($external_tenant);
        $tenant_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $tenants_table WHERE tenant_id = %d", $external_tenant_id)
        );

        if (!$tenant_id) {
            error_log("❌ DB Error: No tenant found for external ID $external_tenant_id");
            continue;
        }

        // 🆗 Get first panel device as unit
        $unit_api_id = null;
        foreach ($devices as $device) {
            if ($device['type'] === 'panels') {
                $unit_api_id = intval($device['id']);
                break;
            }
        }

        $unit_id = null;
        if ($unit_api_id) {
            $unit_id = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM $units_table WHERE unit_id_api = %d", $unit_api_id)
            );
        }

        $valid_from = date('Y-m-d H:i:s', strtotime($attributes['starts_at']));
        $valid_until_str = $valid_until->format('Y-m-d H:i:s');
        $key_name = sanitize_text_field($attributes['name'] ?? '');

        $wpdb->insert($keychains_table, [
            'tenant_id'   => $tenant_id,
            'unit_id'     => $unit_id,
            'key_id'      => intval($virtual_keys[0]['id']),
            'name'        => $key_name,
            'valid_from'  => $valid_from,
            'valid_until' => $valid_until_str
        ], [
            '%d', '%d', '%d', '%s', '%s', '%s'
        ]);

        $keychain_id = $wpdb->insert_id;

        if (!$keychain_id) {
            error_log("❌ DB Error inserting keychain: " . $wpdb->last_error);
            continue;
        }

        foreach ($virtual_keys as $vk) {
            $wpdb->insert($virtual_keys_table, [
                'keychain_id' => $keychain_id,
                'key_id'      => intval($vk['id'])
            ], ['%d', '%d']);
        }

        error_log("✅ Keychain synced: tenant=$tenant_id, unit=$unit_id, keys=" . count($virtual_keys));
    }

    return true;
}

function wp_loft_booking_relink_keychains_to_units() {
    global $wpdb;

    $keychains_table = $wpdb->prefix . 'loft_keychains';
    $units_table     = $wpdb->prefix . 'loft_units';

    $keychains = $wpdb->get_results("SELECT id, name, unit_id FROM {$keychains_table}");

    if (empty($keychains)) {
        return 0;
    }

    $updated = 0;

    foreach ($keychains as $keychain) {
        $normalized = wp_loft_booking_normalize_loft_label($keychain->name);

        if (empty($normalized)) {
            continue;
        }

        $unit_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$units_table} WHERE REPLACE(UPPER(unit_name), ' ', '') LIKE %s LIMIT 1",
            '%' . $normalized . '%'
        ));

        if (!$unit_id || intval($unit_id) === intval($keychain->unit_id)) {
            continue;
        }

        $wpdb->update(
            $keychains_table,
            ['unit_id' => intval($unit_id)],
            ['id' => intval($keychain->id)],
            ['%d'],
            ['%d']
        );

        if ($wpdb->last_error) {
            error_log('❌ Failed to relink keychain ID ' . intval($keychain->id) . ': ' . $wpdb->last_error);
            continue;
        }

        $updated++;
    }

    if ($updated > 0) {
        error_log("🔗 Relinked {$updated} keychains to refreshed units.");
    }

    return $updated;
}



function wp_loft_booking_sync_keychains() {
    global $wpdb;

    $keychains_table = $wpdb->prefix . 'loft_keychains';
    $vk_table        = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $tenants_table   = $wpdb->prefix . 'loft_tenants';
    $units_table     = $wpdb->prefix . 'loft_units';

    $keychains = wp_loft_booking_fetch_keychains_from_api();

    if (is_wp_error($keychains)) {
        return $keychains;
    }

    if (empty($keychains)) {
        return new WP_Error('wp_loft_booking_empty_keychains', 'No keychains were returned from ButterflyMX.');
    }

    // Only clear the existing data once we have fresh results.
    $wpdb->query("DELETE FROM $vk_table");
    $wpdb->query("DELETE FROM $keychains_table");

    foreach ($keychains as $keychain) {
        $attributes    = $keychain['attributes'] ?? [];
        $relationships = $keychain['relationships'] ?? [];

        $external_tenant_id = $relationships['tenant']['data']['id'] ?? null;
        $virtual_keys       = $relationships['virtual_keys']['data'] ?? [];
        $unit_label         = $attributes['name'] ?? null;

        if (!$external_tenant_id || empty($virtual_keys)) {
            error_log("❌ Skipped keychain with missing tenant ID or virtual keys: " . wp_json_encode($keychain));
            continue;
        }

        $tenant_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tenants_table WHERE tenant_id = %d",
            $external_tenant_id
        ));

        $unit_id = null;
        if ($unit_label) {
            $unit_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $units_table WHERE unit_name = %s",
                $unit_label
            ));
        }

        if (!$tenant_id) {
            error_log("❌ Tenant not found for external ID $external_tenant_id");
            continue;
        }

        $kc_data = [
            'tenant_id'   => $tenant_id,
            'unit_id'     => $unit_id,
            'name'        => sanitize_text_field($unit_label ?? ''),
            'valid_from'  => date('Y-m-d H:i:s', strtotime($attributes['starts_at'] ?? 'now')),
            'valid_until' => date('Y-m-d H:i:s', strtotime($attributes['ends_at'] ?? 'now')),
        ];

        $inserted = $wpdb->insert($keychains_table, $kc_data, ['%d', '%d', '%s', '%s', '%s']);

        if (!$inserted) {
            error_log("❌ Error inserting keychain: " . $wpdb->last_error);
            continue;
        }

        $kc_id = $wpdb->insert_id;

        foreach ($virtual_keys as $vk) {
            if (!isset($vk['id'])) {
                continue;
            }

            $wpdb->insert(
                $vk_table,
                [
                    'keychain_id' => $kc_id,
                    'key_id'      => intval($vk['id'])
                ],
                ['%d', '%d']
            );
        }

        error_log("✅ Synced keychain '{$kc_data['name']}' with " . count($virtual_keys) . " virtual keys.");
    }

    $active_units = $wpdb->get_col(
        "SELECT DISTINCT u.unit_name FROM $units_table u INNER JOIN $keychains_table kc ON kc.unit_id = u.id"
    );

    wp_loft_booking_update_unit_statuses($active_units);

    return true;
}

function wp_loft_booking_fetch_keychains_from_api() {
    $token_v4 = get_option('butterflymx_access_token_v4');
    $token_v3 = get_option('butterflymx_access_token_v3');
    $environment = get_option('butterflymx_environment', 'sandbox');

    $base_url = ($environment === 'production')
        ? 'https://api.butterflymx.com'
        : 'https://api.na.sandbox.butterflymx.com';

    $version = null;
    $token   = null;

    if ($token_v4) {
        $version = 'v4';
        $token   = $token_v4;
    } elseif ($token_v3) {
        $version = 'v3';
        $token   = $token_v3;
    }

    if (!$version || !$token) {
        error_log('❌ Missing ButterflyMX token for keychain sync.');
        return new WP_Error('wp_loft_booking_missing_token', 'Missing ButterflyMX API token.');
    }

    $url          = sprintf('%s/%s/keychains', $base_url, $version);
    $all_keychains = [];

    while ($url) {
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/vnd.api+json',
                'Accept'        => 'application/vnd.api+json',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('❌ Keychain API request failed: ' . $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['data']) || !is_array($body['data'])) {
            break;
        }

        $all_keychains = array_merge($all_keychains, $body['data']);

        $next = $body['links']['next'] ?? null;
        $url  = $next ?: null;
    }

    error_log('✅ Keychain API returned ' . count($all_keychains) . ' records using ' . strtoupper($version) . '.');

    return $all_keychains;
}

function wp_loft_booking_fetch_keychains_chunks($chunk_size = 50, $max_pages = 10) {
    $token = get_option('butterflymx_token_v3');
    if (!$token) {
        error_log("❌ ButterflyMX V3 token not set.");
        return [];
    }

    $page = 1;
    $all_keychains = [];

    while ($page <= $max_pages) {
        $response = wp_remote_get("https://api.butterflymx.com/v3/keychains", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/vnd.api+json'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log("❌ Error fetching keychains (page $page): " . $response->get_error_message());
            break;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['data'])) {
            break; // Fin de resultados
        }

        error_log("📦 Page $page: Retrieved " . count($body['data']) . " keychains.");
        $all_keychains[] = $body['data']; // Guardar como lote
        $page++;

        // Salir si no hay "next"
        if (!isset($body['links']['next'])) break;
    }

    return $all_keychains; // Array de arrays, cada uno es un chunk
}

function wp_loft_booking_sync_keychains_chunked() {
    set_time_limit(300);
    ini_set('max_execution_time', 300);

    $chunks = wp_loft_booking_fetch_keychains_chunks(50, 10); // Máx. 500 registros
    $total = 0;

    foreach ($chunks as $keychain_batch) {
        $count = count($keychain_batch);
        $result = wp_loft_booking_sync_keychains_only($keychain_batch);
        if ($result) {
            $total += $count;
            error_log("✅ Synced $count keychains in batch. Running total: $total");
        } else {
            error_log("❌ Failed syncing batch of $count keychains");
        }
    }

    return $total;
}

function wp_loft_booking_sync_keychains_from_api() {
    global $wpdb;

    $environment = function_exists('wp_loft_booking_get_butterflymx_environment')
        ? wp_loft_booking_get_butterflymx_environment()
        : get_option('butterflymx_environment', 'sandbox');

    $access_token = get_option('butterflymx_access_token_v3');
    $version      = 'v3';

    if (!$access_token) {
        $access_token = get_option('butterflymx_token_v3');
    }

    if (!$access_token) {
        $alternate = get_option('butterflymx_access_token_v4');
        if ($alternate) {
            $access_token = $alternate;
            $version      = 'v4';
        }
    }

    if (!$access_token) {
        error_log('❌ ButterflyMX token missing.');
        return new WP_Error('wp_loft_booking_missing_token', 'ButterflyMX access token is not configured.');
    }

    $now = current_time('timestamp');
    $active_keychains = [];

    if ($version === 'v4') {
        $api_base = ($environment === 'production')
            ? 'https://api.butterflymx.com/v4/'
            : 'https://api.na.sandbox.butterflymx.com/v4/';
    } else {
        $api_base = ($environment === 'production')
            ? 'https://api.butterflymx.com/v3/'
            : 'https://api.na.sandbox.butterflymx.com/v3/';
    }

    $query_args = [
        'include' => 'virtual_keys,virtual_key_distributions,people,tenant,devices',
    ];

    if ($version === 'v4') {
        $query_args['page[size]'] = 100;
    } else {
        $query_args['per_page'] = 100;
    }

    $url = add_query_arg($query_args, $api_base . 'keychains');
    $api_root = rtrim($api_base, '/');

    while ($url) {
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/vnd.api+json',
                'Content-Type'  => 'application/vnd.api+json',
            ],
            'timeout' => 20
        ]);

        if (is_wp_error($response)) {
            error_log('❌ API error: ' . $response->get_error_message());
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            $raw_body = wp_remote_retrieve_body($response);
            error_log(sprintf('❌ ButterflyMX API error (%d): %s', $status_code, $raw_body));

            return new WP_Error(
                'wp_loft_booking_http_error',
                sprintf('ButterflyMX API returned HTTP %d.', $status_code),
                [
                    'status' => $status_code,
                    'body'   => $raw_body,
                ]
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['data']) || count($body['data']) === 0) {
            break;
        }

        $included_index = [];
        if (!empty($body['included']) && is_array($body['included'])) {
            foreach ($body['included'] as $resource) {
                $type = $resource['type'] ?? '';
                $id   = $resource['id'] ?? '';

                if (!$type || !$id) {
                    continue;
                }

                $included_index[sprintf('%s:%s', $type, $id)] = $resource;
            }
        }

        foreach ($body['data'] as $item) {
            $attrs         = $item['attributes'];
            $relationships = $item['relationships'];
            $valid_from    = strtotime($attrs['starts_at']);
            $valid_until   = strtotime($attrs['ends_at']);

            if ($valid_from === false || $valid_until === false) {
                continue;
            }

            // Keep keychains that are currently active or scheduled in the future.
            if ($valid_until !== false && $valid_until >= $now) {
                // Extract tenant and unit via panel
                $external_tenant_id = $relationships['tenant']['data']['id'] ?? null;

                $tenant_id = null;
                if ($external_tenant_id) {
                    $tenant_id = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}loft_tenants WHERE tenant_id = %s",
                            $external_tenant_id
                        )
                    );
                    if ($tenant_id) {
                        $tenant_id = intval($tenant_id);
                    }
                }

                $devices = $relationships['devices']['data'] ?? [];
                $unit_api_id = null;
                foreach ($devices as $device) {
                    if ($device['type'] === 'panels') {
                        $unit_api_id = intval($device['id']);
                        break;
                    }
                }

                $unit_id = null;
                if ($unit_api_id) {
                    $unit_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}loft_units WHERE unit_id_api = %d", $unit_api_id
                    ));
                }

                $virtual_keys = [];
                foreach ($relationships['virtual_keys']['data'] ?? [] as $vk) {
                    $lookup_key = sprintf('%s:%s', $vk['type'] ?? 'virtual_keys', $vk['id'] ?? '');
                    $vk_resource = $included_index[$lookup_key] ?? [];
                    $vk_attrs = $vk_resource['attributes'] ?? [];

                    $virtual_keys[] = [
                        'id'        => $vk['id'] ?? '',
                        'type'      => sanitize_text_field($vk_attrs['distribution_method'] ?? $vk_attrs['type'] ?? ($vk['type'] ?? '')),
                        'status'    => sanitize_text_field($vk_attrs['status'] ?? ''),
                        'label'     => sanitize_text_field($vk_attrs['name'] ?? $vk_attrs['label'] ?? ''),
                        'pin_code'  => $vk_attrs['pin_code'] ?? ($vk_attrs['pin'] ?? null),
                        'qr_code_url' => $vk_attrs['qr_code_url'] ?? null,
                    ];
                }

                if (empty($virtual_keys)) {
                    continue;
                }

                $people = [];
                foreach ($relationships['people']['data'] ?? [] as $person) {
                    $lookup_key = sprintf('%s:%s', $person['type'] ?? 'people', $person['id'] ?? '');
                    $person_resource = $included_index[$lookup_key] ?? [];
                    $person_attrs = $person_resource['attributes'] ?? [];

                    $people[] = [
                        'id'         => $person['id'] ?? '',
                        'type'       => $person_attrs['type'] ?? ($person['type'] ?? ''),
                        'first_name' => $person_attrs['first_name'] ?? '',
                        'last_name'  => $person_attrs['last_name'] ?? '',
                        'email'      => $person_attrs['email'] ?? '',
                    ];
                }

                $valid_from_gmt  = gmdate('Y-m-d H:i:s', $valid_from);
                $valid_until_gmt = gmdate('Y-m-d H:i:s', $valid_until);
                $valid_from_local = get_date_from_gmt($valid_from_gmt, 'Y-m-d H:i:s');
                $valid_until_local = get_date_from_gmt($valid_until_gmt, 'Y-m-d H:i:s');

                $active_keychains[] = [
                    'keychain_id'          => intval($item['id'] ?? 0),
                    'name'                 => sanitize_text_field($attrs['name'] ?? 'Unnamed Keychain'),
                    'tenant_id'            => $tenant_id,
                    'unit_id'              => $unit_id ? intval($unit_id) : null,
                    'valid_from'           => $valid_from_local,
                    'valid_until'          => $valid_until_local,
                    'virtual_keys'         => $virtual_keys,
                    'people'               => $people,
                    'external_tenant_id'   => $external_tenant_id,
                    'external_unit_api_id' => $unit_api_id,
                ];
            }
        }

        $next = $body['links']['next'] ?? null; // follow cursor-based pagination

        if ($next) {
            if (strpos($next, 'http') === 0) {
                $url = $next;
            } elseif (strpos($next, '/') === 0) {
                $url = $api_root . $next;
            } else {
                $url = trailingslashit($api_base) . ltrim($next, '/');
            }
        } else {
            $url = null;
        }
    }

    return $active_keychains;
}

/**
 * Update loft unit availability based on a list of active units.
 *
 * @param array $active_units Array of unit names that should be marked as Available.
 */
function wp_loft_booking_update_unit_statuses(array $active_units) {
    global $wpdb;

    $units_table = $wpdb->prefix . 'loft_units';

    // Set all units as Occupied by default.
    $wpdb->query("UPDATE $units_table SET status = 'Occupied'");

    if (empty($active_units)) {
        return;
    }

    // Prepare placeholders for the active unit names.
    $placeholders = implode(',', array_fill(0, count($active_units), '%s'));
    $sql = $wpdb->prepare(
        "UPDATE $units_table SET status = 'Available' WHERE unit_name IN ($placeholders)",
        $active_units
    );

    $wpdb->query($sql);
}

function wp_loft_booking_save_keychain_data($booking_id, $unit_id, $keychain_id, $virtual_key_id, $start, $end) {
    global $wpdb;

    $kc_table   = $wpdb->prefix . 'loft_keychains';
    $vk_table   = $wpdb->prefix . 'loft_virtual_keys';
    $link_table = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $units_table = $wpdb->prefix . 'loft_units';

    $booking_fk = (is_numeric($booking_id) && (int) $booking_id > 0) ? (int) $booking_id : null;
    $unit_fk    = (is_numeric($unit_id) && (int) $unit_id > 0) ? (int) $unit_id : null;

    $valid_from = gmdate('Y-m-d H:i:s', strtotime($start));
    $valid_until = gmdate('Y-m-d H:i:s', strtotime($end));

    $unit_label = '';

    if ($unit_fk) {
        $unit_label = (string) $wpdb->get_var(
            $wpdb->prepare("SELECT unit_name FROM {$units_table} WHERE id = %d", $unit_fk)
        );
    }

    if ('' === $unit_label && $booking_fk) {
        $unit_label = sprintf('Booking %d', $booking_fk);
    } elseif ('' === $unit_label) {
        $unit_label = sprintf('Keychain %d', (int) $keychain_id);
    }

    $kc_data = [
        'keychain_id' => (int) $keychain_id,
        'name'        => $unit_label,
        'valid_from'  => $valid_from,
        'valid_until' => $valid_until,
    ];

    if ($booking_fk) {
        $kc_data['booking_id'] = $booking_fk;
    }

    if (!is_null($unit_fk)) {
        $kc_data['unit_id'] = $unit_fk;
    }

    $wpdb->insert($kc_table, $kc_data);
    $saved_kc_id = (int) $wpdb->insert_id;

    $vk_data = [
        'name'           => $booking_fk ? sprintf('Booking %d Virtual Key', $booking_fk) : sprintf('Virtual Key %s', $virtual_key_id),
        'virtual_key_id' => sanitize_text_field($virtual_key_id),
        'key_status'     => 'active',
    ];

    if ($booking_fk) {
        $vk_data['booking_id'] = $booking_fk;
    }

    $wpdb->insert($vk_table, $vk_data);
    $saved_vk_id = (int) $wpdb->insert_id;

    if ($saved_kc_id && $saved_vk_id) {
        $wpdb->insert($link_table, [
            'keychain_id' => $saved_kc_id,
            'key_id'      => $saved_vk_id,
        ]);
    }
}



