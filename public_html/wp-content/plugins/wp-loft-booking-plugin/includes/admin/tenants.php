<?php
defined('ABSPATH') || exit;

function tenants_page_function() {
    echo '<div class="wrap"><h1>Manage Tenants</h1>';
    echo '<button id="sync-tenants" class="button button-primary" style="margin-bottom: 20px;">Sync Tenants from ButterflyMX</button>';

    // JavaScript for sync button
    ?>
    <script type="text/javascript">
        document.getElementById('sync-tenants').addEventListener('click', function() {
            var button = this;
            button.disabled = true;
            button.innerText = 'Syncing...';

            jQuery.post(ajaxurl, { action: 'wp_loft_booking_sync_tenants' }, function(response) {
                if (response.success) {
                    alert('Tenant sync completed successfully.');
                    location.reload();
                } else {
                    alert('Tenant sync failed.');
                }
                button.disabled = false;
                button.innerText = 'Sync Tenants from ButterflyMX';
            });
        });
    </script>
    <?php

    // Display tenants from the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'loft_tenants';
    $tenants = $wpdb->get_results("SELECT * FROM $table_name");

    if (!empty($tenants)) {
        echo '<table class="widefat fixed striped"><thead><tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Building Name</th>
                <th>Unit Label</th>
                <th>Floor</th>
              </tr></thead><tbody>';
        
        foreach ($tenants as $tenant) {
            echo '<tr>';
            echo '<td>' . esc_html($tenant->tenant_id) . '</td>';
            echo '<td>' . esc_html($tenant->first_name) . '</td>';
            echo '<td>' . esc_html($tenant->last_name) . '</td>';
            echo '<td>' . esc_html($tenant->email) . '</td>';
            echo '<td>' . esc_html($tenant->building_name) . '</td>';
            echo '<td>' . esc_html($tenant->unit_label) . '</td>';
            echo '<td>' . esc_html($tenant->floor) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No tenants found. Click "Sync Tenants" to load tenants from ButterflyMX.</p>';
    }
}

/**
 * Fetch all tenants (with lease dates) and their virtual keys from ButterflyMX,
 * and save into wp_loft_tenants + wp_loft_keychains tables.
 */
function wp_loft_booking_fetch_and_save_tenants() {
    global $wpdb;
    $tenant_table = $wpdb->prefix . 'loft_tenants';

    // ButterflyMX setup
    $token       = get_option( 'butterflymx_access_token_v4' );
    $environment = get_option( 'butterflymx_environment', 'sandbox' );
    $base_url    = $environment === 'production'
        ? 'https://api.butterflymx.com/v4'
        : 'https://api.na.sandbox.butterflymx.com/v4';

    if ( ! $token ) {
        error_log( '❌ Missing ButterflyMX token.' );
        return new WP_Error( 'wp_loft_booking_missing_token', 'Missing API token.' );
    }

    $synced_tenants = 0;

    // 1) Fetch tenants
    $response = wp_remote_get( "{$base_url}/tenants", [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 60,
    ] );

    if ( is_wp_error( $response ) ) {
        error_log( '❌ HTTP Error (tenants): ' . $response->get_error_message() );
        return new WP_Error( 'wp_loft_booking_tenant_http_error', 'Error fetching tenants.' );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    if ( $code === 401 ) {
        error_log( '🔒 Unauthorized. Check API token.' );
        return new WP_Error( 'wp_loft_booking_tenant_unauthorized', 'Unauthorized.' );
    }

    $data = json_decode( $body, true );
    if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
        error_log( '❌ Invalid tenant response.' );
        return new WP_Error( 'wp_loft_booking_tenant_invalid_response', 'Invalid API response.' );
    }

    foreach ( $data['data'] as $tenant ) {
        $tenant_id     = intval( $tenant['id'] ?? 0 );
        if ( ! $tenant_id ) {
            error_log( '⚠️ Skipping tenant with missing ID.' );
            continue;
        }

        // sanitize fields
        $first_name    = sanitize_text_field( $tenant['first_name'] ?? '' );
        $last_name     = sanitize_text_field( $tenant['last_name'] ?? '' );
        $email         = sanitize_email( $tenant['email'] ?? '' );
        $building_name = sanitize_text_field( $tenant['building_name'] ?? '' );
        $unit_label    = sanitize_text_field( $tenant['unit']['label'] ?? '' );
        $floor         = sanitize_text_field( $tenant['unit']['floor'] ?? '' );

        // Fixed: use created_at and inactive_after
        $lease_start = sanitize_text_field( $tenant['created_at'] ?? '' );
        $lease_end   = sanitize_text_field( $tenant['inactive_after'] ?? '' );
        $lease_end   = empty($lease_end) ? null : $lease_end;

        // Skip if unit label or lease start is missing
        if ( empty( $unit_label ) || empty( $lease_start ) ) {
            error_log( "⚠️ Skipping tenant $tenant_id due to missing lease_start or unit_label" );
            continue;
        }

        // Log for debugging
        error_log( "💾 Saved tenant {$tenant_id} | Lease: {$lease_start} → " . ($lease_end ?? 'NULL') );

        // Save to DB
        $wpdb->replace(
            $tenant_table,
            [
                'tenant_id'     => $tenant_id,
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'email'         => $email,
                'building_name' => $building_name,
                'unit_label'    => $unit_label,
                'floor'         => $floor,
                'lease_start'   => $lease_start,
                'lease_end'     => $lease_end,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', is_null($lease_end) ? 'NULL' : '%s' ]
        );

        if ( ! $wpdb->last_error ) {
            $synced_tenants++;
        }

        if ( $wpdb->last_error ) {
            error_log( '❌ DB Error (tenant): ' . $wpdb->last_error );
            continue;
        }

        // Virtual key details are synced separately via the keychain sync that
        // runs immediately after this tenant sync.  The previous implementation
        // fetched each tenant's virtual keys one HTTP request at a time, which
        // caused very long execution times (and AJAX timeouts) for sites with a
        // healthy number of tenants.  By avoiding the per-tenant requests here
        // we keep the tenant sync lean and let the dedicated keychain sync
        // populate the related tables instead.
    }
    wp_loft_booking_fetch_and_save_visitor_passes();

    return [
        'success'        => true,
        'message'        => '🎉 Tenants synced successfully. Keychains will refresh next.',
        'tenants_synced' => $synced_tenants,
        'keys_synced'    => 0,
    ];
}





function create_butterflymx_tenant($unit_api_id, $email, $first_name = 'Guest', $last_name = 'Booking', $checkin = null) {
    $token = get_option('butterflymx_access_token_v3');
    $environment = get_option('butterflymx_environment', 'sandbox');

    if (!$token) {
        error_log("❌ No v3 token available.");
        return false;
    }

    $url = ($environment === 'production')
        ? 'https://api.butterflymx.com/v3/tenants'
        : 'https://api.na.sandbox.butterflymx.com/v3/tenants';

    $active_at = $checkin ? date('c', strtotime($checkin)) : date('c');

    $payload = [
        'data' => [
            'type' => 'tenants',
            'attributes' => [
                'unit_id'    => (int) $unit_api_id,
                'email'      => $email,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'active_at'  => $active_at,
            ],
            'relationships' => null
        ]
    ];

    // Console debug output
    add_action('wp_footer', function () use ($url, $payload) {
        echo "<script>console.log('📡 Sending POST to: {$url}');</script>";
        echo "<script>console.log('📤 Payload: " . json_encode($payload) . "');</script>";
    });

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token, // ✅ Use Bearer token for v3 JSON:API
            'Content-Type'  => 'application/vnd.api+json',
        ],
        'body' => json_encode($payload),
        'timeout' => 30,
    ]);

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    error_log("📥 HTTP Code: $code");
    error_log("📥 Response Body: $body");

    add_action('wp_footer', function () use ($code, $body) {
        echo "<script>console.log('📥 HTTP Code: {$code}');</script>";
        echo "<script>console.log('📥 Response Body: " . esc_js($body) . "');</script>";
    });

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON decode error: " . json_last_error_msg());
        return false;
    }

    if (isset($data['data']['id'])) {
        error_log("✅ Tenant created with ID: " . $data['data']['id']);
        return $data['data']['id'];
    }

    error_log("❌ Failed to create tenant.");
    return false;
}

