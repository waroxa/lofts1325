<?php
defined('ABSPATH') || exit;

function wplb_lofts_page() {
    echo '<div class="wrap"><h1>Manage Lofts</h1>';
    echo '<button id="sync-units-button" class="button button-primary">Sync Units</button>';
    echo '<div id="sync-units-message" style="margin-top: 10px;"></div>';
    wplb_display_units();
    echo '</div>';

    ?>
    <script type="text/javascript">
        document.getElementById('sync-units-button').addEventListener('click', function() {
            var button = this;
            var messageDiv = document.getElementById('sync-units-message');
            button.disabled = true;
            messageDiv.innerHTML = 'Syncing units...';
            jQuery.post(ajaxurl, { action: 'wplb_sync_units' }, function(response) {
                if (response.success) {
                    messageDiv.innerHTML = '<span style="color: green;">' + response.data + '</span>';
                    location.reload();
                } else {
                    messageDiv.innerHTML = '<span style="color: red;">Failed to sync units.</span>';
                }
                button.disabled = false;
            }).fail(function() {
                messageDiv.innerHTML = '<span style="color: red;">AJAX request failed.</span>';
                button.disabled = false;
            });
        });
    </script>
    <?php
}

function wplb_display_units() {
    global $wpdb;

    $units = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}loft_units");

    echo '<table class="widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>Unit Name</th>
                <th>Floor</th>
                <th>Access Group</th>
                <th>Tenants</th>
                <th>Max Adults</th>
                <th>Max Children</th>
                <th>Status</th>
                <th>Available Until</th>
            </tr>
          </thead><tbody>';

    $now = current_time('mysql');

    foreach ($units as $unit) {
        $unit_name = strtoupper(trim($unit->unit_name));

        // 🔍 Search for active keychain linked by name
        $keychain_id = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}loft_keychains
            WHERE name LIKE %s
            AND valid_from <= %s
            AND valid_until >= %s
            LIMIT 1
        ", '%' . $unit_name . '%', $now, $now));

        if ($keychain_id) {
            // Get the valid_until to display
            $until = $wpdb->get_var($wpdb->prepare("
                SELECT valid_until FROM {$wpdb->prefix}loft_keychains
                WHERE id = %d
            ", $keychain_id));

            $status = 'Occupied';
            $status_class = 'style="color:red;font-weight:bold;"';
        } else {
            $status = 'Available';
            $until = 'N/A';
            $status_class = 'style="color:green;font-weight:bold;"';
        }

        echo "<tr>
            <td>{$unit->unit_name}</td>
            <td>N/A</td>
            <td>N/A</td>
            <td>0</td>
            <td>N/A</td>
            <td>N/A</td>
            <td $status_class>$status</td>
            <td>$until</td>
        </tr>";
    }

    echo '</tbody></table>';
}



