<?php

//START
$nd_booking_str .= '
<div class="nd_booking_section">';

     while ( $the_query->have_posts() ) : $the_query->the_post();

        //info
        $nd_booking_id = get_the_ID(); 
        $nd_booking_title = get_the_title();
        $nd_booking_permalink = get_permalink( $nd_booking_id );
        $nd_booking_meta_box_min_price = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_min_price', true );
        
        //image
        $nd_booking_image_id = get_post_thumbnail_id( $nd_booking_id );
        $nd_booking_image_attributes = wp_get_attachment_image_src( $nd_booking_image_id, 'large' );

        $nd_booking_str .= '
        <div id="nd_booking_ss_rooms_l2_'.$nd_booking_id.'" class="nd_booking_ss_rooms_l2 nd_booking_section nd_booking_position_relative">

            <img alt="" class="nd_booking_section" src="'.$nd_booking_image_attributes[0].'">

            <div class="nd_booking_bg_greydark_alpha_gradient_3_3 nd_booking_position_absolute nd_booking_left_0 nd_booking_height_100_percentage nd_booking_width_100_percentage nd_booking_padding_30 nd_booking_box_sizing_border_box">
                
                <div class="nd_booking_position_absolute nd_booking_bottom_30">
                    
                        <a href="'.$nd_booking_permalink.'"><h4 class="nd_options_color_white nd_booking_letter_spacing_1">'.$nd_booking_title.'</h4></a>
                        <div class="nd_booking_section nd_booking_height_10"></div>
                        <p class="nd_options_color_white nd_booking_letter_spacing_2 nd_booking_font_weight_bold nd_booking_font_size_12">'.__('FROM','nd-booking').' '.$nd_booking_meta_box_min_price.' '.nd_booking_get_currency().'</p>
                    
                </div>

            </div>

        </div>';

     endwhile;

$nd_booking_str .= '
</div>';
//END