<?php
defined('ABSPATH') || exit;

function wp_loft_booking_bookings_page() {
    echo '<div class="wrap"><h1>Manage Bookings</h1><p>This is where you can view, edit, and manage bookings.</p></div>';
}

function create_butterflymx_visitor_pass($unit_id, $email, $from, $to) {
    $token = get_option('butterflymx_access_token_v4');
    $environment = get_option('butterflymx_environment', 'sandbox');
    $api_base_url = ($environment === 'production') ? "https://api.butterflymx.com/v4" : "https://api.na.sandbox.butterflymx.com/v4";

    $payload = [
        'visitor_pass' => [
            'unit_id' => $unit_id,
            'recipients' => [$email],
            'starts_at' => $from,
            'ends_at' => $to
        ]
    ];

    $response = wp_remote_post("{$api_base_url}/visitor_passes", [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        error_log("Visitor pass creation failed: " . $response->get_error_message());
        return false;
    }

    return true;
}

update_option('loft_booking_cleaning_calendar_id', 'e964e301b54d0e795b44a76ebfb9d2cfbd2f6517a822429c5af62bc2cb94de20@group.calendar.google.com');
update_option('loft_booking_calendar_id', 'a752f27cffee8c22988adb29fdc933c93184e3a5814c79dcee4f62115d69fbfd@group.calendar.google.com');

add_action('wc_stripe_webhook_payment_intent_succeeded', 'wp_loft_booking_stripe_payment_succeeded', 10, 2);
add_action('nd_booking_stripe_payment_complete', 'wp_loft_booking_nd_stripe_payment_complete', 10, 1);

function wp_loft_booking_stripe_payment_succeeded($order, $event) {
    $intent = $event->data->object ?? null;
    if (!$intent || empty($intent->metadata)) {
        return;
    }

    $meta       = $intent->metadata;
    $email      = $meta->guest_email ?? '';
    $room_type  = $meta->loft_type ?? '';
    $checkin    = $meta->checkin ?? '';
    $checkout   = $meta->checkout ?? '';
    $first_name = $meta->first_name ?? 'Guest';
    $last_name  = $meta->last_name ?? 'Booking';
    $booking_id = isset($meta->booking_id) ? intval($meta->booking_id) : 0;

    wp_loft_booking_process_booking($email, $room_type, $checkin, $checkout, $first_name, $last_name, $booking_id);
}

function wp_loft_booking_nd_stripe_payment_complete($payload) {
    $email      = $payload['guest_email']   ?? '';
    $room_type  = $payload['room_type']     ?? '';
    $checkin    = $payload['check_in_date'] ?? '';
    $checkout   = $payload['check_out_date'] ?? '';
    $booking_id = isset($payload['booking_id']) ? intval($payload['booking_id']) : 0;
    $first_name = $payload['first_name']    ?? 'Guest';
    $last_name  = $payload['last_name']     ?? 'Booking';

    wp_loft_booking_process_booking($email, $room_type, $checkin, $checkout, $first_name, $last_name, $booking_id);
}

function wp_loft_booking_process_booking($email, $room_type, $checkin, $checkout, $first_name = 'Guest', $last_name = 'Booking', $booking_id = 0) {
    $room_type = strtoupper($room_type);

    $loft = find_first_available_loft_unit($room_type);
    if (!$loft) {
        error_log('❌ No matching loft available.');
        return;
    }

    if (!$loft->unit_id_api) {
        error_log("❌ Missing unit_id_api for {$loft->unit_name}");
        return;
    }

    $access_group_id = wp_loft_booking_get_access_group_id($loft->unit_name);
    if (!$access_group_id) {
        error_log('❌ Access group not found for ' . $loft->unit_name);
        return;
    }

    $tenant = [
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
    ];

    $start = $checkin . 'T15:00:00Z';
    $end   = $checkout . 'T11:00:00Z';

    $result = wp_loft_booking_create_keychain_with_vk($tenant, $loft->unit_id_api, $access_group_id, $start, $end);
    if ($result) {
        wp_loft_booking_save_keychain_data($booking_id, $loft->id, $result['keychain_id'], $result['virtual_key_id'], $start, $end);
    }

    add_booking_to_google_calendar("Booking for $first_name $last_name", $checkin, $checkout);
    $cleaning_time = date('Y-m-d H:i:s', strtotime($checkout . ' +1 hour'));
    schedule_cleaning_task("Cleaning: {$loft->unit_name}", $cleaning_time);

    if (function_exists('trigger_amelia_booking_webhook')) {
        $amelia_data = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'checkin'    => $checkin,
            'checkout'   => $checkout,
            'unit'       => [
                'id'     => $loft->id,
                'name'   => $loft->unit_name,
                'api_id' => $loft->unit_id_api,
            ],
        ];
        trigger_amelia_booking_webhook($amelia_data);
    }

    error_log('✅ Booking automation completed.');
}
