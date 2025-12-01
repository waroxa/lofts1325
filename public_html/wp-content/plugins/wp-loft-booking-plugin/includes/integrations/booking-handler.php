<?php
defined('ABSPATH') || exit;

add_action('nd_booking_reservation_added_in_db', 'wp_loft_booking_handle_booking', 10, 23);

/**
 * Return the list of internal recipients who must be copied on guest notifications.
 *
 * @return array<int,string>
 */
function wp_loft_booking_is_blocked_email($email) {
    $normalized = strtolower(trim((string) $email));

    return in_array($normalized, ['waroxa@gmail.com'], true);
}

function wp_loft_booking_parse_email_list($value) {
    $valid = [];

    if (is_string($value)) {
        $value = preg_split('/[,\n]+/', $value);
    }

    if (!is_array($value)) {
        $value = [];
    }

    foreach ($value as $address) {
        $address = sanitize_email((string) $address);

        if ($address && is_email($address) && !wp_loft_booking_is_blocked_email($address)) {
            $valid[strtolower($address)] = $address; // prevent duplicates
        }
    }

    return array_values($valid);
}

function wp_loft_booking_get_notification_recipients() {
    $default_recipients = array_filter([
        'info@loft1325.com',
        'reservation@loft1325.com',
        'concierge@loft1325.com',
        get_option('admin_email'),
        'maria@websitesmdla.com',
    ], static function ($email) {
        return $email && !wp_loft_booking_is_blocked_email($email);
    });

    $default = implode(",\n", $default_recipients);

    $stored = get_option('loft_booking_notification_recipients', $default);

    return wp_loft_booking_parse_email_list($stored);
}

function wp_loft_booking_get_invoice_recipients() {
    $fallback = get_option('loft_booking_notification_recipients', '');
    $stored   = get_option('loft_booking_invoice_recipients', $fallback);

    $list = wp_loft_booking_parse_email_list($stored);

    if (empty($list)) {
        return wp_loft_booking_parse_email_list($fallback);
    }

    return $list;
}

function wp_loft_booking_get_cleaning_recipients() {
    $fallback = get_option('loft_booking_notification_recipients', '');
    $stored   = get_option('loft_booking_cleaning_recipients', $fallback);

    $list = wp_loft_booking_parse_email_list($stored);

    if (empty($list)) {
        return wp_loft_booking_parse_email_list($fallback);
    }

    return $list;
}

function wp_loft_booking_default_template_keys() {
    return [
        'guest-confirmation' => __('Guest confirmation', 'wp-loft-booking'),
        'guest-receipt'      => __('Guest invoice/receipt', 'wp-loft-booking'),
        'guest-post-stay'    => __('Post-stay follow-up', 'wp-loft-booking'),
    ];
}

function wp_loft_booking_get_auto_send_settings() {
    $defaults = [
        'global' => array_fill_keys(array_keys(wp_loft_booking_default_template_keys()), true),
        'lofts'  => [],
    ];

    $settings = get_option('loft_email_auto_send', []);

    if (!is_array($settings)) {
        $settings = [];
    }

    $settings = wp_parse_args($settings, $defaults);

    foreach ($defaults['global'] as $template_key => $enabled) {
        if (!isset($settings['global'][$template_key])) {
            $settings['global'][$template_key] = $enabled;
        }
    }

    return $settings;
}

function wp_loft_booking_should_auto_send($template_key, $loft_id = 0) {
    $settings = wp_loft_booking_get_auto_send_settings();

    $value = $settings['global'][$template_key] ?? true;

    if ($loft_id && isset($settings['lofts'][$loft_id][$template_key])) {
        $value = $settings['lofts'][$loft_id][$template_key];
    }

    return (bool) $value;
}

function wp_loft_booking_calculate_post_stay_send_at(array $booking) {
    $date_to = $booking['date_to'] ?? '';

    if (empty($date_to)) {
        return null;
    }

    $timezone_string = get_option('timezone_string');
    if (empty($timezone_string)) {
        $timezone_string = 'America/Toronto';
    }

    try {
        $tz        = new DateTimeZone($timezone_string);
        $checkout  = new DateTime($date_to, $tz);
        $checkout->setTime(11, 0, 0);
        $checkout->modify('+2 hours');

        return $checkout->getTimestamp();
    } catch (Exception $e) {
        error_log('⚠️ Unable to schedule post-stay email: ' . $e->getMessage());
    }

    return null;
}

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

if (!function_exists('wp_loft_booking_normalize_unit_label_for_lookup')) {
    /**
     * Create a canonical, lowercase label for loft matching.
     *
     * @param string $label Raw label value.
     * @return string Normalized label ready for comparisons.
     */
    function wp_loft_booking_normalize_unit_label_for_lookup($label)
    {
        $label = remove_accents((string) $label);
        $label = strtolower($label);
        $label = preg_replace('/[^a-z0-9]+/', ' ', $label);

        return trim(preg_replace('/\s+/', ' ', $label));
    }
}

if (!function_exists('wp_loft_booking_find_unit_by_label')) {
    /**
     * Find a loft unit by its human-facing label, ignoring case and punctuation.
     *
     * @param string $label Loft label coming from ND Booking/WordPress.
     * @return array{id:int,unit_name:string}|null Matching unit record or null when none found.
     */
    function wp_loft_booking_find_unit_by_label($label)
    {
        global $wpdb;

        $targets = array_filter(
            array_unique(
                array(
                    wp_loft_booking_normalize_unit_label_for_lookup($label),
                    wp_loft_booking_normalize_unit_label_for_lookup(wp_loft_booking_format_unit_label($label)),
                )
            )
        );

        if (empty($targets)) {
            return null;
        }

        $units_table = $wpdb->prefix . 'loft_units';
        $units       = $wpdb->get_results("SELECT id, unit_name FROM {$units_table}");

        foreach ($units as $unit) {
            $normalized_unit_labels = array_filter(
                array_unique(
                    array(
                        wp_loft_booking_normalize_unit_label_for_lookup($unit->unit_name),
                        wp_loft_booking_normalize_unit_label_for_lookup(wp_loft_booking_format_unit_label($unit->unit_name)),
                    )
                )
            );

            if (!empty(array_intersect($targets, $normalized_unit_labels))) {
                return [
                    'id'        => (int) $unit->id,
                    'unit_name' => (string) $unit->unit_name,
                ];
            }
        }

        return null;
    }
}

if (!function_exists('wp_loft_booking_apply_virtual_key_lead_time')) {
    /**
     * Ensure the virtual key check-in time respects the minimum lead time.
     *
     * @param DateTime     $checkin_local  Proposed local check-in.
     * @param DateTime     $checkout_local Local check-out.
     * @param DateTimeZone $timezone       Property timezone.
     *
     * @return DateTime|WP_Error Adjusted check-in on success, WP_Error otherwise.
     */
    function wp_loft_booking_apply_virtual_key_lead_time($checkin_local, $checkout_local, $timezone)
    {
        $lead_time_minutes = (int) apply_filters('wp_loft_booking_virtual_key_lead_time_minutes', 5);

        if ($lead_time_minutes < 0) {
            $lead_time_minutes = 0;
        }

        try {
            $minimum_start = new DateTime('now', $timezone);
        } catch (Exception $e) {
            return new WP_Error('loft_virtual_key_time_error', $e->getMessage());
        }

        if ($lead_time_minutes > 0) {
            $minimum_start->modify(sprintf('+%d minutes', $lead_time_minutes));
        }

        if ($checkin_local <= $minimum_start) {
            $checkin_local = clone $minimum_start;
        }

        if ($checkin_local >= $checkout_local) {
            return new WP_Error(
                'loft_virtual_key_window_invalid',
                __('La période du séjour doit dépasser l\'heure d\'arrivée. / The stay window must extend beyond the arrival time.', 'wp-loft-booking')
            );
        }

        return $checkin_local;
    }
}

if (!function_exists('wp_loft_booking_format_currency')) {
    /**
     * Format an amount using a currency code.
     *
     * @param float|int|string $amount   Amount to format.
     * @param string           $currency Currency code (e.g., CAD).
     *
     * @return string
     */
    function wp_loft_booking_format_currency($amount, $currency = 'CAD')
    {
        $numeric_amount = is_numeric($amount) ? (float) $amount : floatval(preg_replace('/[^0-9\.,-]/', '', (string) $amount));

        return sprintf('%s %s', number_format($numeric_amount, 2), strtoupper($currency ?: 'CAD'));
    }
}

if (!function_exists('wp_loft_booking_format_tax_rate')) {
    /**
     * Format a tax rate with up to three decimals (for 9.975% TVQ compliance).
     *
     * @param float|int|string $rate Raw tax rate value.
     *
     * @return string
     */
    function wp_loft_booking_format_tax_rate($rate)
    {
        $numeric_rate = is_numeric($rate)
            ? (float) $rate
            : floatval(preg_replace('/[^0-9\.,-]/', '', (string) $rate));

        $formatted = number_format($numeric_rate, 3, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}

if (!function_exists('wp_loft_booking_get_tax_registration_numbers')) {
    /**
     * Retrieve the TPS and TVQ registration numbers for invoice rendering.
     *
     * @return array{tps:string,tvq:string}
     */
    function wp_loft_booking_get_tax_registration_numbers()
    {
        $numbers = [
            'tps' => '142422344 RT 0001',
            'tvq' => '1021287543 TQ 0001',
        ];

        /**
         * Filter the registration numbers displayed on invoices and receipts.
         */
        return apply_filters('wp_loft_booking_tax_registration_numbers', $numbers);
    }
}

if (!function_exists('wp_loft_booking_record_virtual_key_log')) {
    /**
     * Persist an audit log of generated virtual keys.
     *
     * @param int|string|null $booking_id   Booking identifier (optional).
     * @param int|string|null $unit_id      Loft/unit identifier.
     * @param int|string|null $keychain_id  ButterflyMX keychain ID.
     * @param array           $virtual_keys List of virtual key IDs.
     * @param string|null     $valid_from   Start datetime (any parseable format).
     * @param string|null     $valid_until  End datetime (any parseable format).
     *
     * @return bool True on success, false if the log table is unavailable or the insert fails.
     */
    function wp_loft_booking_record_virtual_key_log($booking_id, $unit_id, $keychain_id, array $virtual_keys = [], $valid_from = null, $valid_until = null)
    {
        global $wpdb;

        static $table_exists = null;

        $table_name = $wpdb->prefix . 'loft_virtual_key_logs';

        if (null === $table_exists) {
            $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) === $table_name;
        }

        if (!$table_exists) {
            return false;
        }

        $clean_keys = array_values(array_filter(array_map('strval', $virtual_keys), 'strlen'));

        $data = [];
        $formats = [];

        if (!empty($booking_id)) {
            $data['booking_id'] = (int) $booking_id;
            $formats[]          = '%d';
        }

        if (!empty($unit_id)) {
            $data['loft_id'] = (int) $unit_id;
            $formats[]       = '%d';
        }

        if (!empty($keychain_id)) {
            $data['keychain_id'] = (int) $keychain_id;
            $formats[]           = '%d';
        }

        if (!empty($clean_keys)) {
            $data['virtual_key_ids'] = wp_json_encode($clean_keys);
            $formats[]               = '%s';
        }

        if (!empty($valid_from)) {
            $data['valid_from'] = gmdate('Y-m-d H:i:s', strtotime($valid_from));
            $formats[]          = '%s';
        }

        if (!empty($valid_until)) {
            $data['valid_until'] = gmdate('Y-m-d H:i:s', strtotime($valid_until));
            $formats[]           = '%s';
        }

        if (empty($data)) {
            return false;
        }

        return false !== $wpdb->insert($table_name, $data, $formats);
    }
}

/**
 * Build a normalized booking payload using ND Booking records and custom data.
 *
 * @param int   $booking_id ND Booking record ID.
 * @param array $overrides  Values that should take precedence over DB values.
 *
 * @return array<string,mixed>
 */
function wp_loft_booking_build_booking_payload($booking_id, array $overrides = []) {
    $booking = wp_loft_booking_fetch_nd_booking($booking_id);

    foreach ($overrides as $key => $value) {
        if (null !== $value && '' !== $value) {
            $booking[$key] = $value;
        }
    }

    return $booking;
}

/**
 * Retrieve an ND Booking entry and normalize it for email notifications.
 *
 * @param int $booking_id Booking ID from the nd_booking_booking table.
 *
 * @return array<string,mixed>
 */
function wp_loft_booking_fetch_nd_booking($booking_id) {
    global $wpdb;

    $table = $wpdb->prefix . 'nd_booking_booking';

    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $booking_id),
        ARRAY_A
    );

    if (empty($row)) {
        return [];
    }

    $room_id   = isset($row['id_post']) ? (int) $row['id_post'] : 0;
    $room_name = $room_id > 0 ? get_the_title($room_id) : '';

    $matched_unit = wp_loft_booking_find_unit_by_label($room_name);

    return [
        'room_id'        => $room_id,
        'room_name'      => $room_name,
        'unit_id'        => $matched_unit['id'] ?? 0,
        'unit_name'      => $matched_unit['unit_name'] ?? '',
        'name'           => $row['user_first_name'] ?? '',
        'surname'        => $row['user_last_name'] ?? '',
        'email'          => $row['paypal_email'] ?? '',
        'phone'          => $row['user_phone'] ?? '',
        'address'        => $row['user_address'] ?? '',
        'city'           => $row['user_city'] ?? '',
        'country'        => $row['user_country'] ?? '',
        'date_from'      => $row['date_from'] ?? '',
        'date_to'        => $row['date_to'] ?? '',
        'created_at'     => $row['date'] ?? '',
        'total'          => isset($row['final_trip_price']) ? (float) $row['final_trip_price'] : 0.0,
        'currency'       => $row['paypal_currency'] ?? 'CAD',
        'payment_status' => $row['paypal_payment_status'] ?? '',
        'transaction_id' => $row['paypal_tx'] ?? '',
        'extra_services' => $row['extra_services'] ?? '',
        'coupon'         => $row['user_coupon'] ?? '',
        'arrival_time'   => $row['user_arrival'] ?? '',
        'message'        => $row['user_message'] ?? '',
        'guests'         => isset($row['guests']) ? (int) $row['guests'] : 0,
        'action_type'    => $row['action_type'] ?? '',
    ];
}

