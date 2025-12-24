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
add_action('wp_ajax_wplb_sync_units', 'wp_loft_booking_sync_units');

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
    echo wp_nonce_field('wplb_generate_virtual_key', 'wplb_generate_virtual_key_nonce', true, false);
    echo '<div id="wplb-generate-key-feedback" style="margin:15px 0;"></div>';

    $units = $wpdb->get_results(
        "SELECT u.*, b.building_id AS branch_building_id
           FROM $units_table u
      LEFT JOIN {$wpdb->prefix}loft_branches b ON u.branch_id = b.id
          WHERE u.unit_name LIKE '%LOFT%'
       ORDER BY u.unit_name ASC"
    );

    $keychains_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}loft_keychains" );

    if ( empty( $units ) ) {
        echo '<div class="notice notice-error" style="border-left-color:#b91c1c;background:#fef2f2;">';
        echo '<p style="font-size:16px;color:#7f1d1d;font-weight:600;">🚨 Critical: No lofts are currently loaded.</p>';
        echo '<p style="margin:6px 0;color:#7f1d1d;">This should never happen. Please run an immediate sync and verify the ButterflyMX connection.</p>';

        if ( $keychains_total > 0 ) {
            echo '<p style="margin:0;color:#7f1d1d;">Detected ' . esc_html( $keychains_total ) . ' keychains but zero loft units. Data may be out of sync.</p>';
        }

        echo '</div>';
    }

    echo '<table class="widefat fixed striped">
            <thead><tr>
              <th>Unit Name</th><th>ButterflyMX Unit ID</th><th>Building ID</th>
              <th>Status</th><th>Available Until</th><th>Actions</th>
            </tr></thead>
            <tbody>';
    foreach ($units as $unit) {
        $status      = strtolower($unit->status);
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

        // 📝 Update DB status for this unit, preserving 'unavailable'
        if ($status !== 'unavailable') {
            $status = $occupied ? 'occupied' : 'available';
            $wpdb->update(
                $units_table,
                [
                    'status'             => $status,
                    'availability_until' => ($avail !== 'N/A') ? $avail : null,
                ],
                ['id' => $unit->id],
                ['%s', '%s'],
                ['%d']
            );
        } else {
            $wpdb->update(
                $units_table,
                [
                    'availability_until' => ($avail !== 'N/A') ? $avail : null,
                ],
                ['id' => $unit->id],
                ['%s'],
                ['%d']
            );
        }

        $text  = ucfirst($status);
        $color = ($status === 'available') ? 'green' : 'red';

        $button_label   = esc_html__('Generate Virtual Key', 'wp-loft-booking');
        $button_disabled = $status === 'available' ? '' : ' disabled="disabled"';
        $button_html    = sprintf(
            '<button type="button" class="button button-secondary wplb-generate-key" data-unit-id="%d" data-unit-name="%s"%s>%s</button>',
            (int) $unit->id,
            esc_attr($unit->unit_name),
            $button_disabled,
            $button_label
        );

        $unit_api_id          = (int) $unit->unit_id_api;
        $branch_building_id   = isset($unit->branch_building_id) ? trim((string) $unit->branch_building_id) : '';
        $unit_id_display      = $unit_api_id > 0 ? (string) $unit_api_id : '—';
        $building_id_display  = $branch_building_id !== '' ? $branch_building_id : '—';
        $availability_display = ($avail === 'N/A' || empty($avail)) ? '—' : $avail;

        echo '<tr>';
        echo '<td>' . esc_html($unit->unit_name) . '</td>';
        echo '<td>' . esc_html($unit_id_display) . '</td>';
        echo '<td>' . esc_html($building_id_display) . '</td>';
        echo '<td style="color:' . esc_attr($color) . ';font-weight:bold;">' . esc_html($text) . '</td>';
        echo '<td>' . esc_html($availability_display) . '</td>';
        echo '<td>' . wp_kses_post($button_html) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    error_log("✅ Updated unit statuses during display_units check");

    ?>
    <script type="text/javascript">
        (function($) {
            const nonceField = document.getElementById('wplb_generate_virtual_key_nonce');
            const nonce = nonceField ? nonceField.value : '';
            const feedback = document.getElementById('wplb-generate-key-feedback');

            function showMessage(message, type) {
                if (!feedback) {
                    return;
                }

                let color = '#1f2937';

                if (type === 'success') {
                    color = '#047857';
                } else if (type === 'error') {
                    color = '#b91c1c';
                } else if (type === 'info') {
                    color = '#2563eb';
                }

                feedback.innerHTML = '<span style="font-weight:600;color:' + color + ';">' + message + '</span>';
            }

            document.querySelectorAll('.wplb-generate-key').forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    if (button.disabled) {
                        return;
                    }

                    const unitId = button.getAttribute('data-unit-id');
                    const unitName = button.getAttribute('data-unit-name');

                    const guestNamePrompt = window.prompt('Nom du client / Guest name');
                    if (guestNamePrompt === null || guestNamePrompt.trim() === '') {
                        showMessage('Le nom du client est requis. / Guest name is required.', 'error');
                        return;
                    }

                    const guestEmailPrompt = window.prompt('Courriel du client / Guest email');
                    if (guestEmailPrompt === null || guestEmailPrompt.trim() === '') {
                        showMessage('Le courriel du client est requis. / Guest email is required.', 'error');
                        return;
                    }

                    const guestPhonePrompt = window.prompt('Téléphone du client / Guest phone (optionnel)');
                    if (guestPhonePrompt === null) {
                        return;
                    }

                    const checkinPrompt = window.prompt('Date d\'arrivée (YYYY-MM-DD) / Check-in date');
                    if (checkinPrompt === null || checkinPrompt.trim() === '') {
                        showMessage('La date d\'arrivée est requise. / Check-in date is required.', 'error');
                        return;
                    }

                    const checkoutPrompt = window.prompt('Date de départ (YYYY-MM-DD) / Check-out date');
                    if (checkoutPrompt === null || checkoutPrompt.trim() === '') {
                        showMessage('La date de départ est requise. / Check-out date is required.', 'error');
                        return;
                    }

                    const payload = {
                        action: 'wplb_admin_generate_virtual_key',
                        nonce: nonce,
                        unit_id: unitId,
                        guest_name: guestNamePrompt.trim(),
                        guest_email: guestEmailPrompt.trim(),
                        guest_phone: guestPhonePrompt.trim(),
                        checkin_date: checkinPrompt.trim(),
                        checkout_date: checkoutPrompt.trim()
                    };

                    button.disabled = true;
                    showMessage('Création de la clé virtuelle pour ' + unitName + '… / Generating virtual key…', 'info');

                    $.post(ajaxurl, payload).done(function(response) {
                        if (response && response.success) {
                            const message = response.data && response.data.message ? response.data.message : 'Clé virtuelle créée. / Virtual key created.';
                            showMessage(message, 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            const errorMessage = response && response.data && response.data.message ? response.data.message : 'Une erreur est survenue. / An error occurred.';
                            showMessage(errorMessage, 'error');
                            button.disabled = false;
                        }
                    }).fail(function() {
                        showMessage('Erreur de communication avec le serveur. / Server communication error.', 'error');
                        button.disabled = false;
                    });
                });
            });
        })(jQuery);
    </script>
    <?php
}






