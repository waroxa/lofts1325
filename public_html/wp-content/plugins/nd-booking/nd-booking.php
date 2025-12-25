<?php
/*
Plugin Name:       Hotel Booking
Description:       The plugin is used to manage your booking. To get started: 1) Click the "Activate" link to the left of this description. 2) Follow the documentation for installation for use the plugin in the better way.
Version:           99999.9
Plugin URI:        https://nicdark.com
Author:            Nicdark
Author URI:        https://nicdark.com
License:           GPLv2 or later
*/

///////////////////////////////////////////////////TRANSLATIONS///////////////////////////////////////////////////////////////

//translation
function nd_booking_load_textdomain()
{
  load_plugin_textdomain("nd-booking", false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'nd_booking_load_textdomain');


///////////////////////////////////////////////////DB///////////////////////////////////////////////////////////////
register_activation_hook( __FILE__, 'nd_booking_create_booking_db' );

function nd_booking_get_booking_table_schema() {
    global $wpdb;

    $nd_booking_table_name = $wpdb->prefix . 'nd_booking_booking';

    return "CREATE TABLE $nd_booking_table_name (
      id int(11) NOT NULL AUTO_INCREMENT,
      id_post int(11) NOT NULL,
      title_post varchar(255) NOT NULL,
      date varchar(255) NOT NULL,
      date_from varchar(255) NOT NULL,
      date_to varchar(255) NOT NULL,
      guests int(11) NOT NULL,
      final_trip_price decimal(12,2) NOT NULL,
      extra_services varchar(255) NOT NULL,
      id_user int(11) NOT NULL,
      user_first_name varchar(255) NOT NULL,
      user_last_name varchar(255) NOT NULL,
      paypal_email varchar(255) NOT NULL,
      user_phone varchar(255) NOT NULL,
      user_address varchar(255) NOT NULL,
      user_city varchar(255) NOT NULL,
      user_country varchar(255) NOT NULL,
      user_message varchar(255) NOT NULL,
      user_arrival varchar(255) NOT NULL,
      user_coupon varchar(255) NOT NULL,
      paypal_payment_status varchar(255) NOT NULL,
      paypal_currency varchar(255) NOT NULL,
      paypal_tx varchar(255) NOT NULL,
      action_type varchar(255) NOT NULL,
      UNIQUE KEY id (id)
    );";
}

function nd_booking_create_booking_db() {
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( nd_booking_get_booking_table_schema() );
}

function nd_booking_maybe_upgrade_booking_db() {
    global $wpdb;

    $nd_booking_table_name = $wpdb->prefix . 'nd_booking_booking';

    $table_exists = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $nd_booking_table_name ) )
    );

    if ( $table_exists !== $nd_booking_table_name ) {
        return;
    }

    $final_trip_price_column = $wpdb->get_row(
        $wpdb->prepare( "SHOW COLUMNS FROM $nd_booking_table_name LIKE %s", 'final_trip_price' )
    );

    if ( empty( $final_trip_price_column ) || false !== stripos( $final_trip_price_column->Type, 'decimal' ) ) {
        return;
    }

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( nd_booking_get_booking_table_schema() );
}
add_action( 'plugins_loaded', 'nd_booking_maybe_upgrade_booking_db' );



///////////////////////////////////////////////////CSS STYLE///////////////////////////////////////////////////////////////

//add custom css
function nd_booking_scripts() {

  //basic css plugin
  wp_enqueue_style( 'nd_booking_style', esc_url(plugins_url('assets/css/style.css', __FILE__ )) );

  wp_enqueue_script('jquery');

}
add_action( 'wp_enqueue_scripts', 'nd_booking_scripts' );

