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
        $nd_booking_meta_box_color = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_color', true ); if ($nd_booking_meta_box_color == '') { $nd_booking_meta_box_color = '#000'; }
        
        //image
        $nd_booking_image_id = get_post_thumbnail_id( $nd_booking_id );
        $nd_booking_image_attributes = wp_get_attachment_image_src( $nd_booking_image_id, 'thumbnail' );

        $nd_booking_str .= '
        <div id="nd_booking_ss_rooms_'.$nd_booking_id.'" class="nd_booking_ss_rooms nd_booking_width_100_percentage">

            <div style="padding:0px 0px 20px 0px;" class="nd_booking_section nd_booking_box_sizing_border_box">

                <div class="nd_booking_section nd_booking_position_relative">

                    <img alt="" class="nd_booking_position_absolute nd_booking_left_0 nd_booking_top_0" width="100" src="'.$nd_booking_image_attributes[0].'">

                    <div class="nd_booking_section nd_booking_padding_left_120 nd_booking_min_height_100 nd_booking_box_sizing_border_box">

                        <h4>'.$nd_booking_title.'</h4>
                        <div class="nd_booking_section nd_booking_height_10"></div>
                        <p class="">'.__('From','nd-booking').' '.$nd_booking_meta_box_min_price.' '.nd_booking_get_currency().' '.__('per night','nd-booking').'</p>
                        <div class="nd_booking_section nd_booking_height_10"></div>
                        <div class="nd_booking_section">
                            <a href="'.$nd_booking_permalink.'" style="background-color: '.$nd_booking_meta_box_color.';" class="nd_options_color_white nd_booking_padding_5_10 nd_booking_font_size_10 nd_booking_letter_spacing_2 nd_booking_font_weight_bold">'.__('BOOK NOW','nd-booking').'</a>
                        </div>

                    </div>
                </div>

            </div>

        </div>';


     endwhile;

$nd_booking_str .= '
</div>';
//END