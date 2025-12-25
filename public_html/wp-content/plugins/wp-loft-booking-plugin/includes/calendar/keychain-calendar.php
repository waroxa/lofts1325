<?php
defined('ABSPATH') || exit;

/**
 * Enqueue styles and scripts for the keychain calendar admin page.
 *
 * @param string $hook Current admin page hook suffix.
 */
function wp_loft_booking_keychain_calendar_enqueue( $hook ) {
    $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'loft-keychain-calendar' !== $page ) {
        return;
    }

    $plugin_path = trailingslashit( dirname( dirname( __FILE__ ) ) );
    $plugin_url  = trailingslashit( dirname( dirname( plugin_dir_url( __FILE__ ) ) ) );

    $css_file = $plugin_path . 'assets/css/keychain-calendar.css';
    $js_file  = $plugin_path . 'assets/js/keychain-calendar.js';

    $css_url = $plugin_url . 'assets/css/keychain-calendar.css';
    $js_url  = $plugin_url . 'assets/js/keychain-calendar.js';

    wp_enqueue_style( 'wp-loft-keychain-calendar', $css_url, array(), file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0' );
    wp_enqueue_script( 'wp-loft-keychain-calendar', $js_url, array( 'jquery' ), file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0', true );

    $units = wp_loft_booking_keychain_calendar_units();

    wp_localize_script(
        'wp-loft-keychain-calendar',
        'loftKeychainCalendar',
        array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'loft_keychain_calendar' ),
            'initialDate'  => wp_date( 'Y-m-d', current_time( 'timestamp' ) ),
            'initialView'  => 'week',
            'units'        => $units,
            'labels'       => array(
                'searchPlaceholder' => __( 'Search keychains, units, tenants…', 'wp-loft-booking' ),
                'noResults'         => __( 'No keychains match this view.', 'wp-loft-booking' ),
                'virtualKeys'       => __( 'Virtual keys', 'wp-loft-booking' ),
                'people'            => __( 'People', 'wp-loft-booking' ),
                'tenant'            => __( 'Tenant', 'wp-loft-booking' ),
            ),
            'editBase'     => admin_url( 'admin.php?page=wp_loft_booking_keychains&keychain_id=' ),
            'todayLabel'   => wp_date( get_option( 'date_format' ), current_time( 'timestamp' ) ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'wp_loft_booking_keychain_calendar_enqueue' );

/**
 * Render the Keychain Calendar admin page.
 */
function wp_loft_booking_keychain_calendar_page() {
    ?>
    <div class="wrap loft-keychain-calendar">
        <div class="loft-keychain-calendar__hero">
            <div>
                <p class="loft-keychain-calendar__eyebrow"><?php esc_html_e( 'Access orchestration', 'wp-loft-booking' ); ?></p>
                <h1><?php esc_html_e( 'Keychain Calendar', 'wp-loft-booking' ); ?></h1>
                <p class="loft-keychain-calendar__lede"><?php esc_html_e( 'Visualize when each keychain is active. Switch views to scan days, weeks, months, or the full year.', 'wp-loft-booking' ); ?></p>
            </div>
            <div class="loft-keychain-calendar__legend">
                <span class="loft-keychain-calendar__chip loft-keychain-calendar__chip--active"><?php esc_html_e( 'Active now', 'wp-loft-booking' ); ?></span>
                <span class="loft-keychain-calendar__chip loft-keychain-calendar__chip--future"><?php esc_html_e( 'Upcoming', 'wp-loft-booking' ); ?></span>
                <span class="loft-keychain-calendar__chip loft-keychain-calendar__chip--expired"><?php esc_html_e( 'Expired', 'wp-loft-booking' ); ?></span>
                <span class="loft-keychain-calendar__chip loft-keychain-calendar__chip--admin"><?php esc_html_e( 'Admin key', 'wp-loft-booking' ); ?></span>
            </div>
        </div>

        <div class="loft-keychain-calendar__controls" aria-label="<?php esc_attr_e( 'Calendar controls', 'wp-loft-booking' ); ?>">
            <div class="loft-keychain-calendar__search">
                <label for="loft-keychain-search" class="screen-reader-text"><?php esc_html_e( 'Search keychains', 'wp-loft-booking' ); ?></label>
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                <input id="loft-keychain-search" type="search" placeholder="<?php esc_attr_e( 'Search keychains, units, tenants…', 'wp-loft-booking' ); ?>" />
            </div>
            <div class="loft-keychain-calendar__filters">
                <label>
                    <span class="screen-reader-text"><?php esc_html_e( 'Filter by unit', 'wp-loft-booking' ); ?></span>
                    <select id="loft-keychain-unit-filter">
                        <option value=""><?php esc_html_e( 'All units', 'wp-loft-booking' ); ?></option>
                    </select>
                </label>
                <label class="loft-keychain-calendar__toggle">
                    <input type="checkbox" id="loft-keychain-admin-filter" />
                    <span><?php esc_html_e( 'Only admin keys', 'wp-loft-booking' ); ?></span>
                </label>
                <label class="loft-keychain-calendar__toggle">
                    <input type="checkbox" id="loft-keychain-vk-filter" />
                    <span><?php esc_html_e( 'Virtual keys > 0', 'wp-loft-booking' ); ?></span>
                </label>
            </div>
            <div class="loft-keychain-calendar__view-switcher" role="group" aria-label="<?php esc_attr_e( 'Switch calendar view', 'wp-loft-booking' ); ?>">
                <button class="button loft-keychain-calendar__nav" data-nav="prev" aria-label="<?php esc_attr_e( 'Previous range', 'wp-loft-booking' ); ?>">&larr;</button>
                <button class="button loft-keychain-calendar__nav" data-nav="today"><?php esc_html_e( 'Today', 'wp-loft-booking' ); ?></button>
                <button class="button loft-keychain-calendar__nav" data-nav="next" aria-label="<?php esc_attr_e( 'Next range', 'wp-loft-booking' ); ?>">&rarr;</button>
                <div class="loft-keychain-calendar__views">
                    <button class="button button-secondary" data-view="day"><?php esc_html_e( 'Day', 'wp-loft-booking' ); ?></button>
                    <button class="button button-secondary" data-view="week"><?php esc_html_e( 'Week', 'wp-loft-booking' ); ?></button>
                    <button class="button button-secondary" data-view="month"><?php esc_html_e( 'Month', 'wp-loft-booking' ); ?></button>
                    <button class="button button-secondary" data-view="year"><?php esc_html_e( 'Year', 'wp-loft-booking' ); ?></button>
                </div>
            </div>
        </div>

        <div class="loft-keychain-calendar__summary" role="status" aria-live="polite"></div>

        <div class="loft-keychain-calendar__canvas" id="loft-keychain-calendar" aria-live="polite"></div>
    </div>
    <?php
}

/**
 * AJAX handler to return keychain events for the requested range.
 */
function wp_loft_booking_keychain_calendar_data() {
    check_ajax_referer( 'loft_keychain_calendar', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have permission to view this calendar.', 'wp-loft-booking' ) ), 403 );
    }

    $start   = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : '';
    $end     = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : '';
    $search  = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
    $unit    = isset( $_GET['unit'] ) ? sanitize_text_field( wp_unslash( $_GET['unit'] ) ) : '';
    $admin   = isset( $_GET['admin'] ) ? (bool) intval( $_GET['admin'] ) : false;
    $has_vk  = isset( $_GET['virtual_keys'] ) ? (bool) intval( $_GET['virtual_keys'] ) : false;
    $per_row = isset( $_GET['limit'] ) ? max( 50, intval( $_GET['limit'] ) ) : 400;

    $args = array(
        'start'          => $start,
        'end'            => $end,
        'search'         => $search,
        'unit'           => $unit,
        'only_admin'     => $admin,
        'only_virtual'   => $has_vk,
        'limit'          => $per_row,
    );

    $results = wp_loft_booking_query_keychain_calendar( $args );

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_loft_keychain_calendar_data', 'wp_loft_booking_keychain_calendar_data' );

/**
 * Query keychains and prepare resource + event payloads.
 *
 * @param array $args Query arguments.
 *
 * @return array
 */
function wp_loft_booking_query_keychain_calendar( $args ) {
    global $wpdb;

    $defaults = array(
        'start'        => '',
        'end'          => '',
        'search'       => '',
        'unit'         => '',
        'only_admin'   => false,
        'only_virtual' => false,
        'limit'        => 400,
    );

    $args = wp_parse_args( $args, $defaults );

    $kc_table    = $wpdb->prefix . 'loft_keychains';
    $kc_vk_table = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $vk_table    = $wpdb->prefix . 'loft_virtual_keys';
    $units_table = $wpdb->prefix . 'loft_units';
    $ten_table   = $wpdb->prefix . 'loft_tenants';

    $where  = array();
    $params = array();

    if ( $args['start'] ) {
        $where[]  = '(kc.valid_until >= %s)';
        $params[] = $args['start'];
    }

    if ( $args['end'] ) {
        $where[]  = '(kc.valid_from <= %s)';
        $params[] = $args['end'];
    }

    if ( $args['search'] ) {
        $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        $where[]  = '(kc.name LIKE %s OR u.unit_name LIKE %s OR CONCAT_WS(" ", t.first_name, t.last_name) LIKE %s)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ( $args['unit'] ) {
        if ( __( 'Unassigned / Unknown', 'wp-loft-booking' ) === $args['unit'] ) {
            $where[] = '(u.unit_name IS NULL OR u.unit_name = %s)';
        } else {
            $where[] = 'u.unit_name = %s';
        }
        $params[] = $args['unit'];
    }

    if ( $args['only_admin'] ) {
        $where[] = "EXISTS (SELECT 1 FROM {$kc_vk_table} kvk INNER JOIN {$vk_table} vk ON kvk.key_id = vk.id WHERE kvk.keychain_id = kc.id AND vk.key_type = 'admin')";
    }

    if ( $args['only_virtual'] ) {
        $where[] = "EXISTS (SELECT 1 FROM {$kc_vk_table} kvk WHERE kvk.keychain_id = kc.id)";
    }

    $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

    $sql = "SELECT kc.*, u.unit_name, u.id as unit_id, t.first_name, t.last_name,
                COUNT(kvk.key_id) as vk_total,
                SUM(CASE WHEN vk.key_type = 'admin' THEN 1 ELSE 0 END) as admin_keys
            FROM {$kc_table} kc
            LEFT JOIN {$units_table} u ON kc.unit_id = u.id
            LEFT JOIN {$ten_table} t ON kc.tenant_id = t.id
            LEFT JOIN {$kc_vk_table} kvk ON kvk.keychain_id = kc.id
            LEFT JOIN {$vk_table} vk ON kvk.key_id = vk.id
            {$where_sql}
            GROUP BY kc.id
            ORDER BY kc.valid_from ASC
            LIMIT %d";

    $params[] = max( 1, (int) $args['limit'] );

    $prepared = $wpdb->prepare( $sql, $params );
    $rows     = $wpdb->get_results( $prepared, ARRAY_A );

    $resources      = array();
    $events         = array();
    $today_ts       = current_time( 'timestamp' );
    $range_start_ts = $args['start'] ? strtotime( $args['start'] ) : false;
    $range_end_ts   = $args['end'] ? strtotime( $args['end'] ) : false;

    foreach ( $rows as $row ) {
        $tenant_first = isset( $row['first_name'] ) ? sanitize_text_field( $row['first_name'] ) : '';
        $tenant_last  = isset( $row['last_name'] ) ? sanitize_text_field( $row['last_name'] ) : '';
        $tenant_name  = trim( $tenant_first . ' ' . $tenant_last );

        $unit_label = isset( $row['unit_name'] ) ? sanitize_text_field( $row['unit_name'] ) : '';
        $unit_id    = isset( $row['unit_id'] ) ? 'unit-' . absint( $row['unit_id'] ) : 'unit-unassigned';
        $unit_title = $unit_label ? $unit_label : __( 'Unassigned / Unknown', 'wp-loft-booking' );

        $start_mysql = isset( $row['valid_from'] ) ? sanitize_text_field( $row['valid_from'] ) : '';
        $end_mysql   = isset( $row['valid_until'] ) ? sanitize_text_field( $row['valid_until'] ) : '';

        $start_ts = $start_mysql ? strtotime( $start_mysql ) : false;
        $end_ts   = $end_mysql ? strtotime( $end_mysql ) : false;

        $status = 'future';

        if ( $end_ts && $end_ts < $today_ts ) {
            $status = 'expired';
        } elseif ( $start_ts && $start_ts <= $today_ts && ( ! $end_ts || $end_ts >= $today_ts ) ) {
            $status = 'active';
        }

        if ( ! isset( $resources[ $unit_id ] ) ) {
            $resources[ $unit_id ] = array(
                'id'          => $unit_id,
                'title'       => $unit_title,
                'keys_total'  => 0,
                'active_keys' => 0,
            );
        }

        $resources[ $unit_id ]['keys_total']++;

        if ( $range_start_ts && $range_end_ts && $start_ts && $end_ts ) {
            if ( $start_ts <= $range_end_ts && $end_ts >= $range_start_ts ) {
                $resources[ $unit_id ]['active_keys']++;
            }
        } elseif ( 'active' === $status ) {
            $resources[ $unit_id ]['active_keys']++;
        }

        if ( ! $start_mysql || ! $end_mysql ) {
            continue;
        }

        $events[] = array(
            'resourceId'   => $unit_id,
            'start'        => mysql_to_rfc3339( $start_mysql ),
            'end'          => mysql_to_rfc3339( $end_mysql ),
            'title'        => sprintf( _n( '%d virtual key', '%d virtual keys', (int) $row['vk_total'], 'wp-loft-booking' ), (int) $row['vk_total'] ),
            'tenant'       => $tenant_name,
            'unit'         => $unit_title,
            'status'       => $status,
            'admin'        => isset( $row['admin_keys'] ) && (int) $row['admin_keys'] > 0,
            'keychain'     => $row['name'] ? sanitize_text_field( $row['name'] ) : '',
            'virtual'      => (int) $row['vk_total'],
            'keychain_id'  => isset( $row['id'] ) ? (int) $row['id'] : 0,
            'valid_from'   => $start_mysql,
            'valid_until'  => $end_mysql,
        );
    }

    return array(
        'resources' => array_values( $resources ),
        'events'    => $events,
        'meta'      => array(
            'count' => count( $events ),
        ),
    );
}

/**
 * Retrieve units for the dropdown filter.
 *
 * @return array
 */
function wp_loft_booking_keychain_calendar_units() {
    global $wpdb;

    $units_table = $wpdb->prefix . 'loft_units';

    $rows = $wpdb->get_col( "SELECT unit_name FROM {$units_table} ORDER BY unit_name ASC" );

    if ( ! $rows ) {
        return array();
    }

    return array_map( 'sanitize_text_field', array_filter( $rows ) );
}
