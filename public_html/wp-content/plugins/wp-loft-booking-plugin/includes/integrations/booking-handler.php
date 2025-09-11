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
    $booking = [
        'room_id'   => $id_post,
        'name'      => $user_first_name,
        'surname'   => $user_last_name,
        'email'     => $paypal_email,
        'country'   => $user_country,
        'date_from' => $date_from,
        'date_to'   => $date_to,
    ];

    // 🔐 Generar llave virtual con ButterflyMX
    wp_loft_booking_generate_virtual_key(
        $booking['room_id'],
        $booking['name'],
        $booking['email'],
        $booking['date_from'],
        $booking['date_to']
    );

    // 🗓️ Crear evento en Google Calendar
    wp_loft_booking_create_google_event($booking);
}

function wp_loft_booking_generate_virtual_key($unit_id, $name, $email, $date_from, $date_to) {
    $access_token = get_option('butterflymx_access_token_v4');
    $environment = get_option('butterflymx_environment', 'sandbox');

    $api_url = ($environment === 'production') 
        ? "https://api.butterflymx.com/v4/virtual_keys"
        : "https://api.na.sandbox.butterflymx.com/v4/virtual_keys";

    $response = wp_remote_post($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'unit_id'   => intval($unit_id),
            'recipient' => $email,
            'start_time' => $date_from . 'T15:00:00Z',
            'end_time'   => $date_to . 'T11:00:00Z',
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('ButterflyMX error: ' . $response->get_error_message());
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($body['data'])) {
        error_log("🔐 Llave creada para $email desde $date_from hasta $date_to");
    } else {
        error_log("❌ Error creando llave ButterflyMX: " . print_r($body, true));
    }
}

function wp_loft_booking_create_google_event($booking) {
    $client = wp_loft_get_google_client(); // Asumes que ya tienes esto en google-oauth-handler.php
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


