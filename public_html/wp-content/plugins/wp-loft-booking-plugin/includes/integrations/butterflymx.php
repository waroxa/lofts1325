<?php
defined('ABSPATH') || exit;

/**
 * Resolve the ButterflyMX v4 base URL for the given environment.
 *
 * @param string $environment 'production' (default) or 'sandbox'.
 * @return string Base API endpoint including /v4.
 */
function wp_loft_booking_get_butterflymx_base_url( $environment = 'production' ) {
    return ( 'production' === $environment )
        ? 'https://api.butterflymx.com/v4'
        : 'https://api.na.sandbox.butterflymx.com/v4';
}

/**
 * Determine the active ButterflyMX environment.
 *
 * Defaults to production unless the sandbox value is explicitly stored.
 *
 * @return string 'production' or 'sandbox'.
 */
function wp_loft_booking_get_butterflymx_environment() {
    $environment = get_option( 'butterflymx_environment' );

    return ( 'sandbox' === $environment ) ? 'sandbox' : 'production';
}

function wp_loft_booking_get_authorization_url($version) {
    $client_id   = get_option('butterflymx_client_id');
    $environment = wp_loft_booking_get_butterflymx_environment();
    $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob'; // Static redirect URI for out-of-band OAuth flow
 

    // Choose URL based on environment
    $authorize_url = ($environment === 'production')
        ? "https://accounts.butterflymx.com/oauth/authorize"
        : "https://accountssandbox.butterflymx.com/oauth/authorize";

    return add_query_arg(array(
        'client_id' => $client_id,
        'response_type' => 'code',
        'redirect_uri' => $redirect_uri,
    ), $authorize_url);
}


// Handle authorization code submission and exchange for v3 token
if (isset($_POST['submit_code_v3'])) {
    $authorization_code_v3 = sanitize_text_field($_POST['authorization_code_v3']);
    $token_v3 = wp_loft_booking_exchange_code_for_token($authorization_code_v3, 'v3');
    if ($token_v3) {
        update_option('butterflymx_token_v3', $token_v3);
    }
}

// Handle authorization code submission and exchange for v4 token
if (isset($_POST['submit_code_v4'])) {
    $authorization_code_v4 = sanitize_text_field($_POST['authorization_code_v4']);
    $token_v4 = wp_loft_booking_exchange_code_for_token($authorization_code_v4, 'v4');
    if ($token_v4) {
        update_option('butterflymx_token_v4', $token_v4);
    }
}