/**
 * Send all email notifications for a booking event.
 *
 * @param array                 $booking            Normalized booking payload.
 * @param array|WP_Error        $virtual_key_result Result from the virtual key generator.
 * @param bool                  $is_manual          Flag to annotate manual sends.
 *
 * @return void
 */
function wp_loft_booking_send_all_booking_emails(array $booking, $virtual_key_result, $is_manual = false) {
    $loft_id = isset($booking['room_id']) ? (int) $booking['room_id'] : 0;

    if (wp_loft_booking_should_auto_send('guest-confirmation', $loft_id)) {
        wp_loft_booking_send_confirmation_email($booking, $virtual_key_result, $is_manual);
    }

    if (wp_loft_booking_should_auto_send('guest-receipt', $loft_id)) {
        wp_loft_booking_send_receipt_email($booking, $virtual_key_result, $is_manual);
    }

    if (wp_loft_booking_should_auto_send('guest-post-stay', $loft_id)) {
        $send_at = wp_loft_booking_calculate_post_stay_send_at($booking);
        wp_loft_booking_send_post_stay_email($booking, $is_manual, ['send_at' => $send_at]);
    }

    wp_loft_booking_send_admin_summary_email($booking, $virtual_key_result, $is_manual);

    wp_loft_booking_send_cleaning_email($booking, $is_manual);
}

if (!function_exists('wp_loft_booking_parse_extra_services')) {
    /**
     * Parse the ND Booking extra services string into a structured list.
     *
     * @param string $raw_services Raw extra services string from ND Booking.
     *
     * @return array{items: array<int,array{id:int,title:string,price:float,price_raw:string}>, total: float}
     */
    function wp_loft_booking_parse_extra_services($raw_services)
    {
        $result = [
            'items' => [],
            'total' => 0.0,
        ];

        if (empty($raw_services) || !is_string($raw_services)) {
            return $result;
        }

        $entries = array_filter(array_map('trim', explode(',', $raw_services)));

        foreach ($entries as $entry) {
            if ('' === $entry) {
                continue;
            }

            $parts = explode('[', $entry);
            $service_id = isset($parts[0]) ? intval($parts[0]) : 0;
            $price_raw = isset($parts[1]) ? str_replace(']', '', $parts[1]) : '';

            $price = is_numeric($price_raw)
                ? (float) $price_raw
                : floatval(preg_replace('/[^0-9\.,-]/', '', $price_raw));

            $title = $service_id > 0 ? get_the_title($service_id) : '';

            if ('' === $title) {
                $title = sprintf(__('Service #%d', 'wp-loft-booking'), max(1, $service_id));
            }

            $result['items'][] = [
                'id'        => $service_id,
                'title'     => $title,
                'price'     => $price,
                'price_raw' => $price_raw,
            ];

            $result['total'] += $price;
        }

        $result['total'] = round($result['total'], 2);

        return $result;
    }
}

if (!function_exists('wp_loft_booking_calculate_price_breakdown')) {
    /**
     * Calculate a detailed price breakdown using ND Booking helper functions when available.
     *
     * @param array $booking Booking payload.
     *
     * @return array{
     *     subtotal: float,
     *     extras: array<int,array{id:int,title:string,price:float}>,
     *     extras_total: float,
     *     taxes: array<string,array{label:string,rate:float,amount:float}>,
     *     tax_total: float,
     *     total: float,
     *     lodging_subtotal: float
     * }
     */
function wp_loft_booking_calculate_price_breakdown($booking)
{
        $total = isset($booking['total']) ? (float) $booking['total'] : 0.0;
        $currency = isset($booking['currency']) && $booking['currency'] ? $booking['currency'] : 'CAD';

        $extras = wp_loft_booking_parse_extra_services($booking['extra_services'] ?? '');

        $tax_breakdown = function_exists('nd_booking_calculate_tax_breakdown_from_total')
            ? nd_booking_calculate_tax_breakdown_from_total($total)
            : [
                'base'      => round($total, 2),
                'taxes'     => [],
                'total_tax' => 0.0,
                'total'     => round($total, 2),
            ];

        $tax_total = isset($tax_breakdown['total_tax']) ? (float) $tax_breakdown['total_tax'] : 0.0;
        $base = isset($tax_breakdown['base']) ? (float) $tax_breakdown['base'] : ($total - $tax_total);

        $extras_total = $extras['total'];
        $lodging_subtotal = max(0.0, round($base - $extras_total, 2));

        return [
            'subtotal'        => round($base, 2),
            'extras'          => $extras['items'],
            'extras_total'    => $extras_total,
            'taxes'           => isset($tax_breakdown['taxes']) && is_array($tax_breakdown['taxes']) ? $tax_breakdown['taxes'] : [],
            'tax_total'       => round($tax_total, 2),
            'total'           => round($total, 2),
            'currency'        => $currency,
            'lodging_subtotal'=> $lodging_subtotal,
        ];
}

/**
 * Build a stable hash that represents the current booking/charge state for invoice artifacts.
 *
 * @param array $booking
 * @param array $price_breakdown
 *
 * @return string
 */
function wp_loft_booking_build_invoice_fingerprint(array $booking, array $price_breakdown)
{
        $normalized_extras = $price_breakdown['extras'] ?? [];
        $normalized_taxes  = $price_breakdown['taxes'] ?? [];

        usort($normalized_extras, function ($a, $b) {
                return strcmp($a['title'] ?? '', $b['title'] ?? '');
        });

        usort($normalized_taxes, function ($a, $b) {
                return strcmp($a['label'] ?? '', $b['label'] ?? '');
        });

        $data = [
                'booking_id'  => $booking['booking_id'] ?? $booking['room_id'] ?? 0,
                'room_id'     => $booking['room_id'] ?? 0,
                'guest'       => trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? '')),
                'dates'       => [
                        'from' => $booking['date_from'] ?? '',
                        'to'   => $booking['date_to'] ?? '',
                ],
                'charges'     => [
                        'subtotal'         => $price_breakdown['subtotal'] ?? 0,
                        'extras_total'     => $price_breakdown['extras_total'] ?? 0,
                        'tax_total'        => $price_breakdown['tax_total'] ?? 0,
                        'total'            => $price_breakdown['total'] ?? 0,
                        'currency'         => $price_breakdown['currency'] ?? 'CAD',
                        'lodging_subtotal' => $price_breakdown['lodging_subtotal'] ?? 0,
                        'extras'           => array_values($normalized_extras),
                        'taxes'            => array_values($normalized_taxes),
                ],
                'payment'     => [
                        'status' => $booking['payment_status'] ?? '',
                        'txn'    => $booking['transaction_id'] ?? '',
                ],
        ];

        return substr(sha1(wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 16);
}

/**
 * Render the invoice HTML body in a deterministic way for archival and reuse.
 *
 * @param array $booking
 * @param array $price_breakdown
 *
 * @return string
 */
