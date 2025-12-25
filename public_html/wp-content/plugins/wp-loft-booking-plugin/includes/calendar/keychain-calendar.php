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
                'searchPlaceholder' => __( 'Search tenants, units, or keychains…', 'wp-loft-booking' ),
                'noResults'         => __( 'No tenants match this view.', 'wp-loft-booking' ),
                'virtualKeys'       => __( 'Virtual keys', 'wp-loft-booking' ),
                'people'            => __( 'People', 'wp-loft-booking' ),
                'tenant'            => __( 'Tenant', 'wp-loft-booking' ),
            ),
            'editBase'     => admin_url( 'admin.php?page=wp_loft_booking_keychains&keychain_id=' ),
            'tenantBase'   => admin_url( 'admin.php?page=tenants&tenant_id=' ),
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

    $tenants = $wpdb->get_results(
        "SELECT id, tenant_id, first_name, last_name, email, building_name, unit_label, floor FROM {$ten_table}",
        ARRAY_A
    );

    $tenants_by_id    = array();
    $tenants_by_email = array();
    $tenants_by_name  = array();
    $tenant_resources = array();

    foreach ( $tenants as $tenant ) {
        $tenant_id_key                       = (int) $tenant['id'];
        $tenants_by_id[ $tenant_id_key ]     = $tenant;

        if ( ! empty( $tenant['email'] ) ) {
            $tenants_by_email[ strtolower( trim( $tenant['email'] ) ) ] = $tenant;
        }

        $name_key = strtolower( trim( $tenant['first_name'] . ' ' . $tenant['last_name'] ) );
        if ( $name_key ) {
            $tenants_by_name[ $name_key ] = $tenant;
        }

        $tenant_resource_id = 'tenant_' . ( isset( $tenant['tenant_id'] ) ? absint( $tenant['tenant_id'] ) : absint( $tenant['id'] ) );
        $resource_unit      = ! empty( $tenant['unit_label'] ) ? sanitize_text_field( $tenant['unit_label'] ) : '';
        $resource_floor     = ! empty( $tenant['floor'] ) ? sanitize_text_field( $tenant['floor'] ) : '';
        $building           = ! empty( $tenant['building_name'] ) ? sanitize_text_field( $tenant['building_name'] ) : '';

        $resource_sub = $resource_unit;

        if ( $resource_floor ) {
            $resource_sub = $resource_sub ? $resource_sub . ' • ' . $resource_floor : $resource_floor;
        }

        if ( $building ) {
            $resource_sub = $resource_sub ? $resource_sub . ' • ' . $building : $building;
        }

        $tenant_resources[ $tenant_resource_id ] = array(
            'id'        => $tenant_resource_id,
            'title'     => trim( sanitize_text_field( $tenant['first_name'] . ' ' . $tenant['last_name'] ) ) ?: __( 'Unknown tenant', 'wp-loft-booking' ),
            'subtitle'  => $resource_sub,
            'email'     => ! empty( $tenant['email'] ) ? sanitize_email( $tenant['email'] ) : '',
            'unitLabel' => $resource_unit,
        );
    }

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

    if ( $args['only_admin'] ) {
        $where[] = "EXISTS (SELECT 1 FROM {$kc_vk_table} kvk INNER JOIN {$vk_table} vk ON kvk.key_id = vk.id WHERE kvk.keychain_id = kc.id AND vk.key_type = 'admin')";
    }

    if ( $args['only_virtual'] ) {
        $where[] = "EXISTS (SELECT 1 FROM {$kc_vk_table} kvk WHERE kvk.keychain_id = kc.id)";
    }

    $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

    $sql = "SELECT kc.*, u.unit_name AS keychain_unit, u.id as unit_id, t.id as tenant_db_id,
                t.tenant_id as tenant_public_id, t.first_name as tenant_first, t.last_name as tenant_last, t.email as tenant_email,
                t.building_name as tenant_building, t.unit_label as tenant_unit, t.floor as tenant_floor,
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
    $search_term    = strtolower( $args['search'] );
    $filter_unit    = $args['unit'];

    foreach ( $rows as $row ) {
        $start_mysql = isset( $row['valid_from'] ) ? sanitize_text_field( $row['valid_from'] ) : '';
        $end_mysql   = isset( $row['valid_until'] ) ? sanitize_text_field( $row['valid_until'] ) : '';

        if ( ! $start_mysql || ! $end_mysql ) {
            continue;
        }

        $start_ts = $start_mysql ? strtotime( $start_mysql ) : false;
        $end_ts   = $end_mysql ? strtotime( $end_mysql ) : false;

        $status = 'future';

        if ( $end_ts && $end_ts < $today_ts ) {
            $status = 'expired';
        } elseif ( $start_ts && $start_ts <= $today_ts && ( ! $end_ts || $end_ts >= $today_ts ) ) {
            $status = 'active';
        }

        $contact           = wp_loft_booking_primary_contact_from_people_json( $row['people_json'] ?? '' );
        $contact_name      = $contact['name'] ? sanitize_text_field( $contact['name'] ) : '';
        $contact_email     = $contact['email'] ? sanitize_email( $contact['email'] ) : '';
        $contact_email_key = $contact_email ? strtolower( $contact_email ) : '';
        $contact_lookup    = $contact['normalized_name'];

        $tenant = null;

        if ( ! empty( $row['tenant_db_id'] ) && isset( $tenants_by_id[ (int) $row['tenant_db_id'] ] ) ) {
            $tenant = $tenants_by_id[ (int) $row['tenant_db_id'] ];
        } elseif ( $contact_email_key && isset( $tenants_by_email[ $contact_email_key ] ) ) {
            $tenant = $tenants_by_email[ $contact_email_key ];
        } elseif ( $contact_lookup && isset( $tenants_by_name[ $contact_lookup ] ) ) {
            $tenant = $tenants_by_name[ $contact['normalized_name'] ];
        }

        $resource_id    = 'tenant_unknown';
        $resource_title = __( 'Unmatched / Unknown tenant', 'wp-loft-booking' );
        $resource_email = $contact_email;
        $resource_unit  = ! empty( $row['keychain_unit'] ) ? sanitize_text_field( $row['keychain_unit'] ) : '';
        $resource_sub   = $contact_name ? sprintf( __( 'Key owner: %s', 'wp-loft-booking' ), $contact_name ) : __( 'No tenant record', 'wp-loft-booking' );

        if ( $tenant ) {
            $resource_id    = 'tenant_' . ( isset( $tenant['tenant_id'] ) ? absint( $tenant['tenant_id'] ) : absint( $tenant['id'] ) );
            $resource_title = $tenant_resources[ $resource_id ]['title'];
            $resource_email = $tenant_resources[ $resource_id ]['email'];
            $resource_unit  = $tenant_resources[ $resource_id ]['unitLabel'];
            $resource_sub   = $tenant_resources[ $resource_id ]['subtitle'];
        }

        $unit_label = $resource_unit ? $resource_unit : ( ! empty( $row['keychain_unit'] ) ? sanitize_text_field( $row['keychain_unit'] ) : '' );

        $resource_candidate = array(
            'id'        => $resource_id,
            'title'     => $resource_title,
            'subtitle'  => $resource_sub,
            'email'     => $resource_email,
            'unitLabel' => $unit_label,
        );

        if ( ! wp_loft_booking_keychain_resource_matches_filters( $resource_candidate, $search_term, $filter_unit, $row ) ) {
            continue;
        }

        if ( ! isset( $resources[ $resource_id ] ) ) {
            $resources[ $resource_id ] = $resource_candidate;
        }

        $events[] = array(
            'id'               => isset( $row['id'] ) ? 'keychain_' . (int) $row['id'] : uniqid( 'keychain_', true ),
            'resourceId'       => $resource_id,
            'start'            => mysql_to_rfc3339( $start_mysql ),
            'end'              => mysql_to_rfc3339( $end_mysql ),
            'unitLabel'        => $unit_label,
            'keychainName'     => $row['name'] ? sanitize_text_field( $row['name'] ) : '',
            'virtualKeysCount' => (int) $row['vk_total'],
            'isAdminKey'       => isset( $row['admin_keys'] ) && (int) $row['admin_keys'] > 0,
            'status'           => $status,
            'meta'             => array(
                'tenantId'   => $tenant ? (int) $tenant['tenant_id'] : null,
                'keychainId' => isset( $row['id'] ) ? (int) $row['id'] : null,
            ),
            'tenantEmail'      => $resource_email,
            'tenantName'       => $resource_title,
        );
    }

    $resource_list = array_values( $resources );

    usort(
        $resource_list,
        static function ( $a, $b ) {
            return strcasecmp( $a['title'], $b['title'] );
        }
    );

    return array(
        'resources' => $resource_list,
        'events'    => $events,
        'meta'      => array(
            'count' => count( $events ),
        ),
    );
}

