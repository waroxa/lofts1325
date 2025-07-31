<?php

$nd_booking_elementor_enable = get_option('nd_booking_elementor_enable');
if ( $nd_booking_elementor_enable == 1 and get_option('nicdark_theme_author') == 1 ) {

	//add all widgets
	final class nd_booking_Elementor_Extension {

		const VERSION = '1.0.0';
		const MINIMUM_ELEMENTOR_VERSION = '2.0.0';
		const MINIMUM_PHP_VERSION = '7.0';
		private static $_instance = null;

		public static function instance() {
			if ( is_null( self::$_instance ) ) { self::$_instance = new self(); }
			return self::$_instance;
		}

		public function __construct() {
			add_action( 'init', [ $this, 'i18n' ] );
			add_action( 'plugins_loaded', [ $this, 'init' ] );
		}

  		public function i18n() { load_plugin_textdomain( 'nd-booking' );  }

		public function init() {
			// Check if Elementor installed and activated
			if ( ! did_action( 'elementor/loaded' ) ) { add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] ); return; }
			// Check for required Elementor version
			if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) { add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );return; }
			// Check for required PHP version
			if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );return;}
			// Add Plugin actions
			add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
		}

		public function admin_notice_missing_main_plugin() {
			if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
			$message = sprintf(
				esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'nd-booking' ),
				'<strong>' . esc_html__( 'Elementor ND Booking Extension', 'nd-booking' ) . '</strong>',
				'<strong>' . esc_html__( 'Elementor', 'nd-booking' ) . '</strong>'
			);
			printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
		}

		public function admin_notice_minimum_elementor_version() {
			if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
			$message = sprintf(
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'nd-booking' ),
			'<strong>' . esc_html__( 'Elementor ND Booking Extension', 'nd-booking' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'nd-booking' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
			);
			printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
		}

		public function admin_notice_minimum_php_version() {
			if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
			$message = sprintf(
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'nd-booking' ),
			'<strong>' . esc_html__( 'Elementor ND Booking Extension', 'nd-booking' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'nd-booking' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
			);
			printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
		}

		/*ALL WIDGETS*/
		public function init_widgets() {

			require_once( __DIR__ . '/rooms/index.php' );
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \nd_booking_rooms_element() );

			require_once( __DIR__ . '/search/index.php' );
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \nd_booking_search_element() );

			require_once( __DIR__ . '/steps/index.php' );
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \nd_booking_steps_element() );

			require_once( __DIR__ . '/order/index.php' );
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \nd_booking_order_element() );
		
		}

	}
	nd_booking_Elementor_Extension::instance();


	//add elementor category
	function nd_booking_add_elementor_widget_categories( $elements_manager ) {

	  $elements_manager->add_category(
	    'nd-booking',
	    [
	      'title' => __( 'ND Booking', 'nd-booking' ),
	      'icon' => 'fa fa-plug',
	    ]
	  );

	}
	add_action( 'elementor/elements/categories_registered', 'nd_booking_add_elementor_widget_categories' );


}