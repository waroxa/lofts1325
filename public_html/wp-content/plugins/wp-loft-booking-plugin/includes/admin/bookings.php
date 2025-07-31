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

function handle_successful_booking($booking_id) {
    global $wpdb;

    $booking = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}nd_booking_booking WHERE id = %d", $booking_id)
    );

    if (!$booking) {
        error_log("❌ Booking not found.");
        return;
    }

    $room_type  = strtoupper($booking->title_post);
    $first_name = $booking->user_first_name;
    $last_name  = $booking->user_last_name;
    $email      = $booking->paypal_email;
    $checkin    = $booking->date_from;
    $checkout   = $booking->date_to;

    // Normalize room type
    if (stripos($room_type, 'SIMPLE') !== false)    $room_type = 'SIMPLE';
    if (stripos($room_type, 'DOUBLE') !== false)    $room_type = 'DOUBLE';
    if (stripos($room_type, 'PENTHOUSE') !== false) $room_type = 'PENTHOUSE';

    // Log to browser
    add_action('wp_footer', function () use ($booking_id, $room_type, $first_name, $last_name, $email, $checkin, $checkout) {
        echo "<script>
            console.log('%c🔥 Booking Hook Triggered', 'color: green; font-weight: bold;');
            console.log('ID: $booking_id');
            console.log('Room Type: $room_type');
            console.log('Guest: $first_name $last_name');
            console.log('Email: $email');
            console.log('Check-in: $checkin');
            console.log('Checkout: $checkout');
        </script>";
    });

    $loft = find_first_available_loft_unit($room_type);
    if (!$loft) {
        add_action('wp_footer', function () {
            echo "<script>console.warn('❌ No matching loft available');</script>";
        });
        return;
    }

    // 🧠 New check for unit_id_api
    if (!$loft->unit_id_api) {
        error_log("❌ Missing unit_id_api for {$loft->unit_name}");
        add_action('wp_footer', function () use ($loft) {
            echo "<script>console.error('❌ Missing unit_id_api for {$loft->unit_name}');</script>";
        });
        return;
    }

    // ✅ Create tenant
    create_tenant_and_virtual_key(
    $loft->unit_id_api,
    $email,
    $first_name,
    $last_name,
    $checkin // Pass real check-in date
);
    // if (!$tenant_id) {
    //     error_log("❌ Failed to create tenant.");
    //     add_action('wp_footer', function () {
    //         echo "<script>console.error('❌ Failed to create tenant');</script>";
    //     });
    //     return;
    // }

    // ✅ Create visitor pass
    // $created = create_butterflymx_visitor_pass($loft->unit_id_api, $email, $checkin, $checkout);
    // if (!$created) {
    //     error_log("❌ Failed to create visitor pass.");
    //     add_action('wp_footer', function () {
    //         echo "<script>console.error('❌ Failed to create visitor pass');</script>";
    //     });
    //     return;
    // }

    // ✅ Add calendar booking
    add_booking_to_google_calendar("Booking for $first_name $last_name", $checkin, $checkout);

    // ✅ Add cleaning task
    $cleaning_time = date('Y-m-d H:i:s', strtotime($checkout . ' +1 hour'));
    schedule_cleaning_task("Cleaning: {$loft->unit_name}", $cleaning_time);

    error_log("✅ Booking automation completed.");

    // Final success message
    add_action('wp_footer', function () use ($loft) {
        echo "<script>console.log('%c✅ Booking & automation complete for loft: {$loft->unit_name}', 'color: blue; font-weight: bold;');</script>";
    });
}







