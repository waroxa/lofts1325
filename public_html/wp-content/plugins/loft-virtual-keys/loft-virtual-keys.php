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

    $nonce    = wp_create_nonce( 'wp_rest' );
    $rest_url = esc_url_raw( rest_url( 'loft/v1/keychains' ) );

    ob_start();
    ?>
    <div class="loft-vk" data-rest-url="<?php echo esc_attr( $rest_url ); ?>" data-rest-nonce="<?php echo esc_attr( $nonce ); ?>">
        <div class="loft-vk__header">
            <h2><?php esc_html_e( 'Virtual Keys Manager', 'loft-virtual-keys' ); ?></h2>
        </div>
        <div class="loft-vk__status" aria-live="polite"></div>
        <table class="widefat fixed striped loft-vk__table">
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
                    <td colspan="8"><?php esc_html_e( 'Loading keychains…', 'loft-virtual-keys' ); ?></td>
                </tr>
            </tbody>
        </table>
        <nav class="loft-vk__pagination" aria-label="<?php esc_attr_e( 'Keychain pagination', 'loft-virtual-keys' ); ?>" hidden></nav>
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
            'unit'         => '' !== $unit_name ? $unit_name : '❌ None',
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
