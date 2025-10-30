<?php
defined('ABSPATH') || exit;

add_action('nd_booking_reservation_added_in_db', 'wp_loft_booking_handle_booking', 10, 23);

if (!function_exists('wp_loft_booking_format_unit_label')) {
    /**
     * Normalize a unit label so it can be displayed without duplicated wording.
     *
     * @param string $label Raw unit label coming from the booking engine.
     * @return string Normalized label.
     */
    function wp_loft_booking_format_unit_label($label)
    {
        $label = trim((string) $label);

        if ('' === $label) {
            return '';
        }

        $label = preg_replace('/\s+/', ' ', $label);

        if (preg_match('/^(.+)\s+\1$/ui', $label, $matches)) {
            $label = $matches[1];
        }

        if (preg_match('/^lofts?\s*-*\s*([0-9]+[A-Z0-9]*)$/i', $label, $matches)) {
            $label = sprintf('Loft %s', strtoupper($matches[1]));
        } elseif (preg_match('/^ph\s*-*\s*([0-9]+[A-Z0-9]*)$/i', $label, $matches)) {
            $label = sprintf('PH %s', strtoupper($matches[1]));
        }

        return trim($label);
    }
}

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

    $unit_label = wp_loft_booking_format_unit_label($unit->unit_name ?? '');
    if ('' === $unit_label) {
        $unit_label = $unit->unit_name;
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
        $unit_label
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

    $room_name_raw = !empty($booking['room_name']) ? $booking['room_name'] : '';
    $room_name = wp_loft_booking_format_unit_label($room_name_raw);
    if ('' === $room_name) {
        $room_name = __('Votre loft', 'wp-loft-booking');
    }

    $checkin  = !empty($booking['date_from']) ? wp_date('F j, Y', strtotime($booking['date_from'])) : __('N/A', 'wp-loft-booking');
    $checkout = !empty($booking['date_to']) ? wp_date('F j, Y', strtotime($booking['date_to'])) : __('N/A', 'wp-loft-booking');

    $checkin_fr  = !empty($booking['date_from']) ? wp_date('j F Y', strtotime($booking['date_from'])) : __('N/D', 'wp-loft-booking');
    $checkout_fr = !empty($booking['date_to']) ? wp_date('j F Y', strtotime($booking['date_to'])) : __('N/D', 'wp-loft-booking');

    $total = isset($booking['total']) && $booking['total'] !== '' ? sprintf('$%s', number_format((float) $booking['total'], 2)) : __('Non disponible', 'wp-loft-booking');

    $guest_count = isset($booking['guests']) ? (int) $booking['guests'] : 0;
    if ($guest_count > 0) {
        $guest_count_display_fr = $guest_count . ' ' . (1 === $guest_count ? 'invité' : 'invités');
        $guest_count_display_en = $guest_count . ' ' . (1 === $guest_count ? 'guest' : 'guests');
    } else {
        $guest_count_display_fr = 'Non précisé';
        $guest_count_display_en = 'Not specified';
    }

    $total_display_fr = $total;
    $total_display_en = $total;
    if ($total !== __('Non disponible', 'wp-loft-booking')) {
        $total_display_fr = sprintf('%s CAD', $total);
        $total_display_en = sprintf('%s CAD', $total);
    }

    $logo_url         = 'https://loft1325.com/wp-content/uploads/2024/06/Asset-1.png';
    $website_url      = 'https://loft1325.com';
    $property_address = '1325 3e Avenue, Val-d’Or, QC, Canada';

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
    <div style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#111827;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f4f6;padding:36px 0;">
            <tr>
                <td align="center" style="padding:0 16px;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 24px 48px rgba(15,23,42,0.12);">
                        <tr>
                            <td style="padding:40px;background:linear-gradient(135deg,#0f172a,#1f2937);text-align:center;">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Loft 1325" style="max-width:200px;width:100%;height:auto;display:block;margin:0 auto 16px;">
                                <p style="margin:0;font-size:12px;letter-spacing:0.32em;text-transform:uppercase;color:#9ca3af;">Loft 1325</p>
                                <p style="margin:12px 0 0;font-size:16px;color:#e5e7eb;">Expérience de séjour signature &middot; Signature Stay Experience</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:40px 40px 28px;">
                                <p style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">Bonjour <?php echo esc_html($guest_name); ?>,</p>
                                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#374151;">Merci d’avoir choisi <strong>Loft 1325</strong> pour votre passage à Val-d’Or. Nous confirmons votre réservation dans <strong><?php echo esc_html($room_name); ?></strong>.</p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Résumé de votre séjour</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Loft</td>
                                        <td style="padding:16px 24px;font-size:15px;font-weight:600;color:#111827;"><?php echo esc_html($room_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Dates</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($checkin_fr); ?> &ndash; <?php echo esc_html($checkout_fr); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Invités</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_count_display_fr); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Montant total</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;border-bottom-right-radius:18px;"><?php echo esc_html($total_display_fr); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:28px 0;padding:24px;border-radius:18px;background:linear-gradient(135deg,#111827,#1f2937);color:#f9fafb;box-shadow:0 20px 40px rgba(15,23,42,0.18);">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#f9fafb;">Accès et clé numérique</h3>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#e5e7eb;"><?php echo esc_html($virtual_key_message_fr); ?></p>
                                </div>
                                <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Préparez votre arrivée</h3>
                                <ul style="margin:0 0 24px;padding-left:20px;font-size:14px;line-height:1.8;color:#4b5563;">
                                    <li>Arrivée à partir de 15&nbsp;h (heure de l’Est)</li>
                                    <li>Départ au plus tard à 11&nbsp;h (heure de l’Est)</li>
                                    <li>Présentez une pièce d’identité valide à l’enregistrement</li>
                                </ul>
                                <div style="margin:0 0 28px;padding:24px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:18px;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Coordonnées</h3>
                                    <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#4b5563;"><strong>Adresse</strong><br><?php echo esc_html($property_address); ?></p>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">Besoin d’assistance&nbsp;? Écrivez-nous à <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:#1d4ed8;text-decoration:none;"><?php echo esc_html($support_email); ?></a>.</p>
                                </div>
                                <p style="margin:0 0 28px;font-size:14px;line-height:1.7;color:#4b5563;">Nous avons hâte de vous accueillir pour une expérience tout confort signée Loft 1325.</p>
                                <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">
                                <p style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">Hello <?php echo esc_html($guest_name); ?>,</p>
                                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#374151;">Thank you for selecting <strong>Loft 1325</strong> for your upcoming stay in Val-d’Or. Your reservation in <strong><?php echo esc_html($room_name); ?></strong> is confirmed.</p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Stay highlights</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Loft</td>
                                        <td style="padding:16px 24px;font-size:15px;font-weight:600;color:#111827;"><?php echo esc_html($room_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Dates</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($checkin); ?> &ndash; <?php echo esc_html($checkout); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Guests</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_count_display_en); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Total amount</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;border-bottom-right-radius:18px;"><?php echo esc_html($total_display_en); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:28px 0;padding:24px;border-radius:18px;background-color:#111827;color:#f9fafb;box-shadow:0 20px 40px rgba(15,23,42,0.18);">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#f9fafb;">Digital key &amp; access</h3>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#e5e7eb;"><?php echo esc_html($virtual_key_message_en); ?></p>
                                </div>
                                <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Before you arrive</h3>
                                <ul style="margin:0 0 24px;padding-left:20px;font-size:14px;line-height:1.8;color:#4b5563;">
                                    <li>Check-in available from 3:00&nbsp;PM (Eastern Time)</li>
                                    <li>Check-out by 11:00&nbsp;AM (Eastern Time)</li>
                                    <li>Please have a valid photo ID ready at arrival</li>
                                </ul>
                                <div style="margin:0 0 28px;padding:24px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:18px;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Contact</h3>
                                    <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#4b5563;"><strong>Address</strong><br><?php echo esc_html($property_address); ?></p>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">Need assistance? Email us at <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:#1d4ed8;text-decoration:none;"><?php echo esc_html($support_email); ?></a> or visit <a href="<?php echo esc_url($website_url); ?>" style="color:#1d4ed8;text-decoration:none;">loft1325.com</a>.</p>
                                </div>
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">We can’t wait to welcome you to your private retreat at Loft 1325.</p>
                                <?php if ($is_manual) : ?>
                                    <p style="margin:24px 0 0;font-size:12px;line-height:1.7;color:#9ca3af;">Cette confirmation a été générée depuis le portail administrateur de Loft 1325. / This confirmation was issued from the Loft 1325 admin portal.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:24px 40px;background-color:#0f172a;color:#9ca3af;font-size:12px;line-height:1.6;text-align:center;">
                                &copy; <?php echo esc_html(wp_date('Y')); ?> Loft 1325 &middot; <?php echo esc_html($property_address); ?>
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
    $access_token = loft_booking_get_valid_access_token();

    if (!$access_token) {
        error_log('⚠️ Google access token unavailable. Skipping calendar event creation.');
        return;
    }

    $calendar_id = get_option('loft_booking_calendar_id');
    if (empty($calendar_id)) {
        $calendar_id = 'primary';
    }

    $checkin_date  = $booking['date_from'] ?? '';
    $checkout_date = $booking['date_to'] ?? '';

    if (empty($checkin_date) || empty($checkout_date)) {
        error_log('⚠️ Booking dates missing. Skipping Google Calendar event creation.');
        return;
    }

    $event_payload = [
        'summary'     => 'Reserva de Loft - ' . ($booking['name'] ?? ''),
        'location'    => $booking['country'] ?? '',
        'description' => sprintf(
            "Cliente: %s %s\nCorreo: %s",
            $booking['name'] ?? '',
            $booking['surname'] ?? '',
            $booking['email'] ?? ''
        ),
        'start' => [
            'date'     => $checkin_date,
            'timeZone' => 'America/Toronto',
        ],
        'end' => [
            'date'     => $checkout_date,
            'timeZone' => 'America/Toronto',
        ],
    ];

    $response = wp_remote_post(
        sprintf('https://www.googleapis.com/calendar/v3/calendars/%s/events', rawurlencode($calendar_id)),
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 15,
            'body'    => wp_json_encode($event_payload),
        ]
    );

    if (is_wp_error($response)) {
        error_log('❌ Error al crear evento de Google Calendar: ' . $response->get_error_message());
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code >= 200 && $status_code < 300) {
        error_log('📅 Evento de reserva creado en Google Calendar');
        return;
    }

    $body = wp_remote_retrieve_body($response);
    error_log(
        sprintf(
            '❌ Error al crear evento de Google Calendar: HTTP %d - %s',
            $status_code,
            $body
        )
    );
}


