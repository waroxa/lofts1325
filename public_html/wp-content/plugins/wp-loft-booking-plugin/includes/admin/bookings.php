<?php
defined('ABSPATH') || exit;

add_action('admin_init', 'wp_loft_booking_handle_bulk_receipts');
add_action('admin_init', 'wp_loft_booking_handle_booking_actions');

function wp_loft_booking_bookings_page() {
    global $wpdb;

    $selected_loft = isset($_GET['loft_id']) ? absint($_GET['loft_id']) : 0;
    $lofts         = $wpdb->get_results("SELECT id, unit_name FROM {$wpdb->prefix}loft_lofts ORDER BY unit_name ASC");
    $settings      = wp_loft_booking_get_auto_send_settings();
    $templates     = wp_loft_booking_default_template_keys();
    $booking_id    = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
    $booking       = $booking_id ? wp_loft_booking_build_booking_payload($booking_id) : [];
    $auto_values   = $selected_loft && !empty($settings['lofts'][$selected_loft])
        ? $settings['lofts'][$selected_loft]
        : ($settings['global'] ?? []);

    ?>
    <div class="wrap">
        <h1>Manage Bookings</h1>
        <?php settings_errors('wp_loft_booking_bookings'); ?>

        <h2>Automatic sends</h2>
        <p>Toggle per-loft automation for each template. Global settings apply unless a loft override is provided.</p>
        <form method="post">
            <?php wp_nonce_field('wp_loft_booking_update_autosend'); ?>
            <input type="hidden" name="wp_loft_booking_update_autosend" value="1">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Loft</th>
                    <td>
                        <select name="auto_send_loft_id">
                            <option value="0" <?php selected(0, $selected_loft); ?>>All lofts (default)</option>
                            <?php foreach ($lofts as $loft) : ?>
                                <option value="<?php echo esc_attr($loft->id); ?>" <?php selected((int) $loft->id, $selected_loft); ?>><?php echo esc_html($loft->unit_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Overrides apply only to the selected loft.</p>
                    </td>
                </tr>
                <?php foreach ($templates as $template_key => $label) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_send[<?php echo esc_attr($template_key); ?>]" value="1" <?php checked(!empty($auto_values[$template_key])); ?>>
                                Enable automatic sends
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p>
                <button class="button button-primary" type="submit">Save automation settings</button>
                <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'wp_loft_booking_bookings'], admin_url('admin.php'))); ?>">Reset to defaults</a>
            </p>
        </form>

        <hr>

        <h2>Booking lookup</h2>
        <form method="get" style="margin-bottom:16px;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'wp_loft_booking_bookings'); ?>">
            <label for="booking_id">Booking ID</label>
            <input type="number" id="booking_id" name="booking_id" value="<?php echo esc_attr($booking_id); ?>" min="1" step="1">
            <button class="button">Load booking</button>
        </form>

        <?php if (!empty($booking)) : ?>
            <div class="notice notice-info inline">
                <p><strong>Guest:</strong> <?php echo esc_html(trim(($booking['name'] ?? '') . ' ' . ($booking['surname'] ?? ''))); ?> · <strong>Email:</strong> <?php echo esc_html($booking['email'] ?? ''); ?> · <strong>Loft:</strong> <?php echo esc_html($booking['room_name'] ?? ''); ?></p>
                <p><strong>Dates:</strong> <?php echo esc_html($booking['date_from'] ?? ''); ?> → <?php echo esc_html($booking['date_to'] ?? ''); ?> · <strong>Total:</strong> <?php echo esc_html($booking['total'] ?? ''); ?> <?php echo esc_html($booking['currency'] ?? 'CAD'); ?></p>
            </div>

            <form method="post" style="margin-bottom:24px;">
                <?php wp_nonce_field('wp_loft_booking_manual_send'); ?>
                <input type="hidden" name="wp_loft_booking_manual_send" value="1">
                <input type="hidden" name="booking_id" value="<?php echo esc_attr($booking_id); ?>">
                <p>
                    <label><input type="checkbox" name="dry_run" value="1"> Dry-run mode (render without sending)</label>
                </p>
                <p>
                    <button class="button button-primary" type="submit" name="template_key" value="guest-confirmation">Send/Resend confirmation</button>
                    <button class="button" type="submit" name="template_key" value="guest-receipt">Send/Resend invoice</button>
                    <button class="button" type="submit" name="template_key" value="guest-receipt-recreate">Recreate &amp; send invoice</button>
                    <button class="button" type="submit" name="template_key" value="guest-post-stay">Send/Resend post-stay</button>
                    <button class="button" type="submit" name="template_key" value="admin-summary">Send/Resend admin summary</button>
                </p>
                <p class="description">Manual sends are tagged as such in the email job log. Post-stay emails scheduled via automation are delayed until after checkout. Admin summaries deliver to your internal notification list.</p>
            </form>
        <?php elseif ($booking_id) : ?>
            <div class="notice notice-warning inline"><p>Booking not found for ID <?php echo esc_html($booking_id); ?>.</p></div>
        <?php endif; ?>

        <hr>

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

