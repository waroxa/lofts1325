<?php
/**
 * Plugin Name: Loft 1325 Virtual Keys
 * Description: Provides a block for administrators to generate and manage virtual keys from within WordPress.
 * Version: 1.0.0
 * Author: Loft 1325
 */

define( 'LOFT_VK_PLUGIN_FILE', __FILE__ );
define( 'LOFT_VK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOFT_VK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LOFT_VK_VERSION', '1.0.0' );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'loft_vk_register_block' );
add_action( 'rest_api_init', 'loft_vk_register_rest_routes' );
add_shortcode( 'loft_virtual_keys', 'loft_vk_render_block' );
add_filter( 'the_content', 'loft_vk_force_shortcode_rendering', 9 );
add_action( 'login_enqueue_scripts', 'loft_vk_customize_login_logo' );

/**
 * Register the virtual keys Gutenberg block and related scripts.
 */
function loft_vk_register_block() {
    $editor_script_version = loft_vk_asset_version( 'assets/js/editor.js' );
    $frontend_script_version = loft_vk_asset_version( 'assets/js/frontend.js' );
    $frontend_style_version  = loft_vk_asset_version( 'assets/css/frontend.css' );

    $editor_dependencies = array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor' );

    wp_register_script(
        'loft-vk-block-editor',
        LOFT_VK_PLUGIN_URL . 'assets/js/editor.js',
        $editor_dependencies,
        $editor_script_version,
        true
    );

    wp_register_script(
        'loft-vk-frontend',
        LOFT_VK_PLUGIN_URL . 'assets/js/frontend.js',
        array(),
        $frontend_script_version,
        true
    );

    wp_register_style(
        'loft-vk-frontend',
        LOFT_VK_PLUGIN_URL . 'assets/css/frontend.css',
        array(),
        $frontend_style_version
    );

    register_block_type(
        'loft/virtual-keys',
        array(
            'api_version'      => 2,
            'editor_script'    => 'loft-vk-block-editor',
            'render_callback'  => 'loft_vk_render_block',
            'style'            => 'loft-vk-frontend',
            'supports'         => array(
                'html' => false,
            ),
        )
    );
}

/**
 * Render callback for the virtual keys block and shortcode.
 *
 * @return string
 */
