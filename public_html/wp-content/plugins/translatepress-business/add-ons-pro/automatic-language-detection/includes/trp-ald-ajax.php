<?php

/**
 * Class TRP_IN_ALD_Ajax
 *
 * Custom Ajax to get needed language based on IP or browser
 */
class TRP_IN_ALD_Ajax{

    /**
     * TRP_IN_ALD_Ajax constructor. Echos the preferred language.
     *
     */
    public function __construct(){

        if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'trp_ald_get_needed_language' || empty( $_POST['detection_method'] ) || empty( $_POST['publish_languages'] ) || empty( $_POST['iso_codes'] ) ) {
            die();
        }
        require_once( __DIR__ . '/class-determine-language.php' );
	    $trp_determine_language = new TRP_IN_ALD_Determine_Language();

	    // sanitize input
	    $detection_method = $this->sanitize_string( $_POST['detection_method'] );//phpcs:ignore
	    $published_languages = $this->sanitize_array( $_POST['publish_languages'] );//phpcs:ignore
	    $iso_codes = $this->sanitize_array( $_POST['iso_codes'] );//phpcs:ignore

	    $needed_language = $trp_determine_language->get_needed_language( $published_languages, $iso_codes, $detection_method );
	    echo json_encode( $needed_language );
    }

    public function sanitize_string( $string ){
	    return filter_var( $string, FILTER_SANITIZE_STRING );
    }

    public function sanitize_array( $array ){
    	$return = array();
	    if( is_array( $array ) ){
		    foreach ( $array as $key => $value ){
			    $return[$key] = $value;
		    }
	    }
	    return $return;
    }
}



/**
 * Mock-up function to prevent calls to apply_filters() from failing when WP is not loaded
 *
 * @param $function_name
 * @param $parameter
 *
 * @return mixed
 */
function apply_filters( $function_name, $parameter ){
	return $parameter;
}

new TRP_IN_ALD_Ajax;

die();