/**
 * Normalize and persist automation preferences.
 */
function wp_loft_booking_update_auto_send_settings($loft_id, array $template_values) {
    $settings  = wp_loft_booking_get_auto_send_settings();
    $templates = wp_loft_booking_default_template_keys();

    foreach ($templates as $template_key => $label) {
        $value = !empty($template_values[$template_key]);

        if ($loft_id) {
            $settings['lofts'][$loft_id][$template_key] = $value;
        } else {
            $settings['global'][$template_key] = $value;
        }
    }

    update_option('loft_email_auto_send', $settings);
}

/**
 * Handle manual actions on the bookings admin page.
 */
function wp_loft_booking_handle_booking_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!empty($_POST['wp_loft_booking_update_autosend'])) {
        check_admin_referer('wp_loft_booking_update_autosend');

        $loft_id = isset($_POST['auto_send_loft_id']) ? absint($_POST['auto_send_loft_id']) : 0;
        $values  = isset($_POST['auto_send']) && is_array($_POST['auto_send']) ? $_POST['auto_send'] : [];

        wp_loft_booking_update_auto_send_settings($loft_id, $values);

        add_settings_error(
            'wp_loft_booking_bookings',
            'autosend_saved',
            __('Automation preferences saved.', 'wp-loft-booking'),
            'updated'
        );
    }

    if (!empty($_POST['wp_loft_booking_manual_send'])) {
        check_admin_referer('wp_loft_booking_manual_send');

        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $template   = isset($_POST['template_key']) ? sanitize_text_field((string) $_POST['template_key']) : '';
        $dry_run    = !empty($_POST['dry_run']);
        $force_new_job = false;

        if ('guest-receipt-recreate' === $template) {
            $template      = 'guest-receipt';
            $force_new_job = true;
        }

        if (!$booking_id || '' === $template) {
            add_settings_error(
                'wp_loft_booking_bookings',
                'manual_send_missing',
                __('Booking ID and template are required.', 'wp-loft-booking'),
                'error'
            );

            return;
        }

        $booking = wp_loft_booking_build_booking_payload($booking_id);

        if (empty($booking)) {
            add_settings_error(
                'wp_loft_booking_bookings',
                'manual_send_missing_booking',
                __('Booking not found.', 'wp-loft-booking'),
                'error'
            );

            return;
        }

        $result_message = __('Email queued.', 'wp-loft-booking');

        switch ($template) {
            case 'guest-confirmation':
                wp_loft_booking_send_confirmation_email($booking, [], true, ['dry_run' => $dry_run]);
                $result_message = __('Confirmation queued.', 'wp-loft-booking');
                break;
            case 'guest-receipt':
                wp_loft_booking_send_receipt_email($booking, [], true, [
                    'dry_run'       => $dry_run,
                    'force_new_job' => $force_new_job,
                ]);
                $result_message = $force_new_job
                    ? __('Invoice regenerated and queued.', 'wp-loft-booking')
                    : __('Invoice queued.', 'wp-loft-booking');
                break;
            case 'guest-post-stay':
                $send_at = $dry_run ? null : wp_loft_booking_calculate_post_stay_send_at($booking);
                wp_loft_booking_send_post_stay_email($booking, true, [
                    'dry_run' => $dry_run,
                    'send_at' => $send_at,
                ]);
                $result_message = __('Post-stay email queued.', 'wp-loft-booking');
                break;
            case 'admin-summary':
                wp_loft_booking_send_admin_summary_email($booking, [], true, [
                    'dry_run' => $dry_run,
                ]);
                $result_message = __('Admin summary queued.', 'wp-loft-booking');
                break;
            default:
                add_settings_error(
                    'wp_loft_booking_bookings',
                    'manual_send_unknown',
                    __('Unknown template requested.', 'wp-loft-booking'),
                    'error'
                );

                return;
        }

        $suffix = $dry_run ? ' ' . __('(dry-run render only)', 'wp-loft-booking') : '';

        add_settings_error(
            'wp_loft_booking_bookings',
            'manual_send_success',
            $result_message . $suffix,
            'updated'
        );
    }
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