function loft_vk_render_block( $attributes = array(), $content = '' ) {
    if ( ! is_user_logged_in() ) {
        $login_url = wp_login_url( get_permalink() );

        return sprintf(
            '<div class="loft-vk-login-prompt"><p>%s</p><a class="button button-primary" href="%s">%s</a></div>',
            esc_html__( 'You must be logged in to view the virtual keys manager.', 'loft-virtual-keys' ),
            esc_url( $login_url ),
            esc_html__( 'Log in with your WordPress account', 'loft-virtual-keys' )
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return sprintf(
            '<div class="loft-vk-login-prompt"><p>%s</p></div>',
            esc_html__( 'You do not have permission to manage virtual keys.', 'loft-virtual-keys' )
        );
    }

    wp_enqueue_script( 'loft-vk-frontend' );
    wp_enqueue_style( 'loft-vk-frontend' );

    $nonce         = wp_create_nonce( 'wp_rest' );
    $rest_url      = esc_url_raw( rest_url( 'loft/v1/keychains' ) );
    $lofts_base    = rest_url( 'loft/v1/lofts' );
    $lofts_url     = esc_url_raw( $lofts_base );
    $generate_base = esc_url_raw( trailingslashit( $lofts_base ) );
    $instance_id   = uniqid( 'loftvk_', false );
    $keys_tab_id   = $instance_id . '_tab_keys';
    $lofts_tab_id  = $instance_id . '_tab_lofts';
    $keys_panel_id = $instance_id . '_panel_keys';
    $lofts_panel_id = $instance_id . '_panel_lofts';

    ob_start();
    ?>
    <div
        class="loft-vk"
        data-rest-url="<?php echo esc_attr( $rest_url ); ?>"
        data-lofts-url="<?php echo esc_attr( $lofts_url ); ?>"
        data-rest-nonce="<?php echo esc_attr( $nonce ); ?>"
        data-generate-url="<?php echo esc_attr( $generate_base ); ?>"
    >
        <div class="loft-vk__header">
            <h2><?php esc_html_e( 'Virtual Keys Manager', 'loft-virtual-keys' ); ?></h2>
        </div>
        <div class="loft-vk__toast-container" aria-live="polite" aria-atomic="true"></div>
        <div class="loft-vk__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Virtual key tools', 'loft-virtual-keys' ); ?>">
            <button
                type="button"
                class="button button-secondary loft-vk__tab loft-vk__tab--active"
                id="<?php echo esc_attr( $lofts_tab_id ); ?>"
                role="tab"
                aria-selected="true"
                aria-controls="<?php echo esc_attr( $lofts_panel_id ); ?>"
                data-tab="lofts"
                tabindex="0"
            >
                <?php esc_html_e( 'Lofts', 'loft-virtual-keys' ); ?>
            </button>
            <button
                type="button"
                class="button button-secondary loft-vk__tab"
                id="<?php echo esc_attr( $keys_tab_id ); ?>"
                role="tab"
                aria-selected="false"
                aria-controls="<?php echo esc_attr( $keys_panel_id ); ?>"
                data-tab="keys"
                tabindex="-1"
            >
                <?php esc_html_e( 'Virtual Keys', 'loft-virtual-keys' ); ?>
            </button>
        </div>
        <div class="loft-vk__status" aria-live="polite"></div>
        <div
            class="loft-vk__panel"
            id="<?php echo esc_attr( $keys_panel_id ); ?>"
            role="tabpanel"
            aria-labelledby="<?php echo esc_attr( $keys_tab_id ); ?>"
            data-panel="keys"
            hidden
        >
            <div class="loft-vk__table-wrapper" role="group" aria-label="<?php esc_attr_e( 'Active keychains', 'loft-virtual-keys' ); ?>">
                <table class="widefat fixed striped loft-vk__table loft-vk__keychains-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Name', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Tenant', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Unit', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'People', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Virtual Keys', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Valid From', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Valid Until', 'loft-virtual-keys' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="loft-vk__loading">
                            <td colspan="8"><?php esc_html_e( 'Select the Virtual Keys tab to load data.', 'loft-virtual-keys' ); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nav class="loft-vk__pagination" aria-label="<?php esc_attr_e( 'Keychain pagination', 'loft-virtual-keys' ); ?>" hidden></nav>
        </div>
        <div
            class="loft-vk__panel loft-vk__panel--active"
            id="<?php echo esc_attr( $lofts_panel_id ); ?>"
            role="tabpanel"
            aria-labelledby="<?php echo esc_attr( $lofts_tab_id ); ?>"
            data-panel="lofts"
        >
            <div class="loft-vk__table-wrapper" role="group" aria-label="<?php esc_attr_e( 'Loft availability', 'loft-virtual-keys' ); ?>">
                <table class="widefat fixed striped loft-vk__table loft-vk__lofts-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Unit', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'ButterflyMX Unit ID', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Available Until', 'loft-virtual-keys' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'loft-virtual-keys' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="loft-vk__loading">
                            <td colspan="5"><?php esc_html_e( 'Loading lofts…', 'loft-virtual-keys' ); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="loft-vk__cards" role="list" aria-label="<?php esc_attr_e( 'Loft availability', 'loft-virtual-keys' ); ?>">
                <div class="loft-vk__card loft-vk__card--loading" role="listitem"><?php esc_html_e( 'Loading lofts…', 'loft-virtual-keys' ); ?></div>
            </div>
        </div>
        <div
            class="loft-vk__dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="<?php echo esc_attr( $instance_id ); ?>_dialog_title"
            hidden
        >
            <div class="loft-vk__dialog-backdrop" data-dialog-cancel></div>
            <div class="loft-vk__dialog-content" role="document">
                <button type="button" class="loft-vk__dialog-close" data-dialog-cancel aria-label="<?php esc_attr_e( 'Close', 'loft-virtual-keys' ); ?>">&times;</button>
                <h3 class="loft-vk__dialog-title" id="<?php echo esc_attr( $instance_id ); ?>_dialog_title"><?php esc_html_e( 'Generate a virtual key', 'loft-virtual-keys' ); ?></h3>
                <p class="loft-vk__dialog-subtitle">
                    <?php esc_html_e( 'Selected loft / Loft sélectionné', 'loft-virtual-keys' ); ?>:
                    <strong class="loft-vk__dialog-loft"></strong>
                </p>
                <form class="loft-vk__form" novalidate>
                    <div class="loft-vk__form-field">
                        <label class="loft-vk__form-label" for="<?php echo esc_attr( $instance_id ); ?>_guest_name"><?php esc_html_e( 'Guest name / Nom du client', 'loft-virtual-keys' ); ?></label>
                        <input class="loft-vk__form-input" type="text" id="<?php echo esc_attr( $instance_id ); ?>_guest_name" name="guest_name" autocomplete="name" required />
                    </div>
                    <div class="loft-vk__form-field">
                        <label class="loft-vk__form-label" for="<?php echo esc_attr( $instance_id ); ?>_guest_email"><?php esc_html_e( 'Guest email / Courriel du client', 'loft-virtual-keys' ); ?></label>
                        <input class="loft-vk__form-input" type="email" id="<?php echo esc_attr( $instance_id ); ?>_guest_email" name="guest_email" autocomplete="email" required />
                    </div>
                    <div class="loft-vk__form-field">
                        <label class="loft-vk__form-label" for="<?php echo esc_attr( $instance_id ); ?>_guest_phone"><?php esc_html_e( 'Guest phone (optional) / Téléphone du client (optionnel)', 'loft-virtual-keys' ); ?></label>
                        <input class="loft-vk__form-input" type="tel" id="<?php echo esc_attr( $instance_id ); ?>_guest_phone" name="guest_phone" autocomplete="tel" />
                    </div>
                    <div class="loft-vk__form-grid">
                        <div class="loft-vk__form-field">
                            <label class="loft-vk__form-label" for="<?php echo esc_attr( $instance_id ); ?>_checkin"><?php esc_html_e( 'Check-in date / Date d’arrivée', 'loft-virtual-keys' ); ?></label>
                            <input class="loft-vk__form-input" type="date" id="<?php echo esc_attr( $instance_id ); ?>_checkin" name="checkin_date" required />
                        </div>
                        <div class="loft-vk__form-field">
                            <label class="loft-vk__form-label" for="<?php echo esc_attr( $instance_id ); ?>_checkout"><?php esc_html_e( 'Check-out date / Date de départ', 'loft-virtual-keys' ); ?></label>
                            <input class="loft-vk__form-input" type="date" id="<?php echo esc_attr( $instance_id ); ?>_checkout" name="checkout_date" required />
                        </div>
                    </div>
                    <p class="loft-vk__form-error" role="alert" aria-live="assertive"></p>
                    <div class="loft-vk__form-actions">
                        <button type="submit" class="button button-primary loft-vk__form-submit"><?php esc_html_e( 'Generate key / Générer la clé', 'loft-virtual-keys' ); ?></button>
                        <button type="button" class="button loft-vk__form-cancel" data-dialog-cancel><?php esc_html_e( 'Cancel', 'loft-virtual-keys' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Register REST API routes used by the virtual keys manager.
 */
function loft_vk_register_rest_routes() {
    register_rest_route(
        'loft/v1',
        '/virtual-keys',
        array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'loft_vk_rest_get_keys',
                'permission_callback' => 'loft_vk_rest_permissions_check',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'loft_vk_rest_create_key',
                'permission_callback' => 'loft_vk_rest_permissions_check',
            ),
        )
    );

    register_rest_route(
        'loft/v1',
        '/keychains',
        array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'loft_vk_rest_get_keychains',
                'permission_callback' => 'loft_vk_rest_permissions_check',
                'args'                => array(
                    'page'     => array(
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default'           => 15,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        )
    );

    register_rest_route(
        'loft/v1',
        '/lofts',
        array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'loft_vk_rest_get_lofts',
                'permission_callback' => 'loft_vk_rest_permissions_check',
            ),
        )
    );

    register_rest_route(
        'loft/v1',
        '/lofts/(?P<unit_id>\d+)/generate-key',
        array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'loft_vk_rest_generate_key_for_loft',
                'permission_callback' => 'loft_vk_rest_permissions_check',
                'args'                => array(
                    'unit_id' => array(
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        )
    );
}

/**
 * Permission callback for REST interactions.
 *
 * @return bool
 */
function loft_vk_rest_permissions_check() {
    return current_user_can( 'manage_options' );
}

/**
 * Retrieve stored virtual keys.
 *
 * @return WP_REST_Response
 */
function loft_vk_rest_get_keys() {
    $keys = get_option( 'loft_vk_keys', array() );

    if ( ! is_array( $keys ) ) {
        $keys = array();
    }

    return rest_ensure_response( array( 'keys' => array_values( $keys ) ) );
}

/**
 * Retrieve active keychains in a format that mirrors the WordPress admin table.
 *
 * @param WP_REST_Request $request Request instance.
 *
 * @return WP_REST_Response
 */
function loft_vk_rest_get_keychains( WP_REST_Request $request ) {
    global $wpdb;

    $page     = max( 1, (int) $request->get_param( 'page' ) );
    $per_page = min( 50, max( 1, (int) $request->get_param( 'per_page' ) ) );
    $offset   = ( $page - 1 ) * $per_page;

    $now           = current_time( 'mysql' );
    $kc_table      = $wpdb->prefix . 'loft_keychains';
    $vk_table      = $wpdb->prefix . 'loft_virtual_keys';
    $kc_vk_table   = $wpdb->prefix . 'loft_keychain_virtual_keys';
    $units_table   = $wpdb->prefix . 'loft_units';
    $tenants_table = $wpdb->prefix . 'loft_tenants';

    $total = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$kc_table} WHERE valid_from <= %s AND valid_until >= %s",
            $now,
            $now
        )
    );

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT kc.*, t.first_name, t.last_name, u.unit_name
            FROM {$kc_table} kc
            LEFT JOIN {$tenants_table} t ON kc.tenant_id = t.id
            LEFT JOIN {$units_table} u ON kc.unit_id = u.id
            WHERE kc.valid_from <= %s AND kc.valid_until >= %s
            ORDER BY kc.valid_until DESC
            LIMIT %d OFFSET %d",
            $now,
            $now,
            $per_page,
            $offset
        )
    );

    $keychains = array();

    foreach ( $rows as $kc ) {
        $vk_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vk.name, vk.key_type, vk.key_status, vk.virtual_key_id
                FROM {$kc_vk_table} kvk
                INNER JOIN {$vk_table} vk ON kvk.key_id = vk.id
                WHERE kvk.keychain_id = %d
                ORDER BY vk.name ASC",
                $kc->id
            )
        );

        $virtual_keys = array();

        foreach ( $vk_rows as $vk ) {
            $virtual_keys[] = array(
                'name'   => sanitize_text_field( $vk->name ),
                'type'   => sanitize_text_field( $vk->key_type ),
                'status' => sanitize_text_field( $vk->key_status ),
                'id'     => sanitize_text_field( $vk->virtual_key_id ),
            );
        }

        $people = array();

        if ( ! empty( $kc->people_json ) ) {
            $decoded_people = json_decode( $kc->people_json, true );

            if ( is_array( $decoded_people ) ) {
                foreach ( $decoded_people as $person ) {
                    if ( ! is_array( $person ) ) {
                        continue;
                    }

                    $first = isset( $person['first_name'] ) ? sanitize_text_field( $person['first_name'] ) : '';
                    $last  = isset( $person['last_name'] ) ? sanitize_text_field( $person['last_name'] ) : '';
                    $name  = trim( $first . ' ' . $last );

                    if ( '' === $name && empty( $person['email'] ) ) {
                        continue;
                    }

                    $people[] = array(
                        'name'  => $name,
                        'type'  => isset( $person['type'] ) ? sanitize_text_field( $person['type'] ) : '',
                        'email' => isset( $person['email'] ) ? sanitize_email( $person['email'] ) : '',
                    );
                }
            }
        }

        $tenant_first = isset( $kc->first_name ) ? sanitize_text_field( $kc->first_name ) : '';
        $tenant_last  = isset( $kc->last_name ) ? sanitize_text_field( $kc->last_name ) : '';
        $tenant_name  = trim( $tenant_first . ' ' . $tenant_last );

        $unit_name = isset( $kc->unit_name ) ? sanitize_text_field( $kc->unit_name ) : '';

        $keychains[] = array(
            'id'           => (int) $kc->id,
            'name'         => sanitize_text_field( $kc->name ),
            'tenant'       => $tenant_name,
            'unit'         => '' !== $unit_name ? $unit_name : __( 'None', 'loft-virtual-keys' ),
            'people'       => $people,
            'virtual_keys' => $virtual_keys,
            'valid_from'   => sanitize_text_field( $kc->valid_from ),
            'valid_until'  => sanitize_text_field( $kc->valid_until ),
        );
    }

    return rest_ensure_response(
        array(
            'keychains'  => $keychains,
            'pagination' => array(
                'total'       => $total,
                'per_page'    => $per_page,
                'page'        => $page,
                'total_pages' => (int) max( 1, ceil( $total / $per_page ) ),
            ),
        )
    );
}