if ( ! function_exists( 'nd_booking_should_enqueue_search_assets' ) ) {
    /**
     * Determine whether the current request should load the enhanced search assets.
     *
     * @return bool
     */
    function nd_booking_should_enqueue_search_assets() {
        if ( ! function_exists( 'is_search' ) ) {
            return false;
        }

        $should_enqueue = is_search();

        if ( ! $should_enqueue && function_exists( 'is_page' ) && is_page() ) {
            $page = function_exists( 'get_post' ) ? get_post() : null;

            if ( $page instanceof WP_Post ) {
                if ( function_exists( 'has_shortcode' ) && has_shortcode( $page->post_content, 'nd_booking_search_results' ) ) {
                    $should_enqueue = true;
                }

                if ( ! $should_enqueue && function_exists( 'nd_booking_post_contains_shortcode' ) && nd_booking_post_contains_shortcode( $page, 'nd_booking_search_results' ) ) {
                    $should_enqueue = true;
                }
            }
        }

        if ( $should_enqueue ) {
            return true;
        }

        $nd_booking_query_params = array(
            'nd_booking_archive_form_date_range_from',
            'nd_booking_archive_form_date_range_to',
            'nd_booking_archive_form_guests',
            'nd_booking_archive_form_services',
            'nd_booking_archive_form_additional_services',
            'nd_booking_archive_form_branch_stars',
            'nd_booking_archive_form_branches',
            'nd_booking_archive_form_max_price_for_day',
        );

        foreach ( $nd_booking_query_params as $query_param ) {
            if ( isset( $_GET[ $query_param ] ) && '' !== $_GET[ $query_param ] ) {
                return true;
            }
        }

        return false;
    }
}

