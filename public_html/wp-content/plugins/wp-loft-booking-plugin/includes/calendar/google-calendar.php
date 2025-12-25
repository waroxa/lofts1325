<?php
defined('ABSPATH') || exit;

add_action('admin_enqueue_scripts', 'wp_loft_booking_calendar_admin_assets');
add_action('wp_ajax_wp_loft_booking_calendar_snapshot', 'wp_loft_booking_ajax_calendar_snapshot');
add_action('wp_ajax_wp_loft_booking_update_cleaning_status', 'wp_loft_booking_ajax_update_cleaning_status');

function wp_loft_booking_calendar_admin_assets($hook) {
    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

    $pages_with_calendar = ['loft-booking-google-calendar', 'loft-keychain-calendar'];

    if (!in_array($page, $pages_with_calendar, true)) {
        return;
    }

    $css_path = dirname(__DIR__, 2) . '/assets/css/calendar-dashboard.css';
    $js_path  = dirname(__DIR__, 2) . '/assets/js/calendar-dashboard.js';

    wp_enqueue_style(
        'wp-loft-booking-calendar',
        plugins_url('assets/css/calendar-dashboard.css', dirname(__DIR__, 2) . '/wp-loft-booking-plugin.php'),
        [],
        file_exists($css_path) ? filemtime($css_path) : wp_get_theme()->get('Version')
    );

    wp_enqueue_script(
        'wp-loft-booking-calendar',
        plugins_url('assets/js/calendar-dashboard.js', dirname(__DIR__, 2) . '/wp-loft-booking-plugin.php'),
        ['jquery'],
        file_exists($js_path) ? filemtime($js_path) : wp_get_theme()->get('Version'),
        true
    );
}

function wp_loft_booking_cleaning_status_labels() {
    return [
        'pending'     => __('Awaiting assignment', 'wp-loft-booking'),
        'assigned'    => __('Assigned', 'wp-loft-booking'),
        'in_progress' => __('In progress', 'wp-loft-booking'),
        'ready'       => __('Ready for arrival', 'wp-loft-booking'),
        'done'        => __('Approved', 'wp-loft-booking'),
        'issue'       => __('Issue reported', 'wp-loft-booking'),
    ];
}

function wp_loft_booking_cleaning_needs_attention($cleaning_date, $status) {
    $status = wp_loft_booking_normalize_cleaning_status($status);

    if (in_array($status, ['ready', 'done'], true)) {
        return false;
    }

    $today      = current_time('timestamp');
    $cleaning_ts = $cleaning_date ? strtotime($cleaning_date) : false;

    if (!$cleaning_ts) {
        return true;
    }

    return $cleaning_ts <= strtotime('+3 days', $today);
}

function wp_loft_booking_normalize_date($raw_date) {
    if (empty($raw_date)) {
        return '';
    }

    $timestamp = strtotime($raw_date);

    if (!$timestamp) {
        return '';
    }

    return wp_date('Y-m-d', $timestamp);
}