/**
 * Normalize a loft label so it matches the naming used in the admin tools.
 *
 * @param string $label Raw loft/unit label.
 *
 * @return string
 */
function loft_vk_normalize_unit_label( $label ) {
    $label = strtoupper( trim( (string) $label ) );

    if ( preg_match( '/LOFTS?\s*-*\s*([0-9]+)/i', $label, $matches ) ) {
        return 'LOFT' . $matches[1];
    }

    return preg_replace( '/[^A-Z0-9]/', '', $label );
}

/**
 * Build lookup maps for active keys and tenants.
 *
 * @return array
 */
function loft_vk_collect_loft_context() {
    global $wpdb;

    $keychains_table = $wpdb->prefix . 'loft_keychains';
    $tenant_table    = $wpdb->prefix . 'loft_tenants';

    $now_mysql  = current_time( 'mysql' );
    $now_ts     = current_time( 'timestamp' );
    $threshold  = $now_ts + DAY_IN_SECONDS;
    $keys_map   = array();
    $tenants    = array();

    $key_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT name, valid_from, valid_until FROM {$keychains_table} WHERE valid_until >= %s",
            $now_mysql
        ),
        ARRAY_A
    );

    foreach ( $key_rows as $row ) {
        $label = loft_vk_normalize_unit_label( $row['name'] ?? '' );

        if ( '' === $label ) {
            continue;
        }

        $valid_from  = ! empty( $row['valid_from'] ) ? strtotime( $row['valid_from'] ) : false;
        $valid_until = ! empty( $row['valid_until'] ) ? strtotime( $row['valid_until'] ) : false;

        if ( false === $valid_until ) {
            continue;
        }

        $status = '';

        if ( $valid_from && $valid_from <= $now_ts ) {
            $status = 'occupied';
        } elseif ( $valid_from && $valid_from <= $threshold ) {
            $status = 'reserved';
        } else {
            continue;
        }

        $existing_status = isset( $keys_map[ $label ]['status'] ) ? $keys_map[ $label ]['status'] : '';

        if ( 'occupied' === $existing_status && 'occupied' !== $status ) {
            continue;
        }

        if ( 'reserved' === $existing_status && 'reserved' === $status ) {
            $current_from = isset( $keys_map[ $label ]['valid_from'] ) ? strtotime( $keys_map[ $label ]['valid_from'] ) : false;

            if ( $current_from && $valid_from && $current_from <= $valid_from ) {
                continue;
            }
        }

        $keys_map[ $label ] = array(
            'status'      => $status,
            'valid_from'  => $row['valid_from'] ?? '',
            'valid_until' => $row['valid_until'] ?? '',
        );
    }

    $active_tenants = $wpdb->get_results(
        "SELECT unit_label, lease_start, lease_end FROM {$tenant_table}",
        ARRAY_A
    );

    foreach ( $active_tenants as $row ) {
        $label = loft_vk_normalize_unit_label( $row['unit_label'] ?? '' );

        if ( '' === $label ) {
            continue;
        }

        $lease_start = ! empty( $row['lease_start'] ) ? strtotime( $row['lease_start'] ) : false;
        $lease_end   = ! empty( $row['lease_end'] ) ? strtotime( $row['lease_end'] ) : false;

        if ( ! $lease_start || ! $lease_end ) {
            continue;
        }

        if ( $lease_start <= $now_ts && $lease_end >= $now_ts ) {
            if ( empty( $tenants[ $label ] ) || $lease_end < strtotime( $tenants[ $label ] ) ) {
                $tenants[ $label ] = $row['lease_end'];
            }
        }
    }

    return array(
        'keys'    => $keys_map,
        'tenants' => $tenants,
    );
}

