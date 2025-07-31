<?php

namespace MPHBR;

class Dependencies {

	const MPHB_VERSION = '3.0.3';

	public function __construct() {
		
		add_action( 'admin_notices', array( $this, 'checkAndShowNotice' ) );
	}

	/**
	 * Check plugin dependencies. Don't use before plugins_loaded action
	 * @return boolean
	 */
	public function check() {

		return $this->checkMPHB();
	}

	private function isMPHBActive() {

		return mphbr_is_plugin_active( 'motopress-hotel-booking/motopress-hotel-booking.php' )
			|| mphbr_is_plugin_active( 'motopress-hotel-booking-lite/motopress-hotel-booking.php' );
	}

	private function isMPHBCorrectVersion() {

		if ( ! function_exists( 'MPHB' ) ) {
			return false;
		}

		$mphb = MPHB();

		return method_exists( $mphb, 'getVersion' ) &&
			version_compare( $mphb->getVersion(), self::MPHB_VERSION, '>=' );
	}

	private function checkMPHB() {

		return $this->isMPHBActive() && $this->isMPHBCorrectVersion();
	}

	function checkAndShowNotice() {

		if ( ! $this->checkMPHB() ) {
			echo '<div class="error"><p>' . esc_html( sprintf( __( 'Hotel Booking Reviews plugin requires activated Hotel Booking plugin version %s or higher.', 'mphb-reviews' ), self::MPHB_VERSION ) ) . '</p></div>';
		}
	}
}