function create_tenant_and_virtual_key($unit_api_id, $email, $first_name = 'Guest', $last_name = 'Booking', $checkin = null) {
    // $token = get_option('butterflymx_access_token_v3');
    // $environment = get_option('butterflymx_environment', 'sandbox');
    // $base_url = ($environment === 'production') ? 'https://api.butterflymx.com/v3' : 'https://api.na.sandbox.butterflymx.com/v3';

    // $active_at = $checkin ? date('c', strtotime($checkin)) : date('c');

    // // Step 1: Create Tenant
    // $tenant_payload = [
    //     'data' => [
    //         'type' => 'tenants',
    //         'attributes' => [
    //             'unit_id'   => (int) $unit_api_id,
    //             'email'     => $email,
    //             'first_name'=> $first_name,
    //             'last_name' => $last_name,
    //             'active_at' => $active_at
    //         ]
    //     ]
    // ];

    // $tenant_response = wp_remote_post("$base_url/tenants", [
    //     'headers' => [
    //         'Authorization' => 'Token token=' . $token,
    //         'Content-Type'  => 'application/vnd.api+json',
    //     ],
    //     'body' => json_encode($tenant_payload),
    // ]);

    // $tenant_body = wp_remote_retrieve_body($tenant_response);
    // $tenant_data = json_decode($tenant_body, true);

    $token = get_option('butterflymx_access_token_v3');
    $token4 = get_option('butterflymx_access_token_v4');
    $environment = get_option('butterflymx_environment', 'sandbox');

    if (!$token) {
        error_log("❌ No v3 token available.");
        return false;
    }

    $url = ($environment === 'production')
        ? 'https://api.butterflymx.com/v3/tenants'
        : 'https://api.na.sandbox.butterflymx.com/v3/tenants';

    $url4 = ($environment === 'production')
        ? 'https://api.butterflymx.com/v4/tenants'
        : 'https://api.na.sandbox.butterflymx.com/v4/tenants';

    $active_at = $checkin ? date('c', strtotime($checkin)) : date('c');

    $payload = [
        'data' => [
            'type' => 'tenants',
            'attributes' => [
                'unit_id'    => (int) $unit_api_id,
                'email'      => $email,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'active_at'  => $active_at,
            ],
            'relationships' => null
        ]
    ];

    // Console debug output
    add_action('wp_footer', function () use ($url, $payload) {
        echo "<script>console.log('📡 Sending POST to: {$url}');</script>";
        echo "<script>console.log('📤 Payload: " . json_encode($payload) . "');</script>";
    });

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token, // ✅ Use Bearer token for v3 JSON:API
            'Content-Type'  => 'application/vnd.api+json',
        ],
        'body' => json_encode($payload),
        'timeout' => 30,
    ]);

    $tenant_body = wp_remote_retrieve_response_code($response);
    $tenant_data = wp_remote_retrieve_body($tenant_body);

    error_log("📥 HTTP Code: $tenant_body");
    error_log("📥 Response Body: $tenant_data");
    error_log("📥 Starting tenants");
    
    // Step 2: Find tenant just created (via email match)
    // Step 2: Get user ID from tenants list
    echo "<script>console.log('📥Starting tenants');</script>";
    $list_response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/vnd.api+json'
        ]
    ]);

    $list_body = wp_remote_retrieve_body($list_response);
    $list_data = json_decode($list_body, true);
    echo "<script>console.log('check list of tenants');</script>";
    if (!isset($list_data['data'])) {
        error_log("❌ Failed to list tenants.");
        return false;
    }

    // Step 3: Get user ID from tenants list and email
    echo "<script>console.log('📥Starting tenants');</script>";
    $list_response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/vnd.api+json'
        ]
    ]);

    $list_body = wp_remote_retrieve_body($list_response);
    $list_data = json_decode($list_body, true);
    echo "<script>console.log('check list of tenants');</script>";
    if (!isset($list_data['data'])) {
        error_log("❌ Failed to list tenants.");
        return false;
    }

    // Step 2: Match tenant to user via included[]
    $user_id = null;

    // 1. First, find the tenant with the matching unit_id
    foreach ($list_data['data'] as $tenant) {
        $tenant_unit_id = $tenant['relationships']['unit']['data']['id'] ?? null;
        $tenant_user_id = $tenant['relationships']['user']['data']['id'] ?? null;

        if ((string)$tenant_unit_id === (string)$unit_api_id) {
            // 2. Look inside included[] to find user and match email
            error_log("Matched unit id");
            foreach ($list_data['included'] as $included) {
                if (
                    $included['type'] === 'users' &&
                    $included['id'] === $tenant_user_id &&
                    strtolower($included['attributes']['email']) === strtolower($email)
                ) {
                    $user_id = $included['id'];
                    error_log("Match found");
                    break 2; // Match found, exit both loops
                }
            }
        }
    }

    if (!$user_id) {
        error_log("❌ No matching tenant found with email $email and unit $unit_api_id.");
        return false;
    }

    echo "<script>console.log('📥 User id: {$user_id}');</script>";

    // Step 3: Get Keychain
    $keychain_response = wp_remote_get("$base_url/users/{$user_id}/keychains", [
        'headers' => [
            'Authorization' => 'Token token=' . $token,
            'Accept' => 'application/vnd.api+json'
        ]
    ]);

    $keychain_body = wp_remote_retrieve_body($keychain_response);
    $keychain_data = json_decode($keychain_body, true);

    if (!isset($keychain_data['data'][0]['id'])) {
        error_log("❌ No keychain found for user $user_id.");
        return false;
    }

    $keychain_id = $keychain_data['data'][0]['id'];

    // Step 4: Create Virtual Key
    $virtual_key_payload = [
        'data' => [
            'type' => 'virtual_keys',
            'attributes' => [
                'name'  => 'Virtual Key',
                'email' => $email
            ]
        ]
    ];

    $virtual_key_response = wp_remote_post("$base_url/keychains/{$keychain_id}/virtual_keys", [
        'headers' => [
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/vnd.api+json',
        ],
        'body' => json_encode($virtual_key_payload),
    ]);

    $vk_code = wp_remote_retrieve_response_code($virtual_key_response);
    $vk_body = wp_remote_retrieve_body($virtual_key_response);
    error_log("📥 Virtual Key Response ($vk_code): $vk_body");

    $vk_data = json_decode($vk_body, true);

    if (!isset($vk_data['data']['id'])) {
        error_log("❌ Failed to create virtual key.");
        return false;
    }

    error_log("✅ Success! Virtual key ID: " . $vk_data['data']['id']);
    return true;
}