function wp_loft_booking_render_invoice_html(array $booking, array $price_breakdown)
{
        $guest_name  = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? '')) ?: __('Invité', 'wp-loft-booking');
        $room_name   = wp_loft_booking_format_unit_label($booking['room_name'] ?? '') ?: __('Votre loft', 'wp-loft-booking');
        $booking_ref = $booking['booking_id'] ?? $booking['room_id'] ?? __('N/A', 'wp-loft-booking');
        $checkin     = !empty($booking['date_from']) ? wp_date('Y-m-d', strtotime($booking['date_from'])) : __('N/A', 'wp-loft-booking');
        $checkout    = !empty($booking['date_to']) ? wp_date('Y-m-d', strtotime($booking['date_to'])) : __('N/A', 'wp-loft-booking');
        $currency    = $price_breakdown['currency'] ?? 'CAD';

        $extras = $price_breakdown['extras'] ?? [];
        $taxes  = $price_breakdown['taxes'] ?? [];

        usort($extras, function ($a, $b) {
                return strcmp($a['title'] ?? '', $b['title'] ?? '');
        });

        usort($taxes, function ($a, $b) {
                return strcmp($a['label'] ?? '', $b['label'] ?? '');
        });

        $payment_status = $booking['payment_status'] ?? __('Unknown', 'wp-loft-booking');
        $transaction_id = $booking['transaction_id'] ?? __('Not provided', 'wp-loft-booking');
        $support_email  = 'reservation@loft1325.com';
        $tax_numbers    = wp_loft_booking_get_tax_registration_numbers();

        $admin_email = sanitize_email(get_option('admin_email'));
        if ($admin_email && !wp_loft_booking_is_blocked_email($admin_email)) {
            $support_email = $admin_email;
        }

        ob_start();
        ?>
        <div style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#0f172a;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f4f6;padding:28px 0;">
                <tr>
                    <td align="center" style="padding:0 16px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="760" style="width:100%;max-width:760px;background-color:#ffffff;border-radius:28px;overflow:hidden;box-shadow:0 30px 52px rgba(15,23,42,0.14);">
                            <tr>
                                <td style="padding:36px;background:linear-gradient(135deg,#0f172a,#0b1222);text-align:center;">
                                    <img src="https://loft1325.com/wp-content/uploads/2024/06/Asset-1.png" alt="Loft 1325" style="max-width:220px;width:100%;height:auto;display:block;margin:0 auto 12px;">
                                    <p style="margin:0;font-size:12px;letter-spacing:0.32em;text-transform:uppercase;color:#cbd5e1;">Loft 1325 &middot; Val-d’Or</p>
                                    <p style="margin:12px 0 0;font-size:17px;color:#f3f4f6;font-weight:700;">Reçu de paiement &bull; Payment Receipt</p>
                                    <p style="margin:6px 0 0;font-size:13px;color:#e5e7eb;">Expérience de séjour signature &middot; Signature Stay Experience</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:28px 36px 8px;">
                                    <p style="margin:0 0 6px;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#475569;font-weight:800;">Reçu &ndash; Invoice</p>
                                    <p style="margin:0 0 12px;font-size:24px;font-weight:800;color:#0f172a;">Séjour confirmé | Stay confirmed</p>
                                    <p style="margin:0 0 10px;font-size:15px;line-height:1.6;color:#334155;">Réservation #<?php echo esc_html($booking_ref); ?> &middot; <?php echo esc_html($room_name); ?>.<br>Dates&nbsp;: <?php echo esc_html($checkin); ?> → <?php echo esc_html($checkout); ?>.</p>
                                    <div style="margin:12px 0 20px;display:flex;gap:12px;flex-wrap:wrap;">
                                        <span style="display:inline-block;padding:10px 14px;border-radius:12px;background-color:#0f172a;color:#f8fafc;font-size:13px;font-weight:700;">Statut&nbsp;/ Status: <?php echo esc_html($payment_status); ?></span>
                                        <span style="display:inline-block;padding:10px 14px;border-radius:12px;background-color:#e0f2fe;color:#075985;font-size:13px;font-weight:700;">Transaction Stripe: <?php echo esc_html($transaction_id); ?></span>
                                    </div>
                                    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#334155;">Paiement traité via Stripe. Pour l’entrée dans Sage, réutilisez le numéro de transaction <strong><?php echo esc_html($transaction_id); ?></strong> comme référence unique.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 36px 24px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:separate;border-spacing:0 12px;">
                                        <tr>
                                            <td style="width:50%;vertical-align:top;">
                                                <div style="padding:18px;border:1px solid #e5e7eb;border-radius:16px;background-color:#f9fafb;">
                                                    <p style="margin:0 0 6px;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;color:#6b7280;font-weight:700;">Invité</p>
                                                    <p style="margin:0;font-size:15px;font-weight:700;color:#0f172a;"><?php echo esc_html($guest_name); ?></p>
                                                    <p style="margin:6px 0 0;font-size:14px;color:#374151;">Courriel / Email<br><strong><?php echo esc_html($booking['email'] ?? __('N/A', 'wp-loft-booking')); ?></strong></p>
                                                </div>
                                            </td>
                                            <td style="width:50%;vertical-align:top;">
                                                <div style="padding:18px;border:1px solid #e5e7eb;border-radius:16px;background-color:#f9fafb;">
                                                    <p style="margin:0 0 6px;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;color:#6b7280;font-weight:700;">Séjour / Stay</p>
                                                    <p style="margin:0;font-size:15px;font-weight:700;color:#0f172a;"><?php echo esc_html($room_name); ?></p>
                                                    <p style="margin:6px 0 0;font-size:14px;color:#374151;">Arrivée<br><strong><?php echo esc_html($checkin); ?></strong></p>
                                                    <p style="margin:6px 0 0;font-size:14px;color:#374151;">Départ<br><strong><?php echo esc_html($checkout); ?></strong></p>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 36px 16px;">
                                    <div style="padding:22px;border-radius:20px;background-color:#fffbeb;border:1px solid #fcd34d;box-shadow:0 18px 34px rgba(217,119,6,0.16);color:#92400e;">
                                        <h3 style="margin:0 0 12px;font-size:17px;font-weight:900;color:#92400e;">Détails du paiement &middot; Payment details</h3>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                                            <tr>
                                                <td style="padding:6px 0;color:#b45309;font-weight:800;">Statut / Status</td>
                                                <td style="padding:6px 0;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html($payment_status); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#b45309;font-weight:800;">Transaction (Stripe)</td>
                                                <td style="padding:6px 0;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html($transaction_id); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#b45309;font-weight:800;">Sous-total hébergement / Lodging subtotal</td>
                                                <td style="padding:6px 0;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['lodging_subtotal'] ?? 0, $currency)); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#b45309;font-weight:800;">Services additionnels / Extras</td>
                                                <td style="padding:6px 0;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['extras_total'] ?? 0, $currency)); ?></td>
                                            </tr>
                                            <?php if (!empty($extras)) : ?>
                                                <tr>
                                                    <td colspan="2" style="padding:6px 0 0;">
                                                        <ul style="margin:6px 0 0;padding-left:18px;color:#92400e;font-size:13px;">
                                                            <?php foreach ($extras as $extra) : ?>
                                                                <li style="font-weight:800;"><?php echo esc_html($extra['title']); ?> &middot; <?php echo esc_html(wp_loft_booking_format_currency($extra['price'] ?? 0, $currency)); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($taxes)) : ?>
                                                <?php foreach ($taxes as $tax) : ?>
                                                    <tr>
                                                        <td style="padding:6px 0;color:#92400e;font-weight:900;">Taxe / Tax &ndash; <?php echo esc_html($tax['label']); ?> (<?php echo esc_html(wp_loft_booking_format_tax_rate($tax['rate'] ?? 0)); ?>%)</td>
                                                        <td style="padding:6px 0;color:#7c2d12;text-align:right;font-weight:900;"><?php echo esc_html(wp_loft_booking_format_currency($tax['amount'] ?? 0, $currency)); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <tr>
                                                <td style="padding:12px 0 0;font-size:15px;font-weight:900;color:#92400e;border-top:1px solid rgba(217,119,6,0.35);">Total</td>
                                                <td style="padding:12px 0 0;font-size:16px;font-weight:900;color:#7c2d12;text-align:right;border-top:1px solid rgba(217,119,6,0.35);"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['total'] ?? 0, $currency)); ?></td>
                                            </tr>
                                        </table>
                                        <p style="margin:10px 0 0;font-size:13px;color:#92400e;font-weight:700;">Numéros de taxes / Tax numbers<br>TPS: <?php echo esc_html($tax_numbers['tps']); ?> &middot; TVQ: <?php echo esc_html($tax_numbers['tvq']); ?></p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 32px 8px;">
                                    <div style="padding:18px;border:1px solid #e5e7eb;border-radius:16px;background-color:#f9fafb;">
                                        <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#0f172a;">Notes pour la comptabilité | Accounting notes</p>
                                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#374151;line-height:1.6;">
                                            <li>Référence Stripe / Stripe reference&nbsp;: <strong><?php echo esc_html($transaction_id); ?></strong></li>
                                            <li>Code réservation / Booking ref&nbsp;: <strong><?php echo esc_html($booking_ref); ?></strong></li>
                                            <li>Utilisez ces deux identifiants dans Sage pour accélérer la saisie et l’appariement.</li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 32px 28px;">
                                    <div style="padding:18px;border-radius:16px;border:1px solid #e5e7eb;background-color:#ffffff;">
                                        <p style="margin:0 0 6px;font-size:14px;font-weight:700;color:#0f172a;">Contact</p>
                                        <p style="margin:0 0 6px;font-size:14px;color:#374151;">1325 3e Avenue, Val-d’Or, QC, Canada</p>
                                        <p style="margin:0;font-size:14px;color:#1d4ed8;">reservation@loft1325.com &bull; info@loft1325.com &bull; 514-239-9080</p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        <?php

        return trim((string) ob_get_clean());
}

/**
 * Render a minimal PDF document containing the invoice summary.
 *
 * @param array $booking
 * @param array $price_breakdown
 * @param string $fingerprint
 *
 * @return string PDF binary payload
 */
function wp_loft_booking_render_invoice_pdf(array $booking, array $price_breakdown, $fingerprint)
{
        $escape = function ($text) {
                $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $text);

                return preg_replace('/[\r\n]+/', ' ', $text);
        };

        $room_name       = wp_loft_booking_format_unit_label($booking['room_name'] ?? '') ?: __('Votre loft', 'wp-loft-booking');
        $guest_name      = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? '')) ?: __('Invité', 'wp-loft-booking');
        $booking_ref     = $booking['booking_id'] ?? $booking['room_id'] ?? __('N/A', 'wp-loft-booking');
        $currency        = $price_breakdown['currency'] ?? 'CAD';
        $payment_status  = $booking['payment_status'] ?? __('Unknown', 'wp-loft-booking');
        $transaction_id  = $booking['transaction_id'] ?? __('Not provided', 'wp-loft-booking');
        $tax_numbers     = wp_loft_booking_get_tax_registration_numbers();

        $lines = [];
        $lines[] = 'BT';
        $lines[] = '/F1 18 Tf';
        $lines[] = '50 760 Td';
	$lines[] = '(' . $escape('Loft 1325 · Payment Receipt / Reçu de paiement') . ') Tj';
	$lines[] = '0 -18 Td';
        $lines[] = '/F1 11 Tf';
        $lines[] = '(' . $escape('Expérience signature · Signature stay experience') . ') Tj';
        $lines[] = '0 -11 Td';
        $lines[] = '/F1 10 Tf';
        $lines[] = '(' . $escape('1325 3e Avenue, Val-d’Or, QC · reservation@loft1325.com · 514-239-9080') . ') Tj';
        $lines[] = '0 -12 Td';
        $lines[] = '(' . $escape(sprintf('TPS: %s · TVQ: %s', $tax_numbers['tps'] ?? '', $tax_numbers['tvq'] ?? '')) . ') Tj';

        $lines[] = '0 -26 Td';
        $lines[] = '/F1 12 Tf';
        $lines[] = '(' . $escape('Booking overview / Résumé de réservation') . ') Tj';
	$lines[] = '0 -14 Td';
	$lines[] = '/F1 10 Tf';
	$lines[] = '(' . $escape(sprintf('• Booking #: %s', $booking_ref)) . ') Tj';
	$lines[] = '0 -12 Td';
	$lines[] = '(' . $escape(sprintf('• Loft: %s', $room_name)) . ') Tj';
	$lines[] = '0 -12 Td';
	$lines[] = '(' . $escape(sprintf('• Guest: %s', $guest_name)) . ') Tj';
	$lines[] = '0 -12 Td';
	$lines[] = '(' . $escape(sprintf('• Stay: %s → %s', $booking['date_from'] ?? 'N/A', $booking['date_to'] ?? 'N/A')) . ') Tj';
	$lines[] = '0 -12 Td';
	$lines[] = '(' . $escape(sprintf('• Status: %s', $payment_status)) . ') Tj';

	$lines[] = '0 -20 Td';
	$lines[] = '/F1 12 Tf';
	$lines[] = '(' . $escape('Payment summary / Sommaire du paiement') . ') Tj';
	$lines[] = '0 -14 Td';
	$lines[] = '/F1 10 Tf';
	$lines[] = '(' . $escape(sprintf('• Lodging (before taxes): %s', wp_loft_booking_format_currency($price_breakdown['lodging_subtotal'] ?? 0, $currency))) . ') Tj';

	$extras = $price_breakdown['extras'] ?? [];
	usort($extras, function ($a, $b) {
		return strcmp($a['title'] ?? '', $b['title'] ?? '');
	});

	foreach ($extras as $extra) {
		$lines[] = '0 -12 Td';
		$lines[] = '(' . $escape(sprintf('• Extra – %s: %s', $extra['title'], wp_loft_booking_format_currency($extra['price'] ?? 0, $currency))) . ') Tj';
	}

	$taxes = $price_breakdown['taxes'] ?? [];
	usort($taxes, function ($a, $b) {
		return strcmp($a['label'] ?? '', $b['label'] ?? '');
	});

	$lines[] = '0 -16 Td';
	$lines[] = '/F1 12 Tf';
	$lines[] = '(' . $escape('Taxes / Taxes applicables') . ') Tj';
	$lines[] = '0 -14 Td';
	$lines[] = '/F1 10 Tf';

	foreach ($taxes as $tax) {
        $lines[] = '(' . $escape(sprintf('• %s (%s%%): %s', $tax['label'], wp_loft_booking_format_tax_rate($tax['rate'] ?? 0), wp_loft_booking_format_currency($tax['amount'] ?? 0, $currency))) . ') Tj';
		$lines[] = '0 -12 Td';
	}

	$lines[] = '0 -16 Td';
	$lines[] = '/F1 13 Tf';
	$lines[] = '(' . $escape(sprintf('Total payé / Amount received: %s', wp_loft_booking_format_currency($price_breakdown['total'] ?? 0, $currency))) . ') Tj';

	$lines[] = '0 -20 Td';
	$lines[] = '/F1 12 Tf';
	$lines[] = '(' . $escape('Références comptables / Accounting references') . ') Tj';
	$lines[] = '0 -14 Td';
	$lines[] = '/F1 10 Tf';
	$lines[] = '(' . $escape(sprintf('• Stripe transaction (Sage ref): %s', $transaction_id)) . ') Tj';
	$lines[] = '0 -12 Td';
	$lines[] = '(' . $escape(sprintf('• Receipt fingerprint: %s', $fingerprint)) . ') Tj';
	$lines[] = '0 -12 Td';
	$lines[] = '(' . $escape(sprintf('• Booking reference: %s', $booking_ref)) . ') Tj';

	$lines[] = '0 -18 Td';
	$lines[] = '/F1 11 Tf';
	$lines[] = '(' . $escape('Support: reservation@loft1325.com · info@loft1325.com') . ') Tj';
	$lines[] = '0 -12 Td';
	$lines[] = '(' . $escape('Adresse: 1325 3e Avenue, Val-d’Or, QC J9P 5P5') . ') Tj';
	$lines[] = 'ET';

        $stream = implode("\n", $lines);
        $stream_length = strlen($stream);

        $objects = [
                1 => "<< /Type /Catalog /Pages 2 0 R >>",
                2 => "<< /Type /Pages /Count 1 /Kids [3 0 R] >>",
                3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>",
                4 => "<< /Length {$stream_length} >>\nstream\n{$stream}\nendstream",
                5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
                $offsets[$id] = strlen($pdf);
                $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $id, $body);
        }

        $xref_position = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n", count($objects) + 1);
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
                $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref_position . "\n%%EOF";

        return $pdf;
}