function wp_loft_booking_prepare_calendar_payload() {
    global $wpdb;

    $table        = $wpdb->prefix . 'nd_booking_booking';
    $window_start = wp_date('Y-m-d', strtotime('-2 years', current_time('timestamp')));
    $window_end   = wp_date('Y-m-d', strtotime('+2 years', current_time('timestamp')));

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE date_to >= %s AND date_from <= %s ORDER BY date_from ASC LIMIT 600",
            $window_start,
            $window_end
        ),
        ARRAY_A
    );

    $plugin_bookings_table = $wpdb->prefix . 'loft_bookings';
    $units_table           = $wpdb->prefix . 'loft_units';

    $status_store = wp_loft_booking_get_cleaning_status_store();
    $bookings     = [];
    $cleaning     = [];
    $summary      = [
        'upcoming_bookings' => 0,
        'arrivals_today'    => 0,
        'departures_today'  => 0,
        'pending_cleaning'  => 0,
    ];

    $today = current_time('Y-m-d');

    foreach ($rows as $row) {
        $booking = wp_loft_booking_build_booking_payload((int) $row['id']);

        if (empty($booking)) {
            continue;
        }

        $virtual_keys      = wp_loft_booking_get_virtual_key_details($booking);
        $virtual_key_label = wp_loft_booking_format_virtual_key_summary($virtual_keys, 'en');
        $booking_id   = isset($booking['booking_id']) ? (int) $booking['booking_id'] : (int) $row['id'];
        $room_name    = wp_loft_booking_format_unit_label($booking['room_name'] ?? '');
        $guest_name   = trim(sprintf('%s %s', $booking['name'] ?? '', $booking['surname'] ?? '')) ?: __('Guest', 'wp-loft-booking');
        $checkin      = wp_loft_booking_normalize_date($booking['date_from'] ?? '');
        $checkout     = wp_loft_booking_normalize_date($booking['date_to'] ?? '');
        $booking['date_from'] = $checkin;
        $booking['date_to']   = $checkout;

        $currency     = $booking['currency'] ?? 'CAD';
        $payment      = strtolower((string) ($booking['payment_status'] ?? 'confirmed'));
        $nights       = wp_loft_booking_calculate_nights($booking);
        $status_data  = $status_store[$booking_id] ?? [];
        $clean_status = wp_loft_booking_normalize_cleaning_status($status_data['status'] ?? 'pending');

        $bookings[] = [
            'id'                => $booking_id,
            'loft'              => $room_name ?: __('Loft', 'wp-loft-booking'),
            'loft_label'        => $virtual_keys['loft_label'] ?? ($room_name ?: __('Loft', 'wp-loft-booking')),
            'guest'             => $guest_name,
            'start'             => $checkin,
            'end'               => $checkout,
            'nights'            => $nights,
            'status'            => $payment ?: 'confirmed',
            'amount'            => wp_loft_booking_format_currency($booking['total'] ?? 0, $currency),
            'currency'          => $currency,
            'virtual_keys'      => $virtual_keys['virtual_keys'] ?? [],
            'virtual_key_label' => $virtual_key_label,
        ];

        $attention = wp_loft_booking_cleaning_needs_attention($checkout, $clean_status);

        $cleaning[] = [
            'booking_id'      => $booking_id,
            'loft'            => $room_name ?: __('Loft', 'wp-loft-booking'),
            'guest'           => $guest_name,
            'arrival'         => $checkin,
            'departure'       => $checkout,
            'cleaning_date'   => $checkout,
            'status'          => $clean_status,
            'status_label'    => wp_loft_booking_cleaning_status_labels()[$clean_status] ?? ucfirst($clean_status),
            'note'            => $status_data['note'] ?? '',
            'email_sent'      => !empty($status_data['email_sent']) || !empty($status_data['notified_at']),
            'notified_at'     => $status_data['notified_at'] ?? '',
            'needs_attention' => $attention,
        ];

        $summary['upcoming_bookings']++;

        if ($checkin === $today) {
            $summary['arrivals_today']++;
        }

        if ($checkout === $today) {
            $summary['departures_today']++;
        }

        if ($attention) {
            $summary['pending_cleaning']++;
        }
    }

    // Fallback to bookings saved through the custom Loft Booking table so the calendar never renders empty.
    $plugin_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT b.*, u.unit_name
             FROM {$plugin_bookings_table} b
             LEFT JOIN {$units_table} u ON b.unit_id = u.id
             WHERE b.checkout_date >= %s AND b.checkin_date <= %s
             ORDER BY b.checkin_date ASC
             LIMIT 600",
            $window_start,
            $window_end
        ),
        ARRAY_A
    );

    foreach ($plugin_rows as $row) {
        $checkin  = wp_loft_booking_normalize_date($row['checkin_date'] ?? '');
        $checkout = wp_loft_booking_normalize_date($row['checkout_date'] ?? '');

        if (!$checkin || !$checkout) {
            continue;
        }

        $booking_id  = (int) ($row['id'] ?? 0);
        $room_name   = wp_loft_booking_format_unit_label($row['unit_name'] ?? '');
        $guest_name  = trim((string) ($row['customer_name'] ?? '')) ?: __('Guest', 'wp-loft-booking');
        $nights      = max(1, (int) round((strtotime($checkout) - strtotime($checkin)) / DAY_IN_SECONDS));
        $payment     = strtolower((string) ($row['payment_status'] ?? 'confirmed')) ?: 'confirmed';
        $clean_state = $status_store[$booking_id]['status'] ?? 'pending';
        $attention   = wp_loft_booking_cleaning_needs_attention($checkout, $clean_state);

        $bookings[] = [
            'id'         => $booking_id,
            'loft'       => $room_name ?: __('Loft', 'wp-loft-booking'),
            'loft_label' => $room_name ?: __('Loft', 'wp-loft-booking'),
            'guest'      => $guest_name,
            'start'      => $checkin,
            'end'        => $checkout,
            'nights'     => $nights,
            'status'     => $payment,
            'amount'     => wp_loft_booking_format_currency((float) ($row['total_amount'] ?? 0), 'CAD'),
            'currency'   => 'CAD',
        ];

        $cleaning[] = [
            'booking_id'      => $booking_id,
            'loft'            => $room_name ?: __('Loft', 'wp-loft-booking'),
            'guest'           => $guest_name,
            'arrival'         => $checkin,
            'departure'       => $checkout,
            'cleaning_date'   => $checkout,
            'status'          => wp_loft_booking_normalize_cleaning_status($clean_state),
            'status_label'    => wp_loft_booking_cleaning_status_labels()[$clean_state] ?? ucfirst($clean_state),
            'note'            => $status_store[$booking_id]['note'] ?? '',
            'email_sent'      => !empty($status_store[$booking_id]['email_sent']) || !empty($status_store[$booking_id]['notified_at']),
            'notified_at'     => $status_store[$booking_id]['notified_at'] ?? '',
            'needs_attention' => $attention,
        ];

        $summary['upcoming_bookings']++;

        if ($checkin === $today) {
            $summary['arrivals_today']++;
        }

        if ($checkout === $today) {
            $summary['departures_today']++;
        }

        if ($attention) {
            $summary['pending_cleaning']++;
        }
    }

    return [
        'today'         => $today,
        'bookings'      => $bookings,
        'cleaning'      => $cleaning,
        'summary'       => $summary,
        'status_labels' => wp_loft_booking_cleaning_status_labels(),
        'window'        => [
            'start' => $window_start,
            'end'   => $window_end,
        ],
    ];
}

