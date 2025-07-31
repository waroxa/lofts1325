<?php


//image
$nd_booking_image_id = get_post_thumbnail_id( $nd_booking_id );
$nd_booking_image_attributes = wp_get_attachment_image_src( $nd_booking_image_id, $roomsgrid_image_size );

if ( $nd_booking_image_attributes[0] == '' ) { $nd_booking_output_image = ''; }else{
  
  $nd_booking_output_image = '

	<div class="nd_booking_section nd_booking_position_relative">

		<a class="nd_booking_position_absolute nd_booking_height_100_percentage nd_booking_width_100_percentage nd_booking_cursor_pointer nd_booking_top_0 nd_booking_left_0 nd_booking_z_index_9" href="'.$nd_booking_permalink.'"></a>

	    <img alt="" class="nd_booking_section nd_booking_postgrid_rooms_single_room_img" src="'.$nd_booking_image_attributes[0].'">

	    <div class="nd_booking_bg_greydark_alpha_gradient_3 nd_booking_position_absolute nd_booking_left_0 nd_booking_height_100_percentage nd_booking_width_100_percentage nd_booking_padding_30 nd_booking_box_sizing_border_box">
	        
	        <div class="nd_booking_position_absolute nd_booking_top_20 nd_booking_left_0 nd_booking_width_100_percentage">
	            <div class="nd_booking_section nd_booking_text_align_right">
	                <a style="background-color: '.$nd_booking_meta_box_color.';" href="'.$nd_booking_permalink.'" class=" nd_booking_e_rooms_postgrid_l1_price nd_booking_padding_10_20 nd_booking_text_transform_uppercase nd_options_second_font_important nd_booking_border_radius_0_important nd_options_color_white nd_booking_cursor_pointer nd_booking_display_inline_block nd_booking_font_size_12 nd_booking_letter_spacing_4">'.__('FROM','nd-booking').' '.$nd_booking_meta_box_min_price.' '.nd_booking_get_currency().'</a>
	            </div>
	        </div>

	        <div class="nd_booking_position_absolute nd_booking_bottom_0 nd_booking_padding_20_30 nd_booking_left_0 nd_booking_width_100_percentage">

	            <div class="nd_booking_section">

	            	<h3 class="nd_options_color_white nd_booking_letter_spacing_1 nd_booking_e_rooms_postgrid_l1_title">'.$nd_booking_title.'</h3>

	            	<div class="nd_booking_section nd_booking_height_10 nd_booking_display_none_iphone_port"></div>

	                <div class="nd_booking_display_table nd_booking_display_none_iphone_port">
	                    <img alt="" class="nd_booking_margin_right_10 nd_booking_display_table_cell nd_booking_vertical_align_middle" width="23" src="'.esc_url(plugins_url('../img/icon-guests.png', __FILE__ )).'">
	                    <p class=" nd_booking_letter_spacing_2 nd_options_color_white nd_booking_display_table_cell nd_booking_vertical_align_middle nd_booking_font_size_12 nd_booking_line_height_26 nd_booking_text_transform_uppercase nd_booking_e_rooms_postgrid_l1_guests">'.$nd_booking_meta_box_max_people.' '.__('Guests','nd-booking').'</p>
	                    <img alt="" class="nd_booking_margin_right_10 nd_booking_margin_left_20 nd_booking_display_table_cell nd_booking_vertical_align_middle" width="20" src="'.esc_url(plugins_url('../img/icon-size.png', __FILE__ )).'">
	                    <p class=" nd_booking_letter_spacing_2 nd_options_color_white nd_booking_display_table_cell nd_booking_vertical_align_middle nd_booking_font_size_12 nd_booking_line_height_26 nd_booking_text_transform_uppercase nd_booking_e_rooms_postgrid_l1_size">'.$nd_booking_meta_box_room_size.' '.nd_booking_get_units_of_measure().'</p>
	                </div>
	            </div> 

	        </div>
	    </div>

	</div>




    ';

}
//end image


/*START preview*/
$nd_booking_result .= '
  <div class=" '.$rooms_width.' nd_booking_rooms_widget_l1 nd_booking_width_100_percentage_responsive nd_booking_float_left nd_booking_masonry_item nd_booking_padding_15 nd_booking_padding_15_0_all_iphone nd_booking_box_sizing_border_box">

    <div class="nd_booking_section nd_booking_background_color_fff nd_booking_box_shadow_0_0_15_0_0001">

      '.$nd_booking_output_image.'

  </div>
    
</div>';
/*END preview*/ 