function wp_loft_booking_lofts_page() {
    echo '<div class="wrap"><h1>Manage Lofts</h1>';

    // Sync Units Button
    echo '<button id="sync-units-button" class="button button-primary">Sync Units</button>';
    echo '<div id="sync-units-message" style="margin-top: 10px;"></div>'; // Placeholder for sync status

    // Display units table by calling wp_loft_booking_display_units
    wp_loft_booking_display_units();

    echo '</div>';

    // JavaScript for handling the AJAX sync request
    ?>
    <script type="text/javascript">
        document.getElementById('sync-units-button').addEventListener('click', function() {
            var button = this;
            var messageDiv = document.getElementById('sync-units-message');

            button.disabled = true; // Disable button during sync
            messageDiv.innerHTML = 'Syncing units...';

            // AJAX request to sync units
            jQuery.post(ajaxurl, { action: 'wp_loft_booking_sync_units' }, function(response) {
                if (response.success) {
                    messageDiv.innerHTML = '<span style="color: green;">' + response.data + '</span>';
                    // Reload units display after sync
                    location.reload();
                } else {
                    messageDiv.innerHTML = '<span style="color: red;">Failed to sync units. Please try again.</span>';
                }
                button.disabled = false; // Re-enable button after sync
            }).fail(function() {
                messageDiv.innerHTML = '<span style="color: red;">AJAX request failed. Please try again.</span>';
                button.disabled = false;
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_wp_loft_booking_sync_units', 'wp_loft_booking_sync_units');

/**
 * Display all LOFT units, marking as Occupied if there is
 * an active digital key OR an active tenant lease.
 */
function normalize_label( $label ) {
    if ( preg_match('/LOFTS?\s*-*\s*([0-9]+)/i', $label, $matches) ) {
        return 'LOFT' . $matches[1];
    }
    return strtoupper( preg_replace( '/[^A-Z0-9]/', '', $label ) );
}

function wp_loft_booking_display_units() {
    global $wpdb;
    $units_table     = $wpdb->prefix . 'loft_units';
    $keychains_table = $wpdb->prefix . 'loft_keychains';
    $tenant_table    = $wpdb->prefix . 'loft_tenants';
    $now             = current_time('mysql');

    // 1) Load active digital keys
    $active_keys = $wpdb->get_results($wpdb->prepare(
        "SELECT name, valid_until
           FROM $keychains_table
          WHERE valid_from <= %s AND valid_until >= %s",
        $now, $now
    ), ARRAY_A);

    $keys_map = [];
    foreach ($active_keys as $row) {
        $label = normalize_label($row['name']);
        $keys_map[$label] = $row['valid_until'];
    }

    error_log("🗝️ KEYS MAP: " . print_r($keys_map, true));


    // 2) Load only valid tenants
    $active_tenants = $wpdb->get_results("SELECT unit_label, lease_start, lease_end FROM $tenant_table", ARRAY_A);

    $tenants_map = [];
    foreach ($active_tenants as $row) {
        $label = normalize_label($row['unit_label']);
        if (
            !empty($row['lease_start']) &&
            !empty($row['lease_end']) &&
            strtotime($row['lease_start']) <= time() &&
            strtotime($row['lease_end']) >= time()
        ) {
            if (
                empty($tenants_map[$label]) ||
                strtotime($row['lease_end']) < strtotime($tenants_map[$label])
            ) {
                $tenants_map[$label] = $row['lease_end'];
            }
        }
    }

    error_log("🏠 TENANTS MAP: " . print_r($tenants_map, true));


    // 3) Render the table and update DB statuses
    echo '<table class="widefat fixed striped">
            <thead><tr>
              <th>Unit Name</th><th>Floor</th><th>Access Group</th>
              <th>Tenants</th><th>Max Adults</th><th>Max Children</th>
              <th>Status</th><th>Available Until</th>
            </tr></thead>
            <tbody>';

    $units = $wpdb->get_results("SELECT * FROM $units_table WHERE unit_name LIKE '%LOFT%'");
    foreach ($units as $unit) {
        $label       = normalize_label($unit->unit_name);
        $has_key     = isset($keys_map[$label]);
        $has_tenant  = isset($tenants_map[$label]);
        error_log("🔍 UNIT: {$unit->unit_name} | LABEL: {$label} | HAS_KEY: " . (isset($keys_map[$label]) ? 'YES' : 'NO'));

        $occupied    = $has_key || $has_tenant;

        // pick the right availability date
        if ($has_key) {
            $avail = date('Y-m-d H:i', strtotime($keys_map[$label]));
        } elseif ($has_tenant) {
            $lease_end = $tenants_map[$label];
            $avail     = $lease_end
                ? date('Y-m-d H:i', strtotime($lease_end))
                : 'N/A';
        } else {
            $avail = 'N/A';
        }

        $color = $occupied ? 'red' : 'green';
        $text  = $occupied ? 'Occupied' : 'Available';

        // 📝 Update DB status for this unit
        $wpdb->update(
            $units_table,
            [
                'status'             => $occupied ? 'occupied' : 'available',
                'availability_until' => ($avail !== 'N/A') ? $avail : null,
            ],
            ['id' => $unit->id],
            ['%s', '%s'],
            ['%d']
        );

        echo "<tr>
                <td>{$unit->unit_name}</td>
                <td>N/A</td>
                <td>N/A</td>
                <td>0</td>
                <td>N/A</td>
                <td>N/A</td>
                <td style=\"color:{$color};font-weight:bold;\">{$text}</td>
                <td>{$avail}</td>
              </tr>";
    }

    echo '</tbody></table>';

    error_log("✅ Updated unit statuses during display_units check");
}






function find_first_available_loft_unit($room_type) {
    global $wpdb;
    $type = strtoupper($room_type);
    $units = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}loft_units WHERE status = 'Available' ORDER BY id ASC");

    foreach ($units as $unit) {
        if (stripos($unit->unit_name, "($type)") !== false) {
            error_log("✅ MATCHED UNIT: {$unit->unit_name} (DB ID: {$unit->id}, API ID: {$unit->unit_id_api})");
            return $unit; // includes unit_id_api
        }
    }

    return null;
}


function is_unit_currently_occupied($unit_id) {
    global $wpdb;
    $now = current_time('mysql');

    $keychains_table = $wpdb->prefix . 'loft_keychains';
    $query = $wpdb->prepare("
        SELECT COUNT(*) FROM $keychains_table
        WHERE unit_id = %d AND valid_from <= %s AND valid_until >= %s
    ", $unit_id, $now, $now);

    return $wpdb->get_var($query) > 0;
}