/**
 * Determine if a resource/event pair matches the current filters.
 *
 * @param array $resource Resource data.
 * @param string $search_term Lowercase search term.
 * @param string $filter_unit Unit filter value.
 * @param array $row Raw keychain row for extra searchable fields.
 *
 * @return bool
 */
function wp_loft_booking_keychain_resource_matches_filters( $resource, $search_term, $filter_unit, $row ) {
    $unit_label = $resource['unitLabel'] ?? '';

    if ( $filter_unit ) {
        if ( __( 'Unassigned / Unknown', 'wp-loft-booking' ) === $filter_unit ) {
            if ( $unit_label ) {
                return false;
            }
        } elseif ( $unit_label !== $filter_unit ) {
            return false;
        }
    }

    if ( $search_term ) {
        $haystack = strtolower(
            ( $resource['title'] ?? '' ) . ' ' . ( $resource['email'] ?? '' ) . ' ' . ( $resource['subtitle'] ?? '' ) . ' ' . $unit_label . ' ' . ( $row['name'] ?? '' ) . ' ' . ( $row['keychain_unit'] ?? '' )
        );

        if ( false === strpos( $haystack, $search_term ) ) {
            return false;
        }
    }

    return true;
}

/**
 * Extract the primary contact from a keychain people_json payload.
 *
 * @param string $people_json Raw JSON string.
 * @return array
 */
