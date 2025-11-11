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


$loft_pricing_details = array();
if ( isset( $nd_booking_pricing_cache ) && isset( $nd_booking_pricing_cache[ $nd_booking_id ] ) ) {
    $loft_pricing_details = $nd_booking_pricing_cache[ $nd_booking_id ];
} else {
    $loft_pricing_details = nd_booking_calculate_search_card_pricing( $nd_booking_id, $nd_booking_id_room, $nd_booking_date_from, $nd_booking_date_to, $nd_booking_archive_form_guests );
}

$price = isset( $price ) ? (float) $price : 0.0;

$nd_booking_is_best_value_card = isset( $nd_booking_is_best_value_card ) ? (bool) $nd_booking_is_best_value_card : false;
$nd_booking_best_value_post_id = isset( $nd_booking_best_value_post_id ) ? $nd_booking_best_value_post_id : null;

if ( isset( $lowest_price ) ) {
    $lowest_price = (float) $lowest_price;
} else {
    $lowest_price = null;
}

$is_best_value = $nd_booking_is_best_value_card;

if ( ! $is_best_value && ( null === $nd_booking_best_value_post_id ) && null !== $lowest_price ) {
    $is_best_value = abs( $price - $lowest_price ) < 0.01;
}

$loft_card_classes      = 'nd_booking_section nd_booking_border_1_solid_grey nd_booking_bg_white loft-search-card';
if ( $is_best_value ) {
    $loft_card_classes .= ' has-best-value';
}
$loft_card_wrapper_open = '<div class="' . esc_attr( $loft_card_classes ) . '">';


if ( nd_booking_is_available_block($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to) == 0 ) {
    
    $nd_booking_availability = "<span class='nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_yellow'>".__('NON DISPONIBLE','nd-booking')."</span>";

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

            $nd_booking_availability = "<span class='nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_greydark'>".__('NOMBRE MINIMAL DE NUITS','nd-booking')." : ".$nd_booking_meta_box_min_booking_day."</span>";

        }


    }else{
        $nd_booking_availability = "<span class='nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_yellow'>".__('NON DISPONIBLE','nd-booking')."</span>";
    }

}