/**
 * Persist invoice artifacts (HTML + PDF) and record them in the audit table.
 *
 * @param array $booking
 * @param array $price_breakdown
 *
 * @return array|WP_Error
 */
function wp_loft_booking_store_invoice_artifact(array $booking, array $price_breakdown)
{
        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
                return new WP_Error('invoice_upload_unavailable', $uploads['error']);
        }

        $fingerprint = wp_loft_booking_build_invoice_fingerprint($booking, $price_breakdown);
        $directory   = trailingslashit($uploads['basedir']) . 'loft-invoices';

        if (!wp_mkdir_p($directory)) {
                return new WP_Error('invoice_directory_unwritable', __('Unable to prepare the invoice storage folder.', 'wp-loft-booking'));
        }

        $basename  = sprintf('invoice-%s-%s', $booking['booking_id'] ?? $booking['room_id'] ?? 'booking', $fingerprint);
        $html_body = wp_loft_booking_render_invoice_html($booking, $price_breakdown);
        $html_path = trailingslashit($directory) . $basename . '.html';
        $pdf_path  = trailingslashit($directory) . $basename . '.pdf';

        file_put_contents($html_path, $html_body);
        file_put_contents($pdf_path, wp_loft_booking_render_invoice_pdf($booking, $price_breakdown, $fingerprint));

        $artifact_url = trailingslashit($uploads['baseurl']) . 'loft-invoices/' . $basename . '.pdf';

        global $wpdb;
        $artifacts_table = $wpdb->prefix . 'loft_invoice_artifacts';

        $booking_condition = ' AND booking_id IS NULL';
        $params            = [$artifact_url];

        if (isset($booking['booking_id']) && is_numeric($booking['booking_id'])) {
                $booking_condition = ' AND booking_id = %d';
                $params[]          = (int) $booking['booking_id'];
        }

        $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                        "SELECT id FROM {$artifacts_table} WHERE artifact_url = %s{$booking_condition} ORDER BY id DESC LIMIT 1",
                        ...$params
                )
        );

        if ($existing_id) {
                $wpdb->update(
                        $artifacts_table,
                        ['updated_at' => current_time('mysql'), 'status' => 'stored'],
                        ['id' => (int) $existing_id],
                        ['%s', '%s'],
                        ['%d']
                );
        } else {
                $wpdb->insert(
                        $artifacts_table,
                        [
                                'booking_id'  => isset($booking['booking_id']) ? (int) $booking['booking_id'] : null,
                                'loft_id'     => isset($booking['room_id']) ? (int) $booking['room_id'] : null,
                                'artifact_url'=> $artifact_url,
                                'status'      => 'stored',
                        ],
                        ['%d', '%d', '%s', '%s']
                );

                $existing_id = $wpdb->insert_id;
        }

        return [
                'id'         => $existing_id ? (int) $existing_id : null,
                'html_path'  => $html_path,
                'pdf_path'   => $pdf_path,
                'artifact_url' => $artifact_url,
                'fingerprint' => $fingerprint,
        ];
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

    $requested_label = $title_post;

    $booking = [
        'booking_id'     => $id_post,
        'room_id'        => $id_post,
        'name'           => $user_first_name,
        'surname'        => $user_last_name,
        'email'          => $paypal_email,
        'phone'          => $user_phone,
        'address'        => $user_address,
        'city'           => $user_city,
        'country'        => $user_country,
        'date_from'      => $date_from,
        'date_to'        => $date_to,
        'created_at'     => $date,
        'room_name'      => $title_post,
        'total'          => $final_trip_price,
        'currency'       => $paypal_currency,
        'payment_status' => $paypal_payment_status,
        'transaction_id' => $paypal_tx,
        'extra_services' => $extra_services,
        'coupon'         => $user_coupon,
        'arrival_time'   => $user_arrival,
        'message'        => $user_message,
        'guests'         => $guests,
        'action_type'    => $action_type,
    ];

        $units_table    = $wpdb->prefix . 'loft_units';
        $bookings_table = $wpdb->prefix . 'loft_bookings';

        $has_valid_unit = !empty($booking['room_id']) && $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$units_table} WHERE id = %d", $booking['room_id'])
        );

        $matched_unit = wp_loft_booking_find_unit_by_label($booking['room_name'] ?? '');
        $resolved_by  = '';

        if ($matched_unit && ($booking['room_id'] !== $matched_unit['id'])) {
            // Prefer an exact label match over the raw ND Booking ID to keep the requested loft in sync.
            $booking['room_id']   = $matched_unit['id'];
            $booking['room_name'] = $matched_unit['unit_name'];
            $resolved_by          = 'label-match';
        } elseif (!$has_valid_unit) {
            $resolved_by = 'fallback-available';
            $available_unit = $wpdb->get_var(
                "SELECT id FROM {$units_table} WHERE status = 'available' ORDER BY unit_name ASC LIMIT 1"
            );

            if ($available_unit) {
                $booking['room_id'] = intval($available_unit);
            }
        }

        if (!empty($booking['room_id']) && $resolved_by) {
            $wpdb->update(
                $bookings_table,
                ['unit_id' => $booking['room_id']],
                ['id' => $id_post],
                ['%d'],
                ['%d']
            );

            error_log(sprintf(
                'ℹ️ Booking %d loft resolved via %s. Requested label: "%s" → Assigned ID %d (%s).',
                $id_post,
                $resolved_by,
                (string) $requested_label,
                (int) $booking['room_id'],
                $resolved_by === 'label-match' && $matched_unit ? $matched_unit['unit_name'] : 'fallback'
            ));
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

            $adjusted_checkin = wp_loft_booking_apply_virtual_key_lead_time($checkin_local, $checkout_local, $site_timezone);

            if (is_wp_error($adjusted_checkin)) {
                throw new Exception($adjusted_checkin->get_error_message());
            }

            $checkin_local = $adjusted_checkin;

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

        wp_loft_booking_send_all_booking_emails($booking, $virtual_key_result);

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

                wp_loft_booking_record_virtual_key_log(
                    $id_post,
                    $booking['room_id'],
                    $keychain_id,
                    $virtual_key_result['virtual_key_ids'] ?? [],
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

    $unit_label = trim(preg_replace('/\s+/', ' ', (string) ($unit->unit_name ?? '')));

    if ('' === $unit_label) {
        $unit_label = wp_loft_booking_format_unit_label($unit->unit_name ?? '');
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
        $site_timezone  = new DateTimeZone($timezone_string);
        $checkin_local  = new DateTime($date_from, $site_timezone);
        $checkout_local = new DateTime($date_to, $site_timezone);
    } catch (Exception $e) {
        error_log('❌ Unable to parse booking dates for ButterflyMX keychain: ' . $e->getMessage());
        return new WP_Error('invalid_dates', 'Invalid booking dates.');
    }

    $checkin_local->setTime(15, 0, 0);
    $checkout_local->setTime(11, 0, 0);

    $adjusted_checkin = wp_loft_booking_apply_virtual_key_lead_time($checkin_local, $checkout_local, $site_timezone);

    if (is_wp_error($adjusted_checkin)) {
        return $adjusted_checkin;
    }

    $checkin_local = $adjusted_checkin;

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

function wp_loft_booking_send_confirmation_email($booking, $virtual_key_result, $is_manual = false, array $options = []) {
    $recipients = wp_loft_booking_parse_email_list($options['recipient_override'] ?? ($booking['email'] ?? ''));

    if (empty($recipients)) {
        error_log('⚠️ Booking confirmation email skipped: invalid recipient.');

        return new WP_Error(
            'loft_email_invalid_recipient',
            __('Booking confirmation email skipped: invalid recipient.', 'wp-loft-booking')
        );
    }

    $recipient = array_shift($recipients);

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

    $price_breakdown = wp_loft_booking_calculate_price_breakdown($booking);
    $currency        = $price_breakdown['currency'] ?? 'CAD';

    $total = isset($booking['total']) && $booking['total'] !== '' ? sprintf('$%s', number_format((float) $booking['total'], 2)) : __('Non disponible', 'wp-loft-booking');

    $guest_count = isset($booking['guests']) ? (int) $booking['guests'] : 0;
    if ($guest_count > 0) {
        $guest_count_display_fr = $guest_count . ' ' . (1 === $guest_count ? 'invité' : 'invités');
        $guest_count_display_en = $guest_count . ' ' . (1 === $guest_count ? 'guest' : 'guests');
    } else {
        $guest_count_display_fr = 'Non précisé';
        $guest_count_display_en = 'Not specified';
    }

    $building_entry_instructions_fr = [
        'Utilisez le code 6 chiffres reçu par SMS ou courriel.',
        'Composez le code sur l’interphone ou le clavier, appuyez sur le <strong>3</strong> et <strong>#</strong>.',
        'Passez la porte intérieure, prenez l’ascenseur (porte devant vous) ou les escaliers.',
        'Pour le matériel de déménagement, l’ascenseur est au 2<sup>e</sup> étage.',
        'Pour assistance ou urgence, contactez le concierge au 514-239-9080.',
    ];

    $wayfinding_instructions_fr = [
        'Entrée principale au <strong>1325 3e Avenue</strong> (façade Loft 1325 sombre et logo métallique).',
        'Garez-vous derrière l’immeuble : suivez la 3e Avenue puis les panneaux «&nbsp;Loft 1325&nbsp;».',
        'L’interphone est à droite de la porte vitrée (ou du Logo) ; ascenseur et escaliers juste à l’entrée.',
        'Si la signalisation ne saute pas aux yeux, appelez&nbsp;: <strong>514-239-9080</strong> pour assistance.',
    ];

    $building_entry_instructions_en = [
        'Use the 6-digit code sent by SMS or email.',
        'Enter the code on the intercom or keypad, press <strong>3</strong> and then <strong>#</strong>.',
        'After the glass door, take the elevator (door ahead) or the stairs.',
        'Moving equipment and luggage carts are on the 2nd floor by the elevator.',
        'For assistance or emergencies, contact the concierge at 514-239-9080.',
    ];

    $wayfinding_instructions_en = [
        'Main entrance at <strong>1325 3e Avenue</strong> (dark Loft 1325 façade with metal logo).',
        'Guest parking is behind the building—follow 3e Avenue and the “Loft 1325” signs.',
        'The intercom is to the right of the glass door (or Logo); the elevator and stairs are immediately inside the entrance.',
        'If signage isn’t obvious, call us at <strong>514-239-9080</strong> for guidance.',
    ];

    $has_price_breakdown = is_array($price_breakdown) && !empty($price_breakdown);

    $total_display_fr = $total;
    $total_display_en = $total;
    $tax_total_display = '';
    $taxes_for_display = [];

    if ($has_price_breakdown && isset($price_breakdown['total'])) {
        $total_display_fr = wp_loft_booking_format_currency($price_breakdown['total'], $currency);
        $total_display_en = wp_loft_booking_format_currency($price_breakdown['total'], $currency);
        $tax_total_display = wp_loft_booking_format_currency($price_breakdown['tax_total'] ?? 0, $currency);
        $taxes_for_display = array_values($price_breakdown['taxes'] ?? []);
    } elseif ($total !== __('Non disponible', 'wp-loft-booking')) {
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

    $support_email = 'reservation@loft1325.com';

    $admin_email = sanitize_email(get_option('admin_email'));
    if ($admin_email && !wp_loft_booking_is_blocked_email($admin_email)) {
        $support_email = $admin_email;
    }
    $bcc           = isset($options['bcc_override'])
        ? wp_loft_booking_parse_email_list($options['bcc_override'])
        : [];

    if (empty($options['bcc_override'])) {
        foreach (wp_loft_booking_get_notification_recipients() as $internal_email) {
            if (strtolower($internal_email) !== strtolower($recipient)) {
                $bcc[] = $internal_email;
            }
        }
    }

    $subject = 'Lofts 1325 – Confirmation de réservation | Reservation Confirmation';

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
                                    <?php if (!empty($tax_total_display)) : ?>
                                        <tr>
                                            <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Taxes</td>
                                            <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;">
                                                <?php echo esc_html($tax_total_display); ?>
                                                <?php if (!empty($taxes_for_display)) : ?>
                                                    <div style="margin-top:6px;font-size:13px;color:#6b7280;font-weight:500;">
                                                        <?php foreach ($taxes_for_display as $tax) : ?>
                                                            <div><?php echo esc_html($tax['label']); ?> (<?php echo esc_html(wp_loft_booking_format_tax_rate($tax['rate'] ?? 0)); ?>%) &middot; <?php echo esc_html(wp_loft_booking_format_currency($tax['amount'] ?? 0, $currency)); ?></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Montant total (taxes incluses)</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;border-bottom-right-radius:18px;"><?php echo esc_html($total_display_fr); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:28px 0;padding:24px;border-radius:18px;background-color:#eef2ff;border:1px solid #c7d2fe;color:#0f172a;box-shadow:0 20px 40px rgba(15,23,42,0.08);">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#0f172a;">Accès et clé numérique</h3>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#111827;font-weight:600;letter-spacing:0.01em;">
                                        <?php echo esc_html($virtual_key_message_fr); ?>
                                    </p>
                                </div>
                                <div style="margin:0 0 24px;padding:24px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:18px;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Instructions d'accès au bâtiment</h3>
                                    <ol style="margin:0 0 12px;padding-left:20px;font-size:14px;line-height:1.7;color:#4b5563;">
                                        <?php foreach ($building_entry_instructions_fr as $instruction) : ?>
                                            <li><?php echo $instruction; ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </div>
                                <div style="margin:0 0 24px;padding:18px;border-radius:16px;background-color:#fff7ed;border:1px solid #fb923c;">
                                    <h3 style="margin:0 0 10px;font-size:15px;font-weight:700;color:#9a3412;">Repères pour nous trouver facilement</h3>
                                    <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.7;color:#7c2d12;">
                                        <?php foreach ($wayfinding_instructions_fr as $instruction) : ?>
                                            <li><?php echo $instruction; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Préparez votre arrivée</h3>
                                <ul style="margin:0 0 24px;padding-left:20px;font-size:14px;line-height:1.8;color:#4b5563;">
                                    <li>Arrivée à partir de 15&nbsp;h (heure de l’Est)</li>
                                    <li>Départ au plus tard à 11&nbsp;h (heure de l’Est)</li>
                                </ul>
                                <p style="margin:0 0 24px;font-size:13px;line-height:1.7;color:#6b7280;">Votre pièce d’identité téléversée lors de la réservation est déjà enregistrée pour votre dossier.</p>
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
                                    <?php if (!empty($tax_total_display)) : ?>
                                        <tr>
                                            <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Taxes</td>
                                            <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;">
                                                <?php echo esc_html($tax_total_display); ?>
                                                <?php if (!empty($taxes_for_display)) : ?>
                                                    <div style="margin-top:6px;font-size:13px;color:#6b7280;font-weight:500;">
                                                        <?php foreach ($taxes_for_display as $tax) : ?>
                                                            <div><?php echo esc_html($tax['label']); ?> (<?php echo esc_html(wp_loft_booking_format_tax_rate($tax['rate'] ?? 0)); ?>%) · <?php echo esc_html(wp_loft_booking_format_currency($tax['amount'] ?? 0, $currency)); ?></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Total amount (taxes included)</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;border-bottom-right-radius:18px;"><?php echo esc_html($total_display_en); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:28px 0;padding:24px;border-radius:18px;background-color:#eef2ff;border:1px solid #c7d2fe;color:#0f172a;box-shadow:0 20px 40px rgba(15,23,42,0.08);">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#0f172a;">Digital key &amp; access</h3>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#111827;font-weight:600;letter-spacing:0.01em;">
                                        <?php echo esc_html($virtual_key_message_en); ?>
                                    </p>
                                </div>
                                <div style="margin:0 0 24px;padding:24px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:18px;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Building entry instructions</h3>
                                    <ol style="margin:0 0 12px;padding-left:20px;font-size:14px;line-height:1.7;color:#4b5563;">
                                        <?php foreach ($building_entry_instructions_en as $instruction) : ?>
                                            <li><?php echo $instruction; ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </div>
                                <div style="margin:0 0 24px;padding:18px;border-radius:16px;background-color:#ecfeff;border:1px solid #06b6d4;">
                                    <h3 style="margin:0 0 10px;font-size:15px;font-weight:700;color:#0f172a;">Wayfinding to the lofts</h3>
                                    <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.7;color:#0f172a;">
                                        <?php foreach ($wayfinding_instructions_en as $instruction) : ?>
                                            <li><?php echo $instruction; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Before you arrive</h3>
                                <ul style="margin:0 0 24px;padding-left:20px;font-size:14px;line-height:1.8;color:#4b5563;">
                                    <li>Check-in available from 3:00&nbsp;PM (Eastern Time)</li>
                                    <li>Check-out by 11:00&nbsp;AM (Eastern Time)</li>
                                </ul>
                                <p style="margin:0 0 24px;font-size:13px;line-height:1.7;color:#6b7280;">The ID you uploaded during booking is already securely stored for your reservation record.</p>
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

    $message = [
        'to'      => array_merge([$recipient], $recipients),
        'subject' => $subject,
        'html'    => $body,
        'text'    => wp_strip_all_tags($body),
        'bcc'     => $bcc,
    ];

    $variables = [
        'guest_name'            => $guest_name,
        'room_name'             => $room_name,
        'checkin_fr'            => $checkin_fr,
        'checkout_fr'           => $checkout_fr,
        'checkin_en'            => $checkin,
        'checkout_en'           => $checkout,
        'guest_count_display_fr'=> $guest_count_display_fr,
        'guest_count_display_en'=> $guest_count_display_en,
        'total_display_fr'      => $total_display_fr,
        'total_display_en'      => $total_display_en,
        'virtual_key_message_fr'=> $virtual_key_message_fr,
        'virtual_key_message_en'=> $virtual_key_message_en,
        'tax_total_display'     => $tax_total_display,
        'taxes_for_display'     => $taxes_for_display,
        'building_entry_instructions_fr' => $building_entry_instructions_fr,
        'wayfinding_instructions_fr'     => $wayfinding_instructions_fr,
        'building_entry_instructions_en' => $building_entry_instructions_en,
        'wayfinding_instructions_en'     => $wayfinding_instructions_en,
        'property_address'      => $property_address,
        'support_email'         => $support_email,
        'booking_reference'     => $booking['booking_id'] ?? '',
    ];

    $job_id = wp_loft_email_provider_enqueue_job(
        $message,
        $booking,
        [
            'event'     => 'booking-confirmation',
            'template'  => 'guest-confirmation',
            'variables' => $variables,
            'source'    => $is_manual ? 'manual' : 'automatic',
            'dry_run'   => !empty($options['dry_run']),
            'send_at'   => $options['send_at'] ?? null,
            'force_new_job' => $is_manual || !empty($options['force_new_job']),
        ]
    );

    if (is_wp_error($job_id)) {
        error_log('❌ Booking confirmation email could not be queued for ' . $recipient . ': ' . $job_id->get_error_message());

        return $job_id;
    }

    error_log(sprintf('✅ Booking confirmation email queued as job #%d for %s', $job_id, $recipient));

    return $job_id;
}

function wp_loft_booking_send_receipt_email($booking, $virtual_key_result, $is_manual = false, array $options = []) {
    $recipients = wp_loft_booking_parse_email_list($options['recipient_override'] ?? ($booking['email'] ?? ''));

    if (empty($recipients)) {
        error_log('⚠️ Booking receipt email skipped: invalid recipient.');

        return new WP_Error(
            'loft_email_invalid_recipient',
            __('Booking receipt email skipped: invalid recipient.', 'wp-loft-booking')
        );
    }

    $recipient = array_shift($recipients);

    $guest_name = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? ''));
    if ('' === $guest_name) {
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

    $booking_timestamp = !empty($booking['created_at']) ? strtotime($booking['created_at']) : false;
    if (!$booking_timestamp) {
        $booking_timestamp = current_time('timestamp');
    }

    $purchase_date_fr = wp_date('j F Y \à H\hi', $booking_timestamp);
    $purchase_date_en = wp_date('F j, Y \a\t g:i A', $booking_timestamp);

    $currency = !empty($booking['currency']) ? strtoupper($booking['currency']) : 'CAD';

    $price_breakdown = wp_loft_booking_calculate_price_breakdown($booking);
    $tax_numbers     = wp_loft_booking_get_tax_registration_numbers();

    $invoice_artifact = wp_loft_booking_store_invoice_artifact($booking, $price_breakdown);
    $attachments     = [];

    if (is_wp_error($invoice_artifact)) {
        error_log('⚠️ Failed to store invoice artifact: ' . $invoice_artifact->get_error_message());
    }

    $payment_status = !empty($booking['payment_status']) ? $booking['payment_status'] : __('Unknown', 'wp-loft-booking');
    $transaction_id = !empty($booking['transaction_id']) ? $booking['transaction_id'] : __('Not provided', 'wp-loft-booking');

    $virtual_key_success = !is_wp_error($virtual_key_result);

    $logo_url         = 'https://loft1325.com/wp-content/uploads/2024/06/Asset-1.png';
    $property_address = '1325 3e Avenue, Val-d’Or, QC, Canada';
    $website_url      = 'https://loft1325.com';
    $support_email    = 'reservation@loft1325.com';

    $admin_email = sanitize_email(get_option('admin_email'));
    if ($admin_email && !wp_loft_booking_is_blocked_email($admin_email)) {
        $support_email = $admin_email;
    }

    $bcc = isset($options['bcc_override'])
        ? wp_loft_booking_parse_email_list($options['bcc_override'])
        : [];

    if (empty($options['bcc_override'])) {
        foreach (wp_loft_booking_get_notification_recipients() as $internal_email) {
            if (strtolower($internal_email) !== strtolower($recipient)) {
                $bcc[] = $internal_email;
            }
        }
    }

    $subject = 'Lofts 1325 – Reçu de paiement | Payment Receipt';

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
                        </tr>
                        <tr>
                            <td style="padding:0 40px 0;">
                                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#374151;">Merci pour votre paiement. Nous confirmons la réception de <strong><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['total'], $currency)); ?></strong>. Voici votre reçu détaillé pour votre séjour dans <strong><?php echo esc_html($room_name); ?></strong>.</p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Résumé du paiement</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Réservation</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;">#<?php echo esc_html($booking['room_id']); ?> &middot; <?php echo esc_html($room_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Date d'achat</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($purchase_date_fr); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Statut du paiement</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($payment_status); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Transaction</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;border-bottom-right-radius:18px;"><?php echo esc_html($transaction_id); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:0 0 24px;padding:24px;background-color:#fffbeb;border:1px solid #fcd34d;border-radius:18px;box-shadow:0 16px 30px rgba(217,119,6,0.18);color:#92400e;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:900;color:#92400e;">Détails du paiement</h3>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Hébergement (avant taxes)</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['lodging_subtotal'], $currency)); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Services additionnels</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['extras_total'], $currency)); ?></td>
                                        </tr>
                                        <?php if (!empty($price_breakdown['extras'])) : ?>
                                            <tr>
                                                <td colspan="2" style="padding:6px 0 0;">
                                                    <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;color:#92400e;">
                                                        <?php foreach ($price_breakdown['extras'] as $extra) : ?>
                                                            <li style="font-weight:800;"><?php echo esc_html($extra['title']); ?> &middot; <?php echo esc_html(wp_loft_booking_format_currency($extra['price'], $currency)); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($price_breakdown['taxes'] as $tax) : ?>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#92400e;font-weight:900;">&nbsp;<?php echo esc_html($tax['label']); ?> (<?php echo esc_html(wp_loft_booking_format_tax_rate($tax['rate'])); ?>%)</td>
                                                <td style="padding:6px 0;font-size:14px;color:#7c2d12;text-align:right;font-weight:900;">&nbsp;<?php echo esc_html(wp_loft_booking_format_currency($tax['amount'], $currency)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td style="padding:12px 0 0;font-size:15px;font-weight:900;color:#92400e;border-top:1px solid rgba(217,119,6,0.35);">Total</td>
                                            <td style="padding:12px 0 0;font-size:16px;font-weight:900;color:#7c2d12;text-align:right;border-top:1px solid rgba(217,119,6,0.35);"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['total'], $currency)); ?></td>
                                        </tr>
                                    </table>
                                    <p style="margin:10px 0 0;font-size:13px;color:#92400e;font-weight:700;">Numéros de taxes / Tax numbers&nbsp;: TPS <?php echo esc_html($tax_numbers['tps']); ?> &middot; TVQ <?php echo esc_html($tax_numbers['tvq']); ?></p>
                                </div>
                                <?php if (!empty($booking['coupon'])) : ?>
                                    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#4b5563;">Code promotionnel appliqué&nbsp;: <strong><?php echo esc_html($booking['coupon']); ?></strong></p>
                                <?php endif; ?>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#4b5563;">Dates du séjour&nbsp;: <?php echo esc_html($checkin_fr); ?> &ndash; <?php echo esc_html($checkout_fr); ?></p>
                                <?php if ($virtual_key_success) : ?>
                                    <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#1d4ed8;">Votre clé numérique sera envoyée automatiquement avant votre arrivée.</p>
                                <?php else : ?>
                                    <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#dc2626;">Nous vous contacterons sous peu pour finaliser l'accès numérique à votre loft.</p>
                                <?php endif; ?>
                                <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">
                                <p style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">Hello <?php echo esc_html($guest_name); ?>,</p>
                                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#374151;">Thank you for your payment. We’ve received <strong><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['total'], $currency)); ?></strong> for your stay in <strong><?php echo esc_html($room_name); ?></strong>. Here’s your detailed receipt.</p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Payment summary</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Reservation</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;">#<?php echo esc_html($booking['room_id']); ?> · <?php echo esc_html($room_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Purchase date</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($purchase_date_en); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Payment status</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($payment_status); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Transaction</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;border-bottom-right-radius:18px;"><?php echo esc_html($transaction_id); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:0 0 24px;padding:24px;background-color:#fffbeb;border:1px solid #fcd34d;border-radius:18px;box-shadow:0 16px 30px rgba(217,119,6,0.18);color:#92400e;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:900;letter-spacing:0.06em;text-transform:uppercase;color:#92400e;">Payment details</h3>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Lodging (pre-tax)</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;">
                                                <?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['lodging_subtotal'], $currency)); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Additional services</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;">
                                                <?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['extras_total'], $currency)); ?>
                                            </td>
                                        </tr>
                                        <?php if (!empty($price_breakdown['extras'])) : ?>
                                            <tr>
                                                <td colspan="2" style="padding:6px 0 0;">
                                                    <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;color:#92400e;line-height:1.6;">
                                                        <?php foreach ($price_breakdown['extras'] as $extra) : ?>
                                                            <li style="font-weight:800;"><?php echo esc_html($extra['title']); ?> · <?php echo esc_html(wp_loft_booking_format_currency($extra['price'], $currency)); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($price_breakdown['taxes'] as $tax) : ?>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#92400e;font-weight:900;"><?php echo esc_html($tax['label']); ?> (<?php echo esc_html(wp_loft_booking_format_tax_rate($tax['rate'])); ?>%)</td>
                                                <td style="padding:6px 0;font-size:14px;color:#7c2d12;text-align:right;font-weight:900;">
                                                    <?php echo esc_html(wp_loft_booking_format_currency($tax['amount'], $currency)); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td style="padding:12px 0 0;font-size:15px;font-weight:900;color:#92400e;border-top:1px solid rgba(217,119,6,0.35);">Total</td>
                                            <td style="padding:12px 0 0;font-size:16px;font-weight:900;color:#7c2d12;text-align:right;border-top:1px solid rgba(217,119,6,0.35);"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['total'], $currency)); ?></td>
                                        </tr>
                                    </table>
                                <p style="margin:10px 0 0;font-size:13px;color:#92400e;font-weight:700;">Tax numbers: TPS <?php echo esc_html($tax_numbers['tps']); ?> &middot; TVQ <?php echo esc_html($tax_numbers['tvq']); ?></p>
                                </div>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#4b5563;">Stay dates: <?php echo esc_html($checkin); ?> &ndash; <?php echo esc_html($checkout); ?></p>
                                <?php if (!empty($booking['coupon'])) : ?>
                                    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#4b5563;">Promo code applied: <strong><?php echo esc_html($booking['coupon']); ?></strong></p>
                                <?php endif; ?>
                                <?php if ($virtual_key_success) : ?>
                                    <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#1d4ed8;">Your digital key will be delivered automatically ahead of arrival.</p>
                                <?php else : ?>
                                    <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#dc2626;">Our team will reach out shortly to finalize digital access to your loft.</p>
                                <?php endif; ?>
                                <div style="margin:0 0 28px;padding:24px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:18px;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Contact</h3>
                                    <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#4b5563;"><strong>Adresse</strong><br><?php echo esc_html($property_address); ?></p>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">Besoin d’assistance&nbsp;? Écrivez-nous à <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:#1d4ed8;text-decoration:none;"><?php echo esc_html($support_email); ?></a>.</p>
                                    <p style="margin:12px 0 0;font-size:14px;line-height:1.7;color:#4b5563;">Need assistance? Email us at <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:#1d4ed8;text-decoration:none;"><?php echo esc_html($support_email); ?></a> or visit <a href="<?php echo esc_url($website_url); ?>" style="color:#1d4ed8;text-decoration:none;">loft1325.com</a>.</p>
                                </div>
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">Merci encore d’avoir choisi Loft 1325. / Thank you for choosing Loft 1325.</p>
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

    $message = [
        'to'      => array_merge([$recipient], $recipients),
        'subject' => $subject,
        'html'    => $body,
        'text'    => wp_strip_all_tags($body),
        'bcc'     => $bcc,
        'attachments' => $attachments,
    ];

    $variables = [
        'guest_name'       => $guest_name,
        'room_name'        => $room_name,
        'checkin_fr'       => $checkin_fr,
        'checkout_fr'      => $checkout_fr,
        'checkin_en'       => $checkin,
        'checkout_en'      => $checkout,
        'purchase_date_fr' => $purchase_date_fr,
        'purchase_date_en' => $purchase_date_en,
        'payment_status'   => $payment_status,
        'transaction_id'   => $transaction_id,
        'price_breakdown'  => $price_breakdown,
        'currency'         => $currency,
        'support_email'    => $support_email,
        'property_address' => $property_address,
        'virtual_key'      => $virtual_key_success,
        'booking_reference'=> $booking['booking_id'] ?? '',
        'invoice_artifact' => is_wp_error($invoice_artifact) ? null : $invoice_artifact,
    ];

    $job_id = wp_loft_email_provider_enqueue_job(
        $message,
        $booking,
        [
            'event'     => 'booking-receipt',
            'template'  => 'guest-receipt',
            'variables' => $variables,
            'source'    => $is_manual ? 'manual' : 'automatic',
            'dry_run'   => !empty($options['dry_run']),
            'send_at'   => $options['send_at'] ?? null,
            'force_new_job' => $is_manual || !empty($options['force_new_job']),
        ]
    );

    if (is_wp_error($job_id)) {
        error_log('❌ Booking receipt email could not be queued for ' . $recipient . ': ' . $job_id->get_error_message());

        return $job_id;
    }

    if (!is_wp_error($invoice_artifact) && !empty($invoice_artifact['artifact_url'])) {
        global $wpdb;

        if (!empty($invoice_artifact['id'])) {
            $wpdb->update(
                $wpdb->prefix . 'loft_invoice_artifacts',
                ['status' => 'linked'],
                ['id' => (int) $invoice_artifact['id']],
                ['%s'],
                ['%d']
            );
        }

        error_log(sprintf('🧾 Invoice artifact %s linked to job #%d.', $invoice_artifact['artifact_url'], $job_id));
    }

    error_log(sprintf('✅ Booking receipt email queued as job #%d for %s', $job_id, $recipient));

    return $job_id;
}

