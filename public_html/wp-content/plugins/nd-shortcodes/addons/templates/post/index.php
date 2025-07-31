<?php

//customizer
$nd_options_layout_selected = dirname( __FILE__ ).'/customizer.php';
include realpath($nd_options_layout_selected);


//put post on theme
add_action('nicdark_single_nd','nicdark_single');
function nicdark_single() { 

    
    //get layout
    $nd_options_customizer_post_layout = get_option( 'nd_options_customizer_post_layout' );
    
    if ( $nd_options_customizer_post_layout == '' ) { 
        $nd_options_customizer_post_layout = 'layout-1';  
    }

    $nd_options_layout_selected = dirname( __FILE__ ).'/layout/'.$nd_options_customizer_post_layout.'.php';
	include realpath($nd_options_layout_selected);
		
}