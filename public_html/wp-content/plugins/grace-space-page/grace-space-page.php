<?php
/**
 * Plugin Name:       Grace Space Landing Page
 * Description:       Provides a Grace Space-inspired landing page pattern and styling for easy maintenance.
 * Version:           1.0.0
 * Author:            Lofty Labs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const GSLP_VERSION = '1.0.0';

/**
 * Register custom block pattern category and pattern.
 */
function gslp_register_block_pattern() {
    if ( ! function_exists( 'register_block_pattern' ) ) {
        return;
    }

    register_block_pattern_category(
        'grace-space',
        array(
            'label' => __( 'Grace Space Layouts', 'grace-space' ),
        )
    );

    $pattern_file = plugin_dir_path( __FILE__ ) . 'patterns/full-page.php';

    if ( file_exists( $pattern_file ) ) {
        $pattern_content = gslp_get_pattern_content( $pattern_file );

        register_block_pattern(
            'grace-space/full-page',
            array(
                'title'       => __( 'Grace Space Landing Page', 'grace-space' ),
                'description' => __( 'A full-page layout inspired by Grace Space Christian Coaching.', 'grace-space' ),
                'categories'  => array( 'grace-space' ),
                'content'     => $pattern_content,
            )
        );
    }
}
add_action( 'init', 'gslp_register_block_pattern' );

/**
 * Buffer the output of the pattern file so it can be registered as a string.
 *
 * @param string $path Path to the pattern file.
 *
 * @return string
 */
function gslp_get_pattern_content( $path ) {
    ob_start();
    include $path;
    return ob_get_clean();
}

/**
 * Determine whether the current view should load the plugin assets.
 *
 * @return bool
 */
function gslp_should_enqueue_frontend_assets() {
    if ( is_admin() ) {
        return true;
    }

    if ( ! is_singular() ) {
        return false;
    }

    $post = get_post();

    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    return false !== strpos( $post->post_content, 'grace-space-page' );
}

/**
 * Enqueue the front-end and editor styles.
 */
function gslp_enqueue_assets() {
    if ( ! gslp_should_enqueue_frontend_assets() ) {
        return;
    }

    $handle   = 'grace-space-landing-page';
    $css_path = plugin_dir_path( __FILE__ ) . 'assets/css/grace-space-page.css';
    $css_uri  = plugin_dir_url( __FILE__ ) . 'assets/css/grace-space-page.css';
    $version  = file_exists( $css_path ) ? filemtime( $css_path ) : GSLP_VERSION;

    wp_enqueue_style( $handle, $css_uri, array(), $version );
    gslp_enqueue_fonts();
}
add_action( 'wp_enqueue_scripts', 'gslp_enqueue_assets' );
add_action( 'enqueue_block_editor_assets', 'gslp_enqueue_assets' );

/**
 * Enqueue Google Fonts used for the layout.
 */
function gslp_enqueue_fonts() {
    wp_enqueue_style(
        'grace-space-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Source+Sans+Pro:wght@300;400;600;700&display=swap',
        array(),
        null
    );
}

/**
 * Ensure fonts are available inside the editor even when the layout class is not detected.
 */
function gslp_enqueue_editor_fonts() {
    if ( ! is_admin() ) {
        return;
    }

    gslp_enqueue_fonts();
}
add_action( 'admin_init', 'gslp_enqueue_editor_fonts' );
