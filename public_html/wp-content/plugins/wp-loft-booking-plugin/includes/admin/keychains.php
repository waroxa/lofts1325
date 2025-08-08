<?php
defined('ABSPATH') || exit;

// function keychains_page_function() {
//     global $wpdb;
//     $keychains_table = $wpdb->prefix . 'loft_keychains';
//     $tenants_table = $wpdb->prefix . 'loft_tenants';
//     $units_table = $wpdb->prefix . 'loft_units';
//     $vk_table = $wpdb->prefix . 'loft_keychain_virtual_keys';

//     echo '<div class="wrap"><h1>🔑 Keychains</h1>';

//     echo '<form method="post">';
//     echo '<input type="submit" name="sync_keychains" class="button button-primary" value="🔄 Sync Keychains from ButterflyMX" />';
//     echo '</form>';

//     if (isset($_POST['sync_keychains'])) {
//         $total = wp_loft_booking_sync_keychains_chunked();
//         echo "<div class='updated'><p>✅ $total keychains synced in chunks.</p></div>";
//     }

//     $results = $wpdb->get_results("
//         SELECT kc.*, t.first_name, t.last_name, u.unit_name
//         FROM $keychains_table kc
//         LEFT JOIN $tenants_table t ON kc.tenant_id = t.id
//         LEFT JOIN $units_table u ON kc.unit_id = u.id
//         ORDER BY kc.valid_until DESC
//     ");

//     if (empty($results)) {
//         echo '<p>No keychains found.</p>';
//         return;
//     }

//     echo '<table class="wp-list-table widefat fixed striped">';
//     echo '<thead><tr>
//         <th>ID</th><th>Keychain Name</th><th>Tenant</th><th>Unit</th><th>Virtual Keys</th><th>Valid From</th><th>Valid Until</th>
//     </tr></thead><tbody>';

//     foreach ($results as $kc) {
//         $tenant_name = $kc->first_name ? esc_html($kc->first_name . ' ' . $kc->last_name) : '<span style="color:red;">❌ Not linked</span>';
//         $unit_name = $kc->unit_name ? esc_html($kc->unit_name) : '<span style="color:red;">❌ None</span>';

//         $vk_ids = $wpdb->get_col($wpdb->prepare(
//             "SELECT key_id FROM $vk_table WHERE keychain_id = %d", $kc->id
//         ));

//         $vk_content = empty($vk_ids)
//             ? '<span style="color:gray;">❌ None</span>'
//             : '<details><summary>👁 Ver ' . count($vk_ids) . '</summary><ul>' .
//                 implode('', array_map(fn($id) => '<li>🔑 ' . esc_html($id) . '</li>', $vk_ids)) .
//               '</ul></details>';

//         echo '<tr>';
//         echo '<td>' . esc_html($kc->id) . '</td>';
//         echo '<td>' . esc_html($kc->name) . '</td>';
//         echo '<td>' . $tenant_name . '</td>';
//         echo '<td>' . $unit_name . '</td>';
//         echo '<td>' . $vk_content . '</td>';
//         echo '<td>' . esc_html($kc->valid_from) . '</td>';
//         echo '<td>' . esc_html($kc->valid_until) . '</td>';
//         echo '</tr>';
//     }

//     echo '</tbody></table>';
//     echo '</div>';
// }

