<?php

function loft_booking_google_calendar_page() {
    if (isset($_GET['connected']) && $_GET['connected'] == 1) {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Successfully connected to Google Calendar.</p></div>';
    }

    $auth_url = loft_booking_get_google_auth_url();

    echo '<div class="wrap">';
    echo '<h2>Connect Google Calendar</h2>';
    echo '<a href="' . esc_url($auth_url) . '" class="button button-primary">Connect with Google</a>';

    echo '<hr>';

    echo '<h3>📅 Your Google Calendar View</h3>';
    echo '<iframe src="https://calendar.google.com/calendar/embed?src=a752f27cffee8c22988adb29fdc933c93184e3a5814c79dcee4f62115d69fbfd%40group.calendar.google.com&ctz=America%2FToronto" style="border:0" width="100%" height="600" frameborder="0" scrolling="no"></iframe>';

    echo '</div>';
}



function loft_booking_get_google_auth_url() {
    $client_id = '1057657895142-bkv4nmceeie0b79s3l6nuv9v8c8t5mbn.apps.googleusercontent.com';
    $redirect_uri = admin_url('admin.php?page=loft-booking-google-auth');

    $scopes = [
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',
        'https://www.googleapis.com/auth/calendar.events.readonly',
        'https://www.googleapis.com/auth/calendar.readonly',
    ];

    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => implode(' ', $scopes),
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);

    return $auth_url;
}



function add_booking_to_google_calendar($summary, $start, $end) {
    $calendar_id  = get_option('loft_booking_calendar_id');
    $access_token = get_option('google_calendar_access_token');

    $event = [
        'summary'     => $summary,
        'description' => 'Automated guest booking.',
        'start'       => [
            'dateTime' => date('c', strtotime($start)),
            'timeZone' => 'America/Toronto',
        ],
        'end'         => [
            'dateTime' => date('c', strtotime($end)),
            'timeZone' => 'America/Toronto',
        ],
    ];

    $response = wp_remote_post("https://www.googleapis.com/calendar/v3/calendars/{$calendar_id}/events", [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode($event),
    ]);

    error_log("\xF0\x9F\x93\xA4 Google Calendar API response: " . print_r($response, true));

    if (is_wp_error($response)) {
        error_log("\xE2\x9D\x8C Google Calendar error: " . $response->get_error_message());
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

add_action('admin_footer', function() {
    if (isset($_GET['test_gcal'])) {
        $result = add_booking_to_google_calendar("Test Event", date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+1 hour')));
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Test event sent to Google Calendar</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to send test event</p></div>';
        }
    }
});
