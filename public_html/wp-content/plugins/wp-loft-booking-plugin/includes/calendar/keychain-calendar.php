<?php
defined('ABSPATH') || exit;

function wp_loft_booking_keychain_calendar_page() {
    error_log('✅ Rendering Loft Booking keychain calendar page.');

    $payload = wp_loft_booking_prepare_keychain_calendar_payload();

    wp_localize_script(
        'wp-loft-booking-calendar',
        'wpLoftCalendarData',
        [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('wp_loft_calendar'),
            'payload'     => $payload,
            'statuses'    => [],
            'keyStatuses' => [
                'active'   => __('Active now', 'wp-loft-booking'),
                'upcoming' => __('Upcoming', 'wp-loft-booking'),
                'expired'  => __('Expired', 'wp-loft-booking'),
            ],
        ]
    );

    ?>
    <div class="wrap loft-calendar">
        <div class="loft-calendar__hero">
            <div class="loft-calendar__hero-text">
                <p class="loft-calendar__eyebrow">Loft 1325 operations</p>
                <h1>Virtual key coverage at a glance</h1>
                <p class="loft-calendar__lede">See every configured keychain by loft, coloured by unit so you can spot gaps instantly.</p>
                <div class="loft-calendar__chips">
                    <span class="loft-chip loft-chip--primary">🔑 Total keys <strong><?php echo esc_html($payload['summary']['total']); ?></strong></span>
                    <span class="loft-chip loft-chip--info">🟢 Active today <strong><?php echo esc_html($payload['summary']['active']); ?></strong></span>
                    <span class="loft-chip loft-chip--muted">⏳ Upcoming <strong><?php echo esc_html($payload['summary']['upcoming']); ?></strong></span>
                    <span class="loft-chip loft-chip--warning">⌛ Expired <strong><?php echo esc_html($payload['summary']['expired']); ?></strong></span>
                </div>
            </div>
        </div>

        <section class="loft-calendar__panel loft-calendar__panel--full">
            <header class="loft-calendar__panel-heading">
                <div>
                    <p class="loft-calendar__eyebrow">Access</p>
                    <h2 class="loft-calendar__title">Key calendar</h2>
                    <p class="description">Colours mirror lofts. Each bar shows how long the keychain is valid for and which loft it belongs to.</p>
                </div>
                <div class="loft-calendar__controls">
                    <div class="loft-calendar__filters" data-calendar-target="keys">
                        <label class="loft-calendar__filter">
                            <input type="checkbox" value="active" checked />
                            <span><?php printf(esc_html__('Active (%s)', 'wp-loft-booking'), number_format_i18n($payload['summary']['active'])); ?></span>
                        </label>
                        <label class="loft-calendar__filter">
                            <input type="checkbox" value="upcoming" />
                            <span><?php printf(esc_html__('Upcoming (%s)', 'wp-loft-booking'), number_format_i18n($payload['summary']['upcoming'])); ?></span>
                        </label>
                        <label class="loft-calendar__filter">
                            <input type="checkbox" value="expired" />
                            <span><?php printf(esc_html__('Expired (%s)', 'wp-loft-booking'), number_format_i18n($payload['summary']['expired'])); ?></span>
                        </label>
                    </div>
                    <div class="loft-calendar__nav" data-calendar-target="keys"></div>
                </div>
            </header>
            <div id="loft-keys-calendar" class="loft-calendar__canvas" data-calendar-type="keys"></div>
            <div class="loft-calendar__legend">
                <span class="loft-chip loft-chip--primary">Active</span>
                <span class="loft-chip loft-chip--info">Upcoming</span>
                <span class="loft-chip loft-chip--muted">Expired</span>
            </div>
        </section>
    </div>
    <?php
}

function wp_loft_booking_prepare_keychain_calendar_payload() {
    global $wpdb;

    $kc_table    = $wpdb->prefix . 'loft_keychains';
    $kc_vk_table = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $vk_table    = $wpdb->prefix . 'loft_virtual_keys';
    $units_table = $wpdb->prefix . 'loft_units';

    $window_start = wp_date('Y-m-d', strtotime('-1 year', current_time('timestamp')));
    $window_end   = wp_date('Y-m-d', strtotime('+1 year', current_time('timestamp')));

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT kc.*, u.unit_name
             FROM {$kc_table} kc
             LEFT JOIN {$units_table} u ON kc.unit_id = u.id
             WHERE (kc.valid_until IS NULL OR kc.valid_until >= %s)
               AND (kc.valid_from IS NULL OR kc.valid_from <= %s)
             ORDER BY COALESCE(kc.valid_from, kc.created_at) ASC
             LIMIT 600",
            $window_start,
            $window_end
        ),
        ARRAY_A
    );

    $summary = [
        'total'    => 0,
        'active'   => 0,
        'upcoming' => 0,
        'expired'  => 0,
    ];

    $keys = [];
    $today_ts = current_time('timestamp');

    foreach ($rows as $row) {
        $start = wp_loft_booking_normalize_date($row['valid_from'] ?? '') ?: wp_loft_booking_normalize_date($row['created_at'] ?? '');
        $end   = wp_loft_booking_normalize_date($row['valid_until'] ?? '') ?: $start;

        $key_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vk.name, vk.key_type, vk.key_status
                 FROM {$kc_vk_table} kc_vk
                 LEFT JOIN {$vk_table} vk ON kc_vk.key_id = vk.id
                 WHERE kc_vk.keychain_id = %d",
                (int) $row['id']
            ),
            ARRAY_A
        );

        $names = array_values(array_filter(array_map(
            static function ($key) {
                return isset($key['name']) ? sanitize_text_field($key['name']) : '';
            },
            $key_rows
        )));

        $start_ts = $start ? strtotime($start . ' 00:00:00') : false;
        $end_ts   = $end ? strtotime($end . ' 23:59:59') : false;

        if ($end_ts && $end_ts < $today_ts) {
            $status = 'expired';
        } elseif ($start_ts && $start_ts > $today_ts) {
            $status = 'upcoming';
        } else {
            $status = 'active';
        }

        $summary['total']++;
        $summary[$status]++;

        $keys[] = [
            'id'          => (int) $row['id'],
            'loft'        => wp_loft_booking_format_unit_label($row['unit_name'] ?? ''),
            'loft_label'  => wp_loft_booking_format_unit_label($row['unit_name'] ?? ''),
            'key_label'   => $row['name'] ? sanitize_text_field($row['name']) : __('Keychain', 'wp-loft-booking'),
            'key_names'   => $names,
            'start'       => $start,
            'end'         => $end,
            'status'      => $status,
        ];
    }

    return [
        'keys'    => $keys,
        'summary' => $summary,
        'today'   => wp_date('Y-m-d', $today_ts),
    ];
}
