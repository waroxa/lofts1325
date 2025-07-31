<?php


//put page on theme
add_action('nicdark_archive_nd','nicdark_archive_content');
function nicdark_archive_content() {


	//get layout
	$nd_options_customizer_archives_archive_layout = get_option( 'nd_options_customizer_archives_archive_layout' );
	
	if ( $nd_options_customizer_archives_archive_layout == '' ) { 
		$nd_options_customizer_archives_archive_layout = 'layout-1';  
	}

	$nd_options_layout_selected = dirname( __FILE__ ).'/layout/'.$nd_options_customizer_archives_archive_layout.'.php';
  	include realpath($nd_options_layout_selected);
	
}