function wp_loft_booking_exchange_code_for_token($authorization_code, $version) {
    $client_id = get_option('butterflymx_client_id');
    $client_secret = get_option('butterflymx_client_secret');
    $environment = wp_loft_booking_get_butterflymx_environment();
    $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';

    // Check for required credentials
    if (!$client_id || !$client_secret) {
        error_log("Error: Missing ButterflyMX Client ID or Secret.");
        return false;
    }

    // Set the token URL based on environment
    $token_url = ($environment === 'production') 
        ? "https://accounts.butterflymx.com/oauth/token"
        : "https://accountssandbox.butterflymx.com/oauth/token";

    // Prepare POST fields
    $post_fields = array(
        'grant_type'    => 'authorization_code',
        'code'          => $authorization_code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
    );

    // Log the request
    error_log("Sending token exchange request for version $version: " . print_r($post_fields, true));

    // Send the request
    $response = wp_remote_post($token_url, array(
        'body'      => $post_fields,
        'timeout'   => 30,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        error_log("Error: Failed to contact API for version $version: " . $response->get_error_message());
        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Log the response
    error_log("Token exchange HTTP status for version $version: $status_code");
    error_log("Token exchange response for version $version: " . print_r($data, true));

    // Debug output
    echo "<pre>ButterflyMX $version API Response (Status $status_code):\n";
    print_r($data);
    echo "</pre>";

    if (isset($data['error'])) {
        error_log("Error: API returned an error for version $version: " . $data['error_description']);
        return false;
    }

    if (isset($data['access_token'])) {
        update_option("butterflymx_access_token_$version", $data['access_token']);
    } else {
        error_log("Error: No new access token received for version $version.");
        return false;
    }

    if (isset($data['refresh_token'])) {
        update_option("butterflymx_refresh_token_$version", $data['refresh_token']);
    } else {
        error_log("Info: No refresh token found for version $version.");
    }

    return true;
}


function wp_loft_booking_get_buildings() {
    $token_v4 = get_option('butterflymx_access_token_v4'); // Use access_token directly
    $environment = wp_loft_booking_get_butterflymx_environment();
    
    error_log("Using token_v4: " . $token_v4);
    
    $buildings_url = wp_loft_booking_get_butterflymx_base_url( $environment ) . '/buildings';

    $response = wp_remote_get($buildings_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token_v4,
            'Content-Type' => 'application/json'
        )
    ));

    if (is_wp_error($response)) {
        $error_message = 'Failed to retrieve buildings: ' . $response->get_error_message();
        error_log($error_message);
        return $error_message;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    error_log("Buildings API response (status: $status_code): " . print_r($data, true));

    if (!empty($data['data'])) {
        return $data['data'];
    } else {
        return 'No buildings found or failed to retrieve data.';
    }
}

function wp_loft_booking_refresh_token($version) {
    $client_id = get_option('butterflymx_client_id');
    $client_secret = get_option('butterflymx_client_secret');
    $environment = wp_loft_booking_get_butterflymx_environment();

    $token_url = ($environment === 'production') 
        ? "https://accounts.butterflymx.com/oauth/token"
        : "https://accountssandbox.butterflymx.com/oauth/token";

    $response = wp_remote_post($token_url, array(
        'method' => 'POST',
        'body' => array(
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ),
    ));

    if (is_wp_error($response)) {
        error_log('Error refreshing token: ' . $response->get_error_message());
        return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['access_token'])) {
        error_log('Token refresh failed: Invalid response from API');
        return false;
    }

    $expires_in = $data['expires_in'] ?? 3600;
    $expires_at = time() + $expires_in;

    update_option("butterflymx_token_{$version}", $data['access_token']);
    update_option("butterflymx_token_{$version}_expires", $expires_at);

    error_log("[ButterflyMX] New $version token saved. Expires at: " . date('Y-m-d H:i:s', $expires_at));
    return $data['access_token'];
}

// Function to get ButterflyMX access token
function get_butterflymx_access_token($version = 'v3') {
    $clientId = get_option('butterflymx_client_id');
    $clientSecret = get_option('butterflymx_client_secret');
    $environment = wp_loft_booking_get_butterflymx_environment();

    if ($version === 'v3') {
        $tokenEndpoint = 'https://' . ($environment === 'production' ? '' : 'sandbox.') . 'butterflymx.com/oauth/token';
        $token = get_option('butterflymx_token_v3');
        $expires = get_option('butterflymx_token_v3_expires');
    } else {
        $tokenEndpoint = 'https://' . ($environment === 'production' ? '' : 'sandbox.') . 'butterflymx.com/oauth/token';
        $token = get_option('butterflymx_token_v4');
        $expires = get_option('butterflymx_token_v4_expires');
    }

    if (empty($clientId) || empty($clientSecret)) {
        error_log('Error: Missing ButterflyMX Client ID or Secret.');
        return $token;
    }

    if (empty($token) || $expires < time()) {
        $http_response = wp_remote_post($tokenEndpoint, [
            'method'  => 'POST',
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'    => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ],
        ]);

        if (is_wp_error($http_response)) {
            error_log('Error: Failed to contact ButterflyMX OAuth endpoint: ' . $http_response->get_error_message());
            return $token;
        }

        $status_code = wp_remote_retrieve_response_code($http_response);
        $body        = wp_remote_retrieve_body($http_response);
        $response    = json_decode($body, true);

        if ($status_code !== 200 || !is_array($response)) {
            error_log('Error: Unexpected ButterflyMX OAuth response. Status: ' . $status_code . ' Body: ' . $body);
            return $token;
        }

        if (isset($response['access_token'])) {
            $expires_in = isset($response['expires_in']) ? max(60, (int) $response['expires_in']) : 3600;

            if ($version === 'v3') {
                update_option('butterflymx_token_v3', $response['access_token']);
                update_option('butterflymx_token_v3_expires', time() + $expires_in);
            } else {
                update_option('butterflymx_token_v4', $response['access_token']);
                update_option('butterflymx_token_v4_expires', time() + $expires_in);
            }
            return $response['access_token'];
        }

        error_log('Error: ButterflyMX OAuth response missing access_token. Body: ' . $body);
        return $token;
    }

    return $token;
}