function wp_loft_booking_primary_contact_from_people_json( $people_json ) {
    if ( empty( $people_json ) ) {
        return array(
            'name'  => '',
            'email' => '',
        );
    }

    $decoded = json_decode( $people_json, true );

    if ( ! is_array( $decoded ) || empty( $decoded ) ) {
        return array(
            'name'  => '',
            'email' => '',
        );
    }

    $first = array_filter( $decoded, static function ( $person ) {
        return ! empty( $person['email'] ) || ! empty( $person['first_name'] ) || ! empty( $person['last_name'] );
    } );

    $person = reset( $first );

    $full_name = '';

    if ( $person ) {
        $full_name = trim( ( $person['first_name'] ?? '' ) . ' ' . ( $person['last_name'] ?? '' ) );
    }

    return array(
        'name'            => $full_name,
        'normalized_name' => strtolower( $full_name ),
        'email'           => isset( $person['email'] ) ? strtolower( trim( $person['email'] ) ) : '',
    );
}

/**
 * Retrieve units for the dropdown filter.
 *
 * @return array
 */
function wp_loft_booking_keychain_calendar_units() {
    global $wpdb;

    $tenants_table = $wpdb->prefix . 'loft_tenants';

    $rows = $wpdb->get_col( "SELECT DISTINCT unit_label FROM {$tenants_table} WHERE unit_label IS NOT NULL AND unit_label != '' ORDER BY unit_label ASC" );

    if ( ! $rows ) {
        return array();
    }

    return array_map( 'sanitize_text_field', array_filter( $rows ) );
}
