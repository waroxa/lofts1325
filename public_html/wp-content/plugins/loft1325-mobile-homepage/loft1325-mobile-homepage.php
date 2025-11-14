<?php
/**
 * Plugin Name: Loft1325 Mobile Homepage
 * Description: Provides a dedicated mobile-only homepage experience without altering the desktop layout.
 * Author: Loft1325 Automation
 * Version: 1.0.0
 * Text Domain: loft1325-mobile-home
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Loft1325_Mobile_Homepage' ) ) {
    final class Loft1325_Mobile_Homepage {

        /**
         * Singleton instance.
         *
         * @var Loft1325_Mobile_Homepage|null
         */
        private static $instance = null;

        /**
         * Flag that tracks whether the mobile template is being rendered.
         *
         * @var bool
         */
        private $is_mobile_template = false;

        /**
         * Cached default strings for the mobile layout.
         *
         * @var array<string, string>
         */
        private $default_strings = array();

        /**
         * Initialize singleton instance.
         *
         * @return Loft1325_Mobile_Homepage
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Loft1325_Mobile_Homepage constructor.
         */
        private function __construct() {
            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
            add_action( 'init', array( $this, 'register_feature_post_type' ) );
            add_action( 'init', array( $this, 'register_image_sizes' ) );
            add_filter( 'query_vars', array( $this, 'register_preview_query_var' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_filter( 'template_include', array( $this, 'maybe_use_mobile_template' ), 99 );
            add_filter( 'body_class', array( $this, 'filter_body_class' ) );
            add_action( 'customize_register', array( $this, 'register_customizer_settings' ) );
        }

        /**
         * Load plugin text domain.
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'loft1325-mobile-home', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        /**
         * Register query var that forces the mobile preview.
         *
         * @param array<string> $vars Query variables.
         *
         * @return array<string>
         */
        public function register_preview_query_var( $vars ) {
            if ( ! in_array( 'loft1325_mobile_preview', $vars, true ) ) {
                $vars[] = 'loft1325_mobile_preview';
            }

            return $vars;
        }

        /**
         * Register the custom post type that powers the feature grid.
         */
        public function register_feature_post_type() {
            $labels = array(
                'name'                  => __( 'Mobile Features', 'loft1325-mobile-home' ),
                'singular_name'         => __( 'Mobile Feature', 'loft1325-mobile-home' ),
                'add_new'               => __( 'Add New', 'loft1325-mobile-home' ),
                'add_new_item'          => __( 'Add New Feature', 'loft1325-mobile-home' ),
                'edit_item'             => __( 'Edit Feature', 'loft1325-mobile-home' ),
                'new_item'              => __( 'New Feature', 'loft1325-mobile-home' ),
                'view_item'             => __( 'View Feature', 'loft1325-mobile-home' ),
                'search_items'          => __( 'Search Features', 'loft1325-mobile-home' ),
                'not_found'             => __( 'No features found', 'loft1325-mobile-home' ),
                'not_found_in_trash'    => __( 'No features found in Trash', 'loft1325-mobile-home' ),
                'all_items'             => __( 'All Mobile Features', 'loft1325-mobile-home' ),
                'menu_name'             => __( 'Mobile Features', 'loft1325-mobile-home' ),
                'name_admin_bar'        => __( 'Mobile Feature', 'loft1325-mobile-home' ),
            );

            $args = array(
                'labels'             => $labels,
                'public'             => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'show_in_admin_bar'  => true,
                'menu_position'      => 25,
                'menu_icon'          => 'dashicons-screenoptions',
                'supports'           => array( 'title', 'thumbnail', 'editor' ),
                'exclude_from_search'=> true,
                'publicly_queryable' => false,
                'has_archive'        => false,
                'rewrite'            => false,
            );

            register_post_type( 'loft1325_mobile_feature', $args );
        }

        /**
         * Register the custom image size used throughout the layout.
         */
        public function register_image_sizes() {
            add_image_size( 'loft1325_mobile_feature_icon', 96, 96, true );
            add_image_size( 'loft1325_mobile_room_card', 720, 480, true );
        }

        /**
         * Determine whether the mobile layout should load on the current request.
         *
         * @return bool
         */
        public function should_use_mobile_layout() {
            if ( is_admin() || is_feed() || is_embed() ) {
                return false;
            }

            if ( ! is_front_page() ) {
                return false;
            }

            if ( apply_filters( 'loft1325_mobile_home_force_layout', false ) ) {
                return true;
            }

            if ( isset( $_GET['loft1325_mobile_preview'] ) && '1' === $_GET['loft1325_mobile_preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return true;
            }

            return wp_is_mobile();
        }

        /**
         * Enqueue assets required for the mobile homepage.
         */
        public function enqueue_assets() {
            if ( ! $this->should_use_mobile_layout() ) {
                return;
            }

            wp_enqueue_style( 'dashicons' );

            $style_path = plugin_dir_path( __FILE__ ) . 'assets/css/mobile-home.css';
            $style_uri  = plugin_dir_url( __FILE__ ) . 'assets/css/mobile-home.css';
            $version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : '1.0.0';

            wp_enqueue_style( 'loft1325-mobile-home', $style_uri, array(), $version );

            $fonts_url = 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap';
            wp_enqueue_style( 'loft1325-mobile-home-fonts', $fonts_url, array(), null );

            $script_path = plugin_dir_path( __FILE__ ) . 'assets/js/mobile-home.js';
            $script_uri  = plugin_dir_url( __FILE__ ) . 'assets/js/mobile-home.js';
            $script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1.0.0';

            wp_enqueue_script( 'loft1325-mobile-home', $script_uri, array( 'jquery', 'jquery-ui-datepicker' ), $script_ver, true );

            $this->enqueue_search_dependencies();
        }

        /**
         * Ensure the ND Booking search dependencies are available for the mobile form.
         */
        private function enqueue_search_dependencies() {
            wp_enqueue_script( 'jquery-ui-datepicker' );

            $nd_booking_search_file = WP_PLUGIN_DIR . '/nd-booking/addons/visual/search/index.php';

            if ( ! file_exists( $nd_booking_search_file ) ) {
                return;
            }

            $datepicker_path = plugin_dir_path( $nd_booking_search_file ) . 'jquery-ui-datepicker.css';

            if ( ! file_exists( $datepicker_path ) ) {
                return;
            }

            $datepicker_uri = plugin_dir_url( $nd_booking_search_file ) . 'jquery-ui-datepicker.css';
            $handle         = 'nd-booking-datepicker';
            $version        = (string) filemtime( $datepicker_path );

            if ( ! wp_style_is( $handle, 'enqueued' ) ) {
                wp_enqueue_style( $handle, $datepicker_uri, array(), $version );
            }
        }

        /**
         * Swap the front-page template with our mobile-only version when appropriate.
         *
         * @param string $template Original template path.
         *
         * @return string
         */
        public function maybe_use_mobile_template( $template ) {
            if ( ! $this->should_use_mobile_layout() ) {
                return $template;
            }

            $mobile_template = plugin_dir_path( __FILE__ ) . 'templates/mobile-front-page.php';

            if ( ! file_exists( $mobile_template ) ) {
                return $template;
            }

            $this->is_mobile_template = true;

            return $mobile_template;
        }

        /**
         * Append custom body class when the mobile template is active.
         *
         * @param array<int, string> $classes Existing body classes.
         *
         * @return array<int, string>
         */
        public function filter_body_class( $classes ) {
            if ( $this->is_mobile_template ) {
                $classes[] = 'loft1325-mobile-home-active';
            }

            return $classes;
        }

        /**
         * Generate the ND Booking search form markup used on the mobile homepage.
         *
         * @return string
         */
        public function get_mobile_search_form_markup() {
            $this->enqueue_search_dependencies();

            $action = function_exists( 'nd_booking_search_page' ) ? nd_booking_search_page() : home_url( '/' );

            $check_in_ts  = current_time( 'timestamp' );
            $check_out_ts = $check_in_ts + DAY_IN_SECONDS;

            $check_in_value  = wp_date( 'm/d/Y', $check_in_ts );
            $check_out_value = wp_date( 'm/d/Y', $check_out_ts );

            $default_guests = 1;
            $default_nights = max( 1, (int) round( ( $check_out_ts - $check_in_ts ) / DAY_IN_SECONDS ) );

            $nights_label = sprintf( _n( '%s nuit', '%s nuits', $default_nights, 'nd-booking' ), number_format_i18n( $default_nights ) );
            $guests_label = sprintf( _n( '%s invité', '%s invités', $default_guests, 'nd-booking' ), number_format_i18n( $default_guests ) );

            ob_start();
            ?>
            <form id="nd_booking_search_cpt_1_form_sidebar" class="loft-search-toolbar__form" action="<?php echo esc_url( $action ); ?>" method="get">
                <div id="nd_booking_search_main_bg" class="loft-search-toolbar nd_booking_search_form">
                    <div class="loft-search-toolbar__field loft-search-toolbar__field--date">
                        <label for="nd_booking_archive_form_date_range_from" class="loft-search-toolbar__label"><?php esc_html_e( 'Arrivée', 'nd-booking' ); ?></label>
                        <div class="loft-search-toolbar__control loft-search-toolbar__control--date loft-search-toolbar__group">
                            <input type="text" id="nd_booking_archive_form_date_range_from" name="nd_booking_archive_form_date_range_from" class="loft-search-toolbar__input" value="<?php echo esc_attr( $check_in_value ); ?>" autocomplete="off" readonly />
                        </div>
                    </div>

                    <div class="loft-search-toolbar__field loft-search-toolbar__field--date">
                        <label for="nd_booking_archive_form_date_range_to" class="loft-search-toolbar__label"><?php esc_html_e( 'Départ', 'nd-booking' ); ?></label>
                        <div class="loft-search-toolbar__control loft-search-toolbar__control--date loft-search-toolbar__group">
                            <input type="text" id="nd_booking_archive_form_date_range_to" name="nd_booking_archive_form_date_range_to" class="loft-search-toolbar__input" value="<?php echo esc_attr( $check_out_value ); ?>" autocomplete="off" readonly />
                        </div>
                    </div>

                    <div class="loft-search-toolbar__field loft-search-toolbar__field--guests">
                        <label class="loft-search-toolbar__label" for="nd_booking_archive_form_guests"><?php esc_html_e( 'Invités', 'nd-booking' ); ?></label>
                        <div class="loft-search-toolbar__control loft-search-toolbar__control--guests loft-search-toolbar__group loft-search-toolbar__guests">
                            <button type="button" class="loft-search-toolbar__guest-btn" data-direction="down" aria-label="<?php esc_attr_e( 'Diminuer le nombre d’invités', 'nd-booking' ); ?>">−</button>
                            <span class="loft-search-toolbar__guests-value" id="loft_search_guest_display"><?php echo esc_html( $guests_label ); ?></span>
                            <button type="button" class="loft-search-toolbar__guest-btn" data-direction="up" aria-label="<?php esc_attr_e( 'Augmenter le nombre d’invités', 'nd-booking' ); ?>">+</button>
                        </div>
                        <input type="hidden" id="nd_booking_archive_form_guests" name="nd_booking_archive_form_guests" value="<?php echo esc_attr( $default_guests ); ?>" />
                    </div>

                    <div class="loft-search-toolbar__field loft-search-toolbar__field--summary">
                        <span class="loft-search-toolbar__label"><?php esc_html_e( 'Nuits', 'nd-booking' ); ?></span>
                        <div class="loft-search-toolbar__summary loft-search-toolbar__group loft-search-toolbar__nights" id="nd_booking_nights_display"><?php echo esc_html( $nights_label ); ?></div>
                    </div>

                    <div class="loft-search-toolbar__field loft-search-toolbar__field--actions">
                        <span class="loft-search-toolbar__label">&nbsp;</span>
                        <button type="submit" class="loft-search-card__btn loft-search-card__btn--primary loft-search-toolbar__submit"><?php echo esc_html( $this->get_string( 'search_submit_label' ) ); ?></button>
                    </div>
                </div>

                <input type="hidden" id="nd_booking_archive_form_branches" name="nd_booking_archive_form_branches" value="" />
                <input type="hidden" id="nd_booking_archive_form_max_price_for_day" name="nd_booking_archive_form_max_price_for_day" value="" />
                <input type="hidden" id="nd_booking_archive_form_services" name="nd_booking_archive_form_services" value="" />
                <input type="hidden" id="nd_booking_archive_form_additional_services" name="nd_booking_archive_form_additional_services" value="" />
                <input type="hidden" id="nd_booking_archive_form_branch_stars" name="nd_booking_archive_form_branch_stars" value="" />
            </form>
            <?php

            return trim( ob_get_clean() );
        }

        /**
         * Retrieve default strings used in the layout.
         *
         * @return array<string, string>
         */
        public function get_default_strings() {
            if ( ! empty( $this->default_strings ) ) {
                return $this->default_strings;
            }

            $this->default_strings = array(
                'hero_tagline'           => __( 'Concierge Virtuel', 'loft1325-mobile-home' ),
                'hero_title'             => __( 'Expérience Hôtelière 100% Virtuelle', 'loft1325-mobile-home' ),
                'hero_description'       => __( "Pour le prix d'une chambre d'hôtel, offrez-vous tout le confort d'une maison et une expérience entièrement autonome. Notre concept unique vous permet de gérer votre séjour directement depuis votre mobile, sans réception ni attente. Créez vos propres clés numériques, invitez vos proches et contrôlez vos réservations en quelques clics seulement.", 'loft1325-mobile-home' ),
                'hero_primary_label'     => __( 'Réserver un loft', 'loft1325-mobile-home' ),
                'hero_primary_url'       => '#loft1325-mobile-home-search',
                'hero_secondary_label'   => __( 'Nous contacter', 'loft1325-mobile-home' ),
                'hero_secondary_url'     => '/contact',
                'search_card_title'      => __( 'Concierge Virtuel', 'loft1325-mobile-home' ),
                'search_location_label'  => __( 'Où', 'loft1325-mobile-home' ),
                'search_location_value'  => '',
                'search_date_label'      => __( 'Quand', 'loft1325-mobile-home' ),
                'search_guests_label'    => __( 'Invités', 'loft1325-mobile-home' ),
                'search_submit_label'    => __( 'Rechercher', 'loft1325-mobile-home' ),
                'rooms_heading'          => __( 'Lofts Haut de Gamme', 'loft1325-mobile-home' ),
                'rooms_description'      => __( "Contrairement aux chambres d'hôtel traditionnelles, nos lofts offrent un espace de vie plus généreux, souvent 1,5 à 3 fois plus grand au même prix qu'une chambre d'hôtel.", 'loft1325-mobile-home' ),
                'rooms_button_label'     => __( 'Nous joindre', 'loft1325-mobile-home' ),
                'rooms_view_all_label'   => __( 'Voir tous les lofts', 'loft1325-mobile-home' ),
                'cta_heading'            => __( "Prêt à vivre l'expérience?", 'loft1325-mobile-home' ),
                'cta_description'        => __( 'Réservez dès maintenant votre séjour et découvrez une nouvelle façon de voyager.', 'loft1325-mobile-home' ),
                'cta_primary_label'      => __( 'Réserver un loft', 'loft1325-mobile-home' ),
                'cta_primary_url'        => '#loft1325-mobile-home-search',
                'cta_secondary_label'    => __( 'Nous contacter', 'loft1325-mobile-home' ),
                'cta_secondary_url'      => '/contact',
                'footer_nav_heading'     => __( 'Navigation', 'loft1325-mobile-home' ),
                'footer_support_heading' => __( 'Support', 'loft1325-mobile-home' ),
                'footer_social_heading'  => __( 'Suivez-nous', 'loft1325-mobile-home' ),
                'footer_legal'           => __( 'Expérience hôtelière 100% virtuelle', 'loft1325-mobile-home' ),
                'footer_copyright'       => sprintf( __( '© %1$s Loft1325. Tous droits réservés. | CITQ Certificat: 301842', 'loft1325-mobile-home' ), date_i18n( 'Y' ) ),
            );

            return $this->default_strings;
        }

        /**
         * Fetch a localized string, falling back to defaults as needed.
         *
         * @param string $key String identifier.
         *
         * @return string
         */
        public function get_string( $key ) {
            $defaults = $this->get_default_strings();
            $setting  = get_theme_mod( 'loft1325_mobile_home_' . $key );

            if ( is_string( $setting ) && '' !== trim( $setting ) ) {
                return $setting;
            }

            return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
        }

        /**
         * Retrieve social links configured in the Customizer.
         *
         * @return array<int, array<string, string>>
         */
        public function get_social_links() {
            $links = array(
                array(
                    'label' => 'Airbnb',
                    'url'   => get_theme_mod( 'loft1325_mobile_home_social_airbnb', '' ),
                ),
                array(
                    'label' => 'Trip Advisor',
                    'url'   => get_theme_mod( 'loft1325_mobile_home_social_tripadvisor', '' ),
                ),
                array(
                    'label' => 'Instagram',
                    'url'   => get_theme_mod( 'loft1325_mobile_home_social_instagram', '' ),
                ),
            );

            return array_filter(
                $links,
                static function( $link ) {
                    return ! empty( $link['url'] );
                }
            );
        }

        /**
         * Retrieve feature cards, either from the custom post type or fallback defaults.
         *
         * @return array<int, array<string, string>>
         */
        public function get_feature_cards() {
            $features = array();

            $query = new WP_Query(
                array(
                    'post_type'      => 'loft1325_mobile_feature',
                    'post_status'    => 'publish',
                    'posts_per_page' => 8,
                    'orderby'        => array(
                        'menu_order' => 'ASC',
                        'date'       => 'DESC',
                    ),
                )
            );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();

                    $image = get_the_post_thumbnail_url( get_the_ID(), 'loft1325_mobile_feature_icon' );

                    $features[] = array(
                        'title'       => get_the_title(),
                        'description' => has_excerpt() ? get_the_excerpt() : '',
                        'image'       => $image,
                        'icon'        => '',
                    );
                }

                wp_reset_postdata();

                return $features;
            }

            $fallbacks = array(
                array(
                    'title' => __( 'Gestion mobile', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-smartphone',
                ),
                array(
                    'title' => __( 'Check-in 24/7', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-clock',
                ),
                array(
                    'title' => __( '100% Sécurisé', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-lock',
                ),
                array(
                    'title' => __( 'Certifié CITQ', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-awards',
                ),
                array(
                    'title' => __( 'Choisir chambre et date', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-calendar-alt',
                ),
                array(
                    'title' => __( 'Paiement en ligne', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-cart',
                ),
                array(
                    'title' => __( 'Confirmation paiement', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-yes',
                ),
                array(
                    'title' => __( 'Recevez clé virtuelle', 'loft1325-mobile-home' ),
                    'icon'  => 'dashicons-unlock',
                ),
            );

            return $fallbacks;
        }

        /**
         * Gather room cards populated from ND Booking rooms.
         *
         * @return array<int, array<string, string>>
         */
        public function get_room_cards() {
            $rooms = array();

            $query = new WP_Query(
                array(
                    'post_type'      => 'nd_booking_cpt_1',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'meta_query'     => array(
                        array(
                            'key'     => '_thumbnail_id',
                            'compare' => 'EXISTS',
                        ),
                    ),
                )
            );

            if ( ! $query->have_posts() ) {
                return $rooms;
            }

            while ( $query->have_posts() ) {
                $query->the_post();

                $price      = '';
                $currency   = '';
                $room_id    = get_the_ID();
                $rating_raw = get_post_meta( $room_id, 'loft1325_room_rating', true );

                if ( empty( $rating_raw ) ) {
                    $rating_raw = get_post_meta( $room_id, 'nd_booking_meta_box_review_average', true );
                }

                if ( empty( $rating_raw ) ) {
                    $rating_raw = get_post_meta( $room_id, 'nd_booking_meta_box_stars', true );
                }

                if ( '' !== $rating_raw && is_numeric( $rating_raw ) ) {
                    $rating_raw = number_format_i18n( (float) $rating_raw, 1 );
                }

                if ( function_exists( 'nd_booking_get_final_price' ) ) {
                    $price = nd_booking_get_final_price( $room_id, current_time( 'm/d/Y' ) );
                }

                if ( function_exists( 'nd_booking_get_currency' ) ) {
                    $currency = nd_booking_get_currency();
                }

                if ( ! is_numeric( $price ) ) {
                    $price = '';
                }

                $rooms[] = array(
                    'title'       => get_the_title(),
                    'permalink'   => get_permalink(),
                    'excerpt'     => has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : wp_trim_words( wp_strip_all_tags( get_the_content() ), 24 ),
                    'image'       => get_the_post_thumbnail_url( $room_id, 'loft1325_mobile_room_card' ),
                    'price'       => $price,
                    'currency'    => $currency,
                    'rating'      => $rating_raw,
                );
            }

            wp_reset_postdata();

            return $rooms;
        }

        /**
         * Register Customizer controls that allow quick content tweaks.
         *
         * @param WP_Customize_Manager $wp_customize Customizer instance.
         */
        public function register_customizer_settings( $wp_customize ) {
            if ( ! ( $wp_customize instanceof WP_Customize_Manager ) ) {
                return;
            }

            $wp_customize->add_section(
                'loft1325_mobile_home',
                array(
                    'title'      => __( 'Mobile Homepage', 'loft1325-mobile-home' ),
                    'priority'   => 35,
                    'capability' => 'edit_theme_options',
                )
            );

            $fields = array(
                'hero_tagline'         => array( 'type' => 'text' ),
                'hero_title'           => array( 'type' => 'text' ),
                'hero_description'     => array( 'type' => 'textarea' ),
                'hero_primary_label'   => array( 'type' => 'text' ),
                'hero_primary_url'     => array( 'type' => 'url' ),
                'hero_secondary_label' => array( 'type' => 'text' ),
                'hero_secondary_url'   => array( 'type' => 'url' ),
                'rooms_heading'        => array( 'type' => 'text' ),
                'rooms_description'    => array( 'type' => 'textarea' ),
                'rooms_button_label'   => array( 'type' => 'text' ),
                'rooms_view_all_label' => array( 'type' => 'text' ),
                'cta_heading'          => array( 'type' => 'text' ),
                'cta_description'      => array( 'type' => 'textarea' ),
                'cta_primary_label'    => array( 'type' => 'text' ),
                'cta_primary_url'      => array( 'type' => 'url' ),
                'cta_secondary_label'  => array( 'type' => 'text' ),
                'cta_secondary_url'    => array( 'type' => 'url' ),
                'footer_nav_heading'   => array( 'type' => 'text' ),
                'footer_support_heading' => array( 'type' => 'text' ),
                'footer_social_heading'  => array( 'type' => 'text' ),
                'footer_legal'           => array( 'type' => 'textarea' ),
                'footer_copyright'       => array( 'type' => 'textarea' ),
            );

            foreach ( $fields as $key => $config ) {
                $default = $this->get_string( $key );

                $sanitize_callback = 'sanitize_text_field';
                if ( 'textarea' === $config['type'] ) {
                    $sanitize_callback = 'sanitize_textarea_field';
                } elseif ( 'url' === $config['type'] ) {
                    $sanitize_callback = 'esc_url_raw';
                }

                $wp_customize->add_setting(
                    'loft1325_mobile_home_' . $key,
                    array(
                        'default'           => $default,
                        'sanitize_callback' => $sanitize_callback,
                        'transport'         => 'refresh',
                    )
                );

                $control_args = array(
                    'label'    => ucfirst( str_replace( '_', ' ', $key ) ),
                    'section'  => 'loft1325_mobile_home',
                    'settings' => 'loft1325_mobile_home_' . $key,
                );

                if ( 'textarea' === $config['type'] ) {
                    $control_args['type'] = 'textarea';
                } elseif ( 'url' === $config['type'] ) {
                    $control_args['type'] = 'url';
                } else {
                    $control_args['type'] = 'text';
                }

                $wp_customize->add_control( 'loft1325_mobile_home_' . $key, $control_args );
            }

            $wp_customize->add_setting(
                'loft1325_mobile_home_social_airbnb',
                array(
                    'default'           => '',
                    'sanitize_callback' => 'esc_url_raw',
                )
            );
            $wp_customize->add_control(
                'loft1325_mobile_home_social_airbnb',
                array(
                    'label'    => __( 'Airbnb URL', 'loft1325-mobile-home' ),
                    'section'  => 'loft1325_mobile_home',
                    'type'     => 'url',
                )
            );

            $wp_customize->add_setting(
                'loft1325_mobile_home_social_tripadvisor',
                array(
                    'default'           => '',
                    'sanitize_callback' => 'esc_url_raw',
                )
            );
            $wp_customize->add_control(
                'loft1325_mobile_home_social_tripadvisor',
                array(
                    'label'    => __( 'TripAdvisor URL', 'loft1325-mobile-home' ),
                    'section'  => 'loft1325_mobile_home',
                    'type'     => 'url',
                )
            );

            $wp_customize->add_setting(
                'loft1325_mobile_home_social_instagram',
                array(
                    'default'           => '',
                    'sanitize_callback' => 'esc_url_raw',
                )
            );
            $wp_customize->add_control(
                'loft1325_mobile_home_social_instagram',
                array(
                    'label'    => __( 'Instagram URL', 'loft1325-mobile-home' ),
                    'section'  => 'loft1325_mobile_home',
                    'type'     => 'url',
                )
            );

            $wp_customize->add_setting(
                'loft1325_mobile_home_hero_background',
                array(
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                )
            );

            $wp_customize->add_control(
                new WP_Customize_Media_Control(
                    $wp_customize,
                    'loft1325_mobile_home_hero_background',
                    array(
                        'label'    => __( 'Hero Background Image', 'loft1325-mobile-home' ),
                        'section'  => 'loft1325_mobile_home',
                        'mime_type'=> 'image',
                    )
                )
            );
        }
    }
}

// Boot the plugin.
Loft1325_Mobile_Homepage::instance();