// Function to check room availability
function is_room_available($roomId) {
    $accessToken = get_butterflymx_access_token();
    $environment = wp_loft_booking_get_butterflymx_environment();
    $buildingId = get_option('butterflymx_building_id');

    $unitsEndpoint = 'https://' . ($environment === 'production' ? '' : 'sandbox.') . 'butterflymx.com/v3/buildings/' . $buildingId . '/units';
    $headers = [
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/vnd.api+json',
    ];

    $response = json_decode(wp_remote_get($unitsEndpoint, [
        'method' => 'GET',
        'headers' => $headers,
    ]), true);

    // Assuming $response['data'] contains a list of units
    foreach ($response['data'] as $unit) {
        if ($unit['id'] === $roomId && $unit['status'] === 'available') {
            return true;
        }
    }

    return false;
}

function wp_loft_booking_refresh_code_token($version) {
    $client_id = get_option('butterflymx_client_id');
    $client_secret = get_option('butterflymx_client_secret');
    $refresh_token = get_option("butterflymx_refresh_token_{$version}");
    $environment = wp_loft_booking_get_butterflymx_environment();

    if (!$client_id || !$client_secret) {
        error_log("Error: Missing ButterflyMX Client ID or Secret for v{$version}.");
        return false;
    }

    if (!$refresh_token) {
        error_log("Error: No refresh token found for v{$version}; cannot refresh.");
        return false;
    }

    // Set token URL based on environment
    $token_url = ($environment === 'production') 
        ? "https://accounts.butterflymx.com/oauth/token"
        : "https://accountssandbox.butterflymx.com/oauth/token";

    $post_fields = [
        'grant_type'    => 'refresh_token',
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token,
    ];

    error_log("Sending refresh token request for v{$version}: " . print_r($post_fields, true));

    $response = wp_remote_post($token_url, [
        'body'      => $post_fields,
        'timeout'   => 30,
        'sslverify' => true,
    ]);

    if (is_wp_error($response)) {
        error_log("Error: Failed to contact ButterflyMX API for v{$version}: " . $response->get_error_message());
        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    error_log("ButterflyMX v{$version} API Response (Status $status_code): " . print_r($data, true));

    if ($status_code !== 200) {
        error_log("Error: API returned non-200 status for v{$version}: $status_code");
        return false;
    }

    if (isset($data['error'])) {
        error_log("Error: API returned an error for v{$version}: " . $data['error_description']);
        return false;
    }

    if (isset($data['access_token'])) {
        update_option("butterflymx_access_token_{$version}", $data['access_token']);
        update_option("butterflymx_token_{$version}_expires", time() + ($data['expires_in'] ?? 86400));
    } else {
        error_log("Error: No new access token received for v{$version}.");
        return false;
    }

    if (isset($data['refresh_token'])) {
        update_option("butterflymx_refresh_token_{$version}", $data['refresh_token']);
    } else {
        error_log("Info: No refresh token returned for v{$version}.");
    }

    return true;
}

function wp_loft_booking_get_access_group_id($loft_name) {
    $token       = get_option('butterflymx_access_token_v4');
    $environment = wp_loft_booking_get_butterflymx_environment();
    $base_url    = wp_loft_booking_get_butterflymx_base_url( $environment );

    if (!$token) {
        error_log('❌ Missing ButterflyMX token.');
        return false;
    }

    $url      = $base_url . '/access_groups?q[name_cont]=' . rawurlencode($loft_name);
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        error_log('❌ Access group lookup failed: ' . $response->get_error_message());
        return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['data'][0]['id'])) {
        return intval($data['data'][0]['id']);
    }

    return false;
}

