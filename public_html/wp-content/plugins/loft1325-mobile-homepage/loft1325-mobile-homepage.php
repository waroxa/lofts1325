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
         * Cached language code (fr or en).
         *
         * @var string|null
         */
        private $current_language = null;

        /**
         * Tracks whether required booking dependencies are available.
         *
         * @var bool
         */
        private $dependencies_ready = false;

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
            add_action( 'init', array( $this, 'evaluate_dependencies' ), 5 );
            add_action( 'init', array( $this, 'register_feature_post_type' ) );
            add_action( 'init', array( $this, 'register_image_sizes' ) );
            add_filter( 'query_vars', array( $this, 'register_preview_query_var' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_filter( 'template_include', array( $this, 'maybe_use_mobile_template' ), 99 );
            add_filter( 'body_class', array( $this, 'filter_body_class' ) );
            add_action( 'customize_register', array( $this, 'register_customizer_settings' ) );
            add_action( 'template_redirect', array( $this, 'redirect_mobile_search_requests' ) );
            add_action( 'admin_notices', array( $this, 'maybe_show_dependency_notice' ) );
        }

        /**
         * Validate plugin dependencies before running any front-end logic.
         */
        public function evaluate_dependencies() {
            $nd_booking_active = post_type_exists( 'nd_booking_cpt_1' );

            if ( ! $nd_booking_active ) {
                $this->dependencies_ready = false;
                return;
            }

            $this->dependencies_ready = true;
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

            if ( ! $this->dependencies_ready ) {
                return false;
            }

            if ( ! is_front_page() ) {
                return false;
            }

            if ( isset( $_GET['loft1325_mobile_preview'] ) && '1' === $_GET['loft1325_mobile_preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return true;
            }

            $is_mobile_request = wp_is_mobile();

            if ( ! $is_mobile_request ) {
                return false;
            }

            return (bool) apply_filters( 'loft1325_mobile_home_force_layout', true );
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
            wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css', array(), '4.6.13' );

            $fonts_url = 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap';
            wp_enqueue_style( 'loft1325-mobile-home-fonts', $fonts_url, array(), null );

            $script_path = plugin_dir_path( __FILE__ ) . 'assets/js/mobile-home.js';
            $script_uri  = plugin_dir_url( __FILE__ ) . 'assets/js/mobile-home.js';
            $script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1.0.0';

            wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js', array(), '4.6.13', true );
            wp_enqueue_script( 'flatpickr-range-plugin', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/plugins/rangePlugin.js', array( 'flatpickr' ), '4.6.13', true );

            wp_enqueue_script( 'loft1325-mobile-home', $script_uri, array( 'jquery', 'jquery-ui-datepicker', 'flatpickr', 'flatpickr-range-plugin' ), $script_ver, true );

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

			$action = '';
			$language = $this->get_current_language();

			if ( function_exists( 'nd_booking_search_page' ) ) {
				$action = nd_booking_search_page();
			}

			if ( ! $action ) {
				$archive_link = get_post_type_archive_link( 'nd_booking_cpt_1' );
				$action       = $archive_link ? $archive_link : home_url( '/' );
			}

			if ( function_exists( 'trp_get_url_for_language' ) ) {
				$action = trp_get_url_for_language( $action, $language );
			}

			$check_in_ts     = current_time( 'timestamp' );
			$check_out_ts    = $check_in_ts + DAY_IN_SECONDS;
			$check_in_value  = '';
			$check_out_value = '';

			$default_adults   = 2;
			$default_children = 0;

            $dates_label      = $this->localize_label( 'Dates', 'Dates' );
            $date_placeholder = $this->localize_label( 'Sélectionner les dates', 'Select dates' );
            $adults_label     = $this->localize_label( 'Adultes', 'Adults' );
            $children_label   = $this->localize_label( 'Enfants (0–18 ans)', 'Children (0–18 yrs)' );
            $promo_label      = $this->localize_label( 'Code promotionnel', 'Promotional code' );
            $add_promo_label  = $this->localize_label( 'Ajouter un code promotionnel', 'Add promotional code' );
            $promo_placeholder = $this->localize_label( 'Entrez votre code', 'Enter your code' );
            $language_attr    = ( 'en' === $language ) ? 'en' : 'fr';

            ob_start();
            ?>
            <form id="nd_booking_search_cpt_1_form_sidebar" class="loft-search-toolbar__form loft-search-toolbar__form--card" action="<?php echo esc_url( $action ); ?>" method="get" data-language="<?php echo esc_attr( $language_attr ); ?>">
                <div id="nd_booking_search_main_bg" class="loft-search-toolbar nd_booking_search_form">
                    <div class="loft-booking-card">
                        <div class="loft-search-toolbar__field loft-search-toolbar__field--date-range" data-date-field>
                            <label class="loft-search-toolbar__label" for="loft_booking_date_range"><?php echo esc_html( $dates_label ); ?></label>
                            <div class="loft-booking-card__date-input">
                                <input
                                    type="text"
                                    id="loft_booking_date_range"
                                    class="loft-booking-card__input loft-booking-card__input--date"
                                    placeholder="<?php echo esc_attr( $date_placeholder ); ?>"
                                    autocomplete="off"
                                    readonly
                                    aria-label="<?php echo esc_attr( $dates_label ); ?>"
                                />
                                <button type="button" class="loft-booking-card__clear" aria-label="<?php echo esc_attr( $this->localize_label( 'Effacer la plage de dates', 'Clear date range' ) ); ?>" data-date-clear>&times;</button>
                                <span class="loft-booking-card__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
                                        <rect x="4" y="5" width="16" height="16" rx="2"></rect>
                                        <line x1="16" y1="3" x2="16" y2="7"></line>
                                        <line x1="8" y1="3" x2="8" y2="7"></line>
                                        <line x1="4" y1="11" x2="20" y2="11"></line>
                                    </svg>
                                </span>
                            </div>
                            <input type="text" id="nd_booking_archive_form_date_range_from" name="nd_booking_archive_form_date_range_from" class="loft-booking-card__hidden-input loft-search-toolbar__input" value="<?php echo esc_attr( $check_in_value ); ?>" autocomplete="off" readonly />
                            <input type="text" id="nd_booking_archive_form_date_range_to" name="nd_booking_archive_form_date_range_to" class="loft-booking-card__hidden-input loft-search-toolbar__input" value="<?php echo esc_attr( $check_out_value ); ?>" autocomplete="off" readonly />
                        </div>

                        <div class="loft-booking-card__grid">
                            <div class="loft-search-toolbar__field loft-search-toolbar__field--guests">
                                <label class="loft-search-toolbar__label" for="loft_booking_adults"><?php echo esc_html( $adults_label ); ?></label>
                                <div class="loft-search-toolbar__control loft-search-toolbar__control--guests loft-search-toolbar__group loft-search-toolbar__guests" data-guest-group="adults">
                                    <button type="button" class="loft-search-toolbar__guest-btn" data-direction="down" aria-label="<?php echo esc_attr( $this->localize_label( 'Diminuer le nombre d’adultes', 'Decrease adult count' ) ); ?>">−</button>
                                    <span class="loft-search-toolbar__guests-value" id="loft_booking_adults_value"><?php echo esc_html( $default_adults ); ?></span>
                                    <button type="button" class="loft-search-toolbar__guest-btn" data-direction="up" aria-label="<?php echo esc_attr( $this->localize_label( 'Augmenter le nombre d’adultes', 'Increase adult count' ) ); ?>">+</button>
                                    <input type="hidden" id="loft_booking_adults" name="nd_booking_archive_form_adults" value="<?php echo esc_attr( $default_adults ); ?>" />
                                </div>
                            </div>

                            <div class="loft-search-toolbar__field loft-search-toolbar__field--guests">
                                <label class="loft-search-toolbar__label" for="loft_booking_children"><?php echo esc_html( $children_label ); ?></label>
                                <div class="loft-search-toolbar__control loft-search-toolbar__control--guests loft-search-toolbar__group loft-search-toolbar__guests" data-guest-group="children">
                                    <button type="button" class="loft-search-toolbar__guest-btn" data-direction="down" aria-label="<?php echo esc_attr( $this->localize_label( 'Diminuer le nombre d’enfants', 'Decrease child count' ) ); ?>">−</button>
                                    <span class="loft-search-toolbar__guests-value" id="loft_booking_children_value"><?php echo esc_html( $default_children ); ?></span>
                                    <button type="button" class="loft-search-toolbar__guest-btn" data-direction="up" aria-label="<?php echo esc_attr( $this->localize_label( 'Augmenter le nombre d’enfants', 'Increase child count' ) ); ?>">+</button>
                                    <input type="hidden" id="loft_booking_children" name="nd_booking_archive_form_children" value="<?php echo esc_attr( $default_children ); ?>" />
                                </div>
                            </div>
                        </div>

                        <div class="loft-booking-card__promo">
                            <button type="button" class="loft-booking-card__promo-toggle" data-promo-toggle>
                                <span class="loft-booking-card__promo-icon" aria-hidden="true">+</span>
                                <span class="loft-booking-card__promo-text"><?php echo esc_html( $add_promo_label ); ?></span>
                            </button>
                            <div class="loft-booking-card__promo-field" data-promo-field hidden>
                                <label class="loft-search-toolbar__label" for="loft_booking_coupon"><?php echo esc_html( $promo_label ); ?></label>
                                <input type="text" id="loft_booking_coupon" name="nd_booking_booking_form_coupon" class="loft-booking-card__text" placeholder="<?php echo esc_attr( $promo_placeholder ); ?>" autocomplete="off" />
                                <input type="hidden" id="loft_booking_coupon_checkout" name="nd_booking_checkout_form_coupon" value="" />
                            </div>
                        </div>

                        <input type="hidden" id="nd_booking_archive_form_guests" name="nd_booking_archive_form_guests" value="<?php echo esc_attr( $default_adults + $default_children ); ?>" />

                        <div class="loft-search-toolbar__field loft-search-toolbar__field--actions">
                            <button type="button" class="loft-search-card__btn loft-search-card__btn--primary loft-search-toolbar__submit"><?php echo esc_html( $this->get_string( 'search_submit_label' ) ); ?></button>
                        </div>
                    </div>
                </div>

            </form>
            <?php

            return trim( ob_get_clean() );
        }

        /**
         * When the mobile homepage handles ND Booking search requests, forward them to the
         * standard search results endpoint so visitors see the same results as desktop.
         */
        public function redirect_mobile_search_requests() {
            if ( is_admin() ) {
                return;
            }

            if ( ! $this->should_use_mobile_layout() ) {
                return;
            }

            $target = '';

			if ( function_exists( 'nd_booking_search_page' ) ) {
				$target = nd_booking_search_page();
			}

			if ( ! $target ) {
				$target = get_post_type_archive_link( 'nd_booking_cpt_1' );
			}

			if ( ! $target ) {
				return;
			}

			$language = $this->get_current_language();

			if ( function_exists( 'trp_get_url_for_language' ) ) {
				$target = trp_get_url_for_language( $target, $language );
			}

            $query_args = isset( $_GET ) ? (array) wp_unslash( $_GET ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            unset( $query_args['post_type'] );

            $has_search_params = isset( $query_args['nd_booking_archive_form_date_range_from'] ) || isset( $query_args['nd_booking_archive_form_date_range_to'] ) || isset( $query_args['nd_booking_archive_form_guests'] );

            if ( ! $has_search_params ) {
                return;
            }

            $query_args = array_filter(
                $query_args,
                static function ( $value ) {
                    return '' !== $value && null !== $value;
                }
            );

            $query_string = http_build_query( $query_args );

            $redirect_url = $target;

            if ( $query_string ) {
                $redirect_url .= ( false === strpos( $target, '?' ) ? '?' : '&' ) . $query_string;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }

        /**
         * Retrieve the active language.
         *
         * @return string fr or en.
         */
        public function get_current_language() {
            if ( null !== $this->current_language ) {
                return $this->current_language;
            }

            $language = 'fr';

            if ( function_exists( 'trp_get_current_language' ) ) {
                $language = (string) trp_get_current_language();
            } else {
                $language = function_exists( 'determine_locale' ) ? (string) determine_locale() : get_locale();
            }

            $language          = strtolower( substr( $language, 0, 2 ) );
            $this->current_language = ( 'en' === $language ) ? 'en' : 'fr';

            return $this->current_language;
        }

        /**
         * Retrieve default strings used in the layout.
         *
         * @return array<string, string>
         */
        public function get_default_strings() {
            $language = $this->get_current_language();

            if ( isset( $this->default_strings[ $language ] ) ) {
                return $this->default_strings[ $language ];
            }

            $this->default_strings['fr'] = array(
                'hero_tagline'           => __( 'Concierge Virtuel', 'loft1325-mobile-home' ),
                'hero_title'             => __( 'Expérience Hôtelière 100% Virtuelle', 'loft1325-mobile-home' ),
                'hero_description'       => __( "Pour le prix d'une chambre d'hôtel, offrez-vous tout le confort d'une maison et une expérience entièrement autonome. Notre concept unique vous permet de gérer votre séjour directement depuis votre mobile, sans réception ni attente. Créez vos propres clés numériques, invitez vos proches et contrôlez vos réservations en quelques clics seulement.", 'loft1325-mobile-home' ),
                'hero_primary_label'     => __( 'Réserver', 'loft1325-mobile-home' ),
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
                'rooms_button_label'     => __( 'Réserver', 'loft1325-mobile-home' ),
                'rooms_view_all_label'   => __( 'Voir tous les lofts', 'loft1325-mobile-home' ),
                'cta_heading'            => __( "Prêt à vivre l'expérience?", 'loft1325-mobile-home' ),
                'cta_description'        => __( 'Réservez dès maintenant votre séjour et découvrez une nouvelle façon de voyager.', 'loft1325-mobile-home' ),
                'cta_primary_label'      => __( 'Réserver', 'loft1325-mobile-home' ),
                'cta_primary_url'        => '#loft1325-mobile-home-search',
                'cta_secondary_label'    => __( 'Nous contacter', 'loft1325-mobile-home' ),
                'cta_secondary_url'      => '/contact',
                'footer_nav_heading'     => __( 'Navigation', 'loft1325-mobile-home' ),
                'footer_support_heading' => __( 'Support', 'loft1325-mobile-home' ),
                'footer_social_heading'  => __( 'Suivez-nous', 'loft1325-mobile-home' ),
                'footer_legal'           => __( 'Expérience hôtelière 100% virtuelle', 'loft1325-mobile-home' ),
                'footer_copyright'       => sprintf( __( '© %1$s Loft1325. Tous droits réservés. | CITQ Certificat: 301842', 'loft1325-mobile-home' ), date_i18n( 'Y' ) ),
            );

            $this->default_strings['en'] = array(
                'hero_tagline'           => __( 'Virtual Concierge', 'loft1325-mobile-home' ),
                'hero_title'             => __( '100% Virtual Hotel Experience', 'loft1325-mobile-home' ),
                'hero_description'       => __( "For the price of a hotel room, enjoy the comfort of a home and a fully self-service stay. Our unique concept lets you manage your visit from your phone with no front desk or waiting. Create your own digital keys, invite guests, and control bookings in just a few taps.", 'loft1325-mobile-home' ),
                'hero_primary_label'     => __( 'Book Now', 'loft1325-mobile-home' ),
                'hero_primary_url'       => '#loft1325-mobile-home-search',
                'hero_secondary_label'   => __( 'Contact Us', 'loft1325-mobile-home' ),
                'hero_secondary_url'     => '/contact',
                'search_card_title'      => __( 'Virtual Concierge', 'loft1325-mobile-home' ),
                'search_location_label'  => __( 'Where', 'loft1325-mobile-home' ),
                'search_location_value'  => '',
                'search_date_label'      => __( 'When', 'loft1325-mobile-home' ),
                'search_guests_label'    => __( 'Guests', 'loft1325-mobile-home' ),
                'search_submit_label'    => __( 'Search', 'loft1325-mobile-home' ),
                'rooms_heading'          => __( 'Premium Lofts', 'loft1325-mobile-home' ),
                'rooms_description'      => __( 'Unlike traditional hotel rooms, our lofts offer a more generous living space—often 1.5 to 3 times larger for the same price as a hotel room.', 'loft1325-mobile-home' ),
                'rooms_button_label'     => __( 'Book Now', 'loft1325-mobile-home' ),
                'rooms_view_all_label'   => __( 'See all lofts', 'loft1325-mobile-home' ),
                'cta_heading'            => __( 'Ready to experience it?', 'loft1325-mobile-home' ),
                'cta_description'        => __( 'Book your stay now and discover a new way to travel.', 'loft1325-mobile-home' ),
                'cta_primary_label'      => __( 'Book Now', 'loft1325-mobile-home' ),
                'cta_primary_url'        => '#loft1325-mobile-home-search',
                'cta_secondary_label'    => __( 'Contact Us', 'loft1325-mobile-home' ),
                'cta_secondary_url'      => '/contact',
                'footer_nav_heading'     => __( 'Navigation', 'loft1325-mobile-home' ),
                'footer_support_heading' => __( 'Support', 'loft1325-mobile-home' ),
                'footer_social_heading'  => __( 'Follow us', 'loft1325-mobile-home' ),
                'footer_legal'           => __( '100% virtual hotel experience', 'loft1325-mobile-home' ),
                'footer_copyright'       => sprintf( __( '© %1$s Loft1325. All rights reserved. | CITQ Certificate: 301842', 'loft1325-mobile-home' ), date_i18n( 'Y' ) ),
            );

            return $this->default_strings[ $language ];
        }

        /**
         * Fetch a localized string, falling back to defaults as needed.
         *
         * @param string $key String identifier.
         *
         * @return string
         */
        public function get_string( $key ) {
            $defaults  = $this->get_default_strings();
            $language  = $this->get_current_language();
            $setting   = get_theme_mod( 'loft1325_mobile_home_' . $key );
            $has_setting = is_string( $setting ) && '' !== trim( $setting );

            if ( 'fr' === $language && $has_setting ) {
                return $setting;
            }

            return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
        }

        /**
         * Quickly return a localized string for inline labels.
         *
         * @param string $french  French text.
         * @param string $english English text.
         *
         * @return string
         */
        public function localize_label( $french, $english ) {
            return ( 'en' === $this->get_current_language() ) ? $english : $french;
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

        /**
         * Surface a helpful admin notice when dependencies are missing.
         */
        public function maybe_show_dependency_notice() {
            if ( $this->dependencies_ready || ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            echo '<div class="notice notice-error"><p>' . esc_html__( 'Loft1325 Mobile Homepage needs the ND Booking plugin active to render properly. Please activate ND Booking before enabling the mobile experience.', 'loft1325-mobile-home' ) . '</p></div>';
        }
    }
}

// Boot the plugin.
Loft1325_Mobile_Homepage::instance();
