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
    $rest_url = esc_url_raw( rest_url( 'loft/v1/virtual-keys' ) );

    ob_start();
    ?>
    <div class="loft-vk" data-rest-url="<?php echo esc_attr( $rest_url ); ?>" data-rest-nonce="<?php echo esc_attr( $nonce ); ?>">
        <div class="loft-vk__header">
            <h2><?php esc_html_e( 'Virtual Keys Manager', 'loft-virtual-keys' ); ?></h2>
            <button type="button" class="button button-primary loft-vk__generate"><?php esc_html_e( 'Generate Virtual Key', 'loft-virtual-keys' ); ?></button>
        </div>
        <div class="loft-vk__status" aria-live="polite"></div>
        <table class="widefat fixed striped loft-vk__table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Key', 'loft-virtual-keys' ); ?></th>
                    <th><?php esc_html_e( 'Created', 'loft-virtual-keys' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr class="loft-vk__loading">
                    <td colspan="2"><?php esc_html_e( 'Loading keys…', 'loft-virtual-keys' ); ?></td>
                </tr>
            </tbody>
        </table>
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