function wp_loft_booking_send_admin_summary_email($booking, $virtual_key_result, $is_manual = false, array $options = []) {
    $recipients = wp_loft_booking_get_notification_recipients();
    $recipient  = array_shift($recipients);

    if (empty($recipient) || !is_email($recipient)) {
        error_log('⚠️ Admin booking email skipped: invalid recipient.');

        return new WP_Error(
            'loft_email_invalid_recipient',
            __('Admin summary email skipped: invalid recipient.', 'wp-loft-booking')
        );
    }

    $guest_name = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? ''));
    if ('' === $guest_name) {
        $guest_name = __('Invité', 'wp-loft-booking');
    }

    $guest_email = isset($booking['email']) ? sanitize_email($booking['email']) : '';
    $guest_phone = isset($booking['phone']) ? trim((string) $booking['phone']) : '';

    $room_name_raw = !empty($booking['room_name']) ? $booking['room_name'] : '';
    $room_name = wp_loft_booking_format_unit_label($room_name_raw);
    if ('' === $room_name) {
        $room_name = __('Loft non spécifié', 'wp-loft-booking');
    }

    $checkin  = !empty($booking['date_from']) ? wp_date('F j, Y', strtotime($booking['date_from'])) : __('N/A', 'wp-loft-booking');
    $checkout = !empty($booking['date_to']) ? wp_date('F j, Y', strtotime($booking['date_to'])) : __('N/A', 'wp-loft-booking');

    $checkin_fr  = !empty($booking['date_from']) ? wp_date('j F Y', strtotime($booking['date_from'])) : __('N/D', 'wp-loft-booking');
    $checkout_fr = !empty($booking['date_to']) ? wp_date('j F Y', strtotime($booking['date_to'])) : __('N/D', 'wp-loft-booking');

    $booking_timestamp = !empty($booking['created_at']) ? strtotime($booking['created_at']) : false;
    if (!$booking_timestamp) {
        $booking_timestamp = current_time('timestamp');
    }

    $purchase_date_fr = wp_date('j F Y \à H\hi', $booking_timestamp);
    $purchase_date_en = wp_date('F j, Y \a\t g:i A', $booking_timestamp);

    $guest_count = isset($booking['guests']) ? (int) $booking['guests'] : 0;
    if ($guest_count > 0) {
        $guest_count_display_fr = $guest_count . ' ' . (1 === $guest_count ? 'invité' : 'invités');
        $guest_count_display_en = $guest_count . ' ' . (1 === $guest_count ? 'guest' : 'guests');
    } else {
        $guest_count_display_fr = 'Non précisé';
        $guest_count_display_en = 'Not specified';
    }

    $address_parts = array_filter([
        $booking['address'] ?? '',
        $booking['city'] ?? '',
        $booking['country'] ?? '',
    ]);
    $address_display = !empty($address_parts) ? implode(', ', $address_parts) : __('Not provided', 'wp-loft-booking');

    $arrival_note_fr = !empty($booking['arrival_time']) ? $booking['arrival_time'] : __('Non précisé', 'wp-loft-booking');
    $arrival_note_en = !empty($booking['arrival_time']) ? $booking['arrival_time'] : __('Not provided', 'wp-loft-booking');

    $guest_message = !empty($booking['message']) ? nl2br(esc_html($booking['message'])) : __('Aucun message', 'wp-loft-booking');

    $currency = !empty($booking['currency']) ? strtoupper($booking['currency']) : 'CAD';
    $payment_status = !empty($booking['payment_status']) ? $booking['payment_status'] : __('Unknown', 'wp-loft-booking');
    $transaction_id = !empty($booking['transaction_id']) ? $booking['transaction_id'] : __('Not provided', 'wp-loft-booking');

    $price_breakdown = wp_loft_booking_calculate_price_breakdown($booking);

    $virtual_key_success = !is_wp_error($virtual_key_result);
    $virtual_key_message_fr = $virtual_key_success
        ? sprintf(
            __('Clé numérique générée (trousseau #%1$d, clés: %2$s).', 'wp-loft-booking'),
            intval($virtual_key_result['keychain_id'] ?? 0),
            implode(', ', array_map('strval', $virtual_key_result['virtual_key_ids'] ?? [])) ?: __('Aucune', 'wp-loft-booking')
        )
        : sprintf(
            __('Échec de la génération de la clé numérique : %s', 'wp-loft-booking'),
            is_wp_error($virtual_key_result) ? $virtual_key_result->get_error_message() : __('Raison inconnue', 'wp-loft-booking')
        );

    $virtual_key_message_en = $virtual_key_success
        ? sprintf(
            __('Digital key created (keychain #%1$d, keys: %2$s).', 'wp-loft-booking'),
            intval($virtual_key_result['keychain_id'] ?? 0),
            implode(', ', array_map('strval', $virtual_key_result['virtual_key_ids'] ?? [])) ?: __('None', 'wp-loft-booking')
        )
        : sprintf(
            __('Digital key creation failed: %s', 'wp-loft-booking'),
            is_wp_error($virtual_key_result) ? $virtual_key_result->get_error_message() : __('Unknown reason', 'wp-loft-booking')
        );

    $access_point_summary = '';
    if ($virtual_key_success && !empty($virtual_key_result['access_point_ids'])) {
        $access_point_summary = implode(', ', array_map('strval', (array) $virtual_key_result['access_point_ids']));
    }

    $bcc = [];

    foreach ($recipients as $internal_email) {
        $bcc[] = $internal_email;
    }
    $subject = 'Lofts 1325 – Nouvelle réservation confirmée | New Reservation Confirmation';

    $logo_url         = 'https://loft1325.com/wp-content/uploads/2024/06/Asset-1.png';
    $property_address = '1325 3e Avenue, Val-d’Or, QC, Canada';

    ob_start();
    ?>
    <div style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#0f172a;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f4f6;padding:36px 0;">
            <tr>
                <td align="center" style="padding:0 16px;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="720" style="width:100%;max-width:720px;background-color:#ffffff;border-radius:26px;overflow:hidden;box-shadow:0 26px 52px rgba(15,23,42,0.14);">
                        <tr>
                            <td style="padding:40px;background:linear-gradient(135deg,#0f172a,#0b1222);text-align:center;">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Loft 1325" style="max-width:200px;width:100%;height:auto;display:block;margin:0 auto 16px;">
                                <p style="margin:0;font-size:12px;letter-spacing:0.32em;text-transform:uppercase;color:#cbd5e1;font-weight:700;">Loft 1325</p>
                                <p style="margin:12px 0 0;font-size:16px;color:#f3f4f6;font-weight:600;">Expérience de séjour signature &middot; Signature Stay Experience</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:42px 44px 28px;">
                                <p style="margin:0 0 12px;font-size:20px;font-weight:800;color:#0f172a;">Nouvelle réservation confirmée</p>
                                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#334155;">Un paiement a été reçu pour <strong><?php echo esc_html($room_name); ?></strong>. Voici le récapitulatif pour votre équipe.</p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Détails du séjour</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Réservation</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;">#<?php echo esc_html($booking['room_id']); ?> &middot; <?php echo esc_html($room_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Dates</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;">Du <?php echo esc_html($checkin_fr); ?> au <?php echo esc_html($checkout_fr); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Nombre d’invités</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_count_display_fr); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Date d’achat</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;border-bottom-right-radius:18px;"><?php echo esc_html($purchase_date_fr); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:0 0 24px;padding:24px;background-color:#fffbeb;border:1px solid #fcd34d;border-radius:20px;box-shadow:0 18px 34px rgba(217,119,6,0.15);color:#92400e;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:900;color:#92400e;">Paiement</h3>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Statut</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html($payment_status); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Transaction</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html($transaction_id); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Hébergement (avant taxes)</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['lodging_subtotal'], $currency)); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#b45309;font-weight:800;">Services additionnels</td>
                                            <td style="padding:6px 0;font-size:14px;color:#78350f;text-align:right;font-weight:900;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['extras_total'], $currency)); ?></td>
                                        </tr>
                                        <?php if (!empty($price_breakdown['extras'])) : ?>
                                            <tr>
                                                <td colspan="2" style="padding:6px 0 0;">
                                                    <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;color:#92400e;">
                                                        <?php foreach ($price_breakdown['extras'] as $extra) : ?>
                                                            <li style="font-weight:800;"><?php echo esc_html($extra['title']); ?> &middot; <?php echo esc_html(wp_loft_booking_format_currency($extra['price'], $currency)); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($price_breakdown['taxes'] as $tax) : ?>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#92400e;font-weight:900;"><?php echo esc_html($tax['label']); ?> (<?php echo esc_html(wp_loft_booking_format_tax_rate($tax['rate'])); ?>%)</td>
                                                <td style="padding:6px 0;font-size:14px;color:#7c2d12;text-align:right;font-weight:900;">&nbsp;<?php echo esc_html(wp_loft_booking_format_currency($tax['amount'], $currency)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td style="padding:12px 0 0;font-size:15px;font-weight:900;color:#92400e;border-top:1px solid rgba(217,119,6,0.35);">Total</td>
                                            <td style="padding:12px 0 0;font-size:16px;font-weight:900;color:#7c2d12;text-align:right;border-top:1px solid rgba(217,119,6,0.35);">
                                                <?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['total'], $currency)); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <?php if (!empty($booking['coupon'])) : ?>
                                    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#4b5563;">Code promotionnel&nbsp;: <strong><?php echo esc_html($booking['coupon']); ?></strong></p>
                                <?php endif; ?>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#1d4ed8;"><?php echo esc_html($virtual_key_message_fr); ?><?php if ($access_point_summary) : ?> &middot; Accès: <?php echo esc_html($access_point_summary); ?><?php endif; ?></p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Coordonnées client</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Client</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;"><?php echo esc_html($guest_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Courriel</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_email ?: __('Non fourni', 'wp-loft-booking')); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Téléphone</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_phone ?: __('Non fourni', 'wp-loft-booking')); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Adresse</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;border-bottom-right-radius:18px;"><?php echo esc_html($address_display); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:0 0 24px;padding:24px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:18px;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Notes internes</h3>
                                    <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#4b5563;"><strong>Heure d’arrivée prévue :</strong> <?php echo esc_html($arrival_note_fr); ?></p>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;"><strong>Message du client :</strong><br><?php echo $guest_message; ?></p>
                                </div>
                                <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">
                                <p style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">New reservation confirmed</p>
                                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#374151;">A payment was received for <strong><?php echo esc_html($room_name); ?></strong>. Here’s the summary for your records.</p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Stay details</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Reservation</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;">#<?php echo esc_html($booking['room_id']); ?> · <?php echo esc_html($room_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Dates</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;">From <?php echo esc_html($checkin); ?> to <?php echo esc_html($checkout); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Guests</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_count_display_en); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Purchase date</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;border-bottom-right-radius:18px;"><?php echo esc_html($purchase_date_en); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:0 0 24px;padding:24px;background:linear-gradient(135deg,#0f172a,#111827);color:#f9fafb;border-radius:20px;box-shadow:0 18px 34px rgba(15,23,42,0.12);">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#f9fafb;">Payment</h3>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#e2e8f0;font-weight:600;">Status</td>
                                            <td style="padding:6px 0;font-size:14px;color:#f9fafb;text-align:right;font-weight:700;"><?php echo esc_html($payment_status); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#e2e8f0;font-weight:600;">Transaction</td>
                                            <td style="padding:6px 0;font-size:14px;color:#f9fafb;text-align:right;font-weight:700;"><?php echo esc_html($transaction_id); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#e2e8f0;font-weight:600;">Lodging (pre-tax)</td>
                                            <td style="padding:6px 0;font-size:14px;color:#f9fafb;text-align:right;font-weight:700;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['lodging_subtotal'], $currency)); ?></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;font-size:14px;color:#e2e8f0;font-weight:600;">Additional services</td>
                                            <td style="padding:6px 0;font-size:14px;color:#f9fafb;text-align:right;font-weight:700;"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['extras_total'], $currency)); ?></td>
                                        </tr>
                                        <?php if (!empty($price_breakdown['extras'])) : ?>
                                            <tr>
                                                <td colspan="2" style="padding:6px 0 0;">
                                                    <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;color:#e2e8f0;">
                                                        <?php foreach ($price_breakdown['extras'] as $extra) : ?>
                                                            <li><?php echo esc_html($extra['title']); ?> · <?php echo esc_html(wp_loft_booking_format_currency($extra['price'], $currency)); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($price_breakdown['taxes'] as $tax) : ?>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#f8fafc;font-weight:700;"><?php echo esc_html($tax['label']); ?> (<?php echo esc_html(wp_loft_booking_format_tax_rate($tax['rate'])); ?>%)</td>
                                                <td style="padding:6px 0;font-size:14px;color:#ffffff;text-align:right;font-weight:700;">&nbsp;<?php echo esc_html(wp_loft_booking_format_currency($tax['amount'], $currency)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td style="padding:12px 0 0;font-size:15px;font-weight:700;color:#f9fafb;border-top:1px solid rgba(148,163,184,0.3);">Total</td>
                                            <td style="padding:12px 0 0;font-size:15px;font-weight:700;color:#f9fafb;text-align:right;border-top:1px solid rgba(148,163,184,0.3);"><?php echo esc_html(wp_loft_booking_format_currency($price_breakdown['total'], $currency)); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <?php if (!empty($booking['coupon'])) : ?>
                                    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#4b5563;">Promo code: <strong><?php echo esc_html($booking['coupon']); ?></strong></p>
                                <?php endif; ?>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#1d4ed8;"><?php echo esc_html($virtual_key_message_en); ?><?php if ($access_point_summary) : ?> · Access points: <?php echo esc_html($access_point_summary); ?><?php endif; ?></p>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;border-collapse:separate;border-spacing:0;background-color:#f9fafb;border-radius:18px;overflow:hidden;">
                                    <tr>
                                        <td colspan="2" style="padding:16px 24px;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;">Guest details</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;width:42%;">Guest</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;font-weight:600;"><?php echo esc_html($guest_name); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Email</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_email ?: __('Not provided', 'wp-loft-booking')); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;">Phone</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;"><?php echo esc_html($guest_phone ?: __('Not provided', 'wp-loft-booking')); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 24px;font-size:14px;color:#6b7280;border-bottom-left-radius:18px;">Address</td>
                                        <td style="padding:16px 24px;font-size:15px;color:#111827;border-bottom-right-radius:18px;"><?php echo esc_html($address_display); ?></td>
                                    </tr>
                                </table>
                                <div style="margin:0 0 28px;padding:24px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:18px;">
                                    <h3 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Internal notes</h3>
                                    <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#4b5563;"><strong>Expected arrival time:</strong> <?php echo esc_html($arrival_note_en); ?></p>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;"><strong>Guest message:</strong><br><?php echo $guest_message; ?></p>
                                </div>
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

    $message = [
        'to'      => [$recipient],
        'subject' => $subject,
        'html'    => $body,
        'text'    => wp_strip_all_tags($body),
        'bcc'     => $bcc,
    ];

    $variables = [
        'guest_name'             => $guest_name,
        'guest_email'            => $guest_email,
        'guest_phone'            => $guest_phone,
        'room_name'              => $room_name,
        'checkin_fr'             => $checkin_fr,
        'checkout_fr'            => $checkout_fr,
        'checkin_en'             => $checkin,
        'checkout_en'            => $checkout,
        'guest_count_display_fr' => $guest_count_display_fr,
        'guest_count_display_en' => $guest_count_display_en,
        'price_breakdown'        => $price_breakdown,
        'payment_status'         => $payment_status,
        'transaction_id'         => $transaction_id,
        'virtual_key_message_fr' => $virtual_key_message_fr,
        'virtual_key_message_en' => $virtual_key_message_en,
        'access_points'          => $access_point_summary,
        'address'                => $address_display,
        'arrival_note_fr'        => $arrival_note_fr,
        'arrival_note_en'        => $arrival_note_en,
        'guest_message'          => $guest_message,
        'purchase_date_fr'       => $purchase_date_fr,
        'purchase_date_en'       => $purchase_date_en,
        'booking_reference'      => $booking['booking_id'] ?? '',
    ];

    $job_id = wp_loft_email_provider_enqueue_job(
        $message,
        $booking,
        [
            'event'     => 'admin-booking-summary',
            'template'  => 'admin-summary',
            'variables' => $variables,
            'source'    => $is_manual ? 'manual' : 'automatic',
            'dry_run'   => !empty($options['dry_run']),
            'send_at'   => $options['send_at'] ?? null,
            'force_new_job' => $is_manual || !empty($options['force_new_job']),
        ]
    );

    if (is_wp_error($job_id)) {
        error_log('❌ Admin booking email could not be queued for ' . $recipient . ': ' . $job_id->get_error_message());

        return $job_id;
    }

    error_log(sprintf('✅ Admin booking email queued as job #%d for %s', $job_id, $recipient));

    return $job_id;
}