function keychains_page_function() {
    global $wpdb;
    $now = current_time('mysql');
    $kc_table = $wpdb->prefix . 'loft_keychains';
    $vk_table = $wpdb->prefix . 'loft_virtual_keys';
    $kc_vk_table = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $units_table = $wpdb->prefix . 'loft_units';
    $tenants_table = $wpdb->prefix . 'loft_tenants';

    echo '<div class="wrap"><h1>🔑 Keychains</h1>';

    if (isset($_POST['sync_keychains'])) {
        $fetched = wp_loft_booking_sync_keychains_from_api();

        $wpdb->query("DELETE FROM $kc_vk_table");
        $wpdb->query("DELETE FROM $kc_table");

        $count = 0;

        foreach ($fetched as $kc) {
            $wpdb->insert($kc_table, [
                'tenant_id'    => $kc['tenant_id'],
                'unit_id'      => $kc['unit_id'],
                'name'         => $kc['name'],
                'valid_from'   => $kc['valid_from'],
                'valid_until'  => $kc['valid_until']
            ]);

            $kc_id = $wpdb->insert_id;

            foreach ($kc['virtual_keys'] as $vk_id) {
                $wpdb->insert($vk_table, [
                    'name'           => $kc['name'] . ' Key',
                    'booking_id'     => 0,
                    'virtual_key_id' => $vk_id,
                    'key_status'     => 'active',
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

    echo '<form method="post"><input type="submit" name="sync_keychains" class="button button-primary" value="🔄 Sync Keychains from ButterflyMX" /></form>';

    $per_page = 15;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;

    $total = $wpdb->get_var("
        SELECT COUNT(*) FROM $kc_table
        WHERE valid_from <= '$now' AND valid_until >= '$now' AND name LIKE '%LOFT%'
    ");

    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT kc.*, t.first_name, t.last_name, u.unit_name
        FROM $kc_table kc
        LEFT JOIN $tenants_table t ON kc.tenant_id = t.id
        LEFT JOIN $units_table u ON kc.unit_id = u.id
        WHERE kc.valid_from <= %s AND kc.valid_until >= %s AND kc.name LIKE '%%LOFT%%'
        ORDER BY kc.valid_until DESC
        LIMIT %d OFFSET %d
    ", $now, $now, $per_page, $offset));

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Name</th><th>Tenant</th><th>Unit</th><th>Virtual Keys</th><th>Valid From</th><th>Valid Until</th></tr></thead><tbody>';

    foreach ($rows as $kc) {
        $vk_ids = $wpdb->get_col($wpdb->prepare("
            SELECT vk.virtual_key_id
            FROM $kc_vk_table kvk
            JOIN $vk_table vk ON kvk.key_id = vk.id
            WHERE kvk.keychain_id = %d AND vk.key_status = 'active'
        ", $kc->id));

        $vk_html = empty($vk_ids)
            ? '<span style="color:gray;">None</span>'
            : '<details><summary>' . count($vk_ids) . ' keys</summary><ul><li>' .
              implode('</li><li>', array_map('esc_html', $vk_ids)) . '</li></ul></details>';

        echo '<tr>';
        echo '<td>' . esc_html($kc->id) . '</td>';
        echo '<td>' . esc_html($kc->name) . '</td>';
        echo '<td>' . esc_html(trim("{$kc->first_name} {$kc->last_name}")) . '</td>';
        echo '<td>' . esc_html($kc->unit_name ?: '❌ None') . '</td>';
        echo '<td>' . $vk_html . '</td>';
        echo '<td>' . esc_html($kc->valid_from) . '</td>';
        echo '<td>' . esc_html($kc->valid_until) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    echo paginate_links([
        'base' => add_query_arg('paged', '%#%'),
        'format' => '',
        'prev_text' => '« Prev',
        'next_text' => 'Next »',
        'total' => ceil($total / $per_page),
        'current' => $paged
    ]);

    echo '</div>';
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



function wp_loft_booking_sync_keychains() {
    global $wpdb;

    $keychains_table = $wpdb->prefix . 'loft_keychains';
    $vk_table = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $tenants_table = $wpdb->prefix . 'loft_tenants';
    $units_table = $wpdb->prefix . 'loft_units';

    // 🔥 Limpia datos antiguos para evitar duplicados o acumulación
    $wpdb->query("DELETE FROM $vk_table");
    $wpdb->query("DELETE FROM $keychains_table");

    // ✅ Obtén los datos de ButterflyMX
    $keychains = wp_loft_booking_fetch_keychains_from_api(); // Asegúrate que esta exista y funcione
    error_log('🔍 Keychains received from API: ' . count($keychains));

    foreach ($keychains as $keychain) {
        $external_tenant_id = $keychain['relationships']['tenant']['data']['id'] ?? null;
        $virtual_keys = $keychain['relationships']['virtual_keys']['data'] ?? [];
        $unit_label = $keychain['attributes']['name'] ?? null;

        if (!$external_tenant_id || empty($virtual_keys)) {
            error_log("❌ Skipped keychain with missing tenant ID or virtual keys: " . json_encode($keychain));
            continue;
        }

        $tenant_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tenants_table WHERE tenant_id = %d", $external_tenant_id
        ));

        $unit_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $units_table WHERE unit_name = %s", $unit_label
        ));

        if (!$tenant_id) {
            error_log("❌ Tenant not found for ID $external_tenant_id");
            continue;
        }

        $kc_data = [
            'tenant_id'   => $tenant_id,
            'unit_id'     => $unit_id,
            'name'        => sanitize_text_field($unit_label),
            'valid_from'  => date('Y-m-d H:i:s', strtotime($keychain['attributes']['starts_at'])),
            'valid_until' => date('Y-m-d H:i:s', strtotime($keychain['attributes']['ends_at'])),
        ];

        $inserted = $wpdb->insert($keychains_table, $kc_data, [
            '%d', '%d', '%s', '%s', '%s'
        ]);

        if (!$inserted) {
            error_log("❌ Error inserting keychain: " . $wpdb->last_error);
            continue;
        }

        $kc_id = $wpdb->insert_id;

        foreach ($virtual_keys as $vk) {
            $wpdb->insert($vk_table, [
                'keychain_id' => $kc_id,
                'key_id'      => intval($vk['id'])
            ], ['%d', '%d']);
        }

        error_log("✅ Synced keychain '{$kc_data['name']}' with " . count($virtual_keys) . " virtual keys.");
    }
    // 🔄 After syncing keychains, update unit availability status.
    $active_units = $wpdb->get_col(
        "SELECT DISTINCT u.unit_name FROM $units_table u INNER JOIN $keychains_table kc ON kc.unit_id = u.id"
    );

    wp_loft_booking_update_unit_statuses($active_units);

    return true;
}

function wp_loft_booking_fetch_keychains_from_api() {
    $token = get_option('butterflymx_access_token_v4');
    if (!$token) {
        error_log("❌ No V3 token found.");
        return [];
    }

    $page = 1;
    $all_keychains = [];
    error_log("🔑 Usando token V3 desde opciones: $token");

    while (true) {
        $url = "https://api.butterflymx.com/v4/keychains";
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/vnd.api+json'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log("❌ API request failed: " . $response->get_error_message());
            break;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['data'])) {
            break;
        }

        $all_keychains = array_merge($all_keychains, $body['data']);
        $page++;

        if (!isset($body['links']['next'])) break;
    }

    error_log("✅ API V3 returned " . count($all_keychains) . " keychains.");
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

function wp_loft_booking_sync_keychains_from_api(): array {
    global $wpdb;
    $access_token = get_option('butterflymx_access_token_v3');
    $now = strtotime(current_time('mysql'));

    if (!$access_token) {
        error_log('❌ ButterflyMX token missing.');
        return [];
    }

    $active_keychains = [];
    $url = "https://api.butterflymx.com/v3/keychains";

    while ($url) {
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/json',
            ],
            'timeout' => 20
        ]);

        if (is_wp_error($response)) {
            error_log('❌ API error: ' . $response->get_error_message());
            break;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['data']) || count($body['data']) === 0) {
            break;
        }

        foreach ($body['data'] as $item) {
            $attrs = $item['attributes'];
            $relationships = $item['relationships'];
            $valid_from = strtotime($attrs['starts_at']);
            $valid_until = strtotime($attrs['ends_at']);

            if ($valid_from <= $now && $valid_until >= $now) {
                // Extract tenant and unit via panel
                $external_tenant_id = $relationships['tenant']['data']['id'] ?? null;

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

                $active_keychains[] = [
                    'name'         => sanitize_text_field($attrs['name'] ?? 'Unnamed Keychain'),
                    'tenant_id'    => $external_tenant_id,
                    'unit_id'      => $unit_id,
                    'valid_from'   => $attrs['starts_at'],
                    'valid_until'  => $attrs['ends_at'],
                    'virtual_keys' => array_map(fn($vk) => $vk['id'], $relationships['virtual_keys']['data'] ?? [])
                ];
            }
        }

        $url = $body['links']['next'] ?? null; // follow cursor-based pagination
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



