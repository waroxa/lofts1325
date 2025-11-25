<?php
defined('ABSPATH') || exit;

add_action('admin_init', 'wp_loft_booking_handle_bulk_receipts');

function wp_loft_booking_bookings_page() {
    ?>
    <div class="wrap">
        <h1>Manage Bookings</h1>
        <p>Use the controls below to resend receipts/invoices to every guest email on record. A copy is automatically BCC’d to the Loft 1325 inboxes.</p>
        <form method="post">
            <?php wp_nonce_field('wp_loft_booking_send_all_receipts'); ?>
            <input type="hidden" name="wp_loft_booking_send_all_receipts" value="1">
            <p>
                <button class="button button-primary" type="submit">Send receipts for all bookings</button>
            </p>
            <p class="description">This will regenerate the detailed receipt email for every booking in the system and copy internal recipients.</p>
        </form>
    </div>
    <?php
}

function wp_loft_booking_handle_bulk_receipts() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (empty($_POST['wp_loft_booking_send_all_receipts'])) {
        return;
    }

    check_admin_referer('wp_loft_booking_send_all_receipts');

    global $wpdb;

    $table    = $wpdb->prefix . 'nd_booking_booking';
    $bookings = $wpdb->get_results("SELECT id FROM {$table}");
    $sent     = 0;

    if (!empty($bookings)) {
        foreach ($bookings as $record) {
            $payload = wp_loft_booking_build_booking_payload((int) $record->id);

            if (empty($payload)) {
                continue;
            }

            wp_loft_booking_send_receipt_email($payload, []);
            $sent++;
        }
    }

    add_action('admin_notices', function () use ($sent) {
        $message = $sent > 0
            ? sprintf(__('Queued %d receipt(s) for sending.', 'wp-loft-booking'), $sent)
            : __('No bookings were found to email.', 'wp-loft-booking');

        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    });
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
    $phone      = $meta->guest_phone ?? '';
    $booking_id = isset($meta->booking_id) ? intval($meta->booking_id) : 0;

    $payment_total    = isset($intent->amount_received) ? ($intent->amount_received / 100) : (isset($intent->amount) ? ($intent->amount / 100) : null);
    $payment_currency = isset($intent->currency) ? strtoupper($intent->currency) : '';
    $payment_status   = $intent->status ?? 'succeeded';
    $transaction_id   = $intent->id ?? ($meta->payment_intent ?? '');

    wp_loft_booking_process_booking(
        $email,
        $room_type,
        $checkin,
        $checkout,
        $first_name,
        $last_name,
        $booking_id,
        $phone,
        $payment_total,
        $payment_currency,
        $payment_status,
        $transaction_id
    );
}

function wp_loft_booking_nd_stripe_payment_complete($payload) {
    $email       = $payload['guest_email']   ?? '';
    $room_type   = $payload['room_type']     ?? '';
    $checkin     = $payload['check_in_date'] ?? '';
    $checkout    = $payload['check_out_date'] ?? '';
    $booking_id  = isset($payload['booking_id']) ? intval($payload['booking_id']) : 0;
    $first_name  = $payload['first_name']    ?? 'Guest';
    $last_name   = $payload['last_name']     ?? 'Booking';
    $phone       = $payload['guest_phone']   ?? ($payload['phone'] ?? '');
    $total_paid  = isset($payload['total']) ? (float) $payload['total'] : null;
    $currency    = isset($payload['currency']) ? strtoupper($payload['currency']) : '';
    $pay_status  = $payload['payment_status'] ?? 'paid';
    $transaction = $payload['payment_intent'] ?? ($payload['transaction_id'] ?? '');

    wp_loft_booking_process_booking(
        $email,
        $room_type,
        $checkin,
        $checkout,
        $first_name,
        $last_name,
        $booking_id,
        $phone,
        $total_paid,
        $currency,
        $pay_status,
        $transaction
    );
}

function wp_loft_booking_process_booking(
    $email,
    $room_type,
    $checkin,
    $checkout,
    $first_name = 'Guest',
    $last_name = 'Booking',
    $booking_id = 0,
    $phone = '',
    $payment_total = null,
    $currency = '',
    $payment_status = 'paid',
    $transaction_id = ''
) {
    global $wpdb;

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

    $full_name = trim(sprintf('%s %s', $first_name, $last_name));
    if ('' === $full_name) {
        $full_name = 'Guest Booking';
    }

    $timezone_string = get_option('timezone_string');
    if (empty($timezone_string)) {
        $timezone_string = 'America/Toronto';
    }

    try {
        $checkin_local  = new DateTime($checkin, new DateTimeZone($timezone_string));
        $checkout_local = new DateTime($checkout, new DateTimeZone($timezone_string));
    } catch (Exception $e) {
        error_log('❌ Unable to parse booking dates for ButterflyMX keychain: ' . $e->getMessage());
        return;
    }

    $checkin_local->setTime(15, 0, 0);
    $checkout_local->setTime(11, 0, 0);

    $checkin_utc  = clone $checkin_local;
    $checkout_utc = clone $checkout_local;

    $checkin_utc->setTimezone(new DateTimeZone('UTC'));
    $checkout_utc->setTimezone(new DateTimeZone('UTC'));

    $start = $checkin_utc->format('Y-m-d\TH:i:s\Z');
    $end   = $checkout_utc->format('Y-m-d\TH:i:s\Z');

    $virtual_key_result = wp_loft_booking_generate_virtual_key(
        (int) $loft->id,
        $full_name,
        $email,
        $phone,
        $checkin,
        $checkout
    );

    if (is_wp_error($virtual_key_result)) {
        error_log('❌ Failed to create ButterflyMX keychain for booking: ' . $virtual_key_result->get_error_message());
    }

    $keychain_id            = isset($virtual_key_result['keychain_id']) ? (int) $virtual_key_result['keychain_id'] : 0;
    $primary_virtual_key_id = $virtual_key_result['virtual_key_ids'][0] ?? null;

    if ($keychain_id > 0) {
        wp_loft_booking_save_keychain_data(
            $booking_id,
            $loft->id,
            $keychain_id,
            $primary_virtual_key_id,
            $start,
            $end
        );

        if (isset($checkout_local)) {
            $availability_until = $checkout_local->format('Y-m-d H:i:s');

            $wpdb->update(
                $wpdb->prefix . 'loft_units',
                [
                    'status'             => 'occupied',
                    'availability_until' => $availability_until,
                ],
                ['id' => $loft->id],
                ['%s', '%s'],
                ['%d']
            );
        }
    } else {
        error_log('⚠️ ButterflyMX keychain created without a valid keychain ID.');
    }

    add_booking_to_google_calendar("Booking for $first_name $last_name", $checkin, $checkout);
    $cleaning_time = date('Y-m-d H:i:s', strtotime($checkout . ' +1 hour'));
    schedule_cleaning_task("Cleaning: {$loft->unit_name}", $cleaning_time);

    $booking_payload = wp_loft_booking_build_booking_payload(
        $booking_id,
        [
            'room_id'        => (int) $loft->id,
            'room_name'      => $loft->unit_name,
            'name'           => $first_name,
            'surname'        => $last_name,
            'email'          => $email,
            'phone'          => $phone,
            'date_from'      => $checkin,
            'date_to'        => $checkout,
            'currency'       => $currency ?: 'CAD',
            'payment_status' => $payment_status ?: 'paid',
            'transaction_id' => $transaction_id,
            'total'          => $payment_total,
            'created_at'     => current_time('mysql'),
        ]
    );

    wp_loft_booking_send_all_booking_emails($booking_payload, $virtual_key_result);

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
