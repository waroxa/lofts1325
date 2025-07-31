<?php

namespace MPHBR\Admin\Groups;

class LicenseSettingsGroup extends \MPHB\Admin\Groups\SettingsGroup {

	public function render() {

		parent::render();

		$license = MPHBR()->getSettings()->license()->getLicenseKey();

		if ( $license ) {
			$licenseData = MPHBR()->getSettings()->license()->getLicenseData();
		}
		?>
		<i><?php
		echo wp_kses(
			__( "The License Key is required in order to get automatic plugin updates and support. You can manage your License Key in your personal account. <a href='https://motopress.zendesk.com/hc/en-us/articles/202812996-How-to-use-your-personal-MotoPress-account' target='_blank'>Learn more</a>.", 'mphb-reviews' ),
			array(
				'a' => array(
					'href'   => array(),
					'title'  => array(),
					'target' => array(),
				),
			)
		);
		?></i>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php esc_html_e( 'License Key', 'mphb-reviews' ); ?>
					</th>
					<td>
						<input id="mphbr_edd_license_key" name="mphbr_edd_license_key" type="password"
							   class="regular-text" value="<?php echo esc_attr( $license ); ?>" autocomplete="new-password" />

						<?php if ( $license ) { ?>
							<i style="display:block;"><?php echo wp_kses_post( str_repeat( '&#8226;', 20 ) . substr( $license, -7 ) ); ?></i>
						<?php } ?>
					</td>
				</tr>
				<?php if ( isset( $licenseData, $licenseData->license ) ) { ?>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php esc_html_e( 'Status', 'mphb-reviews' ); ?>
						</th>
						<td>
							<?php
							switch ( $licenseData->license ) {
								case 'inactive':
								case 'site_inactive':
									esc_html_e( 'Inactive', 'mphb-reviews' );
									break;
								case 'valid':
									if ( $licenseData->expires !== 'lifetime' ) {
										$date    = ( $licenseData->expires ) ? new \DateTime( $licenseData->expires ) : false;
										$expires = ( $date ) ? ' ' . $date->format( 'd.m.Y' ) : '';
										echo esc_html( __( 'Valid until', 'mphb-reviews' ) . $expires );
									} else {
										esc_html_e( 'Valid (Lifetime)', 'mphb-reviews' );
									}
									break;
								case 'disabled':
									esc_html_e( 'Disabled', 'mphb-reviews' );
									break;
								case 'expired':
									esc_html_e( 'Expired', 'mphb-reviews' );
									break;
								case 'invalid':
									esc_html_e( 'Invalid', 'mphb-reviews' );
									break;
								case 'item_name_mismatch':
									echo wp_kses(
										__( "Your License Key does not match the installed plugin. <a href='https://motopress.zendesk.com/hc/en-us/articles/202957243-What-to-do-if-the-license-key-doesn-t-correspond-with-the-plugin-license' target='_blank'>How to fix this.</a>", 'mphb-reviews' ),
										array(
											'a' => array(
												'href'   => array(),
												'title'  => array(),
												'target' => array(),
											),
										)
									);
									break;
								case 'invalid_item_id':
									esc_html_e( 'Product ID is not valid', 'mphb-reviews' );
									break;
							}
							?>
						</td>
					</tr>

					<?php if ( in_array( $licenseData->license, array( 'inactive', 'site_inactive', 'valid', 'expired' ) ) ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php esc_html_e( 'Action', 'mphb-reviews' ); ?>
							</th>
							<td>
								<?php
								if ( $licenseData->license === 'inactive' || $licenseData->license === 'site_inactive' ) {
									wp_nonce_field( 'mphbr_edd_nonce', 'mphbr_edd_nonce' );
									?>
									<input type="submit" class="button-secondary" name="edd_license_activate"
										   value="<?php esc_attr_e( 'Activate License', 'mphb-reviews' ); ?>"/>

								<?php } elseif ( $licenseData->license === 'valid' ) { ?>
									<?php wp_nonce_field( 'mphbr_edd_nonce', 'mphbr_edd_nonce' ); ?>

									<input type="submit" class="button-secondary" name="edd_license_deactivate"
										   value="<?php esc_attr_e( 'Deactivate License', 'mphb-reviews' ); ?>"/>

								<?php } elseif ( $licenseData->license === 'expired' ) { ?>

									<a href="<?php echo esc_url( MPHBR()->getSettings()->license()->getRenewUrl() ); ?>"
									   class="button-secondary"
									   target="_blank">
										   <?php esc_html_e( 'Renew License', 'mphb-reviews' ); ?>
									</a>

									<?php
								}
								?>
							</td>
						</tr>
					<?php } ?>

				<?php } ?>
			</tbody>
		</table>
		<?php
	}

