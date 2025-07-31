<?php

namespace MPHB\Settings;

use DateTime;

class LicenseSettings {

	/** @since 5.0.0 */
	const EXPIRATION_LIFETIME = 'lifetime';

	private $productName;
	private $productId;
	private $storeUrl;

	public function __construct() {
		$pluginData        = MPHB()->getPluginData();
		$this->storeUrl    = $pluginData['PluginURI'];
		$this->productName = MPHB()->getProductSlug();
		$this->productId   = 439190;
	}

	/**
	 *
	 * @return string
	 */
	public function getLicenseKey() {
		return get_option( 'mphb_license_key', '' );
	}

	/**
	 *
	 * @return string
	 */
	public function getStoreUrl() {
		return $this->storeUrl;
	}

	/**
	 *
	 * @return string
	 */
	public function getRenewUrl() {
		return $this->storeUrl;
	}

	/**
	 *
	 * @return string
	 */
	public function getProductName() {
		return $this->productName;
	}

	/**
	 *
	 * @return int
	 */
	public function getProductId() {
		return $this->productId;
	}

	/**
	 *
	 * @param string $licenseKey
	 */
	public function setLicenseKey( $licenseKey ) {

		$oldLicenseKey = $this->getLicenseKey();

		if ( $oldLicenseKey && $oldLicenseKey !== $licenseKey ) {
			// New license has been entered, so must reactivate
			$this->clearLicenseStatus();
		}

		if ( ! empty( $licenseKey ) ) {
			update_option( 'mphb_license_key', $licenseKey );
		} else {
			delete_option( 'mphb_license_key' );
		}
	}

	/**
	 * @since 5.0.0
	 */
	public function clearLicenseStatus() {
		delete_option( 'mphb_license_status' );
	}

	/**
	 * @since 5.0.0
	 *
	 * @see https://easydigitaldownloads.com/docs/software-licensing-api/
	 *
	 * @param array $licenseData
	 */
	public function setLicenseStatusFromData( $licenseData ) {
		if ( $licenseData->success ) {
			$licenseStatus = [
				'status'  => $licenseData->license,
				'expires' => $licenseData->expires, // 'lifetime' or date like '2020-04-28 23:59:59'
			];

			update_option( 'mphb_license_status', $licenseStatus );
		}
	}

	/**
	 * @since 5.0.0
	 *
	 * @return array [ status, expires ]
	 */
	public function getLicenseStatus() {
		$defaultStatus = [
			'status'  => 'undefined',
			'expires' => self::EXPIRATION_LIFETIME,
		];

		$licenseStatus = get_option( 'mphb_license_status', $defaultStatus );

		if ( ! is_array( $licenseStatus ) ) {
			$licenseStatus = $defaultStatus;
		}

		if ( $licenseStatus['expires'] != self::EXPIRATION_LIFETIME ) {
			if ( ! is_numeric( $licenseStatus['expires'] ) ) {
				$licenseStatus['expires'] = new DateTime( $licenseStatus['expires'] );
			} else {
				// Sometimes EED returns a timestamp instead of a date string
				// (successful "deactivate_license")
				$licenseStatus['expires'] = new DateTime(
					date( 'Y-m-d H:i:s', (int) $licenseStatus['expires'] )
				);
			}
		}

		return $licenseStatus;
	}

	/**
	 *
	 * @return bool
	 */
	public function needHideNotice() {
		return (bool) get_option( 'mphb_hide_license_notice', false );
	}

	/**
	 *
	 * @param bool $isHide
	 */
	public function setNeedHideNotice( $isHide ) {
		update_option( 'mphb_hide_license_notice', $isHide );
	}

	/**
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return (bool) apply_filters( 'mphb_use_edd_license', true );
	}

	/**
	 *
	 * @return stdClass|null
	 */
public function getLicenseData() {
    // Create a stdClass object from the global namespace
    $licenseData = new \stdClass();

    // Populate the object with properties that mimic a valid license
    $licenseData->license = 'valid'; // Indicate that the license is valid
    $licenseData->item_name = $this->getProductName();
    $licenseData->expires = 'lifetime'; // or a specific date if applicable
    // Add any other fields that your system expects in a valid license response

    return $licenseData;
}

	/**
	 * @since 5.0.0
	 */
	public function checkLicense() {
		$this->getLicenseData();
	}

}
