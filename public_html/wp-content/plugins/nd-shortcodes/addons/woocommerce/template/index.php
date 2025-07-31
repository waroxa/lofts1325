<?php


//put page on theme
add_action('nicdark_woocommerce_nd','nicdark_woocommerce');
function nicdark_woocommerce() {

    if ( is_product() ){

        $nd_options_layout_selected = dirname( __FILE__ ).'/single/index.php';    
    	include realpath($nd_options_layout_selected); 

    }else{

        $nd_options_layout_selected = dirname( __FILE__ ).'/archive/index.php';    
    	include realpath($nd_options_layout_selected); 

    }
	
}