//image
if ( has_post_thumbnail() ) {

    $loft_best_value_ribbon_markup = '';
    if ( $is_best_value ) {
        $loft_best_value_ribbon_markup = '<div class="loft-search-card__ribbon"><span class="loft-search-card__ribbon-text">' . esc_html__( 'MEILLEUR TARIF', 'nd-booking' ) . '</span></div>';
    }

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

            '.$loft_best_value_ribbon_markup.'

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

        '.$loft_card_wrapper_open.'

            '.$nd_booking_image.'

            <div class="nd_booking_section nd_booking_box_sizing_border_box loft-search-card__content">
                <div class="loft-search-card__body">
                    <div class="loft-search-card__details">';

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
                                <p class="loft-search-card__feature-text nd_booking_display_table_cell nd_booking_vertical_align_middle">'.$nd_booking_meta_box_max_people.' '.__('INVITÉS','nd-booking').'</p>
                                <img alt="" class="loft-search-card__feature-icon" width="20" src="'.esc_url(plugins_url('icon-plan-grey.svg', __FILE__ )).'">
                                <p class="loft-search-card__feature-text nd_booking_display_table_cell nd_booking_vertical_align_middle">'.$nd_booking_meta_box_room_size.' '.nd_booking_get_units_of_measure().'</p>
                            </div>
                        </div>

                        <div class="loft-search-card__excerpt">'.$loft_room_excerpt.'</div>';

                $loft_services_markup = '';
                $nd_booking_meta_box_normal_services_value = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_normal_services', true );

                if ( $nd_booking_meta_box_normal_services_value !== '' ) {

                    $nd_booking_meta_box_normal_services_array = array_filter( array_map( 'trim', explode( ',', $nd_booking_meta_box_normal_services_value ) ) );

                    if ( ! empty( $nd_booking_meta_box_normal_services_array ) ) {

                        $loft_services_markup .= '<div class="loft-search-card__amenities">';

                        $loft_service_icons_markup = '';

                        foreach ( $nd_booking_meta_box_normal_services_array as $nd_booking_meta_box_normal_services_slug ) {

                            $nd_booking_page_by_path = get_page_by_path( $nd_booking_meta_box_normal_services_slug, OBJECT, 'nd_booking_cpt_2' );

                            if ( $nd_booking_page_by_path instanceof WP_Post ) {
                                $nd_booking_service_id = $nd_booking_page_by_path->ID;
                                $nd_booking_service_name = get_the_title( $nd_booking_service_id );
                                $nd_booking_meta_box_cpt_2_icon = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_icon', true );

                                if ( $nd_booking_meta_box_cpt_2_icon !== '' ) {
                                    $loft_service_icons_markup .= '<a title="' . esc_attr( $nd_booking_service_name ) . '" class="nd_booking_tooltip_jquery loft-search-card__amenity"><img alt="' . esc_attr( $nd_booking_service_name ) . '" class="loft-search-card__amenity-icon" width="23" height="23" src="' . esc_url( $nd_booking_meta_box_cpt_2_icon ) . '"></a>';
                                }
                            }
                        }

                        if ( $loft_service_icons_markup !== '' ) {
                            $loft_services_markup .= '<div class="loft-search-card__amenities-icons">' . $loft_service_icons_markup . '</div>';
                        }

                        $loft_services_markup .= '<a href="' . esc_url( $nd_booking_r_permalink ) . '" class="loft-search-card__details-link">';
                        $loft_services_markup .= '<span class="loft-search-card__details-link-label">' . esc_html__( 'TOUS LES DÉTAILS', 'nd-booking' ) . '</span>';
                        $loft_services_markup .= '<img alt="" class="loft-search-card__details-link-icon" width="10" src="' . esc_url( plugins_url( 'icon-right-arrow-grey.svg', __FILE__ ) ) . '">';
                        $loft_services_markup .= '</a>';
                        $loft_services_markup .= '</div>';
                    }
                }

                $nd_booking_shortcode_right_content .= $loft_services_markup;

                $nd_booking_shortcode_right_content .= '
                    </div>
                    <div class="loft-search-card__sidebar">';


                $loft_has_cta = false;
                $nd_booking_trip_price = 0;
                $loft_total_price_display = '';
                $loft_total_stay_label = '';
                $loft_nightly_label = '';
                $loft_button_label = '';

                if ( ! empty( $loft_pricing_details['has_cta'] ) ) {

                    $loft_has_cta = true;

                    $nd_booking_trip_price     = (float) $loft_pricing_details['trip_price'];
                    $loft_total_price_display  = isset( $loft_pricing_details['total_price_display'] ) ? $loft_pricing_details['total_price_display'] : '';
                    $loft_total_stay_label     = isset( $loft_pricing_details['total_stay_label'] ) ? $loft_pricing_details['total_stay_label'] : '';
                    $loft_nightly_label        = isset( $loft_pricing_details['nightly_label'] ) ? $loft_pricing_details['nightly_label'] : '';
                    $loft_button_label         = isset( $loft_pricing_details['button_label'] ) ? $loft_pricing_details['button_label'] : '';

                    $nd_booking_shortcode_right_content .= '
                    <div class="loft-search-card__rate">
                        <p class="loft-search-card__rate-label">'.esc_html__( 'Tarif total', 'marina-child' ).'</p>
                        <p class="loft-search-card__rate-amount">'.$loft_total_price_display.'</p>
                        <p class="loft-search-card__rate-sub">'.$loft_total_stay_label.'</p>
                        <p class="loft-search-card__rate-sub">'.$loft_nightly_label.'</p>
                    </div>';

                    $loft_booking_url = $nd_booking_r_permalink;

                    if ( $loft_booking_url === $nd_booking_permalink ) {
                        $loft_booking_url = add_query_arg(
                            array(
                                'nd_booking_archive_form_date_range_from' => $nd_booking_date_from,
                                'nd_booking_archive_form_date_range_to'   => $nd_booking_date_to,
                                'nd_booking_archive_form_guests'          => $nd_booking_archive_form_guests,
                            ),
                            $loft_booking_url
                        );
                    }

                    $nd_booking_shortcode_right_content .= '
                    <div class="loft-search-card__actions">
                        <a class="loft-search-card__btn loft-search-card__btn--primary" href="'.esc_url( $loft_booking_url ).'">'.$loft_button_label.'</a>';

                    include realpath(dirname( __FILE__ ).'/nd_booking_info_price_hover_btn.php');

                    $nd_booking_shortcode_right_content .= '
                    </div>';

                }

                if ( ! $loft_has_cta ) {
                    $nd_booking_shortcode_right_content .= '
                    <p class="loft-search-card__unavailable">'.esc_html__( 'Indisponible pour ces dates sélectionnées.', 'marina-child' ).'</p>';
                }

                $nd_booking_shortcode_right_content .= '
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>';