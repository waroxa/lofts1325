<?php
/**
 * Plugin Name: Loft 1325 Header Assets Loader
 * Description: Ensures the Elementor Header 6 layout assets load even when theme hooks are unavailable.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', 'loft1325_maybe_enqueue_header_elementor_assets', 15 );

/**
 * Ensure Elementor assets load for the Header 6 layout on non-Elementor pages.
 */
function loft1325_maybe_enqueue_header_elementor_assets() {
    if ( is_admin() || ! did_action( 'elementor/loaded' ) || ! class_exists( '\\Elementor\\Plugin' ) ) {
        return;
    }

    $active_layout = get_option( 'nd_options_customizer_header_layout', '' );

    if ( 'header-6' !== $active_layout ) {
        return;
    }

    $header_document_id = absint( get_option( 'nd_options_customizer_header_6_content' ) );

    if ( ! $header_document_id ) {
        return;
    }

    $elementor = \Elementor\Plugin::instance();

    if ( ! $elementor ) {
        return;
    }

    if ( isset( $elementor->frontend ) ) {
        if ( method_exists( $elementor->frontend, 'enqueue_styles' ) ) {
            $elementor->frontend->enqueue_styles();
        }

        if ( method_exists( $elementor->frontend, 'enqueue_scripts' ) ) {
            $elementor->frontend->enqueue_scripts();
        }
    }

    $assets_meta_key = '_elementor_page_assets';

    if ( class_exists( '\\Elementor\\Core\\Page_Assets\\Manager' ) && defined( '\\Elementor\\Core\\Page_Assets\\Manager::ASSETS_META_KEY' ) ) {
        $assets_meta_key = \Elementor\Core\Page_Assets\Manager::ASSETS_META_KEY;
    }

    $page_assets = get_post_meta( $header_document_id, $assets_meta_key, true );

    if ( ! empty( $page_assets ) && isset( $elementor->assets_loader ) && method_exists( $elementor->assets_loader, 'enable_assets' ) ) {
        $elementor->assets_loader->enable_assets( $page_assets );
    } else {
        $header_document = isset( $elementor->documents ) && method_exists( $elementor->documents, 'get' )
            ? $elementor->documents->get( $header_document_id )
            : null;

        if ( $header_document && method_exists( $header_document, 'update_runtime_elements' ) ) {
            $header_document->update_runtime_elements();
        }
    }

    if ( class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
        $header_css = \Elementor\Core\Files\CSS\Post::create( $header_document_id );

        if ( $header_css && method_exists( $header_css, 'enqueue' ) ) {
            $header_css->enqueue();
        }
    }

    $kit_id = 0;

    if ( property_exists( $elementor, 'kits_manager' ) && $elementor->kits_manager && method_exists( $elementor->kits_manager, 'get_active_id' ) ) {
        $kit_id = (int) $elementor->kits_manager->get_active_id();
    }

    if ( ! $kit_id ) {
        $kit_id = (int) get_option( 'elementor_active_kit' );
    }

    if ( $kit_id && class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
        $kit_css = \Elementor\Core\Files\CSS\Post::create( $kit_id );

        if ( $kit_css && method_exists( $kit_css, 'enqueue' ) ) {
            $kit_css->enqueue();
        }
    }
}
