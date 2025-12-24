<?php
/**
 * Plugin Name: Loft1325 Mobile Lofts
 * Description: Mobile-forward room detail experience for ND Booking single rooms.
 * Author: Loft1325 Automation
 * Version: 1.0.0
 * Text Domain: loft1325-mobile-lofts
 * Restored: Ensures the mobile-first loft detail layout remains active for designated room pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Loft1325_Mobile_Lofts' ) ) {
	final class Loft1325_Mobile_Lofts {

		/**
		 * Singleton instance.
		 *
		 * @var Loft1325_Mobile_Lofts|null
		 */
		private static $instance = null;

		/**
		 * Whether the mobile loft template is active.
		 *
		 * @var bool
		 */
		private $is_mobile_template = false;

		/**
		 * Cached language code (fr or en).
		 *
		 * @var string|null
		 */
		private $current_language = null;

		/**
		 * Retrieve the singleton instance.
		 *
		 * @return Loft1325_Mobile_Lofts
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'init', array( $this, 'register_image_sizes' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_filter( 'template_include', array( $this, 'maybe_use_mobile_template' ), 99 );
			add_filter( 'body_class', array( $this, 'filter_body_class' ) );
		}

		/**
		 * Load translations.
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'loft1325-mobile-lofts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Register image sizes for the mobile slider.
		 */
		public function register_image_sizes() {
			add_image_size( 'loft1325_mobile_loft_slider', 1200, 800, true );
		}

		/**
		 * Determine if the mobile loft experience should render.
		 *
		 * @return bool
		 */
		public function should_use_mobile_layout() {
			if ( is_admin() || is_feed() || is_embed() ) {
				return false;
			}

			if ( ! is_singular( 'nd_booking_cpt_1' ) ) {
				return false;
			}

			if ( apply_filters( 'loft1325_mobile_lofts_force_layout', false ) ) {
				return true;
			}

			if ( isset( $_GET['loft1325_mobile_preview'] ) && '1' === $_GET['loft1325_mobile_preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}

			return wp_is_mobile();
		}

		/**
		 * Swap template for ND Booking single room pages on mobile.
		 *
		 * @param string $template Default template path.
		 *
		 * @return string
		 */
		public function maybe_use_mobile_template( $template ) {
			if ( ! $this->should_use_mobile_layout() ) {
				return $template;
			}

			$mobile_template = plugin_dir_path( __FILE__ ) . 'templates/mobile-room.php';

			if ( ! file_exists( $mobile_template ) ) {
				return $template;
			}

			$this->is_mobile_template = true;

			return $mobile_template;
		}

		/**
		 * Append a helper body class while the mobile layout is active.
		 *
		 * @param array<int, string> $classes Existing body classes.
		 *
		 * @return array<int, string>
		 */
		public function filter_body_class( $classes ) {
			if ( $this->is_mobile_template ) {
				$classes[] = 'loft1325-mobile-lofts-active';
			}

			return $classes;
		}

		/**
		 * Enqueue assets when the mobile loft template is in play.
		 */
		public function enqueue_assets() {
			if ( ! $this->should_use_mobile_layout() ) {
				return;
			}

                        wp_enqueue_style( 'dashicons' );
                        wp_enqueue_style(
                                'loft1325-mobile-lofts-fonts',
                                'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap',
                                array(),
                                null
                        );

			$style_path = plugin_dir_path( __FILE__ ) . 'assets/css/mobile-lofts.css';
			$style_uri  = plugin_dir_url( __FILE__ ) . 'assets/css/mobile-lofts.css';
			$style_ver  = file_exists( $style_path ) ? (string) filemtime( $style_path ) : '1.0.0';

			wp_enqueue_style( 'loft1325-mobile-lofts', $style_uri, array(), $style_ver );

			$script_path = plugin_dir_path( __FILE__ ) . 'assets/js/mobile-lofts.js';
			$script_uri  = plugin_dir_url( __FILE__ ) . 'assets/js/mobile-lofts.js';
			$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1.0.0';

			wp_enqueue_script( 'loft1325-mobile-lofts', $script_uri, array(), $script_ver, true );

			wp_localize_script(
				'loft1325-mobile-lofts',
				'Loft1325MobileLofts',
				array(
					'autoplayInterval' => 5500,
				)
			);
		}

		/**
		 * Get the active language (fr or en).
		 *
		 * @return string
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

			$language               = strtolower( substr( $language, 0, 2 ) );
			$this->current_language = ( 'en' === $language ) ? 'en' : 'fr';

			return $this->current_language;
		}

		/**
		 * Return a localized label.
		 *
		 * @param string $fr French label.
		 * @param string $en English label.
		 *
		 * @return string
		 */
		public function localize_label( $fr, $en ) {
			return ( 'en' === $this->get_current_language() ) ? $en : $fr;
		}

		/**
		 * Get the booking URL for the room.
		 *
		 * @param int $post_id Room post ID.
		 *
		 * @return string
		 */
		public function get_booking_url( $post_id ) {
			$base = 'https://loft1325.com/nd-booking-pages/nd-booking-page/';

			return add_query_arg(
				array(
					'room'    => get_post_field( 'post_name', $post_id ),
					'room_id' => absint( $post_id ),
				),
				$base
			);
		}

		/**
		 * Assemble key room details.
		 *
		 * @param int $post_id Room post ID.
		 *
		 * @return array<string, mixed>
		 */
		public function get_room_data( $post_id ) {
			$meta           = get_post_meta( $post_id );
			$currency       = function_exists( 'nd_booking_get_currency' ) ? nd_booking_get_currency() : get_option( 'woocommerce_currency', 'CAD' );
			$min_price      = isset( $meta['nd_booking_meta_box_min_price'][0] ) ? (string) $meta['nd_booking_meta_box_min_price'][0] : '';
			$capacity       = isset( $meta['nd_booking_meta_box_max_people'][0] ) ? (string) $meta['nd_booking_meta_box_max_people'][0] : '';
			$room_size      = isset( $meta['nd_booking_meta_box_room_size'][0] ) ? (string) $meta['nd_booking_meta_box_room_size'][0] : '';
			$min_nights     = isset( $meta['nd_booking_meta_box_min_booking_day'][0] ) ? (string) $meta['nd_booking_meta_box_min_booking_day'][0] : '';
			$branch_id      = isset( $meta['nd_booking_meta_box_branches'][0] ) ? absint( $meta['nd_booking_meta_box_branches'][0] ) : 0;
			$text_preview   = isset( $meta['nd_booking_meta_box_text_preview'][0] ) ? (string) $meta['nd_booking_meta_box_text_preview'][0] : '';
			$normal_services = isset( $meta['nd_booking_meta_box_normal_services'][0] ) ? (string) $meta['nd_booking_meta_box_normal_services'][0] : '';
			$extra_services  = isset( $meta['nd_booking_meta_box_additional_services'][0] ) ? (string) $meta['nd_booking_meta_box_additional_services'][0] : '';
			$branch_title   = $branch_id ? get_the_title( $branch_id ) : '';
			$branch_stars   = $branch_id ? (int) get_post_meta( $branch_id, 'nd_booking_meta_box_cpt_4_stars', true ) : 0;
			$hero_image     = isset( $meta['nd_booking_meta_box_image'][0] ) ? esc_url_raw( $meta['nd_booking_meta_box_image'][0] ) : '';

			$price_value = '';
			if ( '' !== $min_price ) {
				$price_value = sprintf(
					'%s %s',
					esc_html( strtoupper( $currency ) ),
					esc_html( number_format_i18n( (float) $min_price ) )
				);
			}

			return array(
				'title'           => get_the_title( $post_id ),
				'slug'            => get_post_field( 'post_name', $post_id ),
				'excerpt'         => $text_preview ? wp_strip_all_tags( $text_preview ) : wp_trim_words( get_the_excerpt( $post_id ), 28, '…' ),
				'description'     => wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ) ),
				'price'           => $price_value,
				'capacity'        => $capacity,
				'room_size'       => $room_size,
				'min_nights'      => $min_nights,
				'branch_title'    => $branch_title,
				'branch_stars'    => $branch_stars,
				'normal_services' => $this->prepare_services( $normal_services ),
				'extra_services'  => $this->prepare_services( $extra_services ),
				'hero_image'      => $hero_image,
			);
		}

		/**
		 * Normalize service values from ND Booking meta.
		 *
		 * @param string $raw Raw meta value.
		 *
		 * @return array<int, string>
		 */
		private function prepare_services( $raw ) {
			if ( '' === trim( $raw ) ) {
				return array();
			}

			$items   = array_map( 'trim', explode( ',', $raw ) );
			$items   = array_filter(
				$items,
				static function ( $item ) {
					return '' !== $item;
				}
			);
			$output  = array();

			foreach ( $items as $item ) {
				$label = $item;

				if ( is_numeric( $item ) ) {
					$title = get_the_title( (int) $item );
					$label = $title ? $title : $item;
				}

				$output[] = wp_strip_all_tags( $label );
			}

			return array_unique( $output );
		}

		/**
		 * Gather gallery images prioritizing featured, header, then attachments.
		 *
		 * @param int $post_id Room post ID.
		 *
		 * @return array<int, array<string, string>>
		 */
		public function get_room_gallery( $post_id ) {
			$images   = array();
			$featured = get_post_thumbnail_id( $post_id );
			$seen     = array();

			if ( $featured ) {
				$images[] = $this->format_image( $featured );
				$seen[]   = (string) $featured;
			}

			$hero_image = get_post_meta( $post_id, 'nd_booking_meta_box_image', true );
			if ( $hero_image ) {
				$images[] = array(
					'url' => esc_url( $hero_image ),
					'alt' => get_the_title( $post_id ),
				);
			}

			$attachments = get_attached_media( 'image', $post_id );

			foreach ( $attachments as $attachment ) {
				if ( in_array( (string) $attachment->ID, $seen, true ) ) {
					continue;
				}

				$images[] = $this->format_image( $attachment->ID );
				$seen[]   = (string) $attachment->ID;
			}

			return $images;
		}

		/**
		 * Format an attachment into a slider-friendly array.
		 *
		 * @param int $attachment_id Attachment ID.
		 *
		 * @return array<string, string>
		 */
		private function format_image( $attachment_id ) {
			$src = wp_get_attachment_image_src( $attachment_id, 'loft1325_mobile_loft_slider' );

			return array(
				'url' => $src ? $src[0] : '',
				'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);
		}
	}
}

Loft1325_Mobile_Lofts::instance();
