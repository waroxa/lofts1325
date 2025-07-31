<?php
defined('ABSPATH') || exit;

function wp_loft_booking_get_authorization_url($version) {
    $client_id = get_option('butterflymx_client_id');
    $environment = get_option('butterflymx_environment', 'sandbox');
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
    $environment = get_option('butterflymx_environment', 'sandbox');
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
        'sslverify' => false, // Set to true in production
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
    $environment = get_option('butterflymx_environment', 'sandbox');
    
    error_log("Using token_v4: " . $token_v4);
    
    $buildings_url = ($environment === 'production') 
        ? "https://api.butterflymx.com/v4/buildings" 
        : "https://apisandbox.butterflymx.com/v4/buildings";

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
    $environment = get_option('butterflymx_environment', 'sandbox');

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
    $environment = get_option('butterflymx_environment', 'sandbox');

    if ($version === 'v3') {
        $tokenEndpoint = 'https://' . ($environment === 'production' ? '' : 'sandbox.') . 'butterflymx.com/oauth/token';
        $token = get_option('butterflymx_token_v3');
        $expires = get_option('butterflymx_token_v3_expires');
    } else {
        $tokenEndpoint = 'https://' . ($environment === 'production' ? '' : 'sandbox.') . 'butterflymx.com/oauth/token';
        $token = get_option('butterflymx_token_v4');
        $expires = get_option('butterflymx_token_v4_expires');
    }

    if (empty($token) || $expires < time()) {
        $response = json_decode(wp_remote_post($tokenEndpoint, [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
        ]), true);

        if (isset($response['access_token'])) {
            if ($version === 'v3') {
                update_option('butterflymx_token_v3', $response['access_token']);
                update_option('butterflymx_token_v3_expires', time() + $response['expires_in']);
            } else {
                update_option('butterflymx_token_v4', $response['access_token']);
                update_option('butterflymx_token_v4_expires', time() + $response['expires_in']);
            }
            return $response['access_token'];
        }
    }

    return $token;
}

// Function to check room availability
function is_room_available($roomId) {
    $accessToken = get_butterflymx_access_token();
    $environment = get_option('butterflymx_environment', 'sandbox');
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
    $environment = get_option('butterflymx_environment', 'sandbox');

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
        'sslverify' => false, // Set to true in production
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

// add_action('nd_booking_after_booking_completed', 'handle_successful_booking', 10, 1);