function is_unit_available_in_butterflymx($unit_id) {
    $token = get_option('butterflymx_access_token_v4');
    $env = get_option('butterflymx_environment', 'sandbox');
    $api_base = ($env === 'production') 
        ? "https://api.butterflymx.com/v4" 
        : "https://api.na.sandbox.butterflymx.com/v4";

    $response = wp_remote_get("{$api_base}/tenants?unit_id={$unit_id}", [
        'headers' => [
            'Authorization' => 'Bearer ' . $token
        ]
    ]);

    if (is_wp_error($response)) {
        error_log("❌ ButterflyMX check failed: " . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return empty($data['data']); // Available if no tenant found
}


function wp_loft_booking_fetch_and_save_visitor_passes() {
    global $wpdb;
    $keychain_table = $wpdb->prefix . 'loft_keychains';

    $token       = get_option( 'butterflymx_access_token_v4' );
    $environment = get_option( 'butterflymx_environment', 'sandbox' );
    $base_url    = $environment === 'production'
        ? 'https://api.butterflymx.com/v4'
        : 'https://api.na.sandbox.butterflymx.com/v4';

    if ( ! $token ) {
        error_log( '❌ Missing ButterflyMX token for visitor pass sync.' );
        return;
    }

    $url = $base_url . '/visitor_passes?include=unit';

    $response = wp_remote_get( $url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 60,
    ] );

    if ( is_wp_error( $response ) ) {
        error_log( '❌ Visitor pass API error: ' . $response->get_error_message() );
        return;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
        error_log( '⚠️ No visitor passes found.' );
        return;
    }

    foreach ( $data['data'] as $pass ) {
        $pass_id     = intval( $pass['id'] ?? 0 );
        $start_time  = sanitize_text_field( $pass['start_time'] ?? '' );
        $end_time    = sanitize_text_field( $pass['expiration'] ?? '' );
        $unit_label  = sanitize_text_field( $pass['attributes']['unit_label'] ?? '' );

        if ( empty($start_time) || empty($end_time) || empty($unit_label) ) {
            continue;
        }

        $label = normalize_label( $unit_label );

        $wpdb->replace(
            $keychain_table,
            [
                'tenant_id'   => 0, // 0 = visitor
                'key_id'      => 900000 + $pass_id, // avoid conflicts
                'valid_from'  => $start_time,
                'valid_until' => $end_time,
                'name'        => $label,
                'unit_id'     => 0
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%d' ]
        );

        if ( $wpdb->last_error ) {
            error_log( "❌ Visitor pass DB error for #$pass_id: " . $wpdb->last_error );
        }
    }

    error_log( '✅ Visitor passes synced successfully.' );
}