function find_first_available_loft_unit($room_type) {
    global $wpdb;

    $units_table = $wpdb->prefix . 'loft_units';
    $requested   = wp_loft_booking_detect_room_type($room_type);

    $candidates = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$units_table} WHERE LOWER(status) = %s ORDER BY id ASC",
            'available'
        )
    );

    $selected = null;

    foreach ($candidates as $candidate) {
        $unit_type = wp_loft_booking_detect_room_type($candidate->unit_name);

        if ($requested && $unit_type && $unit_type !== $requested) {
            continue;
        }

        $selected = $candidate;
        break;
    }

    if (!$selected && !empty($candidates)) {
        // Fall back to the first available unit when the request can't be typed.
        $selected = $candidates[0];
    }

    if ($selected) {
        $selected_type = wp_loft_booking_detect_room_type($selected->unit_name);
        $wpdb->update(
            $units_table,
            ['status' => 'Reserved'],
            ['id' => $selected->id],
            ['%s'],
            ['%d']
        );

        $type_label = $requested ?: ($selected_type ?? '');
        error_log(
            sprintf(
                '✅ MATCHED UNIT: %s (DB ID: %d, API ID: %s) [requested type: %s]',
                $selected->unit_name,
                $selected->id,
                $selected->unit_id_api ?? 'n/a',
                $type_label ?: 'any'
            )
        );
    }

    return $selected;
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


add_action('wp_ajax_wplb_admin_generate_virtual_key', 'wplb_admin_generate_virtual_key');

