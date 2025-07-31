<?php

//customizer
$nd_options_layout_selected = dirname( __FILE__ ).'/customizer.php';
include realpath($nd_options_layout_selected);

//put post on theme
add_action('nicdark_comments_nd','nicdark_comments');
function nicdark_comments() { 


    //get layout
    $nd_options_customizer_comments_layout = get_option( 'nd_options_customizer_comments_layout' );
    
    if ( $nd_options_customizer_comments_layout == '' ) { 
        $nd_options_customizer_comments_layout = 'layout-1';  
    }

    $nd_options_layout_selected = dirname( __FILE__ ).'/layout/'.$nd_options_customizer_comments_layout.'.php';
	include realpath($nd_options_layout_selected);
	
}
//end function