/**
 * Fetch a ButterflyMX unit profile including device and access point ids.
 *
 * @param int    $unit_id     ButterflyMX unit identifier.
 * @param string $environment Environment slug (production|sandbox).
 *
 * @return array|WP_Error Array with keys building_id, access_point_ids, device_ids.
 */
function wp_loft_booking_fetch_unit_profile( $unit_id, $environment = 'production' ) {
    $unit_id = (int) $unit_id;

    if ( $unit_id <= 0 ) {
        return new WP_Error( 'invalid_unit_id', 'Invalid ButterflyMX unit id.' );
    }

    $token = get_butterflymx_access_token( 'v4' );

    if ( empty( $token ) ) {
        return new WP_Error( 'no_token', 'ButterflyMX access token missing.' );
    }

    $base_url = wp_loft_booking_get_butterflymx_base_url( $environment );

    $response = wp_remote_get(
        $base_url . '/units/' . $unit_id,
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'timeout' => 20,
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'http_request_failed', $response->get_error_message() );
    }

    $status   = wp_remote_retrieve_response_code( $response );
    $raw_body = wp_remote_retrieve_body( $response );
    $body     = json_decode( $raw_body, true );

    if ( $status >= 300 ) {
        $message = '';

        if ( isset( $body['message'] ) && '' !== trim( $body['message'] ) ) {
            $message = trim( $body['message'] );
        } elseif ( isset( $body['errors'][0]['detail'] ) && '' !== trim( $body['errors'][0]['detail'] ) ) {
            $message = trim( $body['errors'][0]['detail'] );
        }

        if ( '' === $message ) {
            $message = 'ButterflyMX API error.';
        }

        return new WP_Error(
            'http_error',
            $message,
            array(
                'status' => $status,
                'body'   => is_null( $body ) ? $raw_body : $body,
            )
        );
    }

    $data = is_array( $body ) ? $body : array();

    $unit = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();

    if ( empty( $unit ) ) {
        return new WP_Error( 'unit_not_found', 'ButterflyMX unit payload was empty.' );
    }

    $building_id       = (int) ( $unit['building_id'] ?? 0 );
    $access_point_ids  = array();
    $device_ids        = array();

    foreach ( (array) ( $unit['access_point_ids'] ?? array() ) as $id ) {
        $id = (int) $id;
        if ( $id > 0 ) {
            $access_point_ids[] = $id;
        }
    }

    foreach ( (array) ( $unit['device_ids'] ?? array() ) as $id ) {
        $id = (int) $id;
        if ( $id > 0 ) {
            $device_ids[] = $id;
        }
    }

    return array(
        'building_id'      => $building_id,
        'access_point_ids' => array_values( array_unique( $access_point_ids ) ),
        'device_ids'       => array_values( array_unique( $device_ids ) ),
        'raw'              => $unit,
    );
}

/**
 * Fetch access point identifiers for a ButterflyMX building.
 *
 * @param int    $building_id Building identifier.
 * @param string $environment Environment slug (production|sandbox).
 *
 * @return int[]|WP_Error Array of access point ids or WP_Error on failure.
 */
