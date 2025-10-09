<?php

//default
$nd_booking_title = get_the_title();
$nd_booking_content = do_shortcode(get_the_content());
$nd_booking_id = get_the_ID();
$nd_booking_permalink = get_permalink( $nd_booking_id );

//ids
$nd_booking_id_room = get_post_meta( get_the_ID(), 'nd_booking_id_room', true );
if ( $nd_booking_id_room == '' ) { $nd_booking_id_room = $nd_booking_id; }else{ $nd_booking_id_room = $nd_booking_id_room; }

//metabox
$nd_booking_meta_box_min_price = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_min_price', true );
$nd_booking_meta_box_color = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_color', true ); if ($nd_booking_meta_box_color == '') { $nd_booking_meta_box_color = '#000'; }
$nd_booking_meta_box_max_people = get_post_meta( get_the_ID(), 'nd_booking_meta_box_max_people', true );
$nd_booking_meta_box_room_size = get_post_meta( get_the_ID(), 'nd_booking_meta_box_room_size', true );
$nd_booking_meta_box_text_preview = get_post_meta( get_the_ID(), 'nd_booking_meta_box_text_preview', true );
$nd_booking_meta_box_branches = get_post_meta( get_the_ID(), 'nd_booking_meta_box_branches', true );
$nd_booking_meta_box_cpt_4_stars = get_post_meta( $nd_booking_meta_box_branches, 'nd_booking_meta_box_cpt_4_stars', true );
$nd_booking_rooms_left_b = "";

$loft_branch_title = '';
if ( ! empty( $nd_booking_meta_box_branches ) ) {
    $loft_branch_title = get_the_title( $nd_booking_meta_box_branches );
}

$loft_star_count = intval( $nd_booking_meta_box_cpt_4_stars );
$loft_star_icon_markup = '';
if ( $loft_star_count > 0 ) {
    $loft_star_icon_url = esc_url( plugins_url( 'icon-star-full-white.svg', __FILE__ ) );

    for ( $loft_star_index = 0; $loft_star_index < $loft_star_count; $loft_star_index++ ) {
        $loft_star_icon_markup .= '<img alt="" class="loft-search-card__star" width="12" src="' . $loft_star_icon_url . '">';
    }
}

$loft_room_title   = esc_html( $nd_booking_title );
$loft_room_excerpt = wp_kses_post( $nd_booking_meta_box_text_preview );

//woo
$nd_booking_meta_box_room_woo_product = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_room_woo_product', true );
if ( $nd_booking_meta_box_room_woo_product == '' ){ $nd_booking_meta_box_room_woo_product = 0; }


if ( nd_booking_is_available_block($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to) == 0 ) {
    
    $nd_booking_availability = "<span class='nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_yellow'>".__('NOT AVAILABLE','nd-booking')."</span>";

}else{

    //available or not
    if ( nd_booking_is_qnt_available(nd_booking_is_available($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to),$nd_booking_date_from,$nd_booking_date_to,$nd_booking_id_room) == 1 ) {

        //check the options min booking days
        $nd_booking_meta_box_min_booking_day = get_post_meta( $nd_booking_id_room, 'nd_booking_meta_box_min_booking_day', true ); 
        if ( $nd_booking_meta_box_min_booking_day == '' ) { $nd_booking_meta_box_min_booking_day = 1; }
        if ( nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to) >= $nd_booking_meta_box_min_booking_day ) {
            
            $nd_booking_availability = "";

            //room sleft bokable
            $nd_booking_rooms_left_b = nd_booking_qnt_room_bookable(nd_booking_is_available($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to),$nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to); 

        }else{

            $nd_booking_availability = "<span class='nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_greydark'>".__('MINIMUM BOOKING DAYS','nd-booking')." : ".$nd_booking_meta_box_min_booking_day."</span>";

        }


    }else{
        $nd_booking_availability = "<span class='nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_yellow'>".__('NOT AVAILABLE','nd-booking')."</span>";
    }

}


