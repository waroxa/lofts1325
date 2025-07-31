<?php

//shortcode nd_social
function nd_booking_ss_rooms( $nd_booking_atts ) {
    
    $nd_booking_ss_rooms = shortcode_atts( 
    	array(
            'layout' => '',
	        'qnt' => '',
	        'order' => '',
	        'orderby' => '',
	        'id' => '',
    	), 
    $nd_booking_atts );

    //start
    $nd_booking_str = '';

    //default values
    if ( $nd_booking_ss_rooms['layout'] == '' ) { $nd_booking_ss_rooms_layout = 'layout-1'; }else { $nd_booking_ss_rooms_layout = 'layout-'.$nd_booking_ss_rooms['layout']; }
    if ( $nd_booking_ss_rooms['qnt'] == '' ) { $nd_booking_ss_rooms_qnt = -1; }else { $nd_booking_ss_rooms_qnt = $nd_booking_ss_rooms['qnt']; }
    if ( $nd_booking_ss_rooms['order'] == '' ) { $nd_booking_ss_rooms_order = 'ASC'; }else { $nd_booking_ss_rooms_order = $nd_booking_ss_rooms['order']; }
    if ( $nd_booking_ss_rooms['orderby'] == '' ) { $nd_booking_ss_rooms_orderby = 'title'; }else { $nd_booking_ss_rooms_orderby = $nd_booking_ss_rooms['orderby']; }
    if ( $nd_booking_ss_rooms['id'] == '' ) { $nd_booking_ss_rooms_id = ''; }else { $nd_booking_ss_rooms_id = $nd_booking_ss_rooms['id']; }

    //args
    $args = array(
      'post_type' => 'nd_booking_cpt_1',
      'posts_per_page' => $nd_booking_ss_rooms_qnt,
      'order' => $nd_booking_ss_rooms_order,
      'orderby' => $nd_booking_ss_rooms_orderby,
      'p' => $nd_booking_ss_rooms_id,
    );
    $the_query = new WP_Query( $args );

    //get the layout selected
    $nd_booking_layout_selected = dirname( __FILE__ ).'/layout/'.$nd_booking_ss_rooms_layout.'.php';
    include realpath($nd_booking_layout_selected);

	wp_reset_postdata();

    return $nd_booking_str;
}
add_shortcode( 'nd_booking_ss_rooms', 'nd_booking_ss_rooms' );