function wp_loft_booking_fetch_building_access_points( $building_id, $environment = 'production' ) {
    $building_id = (int) $building_id;

    if ( $building_id <= 0 ) {
        return new WP_Error( 'invalid_building_id', 'Invalid ButterflyMX building id.' );
    }

    $token = get_butterflymx_access_token( 'v4' );

    if ( empty( $token ) ) {
        return new WP_Error( 'no_token', 'ButterflyMX access token missing.' );
    }

    $base_url = wp_loft_booking_get_butterflymx_base_url( $environment );
    $ap_ids   = array();
    $page     = 1;
    $url      = add_query_arg(
        array(
            'q[building_id_eq]' => $building_id,
            'per_page'          => 100,
            'page'              => $page,
        ),
        $base_url . '/access_points'
    );

    $safety_counter = 0;

    while ( $url ) {
        $safety_counter++;

        if ( $safety_counter > 50 ) {
            break; // Prevent infinite loops just in case pagination metadata is missing.
        }

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ),
                'timeout' => 20,
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'http_request_failed', $response->get_error_message() );
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body   = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status >= 300 ) {
            $message = '';

            if ( isset( $body['message'] ) && '' !== trim( $body['message'] ) ) {
                $message = trim( $body['message'] );
            } elseif ( isset( $body['errors'][0]['detail'] ) && '' !== trim( $body['errors'][0]['detail'] ) ) {
                $message = trim( $body['errors'][0]['detail'] );
            }

            if ( '' === $message ) {
                $message = 'ButterflyMX API error.';
            }

            return new WP_Error(
                'http_error',
                $message,
                array(
                    'status' => $status,
                    'body'   => $body,
                )
            );
        }

        foreach ( $body['data'] ?? array() as $access_point ) {
            if ( isset( $access_point['id'] ) ) {
                $ap_ids[] = (int) $access_point['id'];
            }
        }

        $next_url = '';

        if ( ! empty( $body['links']['next'] ) ) {
            $next_url = $body['links']['next'];
        } elseif ( isset( $body['meta']['current_page'], $body['meta']['total_pages'] ) ) {
            $current_page = (int) $body['meta']['current_page'];
            $total_pages  = (int) $body['meta']['total_pages'];

            if ( $current_page < $total_pages ) {
                $next_url = add_query_arg(
                    array(
                        'q[building_id_eq]' => $building_id,
                        'per_page'          => 100,
                        'page'              => $current_page + 1,
                    ),
                    $base_url . '/access_points'
                );
            }
        } elseif ( count( $body['data'] ?? array() ) >= 100 ) {
            $page++;
            $next_url = add_query_arg(
                array(
                    'q[building_id_eq]' => $building_id,
                    'per_page'          => 100,
                    'page'              => $page,
                ),
                $base_url . '/access_points'
            );
        }

        if ( '' === $next_url ) {
            break;
        }

        if ( 0 === strpos( $next_url, '/' ) ) {
            $next_url = $base_url . $next_url;
        }

        $url = $next_url;
    }

    $ap_ids = array_values( array_unique( $ap_ids ) );

    if ( empty( $ap_ids ) ) {
        return new WP_Error( 'no_access_points', 'No access points discovered for building.' );
    }

    return $ap_ids;
}

/**
 * Determine shared access points for a unit by copying from a template unit's
 * access groups or falling back to all building-level access points.
 *
 * @param int         $building_id      Building id.
 * @param int|null    $template_unit_id Optional unit to copy from.
 * @param string      $environment      'production' or 'sandbox'.
 * @return int[]|WP_Error              Array of access_point_ids or WP_Error.
 */
function wp_loft_booking_get_shared_access_points(
    $building_id,
    $template_unit_id = null,
    $environment = 'production'
) {
    $token    = get_butterflymx_access_token( 'v4' );
    $base_url = wp_loft_booking_get_butterflymx_base_url( $environment );

    if ( empty( $token ) ) {
        return new WP_Error( 'no_token', 'ButterflyMX access token missing.' );
    }

    $ap_ids = array();

    if ( $template_unit_id ) {
        $resp = wp_remote_get(
            $base_url . '/access_groups?per_page=100',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 20,
            )
        );

        if ( ! is_wp_error( $resp ) ) {
            $groups = json_decode( wp_remote_retrieve_body( $resp ), true );
            foreach ( $groups['data'] ?? array() as $group ) {
                if ( ! empty( $group['units_ids'] ) && in_array( (int) $template_unit_id, $group['units_ids'], true ) ) {
                    $g_resp = wp_remote_get(
                        $base_url . '/access_groups/' . (int) $group['id'],
                        array(
                            'headers' => array(
                                'Authorization' => 'Bearer ' . $token,
                                'Content-Type'  => 'application/json',
                            ),
                            'timeout' => 20,
                        )
                    );

                    if ( ! is_wp_error( $g_resp ) ) {
                        $g_data = json_decode( wp_remote_retrieve_body( $g_resp ), true );
                        foreach ( $g_data['data']['access_point_ids'] ?? array() as $id ) {
                            $ap_ids[] = (int) $id;
                        }
                    }
                }
            }
        }
    }

    if ( empty( $ap_ids ) ) {
        $ap_ids = wp_loft_booking_fetch_building_access_points( $building_id, $environment );

        if ( is_wp_error( $ap_ids ) ) {
            return $ap_ids;
        }
    }

    return array_values( array_unique( $ap_ids ) );
}