function wp_loft_booking_ajax_calendar_snapshot() {
    check_ajax_referer('wp_loft_calendar', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to view the calendar.', 'wp-loft-booking'), 403);
    }

    wp_send_json_success(wp_loft_booking_prepare_calendar_payload());
}

function wp_loft_booking_ajax_update_cleaning_status() {
    check_ajax_referer('wp_loft_calendar', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to update cleaning status.', 'wp-loft-booking'), 403);
    }

    $booking_id = absint($_POST['booking_id'] ?? 0);
    $status     = wp_loft_booking_normalize_cleaning_status($_POST['status'] ?? 'pending');
    $note       = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));

    if (!$booking_id) {
        wp_send_json_error(__('Missing booking reference.', 'wp-loft-booking'), 400);
    }

    $user        = wp_get_current_user();
    $updated_by  = $user && $user->exists() ? ($user->display_name ?: $user->user_login) : __('System', 'wp-loft-booking');
    $status_data = wp_loft_booking_touch_cleaning_status(
        $booking_id,
        [
            'status'     => $status,
            'note'       => $note,
            'updated_at' => current_time('mysql'),
            'updated_by' => $updated_by,
        ]
    );

    if (empty($status_data['email_sent'])) {
        $booking_payload = wp_loft_booking_build_booking_payload($booking_id);

        if (!empty($booking_payload)) {
            $send_result = wp_loft_booking_send_cleaning_email($booking_payload, true, ['force_new_job' => true]);

            if (!is_wp_error($send_result)) {
                $status_data = wp_loft_booking_mark_cleaning_email_sent($booking_id);
            }
        }
    }

    wp_send_json_success([
        'status'   => $status_data,
        'snapshot' => wp_loft_booking_prepare_calendar_payload(),
    ]);
}