/**
 * Prepare a loft response entry and keep the database in sync with the admin page.
 *
 * @param object $unit    Unit row from the database.
 * @param array  $context Lookup context from loft_vk_collect_loft_context().
 *
 * @return array
 */
function loft_vk_prepare_loft_response( $unit, $context ) {
    global $wpdb;

    $units_table = $wpdb->prefix . 'loft_units';

    $status = strtolower( (string) $unit->status );
    $label  = loft_vk_normalize_unit_label( $unit->unit_name );

    $key_info    = ( '' !== $label && isset( $context['keys'][ $label ] ) ) ? $context['keys'][ $label ] : null;
    $tenant_info = ( '' !== $label && isset( $context['tenants'][ $label ] ) ) ? $context['tenants'][ $label ] : null;

    $key_status        = is_array( $key_info ) && ! empty( $key_info['status'] ) ? $key_info['status'] : '';
    $has_active_key    = 'occupied' === $key_status;
    $has_reserved_key  = 'reserved' === $key_status;
    $reserved_by_key   = $has_reserved_key;
    $has_tenant        = null !== $tenant_info;

    $availability_timestamp = false;

    if ( $key_info && ! empty( $key_info['valid_until'] ) ) {
        $availability_timestamp = strtotime( $key_info['valid_until'] );
    } elseif ( $has_tenant ) {
        $availability_timestamp = strtotime( $tenant_info );
    }

    $availability_for_db = null;

    if ( false !== $availability_timestamp ) {
        $availability_for_db = wp_date( 'Y-m-d H:i', $availability_timestamp );
    }

    if ( 'unavailable' !== $status ) {
        if ( $has_active_key || $has_tenant ) {
            $status = 'occupied';
        } elseif ( $has_reserved_key ) {
            $status = 'unavailable';
        } else {
            $status = 'available';
        }

        $wpdb->update(
            $units_table,
            array(
                'status'             => $status,
                'availability_until' => $availability_for_db,
            ),
            array( 'id' => $unit->id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    } else {
        $wpdb->update(
            $units_table,
            array( 'availability_until' => $availability_for_db ),
            array( 'id' => $unit->id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    $availability_value = $availability_for_db ? $availability_for_db : '';

    switch ( $status ) {
        case 'available':
            $status_label = __( 'Available', 'loft-virtual-keys' );
            break;
        case 'occupied':
            $status_label = __( 'Occupied', 'loft-virtual-keys' );
            break;
        case 'unavailable':
            $status_label = $reserved_by_key ? __( 'Reserved', 'loft-virtual-keys' ) : __( 'Unavailable', 'loft-virtual-keys' );
            break;
        default:
            $status_label = ucfirst( $status );
            break;
    }

    return array(
        'id'                  => (int) $unit->id,
        'unit'                => sanitize_text_field( $unit->unit_name ),
        'butterflymx_unit_id' => ! empty( $unit->unit_id_api ) ? (string) (int) $unit->unit_id_api : '',
        'building_id'         => ! empty( $unit->branch_building_id ) ? sanitize_text_field( (string) $unit->branch_building_id ) : '',
        'status'              => $status,
        'status_label'        => $status_label,
        'availability_until'  => $availability_value,
        'can_generate'        => ( 'available' === $status ),
    );
}

/**
 * Sanitize a phone number while preserving international prefixes and spacing.
 *
 * @param string $phone Raw phone input.
 *
 * @return string Sanitized phone string.
 */
function loft_vk_sanitize_phone_input( $phone ) {
    $phone = trim( (string) $phone );

    if ( '' === $phone ) {
        return '';
    }

    $phone = preg_replace( '/[^0-9+\s().-]/', '', $phone );
    $phone = preg_replace( '/\s+/', ' ', $phone );

    return trim( $phone );
}

/**
 * Retrieve lofts with their availability and status information.
 *
 * @return WP_REST_Response
 */
function loft_vk_rest_get_lofts( WP_REST_Request $request ) {
    global $wpdb;

    $units_table    = $wpdb->prefix . 'loft_units';
    $branches_table = $wpdb->prefix . 'loft_branches';

    $units = $wpdb->get_results(
        "SELECT u.*, b.building_id AS branch_building_id"
            . " FROM {$units_table} u"
            . " LEFT JOIN {$branches_table} b ON u.branch_id = b.id"
            . " WHERE u.unit_name LIKE '%LOFT%'"
            . " ORDER BY u.unit_name ASC"
    );

    if ( empty( $units ) ) {
        return rest_ensure_response( array( 'lofts' => array() ) );
    }

    $context = loft_vk_collect_loft_context();
    $lofts   = array();

    foreach ( $units as $unit ) {
        $lofts[] = loft_vk_prepare_loft_response( $unit, $context );
    }

    return rest_ensure_response( array( 'lofts' => $lofts ) );
}

/**
 * Generate a virtual key for a specific loft.
 *
 * @param WP_REST_Request $request Request instance.
 *
 * @return WP_REST_Response|WP_Error
 */
function loft_vk_rest_generate_key_for_loft( WP_REST_Request $request ) {
    global $wpdb;

    $unit_id = (int) $request->get_param( 'unit_id' );

    if ( $unit_id <= 0 ) {
        return new WP_Error( 'loft_vk_invalid_unit', __( 'Invalid loft selection.', 'loft-virtual-keys' ), array( 'status' => 400 ) );
    }

    $guest_name   = sanitize_text_field( (string) $request->get_param( 'guest_name' ) );
    $guest_email  = sanitize_email( (string) $request->get_param( 'guest_email' ) );
    $guest_phone  = loft_vk_sanitize_phone_input( (string) $request->get_param( 'guest_phone' ) );
    $checkin      = sanitize_text_field( (string) $request->get_param( 'checkin_date' ) );
    $checkout     = sanitize_text_field( (string) $request->get_param( 'checkout_date' ) );

    if ( '' === $guest_name || '' === $guest_email || '' === $checkin || '' === $checkout ) {
        return new WP_Error( 'loft_vk_missing_fields', __( 'Guest name, email, and dates are required.', 'loft-virtual-keys' ), array( 'status' => 400 ) );
    }

    if ( ! is_email( $guest_email ) ) {
        return new WP_Error( 'loft_vk_invalid_email', __( 'The guest email address is not valid.', 'loft-virtual-keys' ), array( 'status' => 400 ) );
    }

    $timezone_string = get_option( 'timezone_string' );

    if ( empty( $timezone_string ) ) {
        $timezone_string = 'America/Toronto';
    }

    try {
        $site_timezone = new DateTimeZone( $timezone_string );
    } catch ( Exception $e ) {
        $site_timezone = new DateTimeZone( 'America/Toronto' );
    }

    $utc_timezone = new DateTimeZone( 'UTC' );

    $checkin_dt  = DateTime::createFromFormat( 'Y-m-d', $checkin, $site_timezone );
    $checkout_dt = DateTime::createFromFormat( 'Y-m-d', $checkout, $site_timezone );

    if ( ! $checkin_dt || $checkin_dt->format( 'Y-m-d' ) !== $checkin ) {
        return new WP_Error( 'loft_vk_invalid_checkin', __( 'The check-in date format must be YYYY-MM-DD.', 'loft-virtual-keys' ), array( 'status' => 400 ) );
    }

    if ( ! $checkout_dt || $checkout_dt->format( 'Y-m-d' ) !== $checkout ) {
        return new WP_Error( 'loft_vk_invalid_checkout', __( 'The check-out date format must be YYYY-MM-DD.', 'loft-virtual-keys' ), array( 'status' => 400 ) );
    }

    if ( $checkout_dt <= $checkin_dt ) {
        return new WP_Error( 'loft_vk_checkout_before_checkin', __( 'The check-out date must be after the check-in date.', 'loft-virtual-keys' ), array( 'status' => 400 ) );
    }

    $units_table    = $wpdb->prefix . 'loft_units';
    $branches_table = $wpdb->prefix . 'loft_branches';

    $unit = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT u.*, b.building_id AS branch_building_id"
                . " FROM {$units_table} u"
                . " LEFT JOIN {$branches_table} b ON u.branch_id = b.id"
                . " WHERE u.id = %d",
            $unit_id
        )
    );

    if ( ! $unit ) {
        return new WP_Error( 'loft_vk_unit_not_found', __( 'Selected loft could not be found.', 'loft-virtual-keys' ), array( 'status' => 404 ) );
    }

    if ( 'available' !== strtolower( (string) $unit->status ) ) {
        return new WP_Error( 'loft_vk_unit_unavailable', __( 'This loft is not currently available for key generation.', 'loft-virtual-keys' ), array( 'status' => 400 ) );
    }

    $checkin_local  = clone $checkin_dt;
    $checkout_local = clone $checkout_dt;

    $checkin_local->setTime( 15, 0, 0 );
    $checkout_local->setTime( 11, 0, 0 );

    if ( function_exists( 'wp_loft_booking_apply_virtual_key_lead_time' ) ) {
        $adjusted_checkin = wp_loft_booking_apply_virtual_key_lead_time( $checkin_local, $checkout_local, $site_timezone );

        if ( is_wp_error( $adjusted_checkin ) ) {
            return new WP_Error( 'loft_vk_invalid_window', $adjusted_checkin->get_error_message(), array( 'status' => 400 ) );
        }

        $checkin_local = $adjusted_checkin;
    } else {
        try {
            $lead_time_minutes = (int) apply_filters( 'wp_loft_booking_virtual_key_lead_time_minutes', 5 );

            if ( $lead_time_minutes < 0 ) {
                $lead_time_minutes = 0;
            }

            $minimum_start = new DateTime( 'now', $site_timezone );

            if ( $lead_time_minutes > 0 ) {
                $minimum_start->modify( sprintf( '+%d minutes', $lead_time_minutes ) );
            }

            if ( $checkin_local <= $minimum_start ) {
                $checkin_local = clone $minimum_start;

                if ( $checkin_local >= $checkout_local ) {
                    return new WP_Error(
                        'loft_vk_invalid_window',
                        __( 'La période du séjour doit dépasser l\'heure d\'arrivée. / The stay window must extend beyond the arrival time.', 'loft-virtual-keys' ),
                        array( 'status' => 400 )
                    );
                }
            }
        } catch ( Exception $e ) {
            return new WP_Error( 'loft_vk_time_error', $e->getMessage(), array( 'status' => 500 ) );
        }
    }

    $checkin_utc  = clone $checkin_local;
    $checkout_utc = clone $checkout_local;

    $checkin_utc->setTimezone( $utc_timezone );
    $checkout_utc->setTimezone( $utc_timezone );

    if ( ! function_exists( 'wp_loft_booking_generate_virtual_key' ) ) {
        return new WP_Error( 'loft_vk_missing_dependency', __( 'Virtual key generation is currently unavailable.', 'loft-virtual-keys' ), array( 'status' => 500 ) );
    }

    $result = wp_loft_booking_generate_virtual_key(
        $unit_id,
        $guest_name,
        $guest_email,
        $guest_phone,
        $checkin_dt->format( 'Y-m-d' ),
        $checkout_dt->format( 'Y-m-d' )
    );

    if ( is_wp_error( $result ) ) {
        return new WP_Error( 'loft_vk_generation_failed', $result->get_error_message(), array( 'status' => 500 ) );
    }

    $starts_at = $checkin_utc->format( 'Y-m-d\TH:i:s\Z' );
    $ends_at   = $checkout_utc->format( 'Y-m-d\TH:i:s\Z' );

    $availability_until = $checkout_local->format( 'Y-m-d H:i:s' );

    $keychain_id            = isset( $result['keychain_id'] ) ? (int) $result['keychain_id'] : 0;
    $primary_virtual_key_id = isset( $result['virtual_key_ids'][0] ) ? $result['virtual_key_ids'][0] : null;

    if ( $keychain_id > 0 && function_exists( 'wp_loft_booking_save_keychain_data' ) ) {
        wp_loft_booking_save_keychain_data(
            null,
            $unit_id,
            $keychain_id,
            $primary_virtual_key_id,
            $starts_at,
            $ends_at
        );

        if ( function_exists( 'wp_loft_booking_record_virtual_key_log' ) ) {
            wp_loft_booking_record_virtual_key_log(
                null,
                $unit_id,
                $keychain_id,
                isset( $result['virtual_key_ids'] ) ? (array) $result['virtual_key_ids'] : array(),
                $starts_at,
                $ends_at
            );
        }
    }

    $wpdb->update(
        $units_table,
        array(
            'status'             => 'unavailable',
            'availability_until' => $availability_until,
        ),
        array( 'id' => $unit_id ),
        array( '%s', '%s' ),
        array( '%d' )
    );

    if ( function_exists( 'wp_loft_booking_send_confirmation_email' ) ) {
        $booking_payload = array(
            'room_id'        => $unit_id,
            'name'           => $guest_name,
            'surname'        => '',
            'email'          => $guest_email,
            'phone'          => $guest_phone,
            'country'        => '',
            'date_from'      => $checkin_dt->format( 'Y-m-d' ),
            'date_to'        => $checkout_dt->format( 'Y-m-d' ),
            'room_name'      => $unit->unit_name,
            'total'          => '',
            'extra_services' => '',
            'guests'         => '',
        );

        wp_loft_booking_send_confirmation_email( $booking_payload, $result, true );
    }

    $message = sprintf(
        /* translators: %s: loft/unit name */
        __( 'Virtual key created for %s. A confirmation email has been sent to the guest.', 'loft-virtual-keys' ),
        sanitize_text_field( $unit->unit_name )
    );

    $context = loft_vk_collect_loft_context();

    $updated_unit = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT u.*, b.building_id AS branch_building_id"
                . " FROM {$units_table} u"
                . " LEFT JOIN {$branches_table} b ON u.branch_id = b.id"
                . " WHERE u.id = %d",
            $unit_id
        )
    );

    $loft = $updated_unit ? loft_vk_prepare_loft_response( $updated_unit, $context ) : null;

    if ( function_exists( 'wp_loft_booking_trigger_unit_sync' ) ) {
        wp_loft_booking_trigger_unit_sync( 'virtual_key_created' );
    }

    $refresh_scheduled = loft_vk_schedule_keychain_refresh();

    return rest_ensure_response(
        array(
            'message'            => $message,
            'loft'               => $loft,
            'refresh_scheduled'  => $refresh_scheduled,
        )
    );
}
/**
 * Generate a new virtual key and store it.
 *
 * @return WP_REST_Response
 */
function loft_vk_rest_create_key() {
    $keys = get_option( 'loft_vk_keys', array() );

    if ( ! is_array( $keys ) ) {
        $keys = array();
    }

    $new_key = array(
        'key'        => wp_generate_password( 16, false ),
        'created_at' => current_time( 'mysql' ),
    );

    array_unshift( $keys, $new_key );
    $keys = array_slice( $keys, 0, 50 );

    update_option( 'loft_vk_keys', $keys, false );

    return rest_ensure_response( array( 'key' => $new_key, 'keys' => $keys ) );
}

/**
 * Ensure the [loft_virtual_keys] shortcode is rendered even if do_shortcode()
 * has been removed from "the_content" filter stack by another plugin/theme.
 *
 * @param string $content The current post content.
 *
 * @return string
 */
function loft_vk_force_shortcode_rendering( $content ) {
    if ( false === strpos( $content, '[loft_virtual_keys' ) ) {
        return $content;
    }

    $content = str_replace( '[/loft_virtual_keys]', '', $content );

    return preg_replace_callback(
        '/\[loft_virtual_keys(?:\s[^\]]*)?\]/',
        'loft_vk_render_shortcode_markup',
        $content
    );
}

/**
 * Helper callback used when forcing shortcode rendering via preg_replace_callback().
 *
 * @return string
 */
function loft_vk_render_shortcode_markup() {
    return loft_vk_render_block();
}

/**
 * Schedule a background refresh of ButterflyMX keychains.
 *
 * @param int $delay Delay in seconds before the sync should run.
 *
 * @return bool True if the refresh was scheduled, false if it ran immediately or could not be scheduled.
 */
function loft_vk_schedule_keychain_refresh( $delay = 45 ) {
    if ( ! function_exists( 'wp_loft_booking_sync_keychains_from_api' ) ) {
        return false;
    }

    $delay     = max( 5, (int) $delay );
    $timestamp = time() + $delay;

    if ( function_exists( 'wp_schedule_single_event' ) && function_exists( 'wp_next_scheduled' ) ) {
        $next = wp_next_scheduled( 'loft_vk_refresh_keychains' );

        if ( $next && $next <= $timestamp ) {
            return true;
        }

        if ( wp_schedule_single_event( $timestamp, 'loft_vk_refresh_keychains' ) ) {
            return true;
        }
    }

    loft_vk_run_keychain_refresh();

    return false;
}

/**
 * Perform a keychain refresh immediately.
 */
function loft_vk_run_keychain_refresh() {
    if ( ! function_exists( 'wp_loft_booking_sync_keychains_from_api' ) ) {
        return;
    }

    $result = wp_loft_booking_sync_keychains_from_api();

    if ( is_wp_error( $result ) ) {
        error_log( '[Loft VK] Keychain refresh failed: ' . $result->get_error_message() );
        return;
    }

    if ( function_exists( 'wp_loft_booking_trigger_unit_sync' ) ) {
        wp_loft_booking_trigger_unit_sync( 'keychain_refresh' );
    }
}

add_action( 'loft_vk_refresh_keychains', 'loft_vk_run_keychain_refresh' );

/**
 * Swap the login logo with the Loft 1325 image.
 */
function loft_vk_customize_login_logo() {
    ?>
    <style>
        #login h1 a, .login h1 a {
            background-image: url('https://loft1325.com/wp-content/uploads/2024/06/Asset-1.png');
            width: 100%;
            background-size: contain;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
    </style>
    <?php
}

/**
 * Retrieve a version string for an asset based on its modification time.
 *
 * @param string $relative_path Relative path within the plugin directory.
 *
 * @return string
 */
function loft_vk_asset_version( $relative_path ) {
    $file_path = LOFT_VK_PLUGIN_DIR . $relative_path;

    if ( file_exists( $file_path ) ) {
        return (string) filemtime( $file_path );
    }

    return LOFT_VK_VERSION;
}
