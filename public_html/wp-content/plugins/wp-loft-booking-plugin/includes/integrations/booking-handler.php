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
    try {
        global $wpdb;

        $booking = [
            'room_id'        => $id_post,
            'name'           => $user_first_name,
            'surname'        => $user_last_name,
            'email'          => $paypal_email,
            'phone'          => $user_phone,
            'country'        => $user_country,
            'date_from'      => $date_from,
            'date_to'        => $date_to,
            'room_name'      => $title_post,
            'total'          => $final_trip_price,
            'extra_services' => $extra_services,
            'guests'         => $guests,
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

        $timezone_string = get_option('timezone_string');
        if (empty($timezone_string)) {
            $timezone_string = 'America/Toronto';
        }

        $starts_at = null;
        $ends_at   = null;
        $availability_until = null;

        try {
            $site_timezone  = new DateTimeZone($timezone_string);
            $checkin_local  = new DateTime($booking['date_from'], $site_timezone);
            $checkout_local = new DateTime($booking['date_to'], $site_timezone);
            $checkin_local->setTime(15, 0, 0);
            $checkout_local->setTime(11, 0, 0);

            $checkin_utc  = clone $checkin_local;
            $checkout_utc = clone $checkout_local;
            $checkin_utc->setTimezone(new DateTimeZone('UTC'));
            $checkout_utc->setTimezone(new DateTimeZone('UTC'));

            $starts_at = $checkin_utc->format('Y-m-d\TH:i:s\Z');
            $ends_at   = $checkout_utc->format('Y-m-d\TH:i:s\Z');
            $availability_until = $checkout_local->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log('⚠️ Unable to prepare booking window for ButterflyMX storage: ' . $e->getMessage());
        }

        // 🔐 Generar llave virtual con ButterflyMX
        $virtual_key_result = wp_loft_booking_generate_virtual_key(
            $booking['room_id'],
            $booking['name'],
            $booking['email'],
            $booking['phone'],
            $booking['date_from'],
            $booking['date_to']
        );

        // 🗓️ Crear evento en Google Calendar
        wp_loft_booking_create_google_event($booking);

        // 📧 Enviar correo de confirmación al huésped
        wp_loft_booking_send_confirmation_email($booking, $virtual_key_result);

        if (!is_wp_error($virtual_key_result)) {
            $keychain_id = isset($virtual_key_result['keychain_id']) ? (int) $virtual_key_result['keychain_id'] : 0;
            $primary_virtual_key_id = $virtual_key_result['virtual_key_ids'][0] ?? null;

            if ($keychain_id > 0 && $starts_at && $ends_at) {
                wp_loft_booking_save_keychain_data(
                    $id_post,
                    $booking['room_id'],
                    $keychain_id,
                    $primary_virtual_key_id,
                    $starts_at,
                    $ends_at
                );
            }

            if (!empty($booking['room_id']) && $availability_until) {
                $wpdb->update(
                    $units_table,
                    [
                        'status'             => 'occupied',
                        'availability_until' => $availability_until,
                    ],
                    ['id' => (int) $booking['room_id']],
                    ['%s', '%s'],
                    ['%d']
                );
            }
        }
    } catch (Throwable $e) {
        error_log(
            sprintf(
                '❌ WP Loft booking automation failed: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );
    }
}

function wp_loft_booking_generate_virtual_key($unit_id, $name, $email, $phone, $date_from, $date_to) {
    global $wpdb;

    if (empty($unit_id)) {
        error_log('❌ Unable to create ButterflyMX keychain: missing unit ID.');
        return new WP_Error('missing_unit_id', 'Missing unit ID.');
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
        return new WP_Error('unit_not_found', 'Unit not found.');
    }

    if (empty($unit->unit_id_api)) {
        error_log('❌ Unable to create ButterflyMX keychain: missing ButterflyMX unit ID for unit ' . $unit->unit_name);
        return new WP_Error('missing_unit_api', 'Missing ButterflyMX unit ID.');
    }

    $environment = wp_loft_booking_get_butterflymx_environment();

    $building_id = (int) ($unit->building_id ?? 0);
    $access_point_ids = array();
    $device_ids       = array();

    $remote_profile = wp_loft_booking_fetch_unit_profile((int) $unit->unit_id_api, $environment);

    if (is_wp_error($remote_profile)) {
        $log_message = sprintf(
            '⚠️ Unable to fetch ButterflyMX unit profile (code: %s): %s',
            $remote_profile->get_error_code(),
            $remote_profile->get_error_message()
        );

        $error_data = $remote_profile->get_error_data();

        if (is_array($error_data)) {
            if (!empty($error_data['status'])) {
                $log_message .= sprintf(' [status %s]', $error_data['status']);
            }

            if (array_key_exists('body', $error_data) && null !== $error_data['body']) {
                $body = $error_data['body'];
                $log_message .= ' Body: ' . (
                    is_string($body)
                        ? $body
                        : wp_json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
            }
        }

        error_log($log_message);
    } else {
        if (!empty($remote_profile['building_id'])) {
            $building_id = $building_id > 0 ? $building_id : (int) $remote_profile['building_id'];
        }

        if (!empty($remote_profile['access_point_ids'])) {
            $access_point_ids = (array) $remote_profile['access_point_ids'];
        }

        if (!empty($remote_profile['device_ids'])) {
            $device_ids = (array) $remote_profile['device_ids'];
        }
    }

    if ($building_id <= 0) {
        error_log('❌ Unable to create ButterflyMX keychain: missing building ID for unit ' . $unit->unit_name);
        return new WP_Error('missing_building_id', 'Missing building ID.');
    }

    $timezone_string = get_option('timezone_string');
    if (empty($timezone_string)) {
        $timezone_string = 'America/Toronto';
    }

    try {
        $checkin_local  = new DateTime($date_from, new DateTimeZone($timezone_string));
        $checkout_local = new DateTime($date_to, new DateTimeZone($timezone_string));
    } catch (Exception $e) {
        error_log('❌ Unable to parse booking dates for ButterflyMX keychain: ' . $e->getMessage());
        return new WP_Error('invalid_dates', 'Invalid booking dates.');
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
        $building_id,
        intval($unit->unit_id_api),
        $starts_at,
        $ends_at,
        $recipients,
        intval($unit->unit_id_api),
        $environment,
        $access_point_ids,
        $device_ids,
        $unit->unit_name
    );

    if (is_wp_error($result)) {
        $log_message = '❌ ButterflyMX keychain creation failed: ' . $result->get_error_message();

        $error_data = $result->get_error_data();

        if (is_array($error_data)) {
            if (!empty($error_data['status'])) {
                $log_message .= sprintf(' [status %s]', $error_data['status']);
            }

            if (array_key_exists('body', $error_data) && null !== $error_data['body']) {
                $body = $error_data['body'];
                $log_message .= ' Body: ' . (
                    is_string($body)
                        ? $body
                        : wp_json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
            }
        }

        error_log($log_message);
        return $result;
    }

    error_log(sprintf(
        '✅ ButterflyMX keychain %d created with access points: %s',
        $result['keychain_id'],
        implode(', ', $result['access_point_ids'])
    ));

    if (function_exists('wp_loft_booking_trigger_unit_sync')) {
        wp_loft_booking_trigger_unit_sync('virtual_key_created');
    }

    return $result;
}

function wp_loft_booking_send_confirmation_email($booking, $virtual_key_result, $is_manual = false) {
    $recipient = isset($booking['email']) ? sanitize_email($booking['email']) : '';

    if (empty($recipient) || !is_email($recipient)) {
        error_log('⚠️ Booking confirmation email skipped: invalid recipient.');
        return;
    }

    $guest_name = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? ''));
    if (empty($guest_name)) {
        $guest_name = __('Invité', 'wp-loft-booking');
    }

    $room_name = !empty($booking['room_name']) ? $booking['room_name'] : __('Votre loft', 'wp-loft-booking');

    $checkin  = !empty($booking['date_from']) ? wp_date('F j, Y', strtotime($booking['date_from'])) : __('N/A', 'wp-loft-booking');
    $checkout = !empty($booking['date_to']) ? wp_date('F j, Y', strtotime($booking['date_to'])) : __('N/A', 'wp-loft-booking');

    $checkin_fr  = !empty($booking['date_from']) ? wp_date('j F Y', strtotime($booking['date_from'])) : __('N/D', 'wp-loft-booking');
    $checkout_fr = !empty($booking['date_to']) ? wp_date('j F Y', strtotime($booking['date_to'])) : __('N/D', 'wp-loft-booking');

    $total = isset($booking['total']) && $booking['total'] !== '' ? sprintf('$%s', number_format((float) $booking['total'], 2)) : __('Non disponible', 'wp-loft-booking');

    $virtual_key_success = !is_wp_error($virtual_key_result);
    $virtual_key_message_fr = $virtual_key_success
        ? __('Votre clé virtuelle sera envoyée automatiquement par courriel et par SMS peu avant votre arrivée.', 'wp-loft-booking')
        : __('Nous n’avons pas pu créer votre clé virtuelle automatiquement. Un membre de notre équipe communiquera avec vous sous peu.', 'wp-loft-booking');

    $virtual_key_message_en = $virtual_key_success
        ? __('Your virtual key will be sent automatically via email and SMS shortly before your arrival.', 'wp-loft-booking')
        : __('We were unable to create your virtual key automatically. A member of our team will contact you shortly.', 'wp-loft-booking');

    if (is_wp_error($virtual_key_result)) {
        error_log('⚠️ Virtual key error for confirmation email: ' . $virtual_key_result->get_error_message());
    }

    $support_email = sanitize_email(get_option('admin_email'));
    $headers       = ['Content-Type: text/html; charset=UTF-8'];

    if ($support_email && is_email($support_email)) {
        $headers[] = 'Bcc: ' . $support_email;
    }

    $subject = 'Loft 1325 – Confirmation de réservation | Reservation Confirmation';

    ob_start();
    ?>
    <div style="margin:0;padding:0;background-color:#f4f5f7;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#111827;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f4f5f7;padding:30px 0;">
            <tr>
                <td align="center">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 20px 40px rgba(15,23,42,0.08);">
                        <tr>
                            <td style="padding:30px 40px;background:linear-gradient(135deg,#0f172a,#1f2937);color:#ffffff;">
                                <h1 style="margin:0;font-size:24px;font-weight:700;">Loft 1325</h1>
                                <p style="margin:8px 0 0;font-size:16px;letter-spacing:0.03em;text-transform:uppercase;">Expérience d’hospitalité cinq étoiles</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:30px 40px;">
                                <h2 style="margin-top:0;font-size:20px;font-weight:700;color:#111827;">Bonjour <?php echo esc_html($guest_name); ?>,</h2>
                                <p style="font-size:15px;line-height:1.7;margin:0 0 16px;color:#374151;">Merci d’avoir choisi <strong>Loft 1325</strong> pour votre séjour. Nous avons le plaisir de confirmer votre réservation dans <strong><?php echo esc_html($room_name); ?></strong>.</p>
                                <p style="font-size:15px;line-height:1.7;margin:0 0 20px;color:#374151;">Dates&nbsp;: <strong><?php echo esc_html($checkin_fr); ?></strong> au <strong><?php echo esc_html($checkout_fr); ?></strong><br>Total du séjour&nbsp;: <strong><?php echo esc_html($total); ?></strong></p>
                                <p style="font-size:15px;line-height:1.7;margin:0 0 20px;color:#374151;"><?php echo esc_html($virtual_key_message_fr); ?></p>
                                <div style="margin:24px 0;padding:24px;background-color:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;">
                                    <h3 style="margin-top:0;margin-bottom:12px;font-size:16px;font-weight:700;color:#111827;">Informations importantes</h3>
                                    <ul style="margin:0;padding-left:20px;color:#4b5563;font-size:14px;line-height:1.7;">
                                        <li>Arrivée à partir de 15&nbsp;h (Heure de l’Est)</li>
                                        <li>Départ au plus tard à 11&nbsp;h (Heure de l’Est)</li>
                                        <li>Veuillez avoir une pièce d’identité valide lors de votre arrivée</li>
                                    </ul>
                                </div>
                                <p style="font-size:15px;line-height:1.7;margin:0 0 20px;color:#374151;">Pour toute demande spéciale ou pour obtenir de l’aide, écrivez-nous à <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:#1d4ed8;text-decoration:none;"><?php echo esc_html($support_email); ?></a>.</p>
                                <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">
                                <h2 style="font-size:20px;font-weight:700;color:#111827;margin-bottom:12px;">Hello <?php echo esc_html($guest_name); ?>,</h2>
                                <p style="font-size:15px;line-height:1.7;margin:0 0 16px;color:#374151;">Thank you for choosing <strong>Loft 1325</strong> for your stay. We are delighted to confirm your reservation in <strong><?php echo esc_html($room_name); ?></strong>.</p>
                                <p style="font-size:15px;line-height:1.7;margin:0 0 20px;color:#374151;">Dates: <strong><?php echo esc_html($checkin); ?></strong> to <strong><?php echo esc_html($checkout); ?></strong><br>Total stay: <strong><?php echo esc_html($total); ?></strong></p>
                                <p style="font-size:15px;line-height:1.7;margin:0 0 20px;color:#374151;"><?php echo esc_html($virtual_key_message_en); ?></p>
                                <div style="margin:24px 0;padding:24px;background-color:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;">
                                    <h3 style="margin-top:0;margin-bottom:12px;font-size:16px;font-weight:700;color:#111827;">Important information</h3>
                                    <ul style="margin:0;padding-left:20px;color:#4b5563;font-size:14px;line-height:1.7;">
                                        <li>Check-in from 3:00&nbsp;PM (Eastern Time)</li>
                                        <li>Check-out by 11:00&nbsp;AM (Eastern Time)</li>
                                        <li>Please have a valid photo ID ready upon arrival</li>
                                    </ul>
                                </div>
                                <p style="font-size:15px;line-height:1.7;margin:0;color:#374151;">If you need anything before your arrival, reach out to us at <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:#1d4ed8;text-decoration:none;"><?php echo esc_html($support_email); ?></a>.</p>
                                <?php if ($is_manual) : ?>
                                    <p style="font-size:13px;line-height:1.7;margin:24px 0 0;color:#6b7280;">Cette confirmation a été générée depuis le portail administrateur de Loft 1325. / This confirmation was issued from the Loft 1325 admin portal.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:20px 40px;background-color:#0f172a;color:#9ca3af;font-size:12px;text-align:center;">
                                &copy; <?php echo esc_html(wp_date('Y')); ?> Loft 1325 &middot; 1325 3e Avenue, Val-d’Or, QC
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <?php
    $body = ob_get_clean();

    $sent = wp_mail($recipient, $subject, $body, $headers);

    if (!$sent) {
        error_log('❌ Booking confirmation email could not be sent to ' . $recipient);
    } else {
        error_log('✅ Booking confirmation email sent to ' . $recipient);
    }
}

function wp_loft_booking_create_google_event($booking) {
    $client = wp_loft_get_google_client();
    if (!$client) {
        error_log('⚠️ Google Client unavailable. Skipping calendar event creation.');
        return;
    }

    if (!class_exists('Google_Service_Calendar') || !class_exists('Google_Service_Calendar_Event')) {
        error_log('⚠️ Google Calendar PHP client library unavailable. Skipping event creation.');
        return;
    }

    try {
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
    } catch (Throwable $e) {
        error_log('❌ Unable to prepare Google Calendar event payload: ' . $e->getMessage());
        return;
    }

    try {
        $calendarId = 'primary'; // Cambia si usas uno distinto
        $service->events->insert($calendarId, $event);
        error_log("📅 Evento de reserva creado en Google Calendar");
    } catch (Throwable $e) {
        error_log("❌ Error al crear evento de Google Calendar: " . $e->getMessage());
    }
}