	public function save() {

		parent::save();

		if ( empty( $_POST ) ) {
			return;
		}

		$queryArgs = array(
			'page' => $this->getPage(),
			'tab'  => $this->getName(),
		);

		if ( isset( $_POST['mphbr_edd_license_key'] ) ) {

			$licenseKey        = trim( sanitize_text_field( wp_unslash( $_POST['mphbr_edd_license_key'] ) ) );
			$licenseKeyChanged = $licenseKey != MPHBR()->getSettings()->license()->getLicenseKey();

			if ( $licenseKeyChanged ) {
				MPHBR()->getSettings()->license()->setLicenseKey( $licenseKey );
			}
		}

		// activate
		if ( isset( $_POST['edd_license_activate'] ) ) {

			if ( ! check_admin_referer( 'mphbr_edd_nonce', 'mphbr_edd_nonce' ) ) {
				return; // get out if we didn't click the Activate button
			}
			$licenseData = self::activateLicense();

			if ( $licenseData === false ) {
				return false;
			}

			if ( ! $licenseData->success && $licenseData->error === 'item_name_mismatch' ) {
				$queryArgs['item-name-mismatch'] = 'true';
			}
		}

		// deactivate
		if ( isset( $_POST['edd_license_deactivate'] ) ) {
			// run a quick security check
			if ( ! check_admin_referer( 'mphbr_edd_nonce', 'mphbr_edd_nonce' ) ) {
				return; // get out if we didn't click the Activate button
			}
			// retrieve the license from the database
			$licenseData = self::deactivateLicense();

			if ( $licenseData === false ) {
				return false;
			}
		}
	}

	public static function activateLicense() {

		// data to send in our API request
		$apiParams = array(
			'edd_action' => 'activate_license',
			'license'    => MPHBR()->getSettings()->license()->getLicenseKey(),
			'item_id'    => MPHBR()->getSettings()->license()->getProductId(),
			'url'        => home_url(),
		);

		$activateUrl = add_query_arg( $apiParams, MPHBR()->getSettings()->license()->getStoreUrl() );

		// Call the custom API.
		$response = wp_remote_get(
			$activateUrl,
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// decode the license data
		$licenseData = json_decode( wp_remote_retrieve_body( $response ) );

		// $licenseData->license will be either "active" or "inactive"
		MPHBR()->getSettings()->license()->setLicenseStatus( $licenseData->license );

		return $licenseData;
	}

	public static function deactivateLicense() {

		// data to send in our API request
		$apiParams = array(
			'edd_action' => 'deactivate_license',
			'license'    => MPHBR()->getSettings()->license()->getLicenseKey(),
			'item_id'    => MPHBR()->getSettings()->license()->getProductId(),
			'url'        => home_url(),
		);

		$deactivateUrl = add_query_arg( $apiParams, MPHBR()->getSettings()->license()->getStoreUrl() );

		// Call the custom API.
		$response = wp_remote_get(
			$deactivateUrl,
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// decode the license data
		$licenseData = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if ( $licenseData->license == 'deactivated' ) {
			MPHBR()->getSettings()->license()->setLicenseStatus( '' );
		}

		return $licenseData;
	}
}
