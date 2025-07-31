<?php

$nd_options_give_enable = get_option('nd_options_give_enable');

// all files
if ( $nd_options_give_enable == 1 ) { 

	$nd_options_layout_selected = dirname( __FILE__ ).'/customizer/index.php';
  	include realpath($nd_options_layout_selected);

}