//image
if ( has_post_thumbnail() ) {

    $loft_room_image_src = esc_url( nd_booking_get_post_img_src( get_the_ID() ) );

    $loft_media_overlay = '';
    if ( $loft_branch_title !== '' || $loft_star_icon_markup !== '' ) {
        $loft_media_overlay .= '<div class="loft-search-card__media-overlay">';

        if ( $loft_branch_title !== '' ) {
            $loft_media_overlay .= '<span class="loft-search-card__badge">' . esc_html( $loft_branch_title ) . '</span>';
        }

        if ( $loft_star_icon_markup !== '' ) {
            $loft_media_overlay .= '<span class="loft-search-card__stars">' . $loft_star_icon_markup . '</span>';
        }

        $loft_media_overlay .= '</div>';
    }

    $nd_booking_image = '

        <div class="nd_booking_section nd_booking_position_relative loft-search-card__media">

            '.$nd_booking_availability.'

            '.$nd_booking_rooms_left_b.'

            <img alt="" class="nd_booking_section loft-search-card__media-img" src="'.$loft_room_image_src.'">

            '.$loft_media_overlay.'

        </div>


    ';
}else{
    $nd_booking_image = '';
}


$nd_booking_shortcode_right_content .= '



<div id="nd_booking_archive_cpt_1_single_'.$nd_booking_id.'" class="nd_booking_masonry_item nd_booking_width_100_percentage nd_booking_width_100_percentage_responsive loft-search-card__item">

    <div class="nd_booking_section nd_booking_padding_15 nd_booking_box_sizing_border_box loft-search-card__outer">

        <div class="nd_booking_section nd_booking_border_1_solid_grey nd_booking_bg_white loft-search-card">

            '.$nd_booking_image.'

            <div class="nd_booking_section nd_booking_padding_30 nd_booking_box_sizing_border_box loft-search-card__content">';

                if ( $nd_booking_meta_box_room_woo_product != 0 ){
                    $nd_booking_r_permalink = $nd_booking_permalink;
                }else{
                    $nd_booking_r_permalink = nd_booking_get_room_link($nd_booking_id,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_archive_form_guests);
                }

                $nd_booking_shortcode_right_content .= '
                <a class="loft-search-card__title-link" href="'.$nd_booking_r_permalink.'"><h2 class="loft-search-card__title">'.$loft_room_title.'</h2></a>

                <div class="nd_booking_section loft-search-card__meta">
                    <div class="nd_booking_display_table loft-search-card__feature-list">
                        <img alt="" class="loft-search-card__feature-icon" width="23" src="'.esc_url(plugins_url('icon-user-grey.svg', __FILE__ )).'">
                        <p class="loft-search-card__feature-text nd_booking_display_table_cell nd_booking_vertical_align_middle">'.$nd_booking_meta_box_max_people.' '.__('GUESTS','nd-booking').'</p>
                        <img alt="" class="loft-search-card__feature-icon" width="20" src="'.esc_url(plugins_url('icon-plan-grey.svg', __FILE__ )).'">
                        <p class="loft-search-card__feature-text nd_booking_display_table_cell nd_booking_vertical_align_middle">'.$nd_booking_meta_box_room_size.' '.nd_booking_get_units_of_measure().'</p>
                    </div>
                </div>

                <div class="loft-search-card__excerpt">'.$loft_room_excerpt.'</div>';


                $loft_has_cta = false;

                if ( nd_booking_is_available_block($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to) == 1 ) {

                    if ( nd_booking_is_qnt_available(nd_booking_is_available($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to),$nd_booking_date_from,$nd_booking_date_to,$nd_booking_id_room) == 1 ) {

                        //check the options min booking days
                        $nd_booking_meta_box_min_booking_day = get_post_meta( $nd_booking_id_room, 'nd_booking_meta_box_min_booking_day', true );
                        if ( $nd_booking_meta_box_min_booking_day == '' ) { $nd_booking_meta_box_min_booking_day = 1; }
                        if ( nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to) >= $nd_booking_meta_box_min_booking_day ) {

                            $nd_booking_trip_price = 0;
                            $nd_booking_index = 1;
                            $nd_booking_date_cicle = $nd_booking_date_from;
                            while ($nd_booking_index <= nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to)) {

                                $nd_booking_trip_price = $nd_booking_trip_price + nd_booking_get_final_price($nd_booking_id,$nd_booking_date_cicle);

                                $nd_booking_date_cicle = date('Y/m/d', strtotime($nd_booking_date_cicle.' + 1 days'));

                                $nd_booking_index++;
                            } 

                            //ADJUST TRIP PRICE based on the price per guest settings
                                    if ( get_option('nd_booking_price_guests') == 1 ) {
                                        $nd_booking_trip_price = $nd_booking_trip_price * $nd_booking_archive_form_guests;
                                    }

                            $loft_price_decimals       = ( floor( $nd_booking_trip_price ) == $nd_booking_trip_price ) ? 0 : 2;
                            $loft_total_price_number   = number_format_i18n( $nd_booking_trip_price, $loft_price_decimals );
                            $loft_currency_code        = nd_booking_get_currency();
                            $loft_total_price_display  = esc_html( sprintf( __( '%1$s %2$s', 'marina-child' ), $loft_total_price_number, $loft_currency_code ) );
                            $loft_total_nights         = nd_booking_get_number_night( $nd_booking_date_from, $nd_booking_date_to );

                            if ( $loft_total_nights <= 0 ) {
                                $loft_total_nights = 1;
                            }

                            $loft_nightly_rate       = $nd_booking_trip_price / $loft_total_nights;
                            $loft_nightly_decimals   = ( floor( $loft_nightly_rate ) == $loft_nightly_rate ) ? 0 : 2;
                            $loft_nightly_rate_number = number_format_i18n( $loft_nightly_rate, $loft_nightly_decimals );
                            $loft_night_label        = _n( 'nuit', 'nuits', $loft_total_nights, 'marina-child' );
                            $loft_total_stay_label   = esc_html( sprintf( __( 'Séjour de %1$d %2$s', 'marina-child' ), $loft_total_nights, $loft_night_label ) );
                            $loft_nightly_label      = esc_html( sprintf( __( '%1$s %2$s par nuit', 'marina-child' ), $loft_nightly_rate_number, $loft_currency_code ) );
                            $loft_button_label       = esc_html( sprintf( __( 'RÉSERVEZ MAINTENANT • %1$s %2$s', 'marina-child' ), $loft_total_price_number, $loft_currency_code ) );

                            $nd_booking_shortcode_right_content .= '
                            <div class="loft-search-card__rate">
                                <p class="loft-search-card__rate-label">'.esc_html__( 'Tarif total', 'marina-child' ).'</p>
                                <p class="loft-search-card__rate-amount">'.$loft_total_price_display.'</p>
                                <p class="loft-search-card__rate-sub">'.$loft_total_stay_label.'</p>
                                <p class="loft-search-card__rate-sub">'.$loft_nightly_label.'</p>
                            </div>
                            <div class="loft-search-card__actions">';

                            $loft_has_cta = true;

                            //start if is linked to woo
                            $nd_booking_insub_woo_class = '';
                            if ( $nd_booking_meta_box_room_woo_product != 0 ) {
                                $nd_booking_shortcode_right_content .= '

                                    <button type="button" onclick="nd_booking_woo('.$nd_booking_trip_price.','.$nd_booking_id.')" class="loft-search-card__btn loft-search-card__btn--primary">'.$loft_button_label.'</button>';
                                $nd_booking_insub_woo_class = 'nd_booking_display_none_important';

                            }
                            //end if is linked to woo


                            $nd_booking_shortcode_right_content .= '
                            <form class="loft-search-card__form" id="nd_booking_book_room_'.$nd_booking_id.'" method="post" action="';

                            if ( nd_booking_get_room_link($nd_booking_id,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_archive_form_guests) == $nd_booking_permalink ) {
                                $nd_booking_shortcode_right_content .= nd_booking_booking_page();
                            }else{
                                $nd_booking_shortcode_right_content .= nd_booking_get_room_link($nd_booking_id,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_archive_form_guests);
                            }

                            $nd_booking_shortcode_right_content .= '">

                                <input type="hidden" name="nd_booking_form_booking_id" value="'.$nd_booking_id.'-'.$nd_booking_id_room.'">
                                <input type="hidden" name="nd_booking_form_booking_date_from" value="'.$nd_booking_date_from.'">
                                <input type="hidden" name="nd_booking_form_booking_date_to" value="'.$nd_booking_date_to.'">
                                <input type="hidden" name="nd_booking_form_booking_guests" value="'.$nd_booking_archive_form_guests.'">
                                <input type="hidden" name="nd_booking_form_booking_arrive_advs" value="1">

                                <input class="loft-search-card__btn '.$nd_booking_insub_woo_class.'" type="submit" value="'.$loft_button_label.'">';

                            $nd_booking_shortcode_right_content .= ' 
                            </form>';

                            include realpath(dirname( __FILE__ ).'/nd_booking_info_price_hover_btn.php');

                            $nd_booking_shortcode_right_content .= '
                            </div>';

                        }

                    }

                }

                if ( ! $loft_has_cta ) {
                    $nd_booking_shortcode_right_content .= '
                    <p class="loft-search-card__unavailable">'.esc_html__( 'Indisponible pour ces dates sélectionnées.', 'marina-child' ).'</p>';
                }

                
                //SERVICES explode the string
                $nd_booking_meta_box_normal_services_array = explode(',', get_post_meta( $nd_booking_id, 'nd_booking_meta_box_normal_services', true ) );

                if ( get_post_meta( $nd_booking_id, 'nd_booking_meta_box_normal_services', true ) != '' ) {


                    $nd_booking_shortcode_right_content .= '
                    <div class="nd_booking_section nd_booking_height_20"></div> 
                    <div class="nd_booking_section nd_booking_height_1 nd_booking_border_bottom_1_solid_grey"></div> 
                    <div class="nd_booking_section nd_booking_height_20"></div>';


                    //START CICLE
                    for ($nd_booking_meta_box_normal_services_array_i = 0; $nd_booking_meta_box_normal_services_array_i < count($nd_booking_meta_box_normal_services_array)-1; $nd_booking_meta_box_normal_services_array_i++) {
                        
                        $nd_booking_page_by_path = get_page_by_path($nd_booking_meta_box_normal_services_array[$nd_booking_meta_box_normal_services_array_i],OBJECT,'nd_booking_cpt_2');
                        
                        //info service
                        $nd_booking_service_id = $nd_booking_page_by_path->ID;
                        $nd_booking_service_name = get_the_title($nd_booking_service_id);

                        //metabox
                        $nd_booking_meta_box_cpt_2_icon = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_icon', true );

                        $nd_booking_shortcode_right_content .= '
                             <a title="'.$nd_booking_service_name.'" class="nd_booking_tooltip_jquery nd_booking_float_left"><img alt="'.$nd_booking_service_name.'" class="nd_booking_margin_right_15 nd_booking_float_left" width="23" height="23" src="'.$nd_booking_meta_box_cpt_2_icon.'"></a>
                        ';

                    }
                    //END CICLE


                    $nd_booking_shortcode_right_content .= '
                    <a href="'.$nd_booking_r_permalink.'" class="nd_booking_margin_top_7 nd_booking_margin_top_20_all_iphone nd_booking_width_100_percentage_all_iphone nd_booking_float_right nd_booking_float_left_all_iphone nd_booking_display_inline_block nd_booking_text_align_center nd_booking_box_sizing_border_box nd_booking_font_size_12">
                        <span class="nd_booking_float_left nd_booking_font_size_11 nd_booking_letter_spacing_2">'.__('FULL INFO','nd-booking').'</span>
                        <img alt="" class="nd_booking_margin_left_5 nd_booking_float_left" width="10" src="'.esc_url(plugins_url('icon-right-arrow-grey.svg', __FILE__ )).'">
                    </a>';

                }

                


                

            $nd_booking_shortcode_right_content .= '
            </div>
        </div>

    </div>

</div>';