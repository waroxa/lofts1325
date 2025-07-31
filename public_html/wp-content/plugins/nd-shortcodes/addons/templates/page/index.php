<?php

//customizer
$nd_options_layout_selected = dirname( __FILE__ ).'/customizer.php';
include realpath($nd_options_layout_selected);

//get layout
$nd_options_customizer_page_layout = get_option( 'nd_options_customizer_page_layout' );
if ( $nd_options_customizer_page_layout == '' ) { 
	$nd_options_customizer_page_layout = 'layout-1';  
}

// layout sidebar
$nd_options_layout_selected = dirname( __FILE__ ).'/layout/sidebar/'.$nd_options_customizer_page_layout.'.php';
include realpath($nd_options_layout_selected);


//put page on theme
add_action('nicdark_page_nd','nicdark_page');
function nicdark_page() { 
    

	//get layout
	$nd_options_customizer_page_layout = get_option( 'nd_options_customizer_page_layout' );
	
	if ( $nd_options_customizer_page_layout == '' ) { 
		$nd_options_customizer_page_layout = 'layout-1';  
	}

	$nd_options_layout_selected = dirname( __FILE__ ).'/layout/'.$nd_options_customizer_page_layout.'.php';
	include realpath($nd_options_layout_selected);

}