function loft_booking_google_calendar_page() {
    if (isset($_GET['connected']) && $_GET['connected'] == 1) {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Successfully connected to Google Calendar.</p></div>';
    }

    $auth_url = loft_booking_get_google_auth_url();
    $payload  = wp_loft_booking_prepare_calendar_payload();

    wp_localize_script(
        'wp-loft-booking-calendar',
        'wpLoftCalendarData',
        [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('wp_loft_calendar'),
            'payload'   => $payload,
            'statuses'  => wp_loft_booking_cleaning_status_labels(),
        ]
    );
    ?>
    <div class="wrap loft-calendar">
        <div class="loft-calendar__hero">
            <div class="loft-calendar__hero-text">
                <p class="loft-calendar__eyebrow">Loft 1325 operations</p>
                <h1>Bookings & cleaning at a glance</h1>
                <p class="loft-calendar__lede">Google-inspired tiles show who is arriving, which loft needs attention, and when the cleaning crew has finished.</p>
                <div class="loft-calendar__chips">
                    <span class="loft-chip loft-chip--primary">📅 Upcoming bookings <strong><?php echo esc_html($payload['summary']['upcoming_bookings']); ?></strong></span>
                    <span class="loft-chip loft-chip--info">🧳 Arrivals today <strong><?php echo esc_html($payload['summary']['arrivals_today']); ?></strong></span>
                    <span class="loft-chip loft-chip--warning">🧹 Cleanings to approve <strong><?php echo esc_html($payload['summary']['pending_cleaning']); ?></strong></span>
                    <?php if (!empty($payload['window']['start']) && !empty($payload['window']['end'])) : ?>
                        <span class="loft-chip loft-chip--muted">📆 Showing <?php echo esc_html(wp_date('M Y', strtotime($payload['window']['start']))); ?> – <?php echo esc_html(wp_date('M Y', strtotime($payload['window']['end']))); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="loft-calendar__actions">
                <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary loft-calendar__cta">Connect Google Calendar</a>
                <p class="description">Keeps the bookings calendar in sync with your Google workspace.</p>
            </div>
        </div>

        <div class="loft-calendar__grid">
            <section class="loft-calendar__panel">
                <header class="loft-calendar__panel-heading">
                    <div>
                        <p class="loft-calendar__eyebrow">Bookings</p>
                        <h2 class="loft-calendar__title">Guest calendar</h2>
                        <p class="description">See every stay on a bright, card-style grid with total nights and payment status.</p>
                    </div>
                    <div class="loft-calendar__nav" data-calendar-target="bookings"></div>
                </header>
                <div id="loft-bookings-calendar" class="loft-calendar__canvas" data-calendar-type="bookings"></div>
            </section>

            <section class="loft-calendar__panel">
                <header class="loft-calendar__panel-heading">
                    <div>
                        <p class="loft-calendar__eyebrow">Housekeeping</p>
                        <h2 class="loft-calendar__title">Cleaning readiness</h2>
                        <p class="description">Assign, track and approve cleanings with one click.</p>
                    </div>
                    <div class="loft-calendar__nav" data-calendar-target="cleaning"></div>
                </header>
                <div id="loft-cleaning-calendar" class="loft-calendar__canvas" data-calendar-type="cleaning"></div>
                <div class="loft-calendar__legend">
                    <span class="loft-chip loft-chip--muted">Pending</span>
                    <span class="loft-chip loft-chip--accent">In progress</span>
                    <span class="loft-chip loft-chip--success">Approved</span>
                    <span class="loft-chip loft-chip--alert">Needs attention</span>
                </div>
            </section>
        </div>

        <section class="loft-calendar__panel loft-calendar__panel--full">
            <header class="loft-calendar__panel-heading">
                <div>
                    <p class="loft-calendar__eyebrow">Cleaning queue</p>
                    <h2 class="loft-calendar__title">Who cleans next?</h2>
                    <p class="description">Prioritised list of departures so the team can approve rooms as soon as they are spotless.</p>
                </div>
            </header>
            <div id="loft-cleaning-queue" class="loft-calendar__queue"></div>
        </section>

        <section class="loft-calendar__panel loft-calendar__panel--full">
            <header class="loft-calendar__panel-heading">
                <div>
                    <p class="loft-calendar__eyebrow">Google</p>
                    <h2 class="loft-calendar__title">Legacy Google calendar feed</h2>
                    <p class="description">Your existing calendar embed stays for reference.</p>
                </div>
            </header>
            <div class="loft-calendar__embed">
                <iframe title="Google Calendar" src="https://calendar.google.com/calendar/embed?src=a752f27cffee8c22988adb29fdc933c93184e3a5814c79dcee4f62115d69fbfd%40group.calendar.google.com&amp;ctz=America%2FToronto" style="border:0" width="100%" height="520" frameborder="0" scrolling="no"></iframe>
            </div>
        </section>
    </div>
    <?php
}

function loft_booking_get_google_auth_url() {
    $client_id = '1057657895142-bkv4nmceeie0b79s3l6nuv9v8c8t5mbn.apps.googleusercontent.com';
    $redirect_uri = admin_url('admin.php?page=loft-booking-google-auth');

    $scopes = [
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',
        'https://www.googleapis.com/auth/calendar.events.readonly',
        'https://www.googleapis.com/auth/calendar.readonly',
    ];

    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => implode(' ', $scopes),
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);

    return $auth_url;
}

function add_booking_to_google_calendar($summary, $start, $end) {
    $calendar_id  = get_option('loft_booking_calendar_id');
    $access_token = get_option('google_calendar_access_token');

    $event = [
        'summary'     => $summary,
        'description' => 'Automated guest booking.',
        'start'       => [
            'dateTime' => date('c', strtotime($start)),
            'timeZone' => 'America/Toronto',
        ],
        'end'         => [
            'dateTime' => date('c', strtotime($end)),
            'timeZone' => 'America/Toronto',
        ],
    ];

    $response = wp_remote_post("https://www.googleapis.com/calendar/v3/calendars/{$calendar_id}/events", [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode($event),
    ]);

    error_log("\xF0\x9F\x93\xA4 Google Calendar API response: " . print_r($response, true));

    if (is_wp_error($response)) {
        error_log("\xE2\x9D\x8C Google Calendar error: " . $response->get_error_message());
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

add_action('admin_footer', function() {
    if (isset($_GET['test_gcal'])) {
        $result = add_booking_to_google_calendar("Test Event", date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+1 hour')));
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Test event sent to Google Calendar</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to send test event</p></div>';
        }
    }
});
