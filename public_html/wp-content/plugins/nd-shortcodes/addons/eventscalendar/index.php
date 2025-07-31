<?php

$nd_options_eventscalendar_enable = get_option('nd_options_eventscalendar_enable');

// all files
if ( $nd_options_eventscalendar_enable == 1 ) { 

	$nd_options_layout_selected_1 = dirname( __FILE__ ).'/customizer/index.php';
	$nd_options_layout_selected_2 = dirname( __FILE__ ).'/metabox/index.php';
	$nd_options_layout_selected_3 = dirname( __FILE__ ).'/template/index.php';
	$nd_options_layout_selected_4 = dirname( __FILE__ ).'/vc/index.php';
  	
  	include realpath($nd_options_layout_selected_1);
  	include realpath($nd_options_layout_selected_2);
  	include realpath($nd_options_layout_selected_3);
  	include realpath($nd_options_layout_selected_4);
  	
}