/**
 * Creates a ButterflyMX visitor pass (keychain + virtual key) by default in
 * production, copying shared access points from peer lofts.
 *
 * @param int         $building_id      Building id.
 * @param int         $target_unit_id   Unit id for the new loft.
 * @param string       $starts_at_utc    UTC ISO8601 start time (with Z).
 * @param string       $ends_at_utc      UTC ISO8601 end time (with Z).
 * @param array|string $recipients       Email/phone recipients for notifications.
 * @param int|null     $template_unit_id  Optional unit id to copy APs from.
 * @param string       $environment       'production' or 'sandbox'.
 * @param int[]        $access_point_ids  Optional preselected access point ids.
 * @param int[]        $device_ids        Optional device ids to associate with the keychain.
 *
 * @return array|WP_Error On success: ['keychain_id'=>int,'virtual_key_ids'=>int[],'access_point_ids'=>int[]].
 */
if ( ! function_exists( 'wp_loft_booking_normalize_phone_number' ) ) {
    /**
     * Normalize a phone number to an approximate E.164 representation.
     *
     * @param string $phone Raw phone input.
     *
     * @return string Normalized phone (including leading +) or empty string when invalid.
     */
    function wp_loft_booking_normalize_phone_number( $phone ) {
        $phone = trim( (string) $phone );

        if ( '' === $phone ) {
            return '';
        }

        if ( 0 === strpos( $phone, '+' ) ) {
            $digits = preg_replace( '/\D+/', '', substr( $phone, 1 ) );
            return $digits ? '+' . $digits : '';
        }

        $digits = preg_replace( '/\D+/', '', $phone );

        if ( '' === $digits ) {
            return '';
        }

        if ( strlen( $digits ) === 11 && 0 === strpos( $digits, '1' ) ) {
            return '+' . $digits;
        }

        if ( strlen( $digits ) === 10 ) {
            return '+1' . $digits;
        }

        return '+' . $digits;
    }
}

/**
 * Prepare a sanitized list of ButterflyMX recipients (emails and phone numbers).
 *
 * @param array $recipients Raw recipients.
 *
 * @return array Sanitized recipients.
 */
function wp_loft_booking_prepare_butterflymx_recipients( $recipients ) {
    $sanitized = array();

    foreach ( (array) $recipients as $recipient ) {
        $recipient = trim( (string) $recipient );

        if ( '' === $recipient ) {
            continue;
        }

        if ( is_email( $recipient ) ) {
            $sanitized[] = sanitize_email( $recipient );
            continue;
        }

        $normalized_phone = wp_loft_booking_normalize_phone_number( $recipient );

        if ( '' !== $normalized_phone ) {
            $sanitized[] = $normalized_phone;
        }
    }

    if ( empty( $sanitized ) ) {
        return array();
    }

    return array_values( array_unique( $sanitized ) );
}