function wplb_admin_generate_virtual_key() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized request.', 'wp-loft-booking')], 403);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

    if (!wp_verify_nonce($nonce, 'wplb_generate_virtual_key')) {
        wp_send_json_error(['message' => __('Invalid request. Please refresh the page and try again.', 'wp-loft-booking')], 400);
    }

    $unit_id = isset($_POST['unit_id']) ? (int) $_POST['unit_id'] : 0;

    if ($unit_id <= 0) {
        wp_send_json_error(['message' => __('Invalid loft selection.', 'wp-loft-booking')], 400);
    }

    $guest_name  = isset($_POST['guest_name']) ? sanitize_text_field(wp_unslash($_POST['guest_name'])) : '';
    $guest_email = isset($_POST['guest_email']) ? sanitize_email(wp_unslash($_POST['guest_email'])) : '';
    $guest_phone = isset($_POST['guest_phone']) ? sanitize_text_field(wp_unslash($_POST['guest_phone'])) : '';
    $checkin     = isset($_POST['checkin_date']) ? sanitize_text_field(wp_unslash($_POST['checkin_date'])) : '';
    $checkout    = isset($_POST['checkout_date']) ? sanitize_text_field(wp_unslash($_POST['checkout_date'])) : '';

    if ('' === $guest_name || '' === $guest_email || '' === $checkin || '' === $checkout) {
        wp_send_json_error(['message' => __('Guest name, email, and dates are required.', 'wp-loft-booking')], 400);
    }

    if (!is_email($guest_email)) {
        wp_send_json_error(['message' => __('The guest email address is not valid.', 'wp-loft-booking')], 400);
    }

    $timezone_string = get_option('timezone_string');
    if (empty($timezone_string)) {
        $timezone_string = 'America/Toronto';
    }

    $site_timezone = new DateTimeZone($timezone_string);
    $utc_timezone  = new DateTimeZone('UTC');

    $checkin_dt  = DateTime::createFromFormat('Y-m-d', $checkin, $site_timezone);
    $checkout_dt = DateTime::createFromFormat('Y-m-d', $checkout, $site_timezone);

    if (!$checkin_dt || $checkin_dt->format('Y-m-d') !== $checkin) {
        wp_send_json_error(['message' => __('The check-in date format must be YYYY-MM-DD.', 'wp-loft-booking')], 400);
    }

    if (!$checkout_dt || $checkout_dt->format('Y-m-d') !== $checkout) {
        wp_send_json_error(['message' => __('The check-out date format must be YYYY-MM-DD.', 'wp-loft-booking')], 400);
    }

    if ($checkout_dt <= $checkin_dt) {
        wp_send_json_error(['message' => __('The check-out date must be after the check-in date.', 'wp-loft-booking')], 400);
    }

    global $wpdb;

    $units_table = $wpdb->prefix . 'loft_units';
    $unit        = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$units_table} WHERE id = %d", $unit_id));

    if (!$unit) {
        wp_send_json_error(['message' => __('Selected loft could not be found.', 'wp-loft-booking')], 404);
    }

    if ('available' !== strtolower($unit->status)) {
        wp_send_json_error(['message' => __('This loft is not currently available for key generation.', 'wp-loft-booking')], 400);
    }

    $checkin_local  = clone $checkin_dt;
    $checkout_local = clone $checkout_dt;

    $checkin_local->setTime(15, 0, 0);
    $checkout_local->setTime(11, 0, 0);

    $checkin_utc  = clone $checkin_local;
    $checkout_utc = clone $checkout_local;

    $checkin_utc->setTimezone($utc_timezone);
    $checkout_utc->setTimezone($utc_timezone);

    $starts_at = $checkin_utc->format('Y-m-d\TH:i:s\Z');
    $ends_at   = $checkout_utc->format('Y-m-d\TH:i:s\Z');

    $result = wp_loft_booking_generate_virtual_key(
        $unit_id,
        $guest_name,
        $guest_email,
        $guest_phone,
        $checkin_dt->format('Y-m-d'),
        $checkout_dt->format('Y-m-d')
    );

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 500);
    }

    $availability_until = $checkout_local->format('Y-m-d H:i:s');

    $keychain_id = isset($result['keychain_id']) ? (int) $result['keychain_id'] : 0;
    $primary_virtual_key_id = $result['virtual_key_ids'][0] ?? null;

    if ($keychain_id > 0) {
        wp_loft_booking_save_keychain_data(
            null,
            $unit_id,
            $keychain_id,
            $primary_virtual_key_id,
            $starts_at,
            $ends_at
        );

        wp_loft_booking_record_virtual_key_log(
            null,
            $unit_id,
            $keychain_id,
            $result['virtual_key_ids'] ?? [],
            $starts_at,
            $ends_at
        );
    }

    $wpdb->update(
        $units_table,
        [
            'status'             => 'occupied',
            'availability_until' => $availability_until,
        ],
        ['id' => $unit_id],
        ['%s', '%s'],
        ['%d']
    );

    $booking_payload = [
        'room_id'        => $unit_id,
        'name'           => $guest_name,
        'surname'        => '',
        'email'          => $guest_email,
        'phone'          => $guest_phone,
        'country'        => '',
        'date_from'      => $checkin_dt->format('Y-m-d'),
        'date_to'        => $checkout_dt->format('Y-m-d'),
        'room_name'      => $unit->unit_name,
        'total'          => '',
        'extra_services' => '',
        'guests'         => '',
    ];

    wp_loft_booking_send_confirmation_email($booking_payload, $result, true);

    $message = sprintf(
        /* translators: %s: loft/unit name */
        __('Virtual key created for %s. A confirmation email has been sent to the guest.', 'wp-loft-booking'),
        $unit->unit_name
    );

    wp_send_json_success(['message' => $message]);
}