function wp_loft_booking_send_cleaning_email($booking, $is_manual = false, array $options = []) {
    $recipients = wp_loft_booking_parse_email_list($options['recipient_override'] ?? wp_loft_booking_get_cleaning_recipients());

    if (empty($recipients)) {
        error_log('⚠️ Cleaning email skipped: no valid recipients.');

        return new WP_Error(
            'loft_email_invalid_recipient',
            __('Cleaning reminder skipped: no recipients.', 'wp-loft-booking')
        );
    }

    $recipient = array_shift($recipients);

    $room_name_raw = !empty($booking['room_name']) ? $booking['room_name'] : '';
    $room_name = wp_loft_booking_format_unit_label($room_name_raw);
    if ('' === $room_name) {
        $room_name = __('Loft non spécifié', 'wp-loft-booking');
    }

    $guest_name = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? '')) ?: __('Guest', 'wp-loft-booking');
    $checkin    = !empty($booking['date_from']) ? wp_date('F j, Y', strtotime($booking['date_from'])) : __('N/A', 'wp-loft-booking');
    $checkout   = !empty($booking['date_to']) ? wp_date('F j, Y', strtotime($booking['date_to'])) : __('N/A', 'wp-loft-booking');

    $bcc = wp_loft_booking_parse_email_list($recipients);

    $subject = sprintf(__('Lofts 1325 – Cleaning scheduled for %s', 'wp-loft-booking'), $room_name);

    $logo_url         = 'https://loft1325.com/wp-content/uploads/2024/06/Asset-1.png';
    $website_url      = 'https://loft1325.com';
    $property_address = '1325 3e Avenue, Val-d’Or, QC, Canada';

    $checkin_fr = !empty($booking['date_from']) ? wp_date('j F Y', strtotime($booking['date_from'])) : $checkin;
    $checkout_fr = !empty($booking['date_to']) ? wp_date('j F Y', strtotime($booking['date_to'])) : $checkout;
    $checkin_en = $checkin;
    $checkout_en = $checkout;

    $attachments = [];

    if (!empty($booking['date_to'])) {
        try {
            $timezone       = new DateTimeZone('America/Toronto');
            $cleaning_start = new DateTime($booking['date_to'] . ' 11:00', $timezone);
            $cleaning_end   = new DateTime($booking['date_to'] . ' 15:00', $timezone);

            $ics_body = "BEGIN:VCALENDAR\r\n" .
                "VERSION:2.0\r\n" .
                "PRODID:-//Loft 1325//Cleaning Schedule//EN\r\n" .
                "BEGIN:VEVENT\r\n" .
                'UID:' . uniqid('loft1325-cleaning-', true) . "@loft1325.com\r\n" .
                'DTSTAMP:' . gmdate('Ymd\THis\Z') . "\r\n" .
                'SUMMARY:' . sprintf('Cleaning – %s', $room_name) . "\r\n" .
                'DESCRIPTION:' . sprintf(
                    'Guest arrives at 3:00 PM and checks out at 11:00 AM. Cleaning window on %s from 11:00 to 15:00.',
                    $cleaning_start->format('Y-m-d')
                ) . "\r\n" .
                'DTSTART;TZID=America/Toronto:' . $cleaning_start->format('Ymd\THis') . "\r\n" .
                'DTEND;TZID=America/Toronto:' . $cleaning_end->format('Ymd\THis') . "\r\n" .
                'LOCATION:' . $property_address . "\r\n" .
                'ORGANIZER;CN=Loft 1325:MAILTO:reservation@loft1325.com' . "\r\n" .
                "END:VEVENT\r\n" .
                "END:VCALENDAR\r\n";

            $ics_path = wp_tempnam('loft1325-cleaning.ics');

            if ($ics_path && false !== file_put_contents($ics_path, $ics_body)) {
                $attachments[] = $ics_path;
            }
        } catch (Exception $exception) {
            error_log('⚠️ Unable to generate cleaning calendar invite: ' . $exception->getMessage());
        }
    }

    ob_start();
    ?>
    <div style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#111827;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f4f6;padding:28px 0;">
            <tr>
                <td align="center" style="padding:0 16px;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 32px rgba(15,23,42,0.12);">
                        <tr>
                            <td style="padding:28px;background:linear-gradient(135deg,#0f172a,#1f2937);text-align:center;">
                                <a href="<?php echo esc_url($website_url); ?>" style="text-decoration:none;">
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Loft 1325" style="max-width:180px;width:100%;height:auto;display:block;margin:0 auto 12px;">
                                </a>
                                <p style="margin:0;font-size:12px;letter-spacing:0.32em;text-transform:uppercase;color:#9ca3af;">Loft 1325</p>
                                <p style="margin:10px 0 0;font-size:14px;color:#e5e7eb;">Préparation du loft &middot; Loft preparation</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:28px 28px 12px;">
                                <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:#0f172a;">Nettoyage planifié</p>
                                <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#374151;">Loft : <strong><?php echo esc_html($room_name); ?></strong><br>Invité : <strong><?php echo esc_html($guest_name); ?></strong><br>Dates : <strong><?php echo esc_html($checkin_fr); ?></strong> → <strong><?php echo esc_html($checkout_fr); ?></strong></p>
                                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#111827;">Arrivée du client : <strong>15&nbsp;h</strong><br>Départ du client : <strong>11&nbsp;h</strong><br>Créneau de ménage le jour du départ : <strong>11&nbsp;h à 15&nbsp;h</strong></p>
                                <div style="margin:0 0 18px;padding:16px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;">
                                    <p style="margin:0;font-size:13px;line-height:1.6;color:#4b5563;">Notes : <?php echo esc_html($booking['message'] ?? __('None provided', 'wp-loft-booking')); ?></p>
                                </div>
                                <p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:#6b7280;">Adresse : <?php echo esc_html($property_address); ?></p>
                                <p style="margin:0 0 20px;font-size:13px;line-height:1.6;color:#6b7280;">Ajoutez l’invitation calendrier ci-jointe pour bloquer votre plage de ménage.</p>
                                <hr style="border:none;border-top:1px solid #e5e7eb;margin:18px 0;">
                                <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:#0f172a;">Cleaning scheduled</p>
                                <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#374151;">Unit: <strong><?php echo esc_html($room_name); ?></strong><br>Guest: <strong><?php echo esc_html($guest_name); ?></strong><br>Dates: <strong><?php echo esc_html($checkin_en); ?></strong> → <strong><?php echo esc_html($checkout_en); ?></strong></p>
                                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#111827;">Guest arrival: <strong>3:00 PM</strong><br>Guest departure: <strong>11:00 AM</strong><br>Cleaning window on checkout day: <strong>11:00 AM to 3:00 PM</strong></p>
                                <div style="margin:0 0 18px;padding:16px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;">
                                    <p style="margin:0;font-size:13px;line-height:1.6;color:#4b5563;">Notes: <?php echo esc_html($booking['message'] ?? __('None provided', 'wp-loft-booking')); ?></p>
                                </div>
                                <p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:#6b7280;">Location: <?php echo esc_html($property_address); ?></p>
                                <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#6b7280;">Invite attached so you can accept and add this cleaning to your calendar.</p>
                                <?php if ($is_manual) : ?>
                                    <p style="margin:12px 0 0;font-size:12px;color:#9ca3af;">Manual resend from the Loft 1325 bookings portal.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <?php
    $body = ob_get_clean();

    $message = [
        'to'      => array_merge([$recipient], $recipients),
        'subject' => $subject,
        'html'    => $body,
        'text'    => wp_strip_all_tags($body),
        'bcc'     => $bcc,
        'attachments' => $attachments,
    ];

    $variables = [
        'room_name' => $room_name,
        'guest_name' => $guest_name,
        'checkin'   => $checkin,
        'checkout'  => $checkout,
        'manual'    => $is_manual,
    ];

    $job_id = wp_loft_email_provider_enqueue_job(
        $message,
        $booking,
        [
            'event'     => 'cleaning-notice',
            'template'  => 'cleaning-notice',
            'variables' => $variables,
            'source'    => $is_manual ? 'manual' : 'automatic',
            'dry_run'   => !empty($options['dry_run']),
            'force_new_job' => $is_manual || !empty($options['force_new_job']),
        ]
    );

    if (is_wp_error($job_id)) {
        error_log('❌ Cleaning email could not be queued for ' . $recipient . ': ' . $job_id->get_error_message());

        return $job_id;
    }

    error_log(sprintf('✅ Cleaning email queued as job #%d for %s', $job_id, $recipient));

    return $job_id;
}