function wp_loft_booking_create_visitor_pass_for_unit(
    $building_id,
    $target_unit_id,
    $starts_at_utc,
    $ends_at_utc,
    $recipients = array(),
    $template_unit_id = null,
    $environment = 'production',
    $access_point_ids = array(),
    $device_ids = array()
) {
    $token    = get_butterflymx_access_token( 'v4' );
    $base_url = wp_loft_booking_get_butterflymx_base_url( $environment );

    if ( empty( $token ) ) {
        return new WP_Error( 'no_token', 'ButterflyMX access token missing.' );
    }

    $ap_ids = array();

    foreach ( (array) $access_point_ids as $id ) {
        $id = (int) $id;
        if ( $id > 0 ) {
            $ap_ids[] = $id;
        }
    }

    $ap_ids = array_values( array_unique( $ap_ids ) );

    if ( empty( $ap_ids ) ) {
        $ap_ids = wp_loft_booking_get_shared_access_points( $building_id, $template_unit_id, $environment );

        if ( is_wp_error( $ap_ids ) ) {
            return $ap_ids;
        }
    }

    $payload = array(
        'keychain' => array(
            'name'             => 'Visitor - ' . (int) $target_unit_id,
            'unit_id'          => (int) $target_unit_id,
            'starts_at'        => $starts_at_utc,
            'ends_at'          => $ends_at_utc,
            'access_point_ids' => $ap_ids,
            'notes'            => 'Booking via WP',
        ),
    );

    $sanitized_device_ids = array();

    foreach ( (array) $device_ids as $device_id ) {
        $device_id = (int) $device_id;

        if ( $device_id > 0 ) {
            $sanitized_device_ids[] = $device_id;
        }
    }

    if ( ! empty( $sanitized_device_ids ) ) {
        $payload['keychain']['device_ids'] = array_values( array_unique( $sanitized_device_ids ) );
    }

    if ( ! empty( $recipients ) ) {
        $sanitized = wp_loft_booking_prepare_butterflymx_recipients( $recipients );

        if ( ! empty( $sanitized ) ) {
            $payload['keychain']['recipients'] = $sanitized;
        }
    }

    $resp = wp_remote_post(
        $base_url . '/keychains/custom',
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ),
            'timeout' => 20,
        )
    );

    if ( is_wp_error( $resp ) ) {
        error_log( '❌ ButterflyMX request error: ' . $resp->get_error_message() );
        return new WP_Error( 'http_request_failed', $resp->get_error_message() );
    }

    $status   = wp_remote_retrieve_response_code( $resp );
    $raw_body = wp_remote_retrieve_body( $resp );
    $data     = json_decode( $raw_body, true );

    if ( $status >= 300 ) {
        $message = isset( $data['message'] ) ? trim( $data['message'] ) : 'ButterflyMX API error.';
        error_log( sprintf( '❌ ButterflyMX API error (%d): %s', $status, $message ) );
        return new WP_Error(
            'http_error',
            $message,
            array(
                'status' => $status,
                'body'   => is_null( $data ) ? $raw_body : $data,
            )
        );
    }

    $keychain_id = (int) ( $data['data']['id'] ?? 0 );
    $vk_ids      = array();
    foreach ( $data['data']['virtual_keys'] ?? array() as $vk ) {
        if ( isset( $vk['id'] ) ) {
            $vk_ids[] = (int) $vk['id'];
        }
    }

    return array(
        'keychain_id'      => $keychain_id,
        'virtual_key_ids'  => $vk_ids,
        'access_point_ids' => $ap_ids,
    );
}

/**
 * Legacy helper to create a keychain and virtual key. Deprecated in favour of
 * wp_loft_booking_create_visitor_pass_for_unit().
 *
 * @deprecated Use wp_loft_booking_create_visitor_pass_for_unit().
 */
function wp_loft_booking_create_keychain_with_vk($tenant, $unit_id_api, $access_group_id, $start, $end) {
    $building_id = get_option('butterflymx_building_id');
    $environment = wp_loft_booking_get_butterflymx_environment();

    $recipients = array();

    if ( ! empty( $tenant['email'] ) ) {
        $recipients[] = $tenant['email'];
    }

    $result = wp_loft_booking_create_visitor_pass_for_unit(
        intval( $building_id ),
        intval( $unit_id_api ),
        $start,
        $end,
        $recipients,
        null,
        $environment
    );

    if ( is_wp_error( $result ) ) {
        error_log( '❌ Visitor pass creation failed: ' . $result->get_error_message() );
        return false;
    }

    return [
        'keychain_id'    => $result['keychain_id'],
        'virtual_key_id' => $result['virtual_key_ids'][0] ?? null,
    ];
}

// add_action('nd_booking_after_booking_completed', 'handle_successful_booking', 10, 1);



