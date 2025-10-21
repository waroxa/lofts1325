<?php
defined('ABSPATH') || exit;

add_action('nd_booking_reservation_added_in_db', 'wp_loft_booking_handle_booking', 10, 23);

function wp_loft_booking_handle_booking(
    $id_post,
    $title_post,
    $date,
    $date_from,
    $date_to,
    $guests,
    $final_trip_price,
    $extra_services,
    $id_user,
    $user_first_name,
    $user_last_name,
    $paypal_email,
    $user_phone,
    $user_address,
    $user_city,
    $user_country,
    $user_message,
    $user_arrival,
    $user_coupon,
    $paypal_payment_status,
    $paypal_currency,
    $paypal_tx,
    $action_type
) {
    global $wpdb;

    $booking = [
        'room_id'   => $id_post,
        'name'      => $user_first_name,
        'surname'   => $user_last_name,
        'email'     => $paypal_email,
        'phone'     => $user_phone,
        'country'   => $user_country,
        'date_from' => $date_from,
        'date_to'   => $date_to,
    ];

    $units_table    = $wpdb->prefix . 'loft_units';
    $bookings_table = $wpdb->prefix . 'loft_bookings';

    $has_valid_unit = !empty($booking['room_id']) && $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$units_table} WHERE id = %d", $booking['room_id'])
    );

    if (!$has_valid_unit) {
        $available_unit = $wpdb->get_var(
            "SELECT id FROM {$units_table} WHERE status = 'available' ORDER BY unit_name ASC LIMIT 1"
        );

        if ($available_unit) {
            $booking['room_id'] = intval($available_unit);

            $wpdb->update(
                $bookings_table,
                ['unit_id' => $booking['room_id']],
                ['id' => $id_post],
                ['%d'],
                ['%d']
            );
        }
    }

    // 🔐 Generar llave virtual con ButterflyMX
    wp_loft_booking_generate_virtual_key(
        $booking['room_id'],
        $booking['name'],
        $booking['email'],
        $booking['phone'],
        $booking['date_from'],
        $booking['date_to']
    );

    // 🗓️ Crear evento en Google Calendar
    wp_loft_booking_create_google_event($booking);
}

function wp_loft_booking_generate_virtual_key($unit_id, $name, $email, $phone, $date_from, $date_to) {
    global $wpdb;

    if (empty($unit_id)) {
        error_log('❌ Unable to create ButterflyMX keychain: missing unit ID.');
        return;
    }

    $units_table    = $wpdb->prefix . 'loft_units';
    $branches_table = $wpdb->prefix . 'loft_branches';

    $unit = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT u.unit_id_api, u.unit_name, b.building_id FROM {$units_table} u LEFT JOIN {$branches_table} b ON u.branch_id = b.id WHERE u.id = %d",
            $unit_id
        )
    );

    if (!$unit) {
        error_log('❌ Unable to create ButterflyMX keychain: unit not found for ID ' . intval($unit_id));
        return;
    }

    if (empty($unit->unit_id_api)) {
        error_log('❌ Unable to create ButterflyMX keychain: missing ButterflyMX unit ID for unit ' . $unit->unit_name);
        return;
    }

    if (empty($unit->building_id)) {
        error_log('❌ Unable to create ButterflyMX keychain: missing building ID for unit ' . $unit->unit_name);
        return;
    }

    $environment = get_option('butterflymx_environment', 'sandbox');

    $timezone_string = get_option('timezone_string');
    if (empty($timezone_string)) {
        $timezone_string = 'America/Toronto';
    }

    try {
        $checkin_local  = new DateTime($date_from, new DateTimeZone($timezone_string));
        $checkout_local = new DateTime($date_to, new DateTimeZone($timezone_string));
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

    $starts_at = $checkin_utc->format('Y-m-d\TH:i:s\Z');
    $ends_at   = $checkout_utc->format('Y-m-d\TH:i:s\Z');

    $recipients = array();

    if (!empty($email)) {
        $recipients[] = $email;
    }

    if (!empty($phone)) {
        $normalized_phone = wp_loft_booking_normalize_phone_number($phone);
        if (!empty($normalized_phone)) {
            $recipients[] = $normalized_phone;
        }
    }

    $result = wp_loft_booking_create_visitor_pass_for_unit(
        intval($unit->building_id),
        intval($unit->unit_id_api),
        $starts_at,
        $ends_at,
        $recipients,
        intval($unit->unit_id_api),
        $environment
    );

    if (is_wp_error($result)) {
        error_log('❌ ButterflyMX keychain creation failed: ' . $result->get_error_message());
        return;
    }

    error_log(sprintf(
        '✅ ButterflyMX keychain %d created with access points: %s',
        $result['keychain_id'],
        implode(', ', $result['access_point_ids'])
    ));
}

function wp_loft_booking_create_google_event($booking) {
    $client = wp_loft_get_google_client();
    if (!$client) {
        error_log('⚠️ Google Client unavailable. Skipping calendar event creation.');
        return;
    }

    $service = new Google_Service_Calendar($client);

    $event = new Google_Service_Calendar_Event([
        'summary'     => 'Reserva de Loft - ' . $booking['name'],
        'location'    => $booking['country'],
        'description' => 'Cliente: ' . $booking['name'] . ' ' . $booking['surname'] . "\nCorreo: " . $booking['email'],
        'start' => [
            'date' => $booking['date_from'],
            'timeZone' => 'America/Toronto',
        ],
        'end' => [
            'date' => $booking['date_to'],
            'timeZone' => 'America/Toronto',
        ],
    ]);

    try {
        $calendarId = 'primary'; // Cambia si usas uno distinto
        $service->events->insert($calendarId, $event);
        error_log("📅 Evento de reserva creado en Google Calendar");
    } catch (Exception $e) {
        error_log("❌ Error al crear evento de Google Calendar: " . $e->getMessage());
    }
}


