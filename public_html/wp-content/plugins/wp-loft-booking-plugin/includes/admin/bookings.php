<?php
defined('ABSPATH') || exit;

add_action('admin_init', 'wp_loft_booking_handle_bulk_receipts');
add_action('admin_init', 'wp_loft_booking_handle_booking_actions');

function wp_loft_booking_bookings_page() {
    global $wpdb;

    $selected_loft = isset($_GET['loft_id']) ? absint($_GET['loft_id']) : 0;
    $lofts         = $wpdb->get_results("SELECT id, name AS unit_name FROM {$wpdb->prefix}loft_lofts ORDER BY name ASC");
    $loft_types    = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}loft_types ORDER BY name ASC");
    $units         = $wpdb->get_results("SELECT id, unit_name, status FROM {$wpdb->prefix}loft_units ORDER BY unit_name ASC");
    $default_currency = get_option('stripe_currency', 'CAD');
    $settings      = wp_loft_booking_get_auto_send_settings();
    $templates     = wp_loft_booking_default_template_keys();
    $booking_id    = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
    $booking       = $booking_id ? wp_loft_booking_build_booking_payload($booking_id) : [];
    $auto_values   = $selected_loft && !empty($settings['lofts'][$selected_loft])
        ? $settings['lofts'][$selected_loft]
        : ($settings['global'] ?? []);
    $notification_recipients = implode("\n", wp_loft_booking_get_notification_recipients());
    $invoice_recipients      = implode("\n", wp_loft_booking_get_invoice_recipients());
    $cleaning_recipients     = implode("\n", wp_loft_booking_get_cleaning_recipients());

    $recent_records = $wpdb->get_results(
        "SELECT id FROM {$wpdb->prefix}nd_booking_booking ORDER BY id DESC LIMIT 50"
    );

    $recent_bookings = [];

    foreach ($recent_records as $record) {
        $payload = wp_loft_booking_build_booking_payload((int) $record->id);

        if (!empty($payload)) {
            $payload['booking_id'] = (int) $record->id;
            $recent_bookings[]     = $payload;
        }
    }

    ?>
    <div class="wrap">
        <h1>Manage Bookings</h1>
        <?php settings_errors('wp_loft_booking_bookings'); ?>
        <script>
            console.log('Bookings admin screen loaded.');
        </script>

        <div class="notice notice-info inline" style="margin:10px 0 18px;">
            <p style="margin:8px 0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <strong>Sandbox checkout available:</strong>
                <span>Trigger the full booking flow (payment, emails, calendars, keys) directly from the dashboard.</span>
                <a class="button button-primary" href="#admin-sandbox-checkout">Open admin sandbox form</a>
            </p>
            <p class="description" style="margin:0;">Tip: turn on Stripe test mode in Payment Settings to keep sandbox charges separated from live payments.</p>
        </div>

        <h2 id="admin-sandbox-checkout">Simulate checkout (admin sandbox)</h2>
        <p>Build a booking with your own dates and prices and run the full automation (emails, calendar, keys). Use test mode in Payment Settings for sandbox payments.</p>
        <form method="post" style="margin-bottom:24px;">
            <?php wp_nonce_field('wp_loft_booking_admin_checkout'); ?>
            <input type="hidden" name="wp_loft_booking_admin_checkout" value="1">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="guest_first_name">Guest name</label></th>
                    <td>
                        <input type="text" name="guest_first_name" id="guest_first_name" placeholder="First name" style="width:180px;" />
                        <input type="text" name="guest_last_name" id="guest_last_name" placeholder="Last name" style="width:180px;" />
                        <p class="description">Names are used on the emails and keychains.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="guest_email">Guest contact</label></th>
                    <td>
                        <input type="email" name="guest_email" id="guest_email" class="regular-text" placeholder="guest@example.com" required />
                        <input type="tel" name="guest_phone" id="guest_phone" class="regular-text" placeholder="Phone (optional)" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="room_type">Loft type</label></th>
                    <td>
                        <select name="room_type" id="room_type" required>
                            <option value="">Select a loft type…</option>
                            <?php foreach ($loft_types as $type) : ?>
                                <option value="<?php echo esc_attr(strtoupper($type->name)); ?>"><?php echo esc_html($type->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">We match the first available unit whose label contains this type (e.g. "(STUDIO)").</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="preferred_unit_id">Preferred unit (optional)</label></th>
                    <td>
                        <select name="preferred_unit_id" id="preferred_unit_id">
                            <option value="">Let the system pick</option>
                            <?php foreach ($units as $unit) : ?>
                                <option value="<?php echo esc_attr($unit->id); ?>"><?php echo esc_html($unit->unit_name); ?> <?php echo $unit->status ? '(' . esc_html($unit->status) . ')' : ''; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">If provided, this exact unit will be reserved for the test booking.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Stay dates</th>
                    <td>
                        <label>Check-in <input type="date" name="checkin_date" required></label>
                        <label style="margin-left:12px;">Check-out <input type="date" name="checkout_date" required></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="payment_total">Charge total</label></th>
                    <td>
                        <input type="number" step="0.01" min="0" name="payment_total" id="payment_total" placeholder="0.00" style="width:140px;">
                        <select name="payment_currency" id="payment_currency">
                            <option value="CAD" <?php selected($default_currency, 'CAD'); ?>>CAD</option>
                            <option value="USD" <?php selected($default_currency, 'USD'); ?>>USD</option>
                            <option value="EUR" <?php selected($default_currency, 'EUR'); ?>>EUR</option>
                        </select>
                        <p class="description">Use any amount to mirror the checkout total you want to test.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="payment_status">Payment status</label></th>
                    <td>
                        <select name="payment_status" id="payment_status">
                            <option value="paid">Paid</option>
                            <option value="processing">Processing</option>
                            <option value="pending">Pending</option>
                        </select>
                        <input type="text" name="transaction_id" id="transaction_id" class="regular-text" placeholder="Test transaction ID (optional)">
                    </td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary">Run test checkout</button></p>
        </form>

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

        <h2>Notification recipients</h2>
        <p>Manage who receives copies of confirmations, invoices, admin notices, and cleaning reminders.</p>
        <form method="post" style="margin-bottom:24px;">
            <?php wp_nonce_field('wp_loft_booking_update_recipients'); ?>
            <input type="hidden" name="wp_loft_booking_update_recipients" value="1">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="notification_recipients">Admin/notification emails</label></th>
                    <td>
                        <textarea id="notification_recipients" name="notification_recipients" class="large-text code" rows="3" placeholder="admin@example.com&#10;team@example.com"><?php echo esc_textarea($notification_recipients); ?></textarea>
                        <p class="description">Copied on confirmations, guest receipts, and admin summaries.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="invoice_recipients">Invoice emails</label></th>
                    <td>
                        <textarea id="invoice_recipients" name="invoice_recipients" class="large-text code" rows="3" placeholder="billing@example.com"><?php echo esc_textarea($invoice_recipients); ?></textarea>
                        <p class="description">Used when resending invoices directly to admins.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cleaning_recipients">Cleaning team emails</label></th>
                    <td>
                        <textarea id="cleaning_recipients" name="cleaning_recipients" class="large-text code" rows="3" placeholder="cleaning@example.com"><?php echo esc_textarea($cleaning_recipients); ?></textarea>
                        <p class="description">Recipients for cleaning reminders tied to each booking.</p>
                    </td>
                </tr>
            </table>
            <p><button class="button button-primary" type="submit">Save recipients</button></p>
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
                <p><strong>Guest:</strong> <?php echo esc_html(trim(($booking['name'] ?? '') . ' ' . ($booking['surname'] ?? ''))); ?> · <strong>Email:</strong> <?php echo esc_html($booking['email'] ?? ''); ?> · <strong>Loft:</strong> <?php echo esc_html($booking['room_name'] ?? ''); ?><?php if (!empty($booking['unit_name']) && $booking['unit_name'] !== ($booking['room_name'] ?? '')) : ?> <small style="color:#475569;">(Assigned: <?php echo esc_html($booking['unit_name']); ?>)</small><?php endif; ?></p>
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
                    <button class="button" type="submit" name="template_key" value="admin-confirmation">Send confirmation to admins</button>
                    <button class="button" type="submit" name="template_key" value="guest-receipt">Send/Resend invoice</button>
                    <button class="button" type="submit" name="template_key" value="admin-receipt">Send invoice to admins</button>
                    <button class="button" type="submit" name="template_key" value="guest-receipt-recreate">Recreate &amp; send invoice</button>
                    <button class="button" type="submit" name="template_key" value="guest-post-stay">Send/Resend post-stay</button>
                    <button class="button" type="submit" name="template_key" value="admin-summary">Send/Resend admin summary</button>
                    <button class="button" type="submit" name="template_key" value="cleaning-notice">Send cleaning reminder</button>
                </p>
                <p class="description">Manual sends are tagged as such in the email job log. Post-stay emails scheduled via automation are delayed until after checkout. Admin summaries deliver to your internal notification list.</p>
            </form>
        <?php elseif ($booking_id) : ?>
            <div class="notice notice-warning inline"><p>Booking not found for ID <?php echo esc_html($booking_id); ?>.</p></div>
        <?php endif; ?>

        <hr>

        <h2>Recent bookings (ND Booking)</h2>
        <p>Browse the latest ND Booking records and resend the same checkout-triggered emails.</p>
        <?php if (!empty($recent_bookings)) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Loft</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_bookings as $recent) : ?>
                        <tr>
                            <td><?php echo esc_html($recent['booking_id'] ?? ''); ?></td>
                            <td><?php echo esc_html(trim(($recent['name'] ?? '') . ' ' . ($recent['surname'] ?? ''))); ?><br><small><?php echo esc_html($recent['email'] ?? ''); ?></small></td>
                            <td>
                                <?php echo esc_html($recent['room_name'] ?? ''); ?>
                                <?php if (!empty($recent['unit_name']) && $recent['unit_name'] !== ($recent['room_name'] ?? '')) : ?>
                                    <br><small class="description">Assigned: <?php echo esc_html($recent['unit_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(($recent['date_from'] ?? '') . ' → ' . ($recent['date_to'] ?? '')); ?></td>
                            <td><?php echo esc_html(wp_loft_booking_format_currency($recent['total'] ?? 0, $recent['currency'] ?? 'CAD')); ?></td>
                            <td>
                                <form method="post" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                    <?php wp_nonce_field('wp_loft_booking_manual_send'); ?>
                                    <input type="hidden" name="wp_loft_booking_manual_send" value="1">
                                    <input type="hidden" name="booking_id" value="<?php echo esc_attr($recent['booking_id'] ?? 0); ?>">
                                    <button class="button" type="submit" name="template_key" value="guest-confirmation">Guest confirmation</button>
                                    <button class="button" type="submit" name="template_key" value="admin-confirmation">Admin confirmation</button>
                                    <button class="button" type="submit" name="template_key" value="guest-receipt">Guest invoice</button>
                                    <button class="button" type="submit" name="template_key" value="admin-receipt">Admin invoice</button>
                                    <button class="button" type="submit" name="template_key" value="admin-summary">Admin summary</button>
                                    <button class="button" type="submit" name="template_key" value="cleaning-notice">Cleaning team</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="description">No ND Booking records were found.</p>
        <?php endif; ?>

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
    global $wpdb;

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!empty($_POST['wp_loft_booking_admin_checkout'])) {
        check_admin_referer('wp_loft_booking_admin_checkout');

        $first_name = sanitize_text_field(wp_unslash($_POST['guest_first_name'] ?? ''));
        $last_name  = sanitize_text_field(wp_unslash($_POST['guest_last_name'] ?? ''));
        $email      = sanitize_email(wp_unslash($_POST['guest_email'] ?? ''));
        $phone      = sanitize_text_field(wp_unslash($_POST['guest_phone'] ?? ''));
        $room_type  = sanitize_text_field(wp_unslash($_POST['room_type'] ?? ''));
        $unit_id    = isset($_POST['preferred_unit_id']) ? absint(wp_unslash($_POST['preferred_unit_id'])) : 0;
        $checkin    = sanitize_text_field(wp_unslash($_POST['checkin_date'] ?? ''));
        $checkout   = sanitize_text_field(wp_unslash($_POST['checkout_date'] ?? ''));
        $payment    = isset($_POST['payment_total']) ? floatval(wp_unslash($_POST['payment_total'])) : null;
        $currency   = sanitize_text_field(wp_unslash($_POST['payment_currency'] ?? 'CAD'));
        $status     = sanitize_text_field(wp_unslash($_POST['payment_status'] ?? 'paid'));
        $txn_id     = sanitize_text_field(wp_unslash($_POST['transaction_id'] ?? ''));

        if (!$email || !$room_type || !$checkin || !$checkout) {
            add_settings_error(
                'wp_loft_booking_bookings',
                'admin_checkout_missing',
                __('Email, loft type, and stay dates are required for the admin checkout.', 'wp-loft-booking'),
                'error'
            );

            return;
        }

        $result = wp_loft_booking_process_booking(
            $email,
            $room_type,
            $checkin,
            $checkout,
            $first_name ?: 'Guest',
            $last_name ?: 'Booking',
            0,
            $phone,
            $payment,
            $currency,
            $status ?: 'paid',
            $txn_id,
            $unit_id ?: null
        );

        if (is_wp_error($result)) {
            add_settings_error(
                'wp_loft_booking_bookings',
                'admin_checkout_error',
                $result->get_error_message(),
                'error'
            );
        } else {
            add_settings_error(
                'wp_loft_booking_bookings',
                'admin_checkout_success',
                __('Admin checkout triggered. Emails, calendar events, and virtual keys are being generated.', 'wp-loft-booking'),
                'updated'
            );
        }
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

    if (!empty($_POST['wp_loft_booking_update_recipients'])) {
        check_admin_referer('wp_loft_booking_update_recipients');

        update_option(
            'loft_booking_notification_recipients',
            sanitize_textarea_field(wp_unslash($_POST['notification_recipients'] ?? ''))
        );
        update_option(
            'loft_booking_invoice_recipients',
            sanitize_textarea_field(wp_unslash($_POST['invoice_recipients'] ?? ''))
        );
        update_option(
            'loft_booking_cleaning_recipients',
            sanitize_textarea_field(wp_unslash($_POST['cleaning_recipients'] ?? ''))
        );

        add_settings_error(
            'wp_loft_booking_bookings',
            'recipients_saved',
            __('Recipient lists saved.', 'wp-loft-booking'),
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

        $result = null;

        switch ($template) {
            case 'guest-confirmation':
                $result = wp_loft_booking_send_confirmation_email($booking, [], true, ['dry_run' => $dry_run]);
                $result_message = __('Confirmation queued.', 'wp-loft-booking');
                break;
            case 'admin-confirmation':
                $result = wp_loft_booking_send_confirmation_email($booking, [], true, [
                    'dry_run'             => $dry_run,
                    'recipient_override'  => wp_loft_booking_get_notification_recipients(),
                    'bcc_override'        => [],
                ]);
                $result_message = __('Admin confirmation queued.', 'wp-loft-booking');
                break;
            case 'guest-receipt':
                $result = wp_loft_booking_send_receipt_email($booking, [], true, [
                    'dry_run'       => $dry_run,
                    'force_new_job' => $force_new_job,
                ]);
                $result_message = $force_new_job
                    ? __('Invoice regenerated and queued.', 'wp-loft-booking')
                    : __('Invoice queued.', 'wp-loft-booking');
                break;
            case 'admin-receipt':
                $result = wp_loft_booking_send_receipt_email($booking, [], true, [
                    'dry_run'            => $dry_run,
                    'recipient_override' => wp_loft_booking_get_invoice_recipients(),
                    'bcc_override'       => [],
                    'admin_context'      => true,
                    'force_new_job'      => $force_new_job,
                ]);
                $result_message = __('Admin invoice queued.', 'wp-loft-booking');
                break;
            case 'guest-post-stay':
                $send_at = $dry_run ? null : wp_loft_booking_calculate_post_stay_send_at($booking);
                $result = wp_loft_booking_send_post_stay_email($booking, true, [
                    'dry_run' => $dry_run,
                    'send_at' => $send_at,
                ]);
                $result_message = __('Post-stay email queued.', 'wp-loft-booking');
                break;
            case 'admin-summary':
                $result = wp_loft_booking_send_admin_summary_email($booking, [], true, [
                    'dry_run' => $dry_run,
                ]);
                $result_message = __('Admin summary queued.', 'wp-loft-booking');
                break;
            case 'cleaning-notice':
                $result = wp_loft_booking_send_cleaning_email($booking, true, [
                    'dry_run'            => $dry_run,
                    'recipient_override' => wp_loft_booking_get_cleaning_recipients(),
                ]);
                $result_message = __('Cleaning reminder queued.', 'wp-loft-booking');
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

        if (is_wp_error($result) || empty($result)) {
            $error_message = is_wp_error($result)
                ? $result->get_error_message()
                : __('Unknown error while queuing the email.', 'wp-loft-booking');

            add_settings_error(
                'wp_loft_booking_bookings',
                'manual_send_error',
                sprintf(__('Unable to queue email: %s', 'wp-loft-booking'), $error_message),
                'error'
            );

            return;
        }

        $job_note = is_int($result)
            ? ' ' . sprintf(__('(job #%d)', 'wp-loft-booking'), $result)
            : '';

        if (is_int($result)) {
            $job_link = add_query_arg(
                [
                    'page'   => 'wp_loft_booking_email_jobs',
                    'job_id' => $result,
                ],
                admin_url('admin.php')
            );

            $jobs_table = $wpdb->prefix . 'loft_email_jobs';
            $job_row    = $wpdb->get_row(
                $wpdb->prepare("SELECT status, last_error FROM {$jobs_table} WHERE id = %d", $result),
                ARRAY_A
            );

            if (!empty($job_row['status']) && 'failed' === $job_row['status']) {
                add_settings_error(
                    'wp_loft_booking_bookings',
                    'manual_send_failed',
                    sprintf(
                        __('Email job #%1$d failed immediately: %2$s. <a href="%3$s">View job log</a>.', 'wp-loft-booking'),
                        $result,
                        esc_html($job_row['last_error'] ?? __('Unknown error', 'wp-loft-booking')),
                        esc_url($job_link)
                    ),
                    'error'
                );

                return;
            }

            $job_note .= ' · <a href="' . esc_url($job_link) . '">' . __('View in Email Jobs', 'wp-loft-booking') . '</a>';
        }

        add_settings_error(
            'wp_loft_booking_bookings',
            'manual_send_success',
            $result_message . $suffix . $job_note,
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
    $transaction_id = '',
    $preferred_unit_id = null
) {
    global $wpdb;

    $room_type = strtoupper($room_type);

    $loft = null;

    if (!empty($preferred_unit_id)) {
        $units_table = $wpdb->prefix . 'loft_units';
        $loft = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$units_table} WHERE id = %d",
                (int) $preferred_unit_id
            )
        );

        if ($loft) {
            $wpdb->update(
                $units_table,
                ['status' => 'Reserved'],
                ['id' => $loft->id],
                ['%s'],
                ['%d']
            );
        }
    }

    if (!$loft) {
        $loft = find_first_available_loft_unit($room_type);
    }

    if (!$loft) {
        error_log('❌ No matching loft available.');

        return new WP_Error('no_loft_available', __('No matching loft is available for the selected type/unit.', 'wp-loft-booking'));
    }

    if (!$loft->unit_id_api) {
        error_log("❌ Missing unit_id_api for {$loft->unit_name}");

        return new WP_Error('missing_unit_api', __('The selected unit is missing a ButterflyMX unit ID.', 'wp-loft-booking'));
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

        return new WP_Error('invalid_dates', __('Check-in or check-out date could not be parsed.', 'wp-loft-booking'));
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

    return $booking_payload;
}
