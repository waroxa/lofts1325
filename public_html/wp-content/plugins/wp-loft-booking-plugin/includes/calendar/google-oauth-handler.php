<?php
function loft_booking_handle_google_auth() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You are not allowed to access this page.'));
    }

    if (!isset($_GET['code'])) {
        echo "<h2>No authorization code found.</h2>";
        return;
    }

    $code = sanitize_text_field($_GET['code']);

    $client_id = '1057657895142-bkv4nmceeie0b79s3l6nuv9v8c8t5mbn.apps.googleusercontent.com';
    $client_secret = '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';
    $redirect_uri = admin_url('admin.php?page=loft-booking-google-auth');

    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'body' => [
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code',
        ]
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['access_token'])) {
        update_option('loft_google_access_token', $body['access_token']);
        update_option('loft_google_refresh_token', $body['refresh_token']);
        update_option('loft_google_token_expires', time() + $body['expires_in']);

        // ✅ Redirigir con mensaje de éxito
        wp_safe_redirect(admin_url('admin.php?page=loft-booking-google-calendar&connected=1'));
        exit;
    } else {
        echo "<h2>❌ Error al conectar con Google</h2>";
        echo "<pre>" . print_r($body, true) . "</pre>";
    }
}


function loft_booking_get_valid_access_token() {
    $access_token = get_option('loft_google_access_token');
    $expires = get_option('loft_google_token_expires');
    $refresh_token = get_option('loft_google_refresh_token');

    if (time() < $expires - 60) {
        return $access_token;
    }

    // Si expiró, pedimos uno nuevo
    $client_id = '1057657895142-bkv4nmceeie0b79s3l6nuv9v8c8t5mbn.apps.googleusercontent.com';
    $client_secret = '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';

    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'body' => [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ]
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['access_token'])) {
        update_option('loft_google_access_token', $body['access_token']);
        update_option('loft_google_token_expires', time() + $body['expires_in']);
        return $body['access_token'];
    }

    return false;
}

function create_google_event($summary, $description, $start, $end, $calendar_id = null) {
    $access_token = get_option('google_calendar_access_token');

    if (!$calendar_id) {
        $calendar_id = get_option('loft_booking_calendar_id');
    }

    $event = [
        'summary' => $summary,
        'description' => $description,
        'start' => ['dateTime' => $start, 'timeZone' => 'America/Toronto'],
        'end'   => ['dateTime' => $end, 'timeZone' => 'America/Toronto']
    ];

    $response = wp_remote_post("https://www.googleapis.com/calendar/v3/calendars/{$calendar_id}/events", [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode($event)
    ]);
    error_log("📤 Google Calendar API response: " . print_r($response, true));


    if (is_wp_error($response)) {
        error_log("❌ Google Calendar error: " . $response->get_error_message());
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

