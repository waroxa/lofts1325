<?php

namespace MPHBR;

class AutoUpdater {

	public function __construct(){
		add_action( 'admin_init', array( $this, 'initAutoUpdater' ), 9 );
	}

	public function initAutoUpdater(){

		if ( MPHBR()->getSettings()->license()->isEnabled() ) {

			$pluginData = MPHBR()->getPluginData();

			$apiData = array(
				'version'	 => $pluginData->getVersion(),
				'license'	 => MPHBR()->getSettings()->license()->getLicenseKey(),
				'item_id'	 => MPHBR()->getSettings()->license()->getProductId(),
				'author'	 => $pluginData->getAuthor()
			);

			new Libraries\EDD_Plugin_Updater\EDD_Plugin_Updater( MPHBR()->getSettings()->license()->getStoreUrl(), $pluginData->getPluginFile(), $apiData );
		}
	}

}