function wp_loft_booking_send_post_stay_email($booking, $is_manual = false, array $options = []) {
    $recipient = isset($booking['email']) ? sanitize_email($booking['email']) : '';

    if (empty($recipient) || !is_email($recipient)) {
        error_log('⚠️ Post-stay email skipped: invalid recipient.');

        return new WP_Error(
            'loft_email_invalid_recipient',
            __('Post-stay email skipped: invalid recipient.', 'wp-loft-booking')
        );
    }

    $guest_name = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? ''));
    if ('' === $guest_name) {
        $guest_name = __('Invité', 'wp-loft-booking');
    }

    $room_name_raw = !empty($booking['room_name']) ? $booking['room_name'] : '';
    $room_name = wp_loft_booking_format_unit_label($room_name_raw);
    if ('' === $room_name) {
        $room_name = __('Votre loft', 'wp-loft-booking');
    }

    $checkin  = !empty($booking['date_from']) ? wp_date('F j, Y', strtotime($booking['date_from'])) : __('N/A', 'wp-loft-booking');
    $checkout = !empty($booking['date_to']) ? wp_date('F j, Y', strtotime($booking['date_to'])) : __('N/A', 'wp-loft-booking');

    $bcc = [];

    foreach (wp_loft_booking_get_notification_recipients() as $internal_email) {
        if (strtolower($internal_email) !== strtolower($recipient)) {
            $bcc[] = $internal_email;
        }
    }

    $subject = 'Lofts 1325 – Merci pour votre séjour | Thank you for your stay';

    ob_start();
    ?>
    <div style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#111827;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f4f6;padding:28px 0;">
            <tr>
                <td align="center" style="padding:0 16px;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 32px rgba(15,23,42,0.12);">
                        <tr>
                            <td style="padding:32px 32px 20px;background:linear-gradient(135deg,#0f172a,#1f2937);color:#f9fafb;">
                                <p style="margin:0;font-size:12px;letter-spacing:0.32em;text-transform:uppercase;color:#9ca3af;">Loft 1325</p>
                                <h1 style="margin:8px 0 0;font-size:20px;">Merci pour votre visite | Thank you for staying</h1>
                                <p style="margin:12px 0 0;font-size:14px;line-height:1.6;">Nous espérons que vous avez apprécié votre séjour dans <?php echo esc_html($room_name); ?>. | We hope you enjoyed your time in <?php echo esc_html($room_name); ?>.</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:28px 32px;">
                                <p style="margin:0 0 12px;font-size:15px;color:#111827;font-weight:600;">Bonjour <?php echo esc_html($guest_name); ?>,</p>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#374151;">Merci d’avoir choisi Loft 1325 pour votre visite à Val-d’Or. / Thank you for choosing Loft 1325 for your stay in Val-d’Or.</p>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#374151;">Vos dates · Your dates:<br><strong><?php echo esc_html($checkin); ?> → <?php echo esc_html($checkout); ?></strong></p>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#374151;">Nous aimerions connaître votre avis. / We would love your feedback.</p>
                                <p style="margin:0;">
                                    <a href="https://g.page/r/CfeXFP4gAiijEAg/review" style="background-color:#0ea5e9;color:#f8fafc;text-decoration:none;padding:12px 18px;border-radius:12px;font-weight:700;display:inline-block;">Laisser un avis · Leave a review</a>
                                </p>
                                <?php if ($is_manual) : ?>
                                    <p style="margin:18px 0 0;font-size:12px;line-height:1.6;color:#9ca3af;">Cette relance a été envoyée manuellement depuis le portail Loft 1325. / This follow-up was issued manually from the Loft 1325 portal.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <?php
    $body = ob_get_clean();

    $message = [
        'to'      => [$recipient],
        'subject' => $subject,
        'html'    => $body,
        'text'    => wp_strip_all_tags($body),
        'bcc'     => $bcc,
    ];

    $variables = [
        'guest_name'    => $guest_name,
        'room_name'     => $room_name,
        'checkin'       => $checkin,
        'checkout'      => $checkout,
        'booking_id'    => $booking['booking_id'] ?? '',
        'manual'        => $is_manual,
        'post_stay_eta' => $options['send_at'] ?? null,
    ];

    $job_id = wp_loft_email_provider_enqueue_job(
        $message,
        $booking,
        [
            'event'     => 'post-stay-follow-up',
            'template'  => 'guest-post-stay',
            'variables' => $variables,
            'source'    => $is_manual ? 'manual' : 'automatic',
            'dry_run'   => !empty($options['dry_run']),
            'send_at'   => $options['send_at'] ?? null,
            'force_new_job' => $is_manual || !empty($options['force_new_job']),
        ]
    );

    if (is_wp_error($job_id)) {
        error_log('❌ Post-stay email could not be queued for ' . $recipient . ': ' . $job_id->get_error_message());

        return $job_id;
    }

    error_log(sprintf('✅ Post-stay email queued as job #%d for %s', $job_id, $recipient));

    return $job_id;
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