if ( ! function_exists( 'nd_booking_enqueue_search_enhancements' ) ) {
    /**
     * Enqueue front-end enhancements for ND Booking search and results pages.
     */
    function nd_booking_enqueue_search_enhancements() {
        if ( ! nd_booking_should_enqueue_search_assets() ) {
            return;
        }

        $styles_relative_path = 'assets/css/search-enhancements.css';
        $styles_path          = plugin_dir_path( __FILE__ ) . $styles_relative_path;

        if ( file_exists( $styles_path ) && is_readable( $styles_path ) ) {
            $styles_version = (string) filemtime( $styles_path );

            wp_enqueue_style(
                'nd-booking-search-enhancements',
                plugins_url( $styles_relative_path, __FILE__ ),
                array( 'nd_booking_style' ),
                $styles_version
            );
        }

        $translation_fix_relative_path = 'assets/js/search-translation-fix.js';
        $translation_fix_path          = plugin_dir_path( __FILE__ ) . $translation_fix_relative_path;

        if ( file_exists( $translation_fix_path ) && is_readable( $translation_fix_path ) ) {
            $translation_fix_version = (string) filemtime( $translation_fix_path );

            wp_enqueue_script(
                'nd-booking-search-translation-fix',
                plugins_url( $translation_fix_relative_path, __FILE__ ),
                array(),
                $translation_fix_version,
                true
            );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'nd_booking_enqueue_search_enhancements', 35 );


if ( ! function_exists( 'nd_booking_elementor_data_contains_shortcode' ) ) {
    /**
     * Determine whether Elementor JSON data references a shortcode.
     *
     * @param mixed  $elementor_data Elementor post meta value.
     * @param string $shortcode      Shortcode tag to search for.
     *
     * @return bool
     */
    function nd_booking_elementor_data_contains_shortcode( $elementor_data, $shortcode ) {
        $shortcode = trim( (string) $shortcode );

        if ( '' === $shortcode || empty( $elementor_data ) ) {
            return false;
        }

        if ( is_array( $elementor_data ) ) {
            $elementor_data = wp_json_encode( $elementor_data );
        }

        if ( ! is_string( $elementor_data ) ) {
            return false;
        }

        return false !== stripos( $elementor_data, $shortcode );
    }
}

if ( ! function_exists( 'nd_booking_post_contains_shortcode' ) ) {
    /**
     * Inspect a post (and any referenced Elementor templates) for a shortcode.
     *
     * @param WP_Post $post      Post object under evaluation.
     * @param string  $shortcode Shortcode tag to search for.
     * @param array   $visited   Recursion guard to avoid repeated scans.
     *
     * @return bool
     */
    function nd_booking_post_contains_shortcode( $post, $shortcode, array &$visited = array() ) {
        if ( ! ( $post instanceof WP_Post ) ) {
            return false;
        }

        $shortcode = trim( (string) $shortcode );

        if ( '' === $shortcode ) {
            return false;
        }

        if ( isset( $visited[ $post->ID ] ) ) {
            return false;
        }

        $visited[ $post->ID ] = true;

        $post_content = (string) $post->post_content;

        if ( function_exists( 'has_shortcode' ) && ( has_shortcode( $post_content, $shortcode ) || false !== stripos( $post_content, '[' . $shortcode ) ) ) {
            return true;
        }

        if ( function_exists( 'nd_booking_elementor_data_contains_shortcode' ) && nd_booking_elementor_data_contains_shortcode( get_post_meta( $post->ID, '_elementor_data', true ), $shortcode ) ) {
            return true;
        }

        if ( ! function_exists( 'has_shortcode' ) || ! has_shortcode( $post_content, 'elementor-template' ) ) {
            return false;
        }

        preg_match_all( '/\[elementor-template[^\]]*id="?(\d+)"?[^\]]*\]/i', $post_content, $matches );

        if ( empty( $matches[1] ) ) {
            return false;
        }

        foreach ( $matches[1] as $template_id ) {
            $template_id = absint( $template_id );

            if ( ! $template_id || isset( $visited[ $template_id ] ) ) {
                continue;
            }

            $template_post = function_exists( 'get_post' ) ? get_post( $template_id ) : null;

            if ( ! $template_post instanceof WP_Post ) {
                continue;
            }

            if ( nd_booking_post_contains_shortcode( $template_post, $shortcode, $visited ) ) {
                return true;
            }
        }

        return false;
    }
}

if ( ! function_exists( 'nd_booking_is_checkout_screen' ) ) {
    /**
     * Determine whether the current request renders the ND Booking checkout experience.
     *
     * @return bool
     */
    function nd_booking_is_checkout_screen() {
        static $is_checkout = null;

        if ( null !== $is_checkout ) {
            return $is_checkout;
        }

        if ( is_admin() || ! is_page() ) {
            $is_checkout = false;

            return $is_checkout;
        }

        $page = get_post();

        if ( ! $page instanceof WP_Post ) {
            $is_checkout = false;

            return $is_checkout;
        }

        $visited = array();

        if ( nd_booking_post_contains_shortcode( $page, 'nd_booking_checkout', $visited ) ) {
            $is_checkout = true;

            return $is_checkout;
        }

        $checkout_page_id = absint( get_option( 'nd_booking_checkout_page' ) );

        if ( $checkout_page_id && $page->ID === $checkout_page_id ) {
            $is_checkout = true;

            return $is_checkout;
        }

        $is_checkout = false;

        return $is_checkout;
    }
}

if ( ! function_exists( 'nd_booking_enqueue_checkout_enhancements' ) ) {
    /**
     * Load the refined checkout experience assets when the checkout shortcode is present.
     */
    function nd_booking_enqueue_checkout_enhancements() {
        if ( ! nd_booking_is_checkout_screen() ) {
            return;
        }

        $style_relative_path = 'assets/css/checkout-form.css';
        $style_path          = plugin_dir_path( __FILE__ ) . $style_relative_path;

        if ( file_exists( $style_path ) && is_readable( $style_path ) ) {
            $style_version = (string) filemtime( $style_path );

            wp_enqueue_style(
                'nd-booking-checkout',
                plugins_url( $style_relative_path, __FILE__ ),
                array( 'nd_booking_style' ),
                $style_version
            );
        }

        $script_relative_path = 'assets/js/checkout-enhancements.js';
        $script_path          = plugin_dir_path( __FILE__ ) . $script_relative_path;

        if ( file_exists( $script_path ) && is_readable( $script_path ) ) {
            $script_version = (string) filemtime( $script_path );

            wp_enqueue_script(
                'nd-booking-checkout',
                plugins_url( $script_relative_path, __FILE__ ),
                array( 'jquery' ),
                $script_version,
                true
            );

            wp_localize_script(
                'nd-booking-checkout',
                'ndBookingCheckoutEnhancements',
                array(
                    'ctaLabel' => apply_filters(
                        'nd_booking_checkout_cta_label',
                        __( 'Confirmer ma réservation de luxe', 'nd-booking' )
                    ),
                )
            );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'nd_booking_enqueue_checkout_enhancements', 30 );

if ( ! function_exists( 'nd_booking_checkout_cta_gettext' ) ) {
    /**
     * Elevate checkout submission copy to align with the five-star brand voice.
     *
     * @param string $translation Translated text.
     * @param string $text        Original text.
     * @param string $domain      Translation domain.
     *
     * @return string
     */
    function nd_booking_checkout_cta_gettext( $translation, $text, $domain ) {
        if ( is_admin() || ! nd_booking_is_checkout_screen() ) {
            return $translation;
        }

        $eligible_domains = array( 'woocommerce', 'nd-booking', 'default' );

        if ( ! in_array( $domain, $eligible_domains, true ) ) {
            return $translation;
        }

        $targets = array(
            'Finaliser la commande',
            'Finalisez la commande',
            'Finaliser la réservation',
            'Finalisez la réservation',
            'Passer la commande',
            'Compléter la commande',
            'Valider la commande',
        );

        if ( in_array( $translation, $targets, true ) || in_array( $text, $targets, true ) ) {
            return apply_filters(
                'nd_booking_checkout_cta_label',
                __( 'Confirmer ma réservation de luxe', 'nd-booking' )
            );
        }

        return $translation;
    }
}
add_filter( 'gettext', 'nd_booking_checkout_cta_gettext', 10, 3 );


//START add admin custom css
function nd_booking_admin_style() {

  wp_enqueue_style( 'nd_booking_admin_style', esc_url(plugins_url('assets/css/admin-style.css', __FILE__ )), array(), false, false );
  
}
add_action( 'admin_enqueue_scripts', 'nd_booking_admin_style' );
//END add custom css


///////////////////////////////////////////////////GET TEMPLATE ///////////////////////////////////////////////////////////////

//single Cpt 1
function nd_booking_get_cpt_1_template($nd_booking_single_cpt_1_template) {
     global $post;

     if ($post->post_type == 'nd_booking_cpt_1') {
          $nd_booking_single_cpt_1_template = dirname( __FILE__ ) . '/templates/single-cpt-1.php';
     }
     return $nd_booking_single_cpt_1_template;
}
add_filter( 'single_template', 'nd_booking_get_cpt_1_template' );

//single Cpt 4
function nd_booking_get_cpt_4_template($nd_booking_single_cpt_4_template) {
     global $post;

     if ($post->post_type == 'nd_booking_cpt_4') {
          $nd_booking_single_cpt_4_template = dirname( __FILE__ ) . '/templates/single-cpt-4.php';
     }
     return $nd_booking_single_cpt_4_template;
}
add_filter( 'single_template', 'nd_booking_get_cpt_4_template' );

//update theme options
function nd_booking_theme_setup_update(){
    update_option( 'nicdark_theme_author', 0 );
}
add_action( 'after_switch_theme' , 'nd_booking_theme_setup_update');


///////////////////////////////////////////////////CPT///////////////////////////////////////////////////////////////
foreach ( glob ( plugin_dir_path( __FILE__ ) . "inc/cpt/*.php" ) as $file ){
  include_once realpath($file);
}


///////////////////////////////////////////////////SHORTCODES ///////////////////////////////////////////////////////////////
foreach ( glob ( plugin_dir_path( __FILE__ ) . "inc/shortcodes/*.php" ) as $file ){
  include_once realpath($file);
}


///////////////////////////////////////////////////ADDONS ///////////////////////////////////////////////////////////////
foreach ( glob ( plugin_dir_path( __FILE__ ) . "addons/*/index.php" ) as $file ){
  include_once realpath($file);
}


///////////////////////////////////////////////////FUNCTIONS///////////////////////////////////////////////////////////////
require_once dirname( __FILE__ ) . '/inc/functions/functions.php';


///////////////////////////////////////////////////METABOX ///////////////////////////////////////////////////////////////
foreach ( glob ( plugin_dir_path( __FILE__ ) . "inc/metabox/*.php" ) as $file ){
  include_once realpath($file);
}


///////////////////////////////////////////////////PLUGIN SETTINGS ///////////////////////////////////////////////////////////
require_once dirname( __FILE__ ) . '/inc/admin/plugin-settings.php';


//function for get plugin version
function nd_booking_get_plugin_version(){

    $nd_booking_plugin_data = get_plugin_data( __FILE__ );
    $nd_booking_plugin_version = $nd_booking_plugin_data['Version'];

    return $nd_booking_plugin_version;

}



