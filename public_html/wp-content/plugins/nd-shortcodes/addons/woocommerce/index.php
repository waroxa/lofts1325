<?php

$nd_options_woocommerce_enable = get_option('nd_options_woocommerce_enable');

// all files
if ( $nd_options_woocommerce_enable == 1 ) { 

	$nd_options_layout_selected_1 = dirname( __FILE__ ).'/template/index.php';
	$nd_options_layout_selected_2 = dirname( __FILE__ ).'/metabox/index.php';
	$nd_options_layout_selected_3 = dirname( __FILE__ ).'/customizer/index.php';
	$nd_options_layout_selected_4 = dirname( __FILE__ ).'/vc/index.php';
	
	include realpath($nd_options_layout_selected_1);
	include realpath($nd_options_layout_selected_2);
	include realpath($nd_options_layout_selected_3);
	include realpath($nd_options_layout_selected_4);

	// Sidebar
	function nd_options_woocommerce_sidebars() {

	    // Sidebar Main
	    register_sidebar(array(
	        'name' =>  esc_html__('WooCommerce Sidebar','nd-shortcodes'),
	        'id' => 'nd_options_woocommerce_sidebar',
	        'before_widget' => '<div id="%1$s" class="widget %2$s">',
	        'after_widget' => '</div>',
	        'before_title' => '<h3>',
	        'after_title' => '</h3>',
	    ));

	}
	add_action( 'widgets_init', 'nd_options_woocommerce_sidebars' );

}
