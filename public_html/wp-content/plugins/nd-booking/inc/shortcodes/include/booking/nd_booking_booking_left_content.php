<?php


//START price
$nd_booking_loft_rule = nd_booking_find_loft_pricing_rule( $nd_booking_form_booking_id );

if ( null !== $nd_booking_loft_rule ) {
    $nd_booking_loft_price          = nd_booking_calculate_loft_pricing( $nd_booking_loft_rule, $nd_booking_date_from, $nd_booking_date_to, $nd_booking_form_booking_guests );
    $nd_booking_trip_price          = $nd_booking_loft_price['total'];
    $nd_booking_trip_price_for_person = ( get_option( 'nd_booking_price_guests' ) == 1 && $nd_booking_form_booking_guests > 0 )
        ? $nd_booking_trip_price / $nd_booking_form_booking_guests
        : $nd_booking_trip_price;
} else {
    $nd_booking_trip_price_for_person = 0;
    $nd_booking_index = 1;
    $nd_booking_date_cicle = $nd_booking_date_from;
    while ($nd_booking_index <= nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to)) {

        $nd_booking_trip_price_for_person = $nd_booking_trip_price_for_person + nd_booking_get_final_price($nd_booking_form_booking_id,$nd_booking_date_cicle);

        $nd_booking_date_cicle = date('Y/m/d', strtotime($nd_booking_date_cicle.' + 1 days'));

        $nd_booking_index++;
    }

    $nd_booking_price_guests_enable = get_option('nd_booking_price_guests');
    if ( $nd_booking_price_guests_enable == 1 ) {
      $nd_booking_trip_price = $nd_booking_trip_price_for_person*$nd_booking_form_booking_guests;
    }else{
      $nd_booking_trip_price = $nd_booking_trip_price_for_person;
    }
}
//END price


$nd_booking_tax_breakdown = nd_booking_calculate_tax_breakdown( $nd_booking_trip_price );
$nd_booking_currency = nd_booking_get_currency();
$nd_booking_initial_total_formatted = nd_booking_format_decimal( $nd_booking_tax_breakdown['total'] );
$nd_booking_initial_subtotal_formatted = nd_booking_format_decimal( $nd_booking_tax_breakdown['base'] );
$nd_booking_initial_tax_total_formatted = nd_booking_format_decimal( $nd_booking_tax_breakdown['total_tax'] );

$nd_booking_known_tax_labels = array(
    'lodging' => __( 'Lodging Tax', 'nd-booking' ),
    'gst'     => __( 'GST', 'nd-booking' ),
    'qst'     => __( 'QST', 'nd-booking' ),
);

$nd_booking_tax_lines = '<div class="nd_booking_section nd_booking_margin_top_20 nd_booking_tax_breakdown">';
$nd_booking_tax_lines .= '<p class="nd_options_color_white nd_booking_font_size_13" data-tax-key="subtotal"><span class="nd_booking_tax_label">'.__( 'Subtotal', 'nd-booking' ).'</span> <span class="nd_booking_tax_amount">'.$nd_booking_initial_subtotal_formatted.'</span> <span class="nd_booking_tax_currency">'.$nd_booking_currency.'</span></p>';

foreach ( $nd_booking_known_tax_labels as $nd_booking_tax_key => $nd_booking_tax_label ) {
    $nd_booking_line_style = '';
    if ( isset( $nd_booking_tax_breakdown['taxes'][ $nd_booking_tax_key ] ) ) {
        $nd_booking_tax_rate = nd_booking_format_percentage( $nd_booking_tax_breakdown['taxes'][ $nd_booking_tax_key ]['rate'] );
        $nd_booking_tax_amount_formatted = nd_booking_format_decimal( $nd_booking_tax_breakdown['taxes'][ $nd_booking_tax_key ]['amount'] );
        $nd_booking_display_label = sprintf( __( '%1$s (%2$s%%)', 'nd-booking' ), $nd_booking_tax_breakdown['taxes'][ $nd_booking_tax_key ]['label'], $nd_booking_tax_rate );
    } else {
        $nd_booking_tax_amount_formatted = nd_booking_format_decimal( 0 );
        $nd_booking_tax_rate = nd_booking_format_percentage( 0 );
        $nd_booking_display_label = sprintf( __( '%1$s (%2$s%%)', 'nd-booking' ), $nd_booking_tax_label, $nd_booking_tax_rate );
        $nd_booking_line_style = ' style="display:none;"';
    }

    $nd_booking_tax_lines .= '<p class="nd_options_color_white nd_booking_font_size_12" data-tax-key="'.$nd_booking_tax_key.'"'.$nd_booking_line_style.'><span class="nd_booking_tax_label">'.$nd_booking_display_label.'</span> <span class="nd_booking_tax_amount">'.$nd_booking_tax_amount_formatted.'</span> <span class="nd_booking_tax_currency">'.$nd_booking_currency.'</span></p>';
}

$nd_booking_tax_lines .= '<div class="nd_booking_section nd_booking_height_10"></div>';
$nd_booking_tax_lines .= '<p class="nd_options_color_white nd_booking_font_size_13 nd_booking_font_weight_bold" data-tax-key="total_tax"><span class="nd_booking_tax_label">'.__( 'Total Tax', 'nd-booking' ).'</span> <span class="nd_booking_tax_amount">'.$nd_booking_initial_tax_total_formatted.'</span> <span class="nd_booking_tax_currency">'.$nd_booking_currency.'</span></p>';
$nd_booking_tax_lines .= '<p class="nd_options_color_white nd_booking_font_size_14 nd_booking_font_weight_bolder" data-tax-key="grand_total"><span class="nd_booking_tax_label">'.__( 'Grand Total', 'nd-booking' ).'</span> <span class="nd_booking_tax_amount">'.$nd_booking_initial_total_formatted.'</span> <span class="nd_booking_tax_currency">'.$nd_booking_currency.'</span></p>';
$nd_booking_tax_lines .= '</div>';

$nd_booking_shortcode_left_content = '';


//image
$nd_booking_image_src = nd_booking_get_post_img_src($nd_booking_form_booking_id);
if ( $nd_booking_image_src != '' ) { 
    
    $nd_booking_image = '

      <div class="nd_booking_section nd_booking_position_relative">

          <img class="nd_booking_section" src="'.$nd_booking_image_src.'">

          <div class="nd_booking_bg_greydark_alpha_gradient_3 nd_booking_position_absolute nd_booking_left_0 nd_booking_height_100_percentage nd_booking_width_100_percentage nd_booking_padding_30 nd_booking_box_sizing_border_box">
              <div class="nd_booking_position_absolute nd_booking_top_20">
                  <p class="nd_options_color_white nd_booking_float_left nd_booking_font_size_11 nd_booking_padding_3_5 nd_booking_bg_greydark nd_booking_letter_spacing_2 nd_booking_text_transform_uppercase">
                    '.get_the_title($nd_booking_form_booking_id).'
                  </p>
              </div>
          </div>

      </div>

    ';

}else{ 
    $nd_booking_image = '';
}


//date
$nd_booking_new_date_from = new DateTime($nd_booking_date_from);
$nd_booking_new_date_from_format_d = date_format($nd_booking_new_date_from, 'd');
$nd_booking_new_date_from_format_M = date_format($nd_booking_new_date_from, 'M');
$nd_booking_new_date_from_format_M = date_i18n('M',strtotime($nd_booking_date_from));
$nd_booking_new_date_from_format_l = date_format($nd_booking_new_date_from, 'l');
$nd_booking_new_date_from_format_l = date_i18n('l',strtotime($nd_booking_date_from));
$nd_booking_new_date_from_format_Y = date_format($nd_booking_new_date_from, 'Y');
$nd_booking_new_date_to = new DateTime($nd_booking_date_to);
$nd_booking_new_date_to_format_d = date_format($nd_booking_new_date_to, 'd');
$nd_booking_new_date_to_format_M = date_format($nd_booking_new_date_to, 'M');
$nd_booking_new_date_to_format_M = date_i18n('M',strtotime($nd_booking_date_to));
$nd_booking_new_date_to_format_l = date_format($nd_booking_new_date_to, 'l');
$nd_booking_new_date_to_format_l = date_i18n('l',strtotime($nd_booking_date_to));
$nd_booking_new_date_to_format_Y = date_format($nd_booking_new_date_to, 'Y');


$nd_booking_shortcode_left_content .= '

<div class="nd_booking_section nd_booking_box_sizing_border_box">

  '.$nd_booking_image.'


  <!--START black section-->
  <div id="nd_booking_book_main_bg" class="nd_booking_section nd_booking_bg_greydark nd_booking_padding_30 nd_booking_padding_0_all_iphone nd_booking_box_sizing_border_box">

      <h6 class="nd_options_second_font nd_booking_margin_top_20_all_iphone nd_options_color_white nd_booking_letter_spacing_2 nd_booking_text_align_center nd_booking_font_size_12 nd_booking_font_weight_lighter">'.__('YOUR RESERVATION','nd-booking').'</h6>


      <div class="nd_booking_section nd_booking_height_30"></div> 

      <div class="nd_booking_width_50_percentage nd_booking_float_left  nd_booking_padding_right_10 nd_booking_box_sizing_border_box ">
           <div id="nd_booking_book_bg_check_in" class="nd_booking_section nd_booking_bg_greydark_2 nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_text_align_center">
              <h6 class="nd_options_color_white nd_booking_color_yellow_important nd_options_second_font nd_booking_letter_spacing_2 nd_booking_font_size_12 nd_booking_font_weight_lighter">'.__('CHECK-IN','nd-booking').'</h6>
              <div class="nd_booking_section nd_booking_height_15"></div> 
              <h1 class="nd_booking_font_size_50 nd_booking_color_yellow_important">'.$nd_booking_new_date_from_format_d.'</h1>
              <div class="nd_booking_section nd_booking_height_15"></div>
              <h6 class="nd_options_color_white nd_booking_font_size_11"><i>'.$nd_booking_new_date_from_format_M.', '.$nd_booking_new_date_from_format_Y.'</i></h6>
              <div class="nd_booking_section nd_booking_height_5"></div>
              <h6 class="nd_options_second_font nd_options_color_grey nd_booking_font_size_11 nd_booking_letter_spacing_2 nd_booking_font_weight_lighter nd_booking_text_transform_uppercase">'.$nd_booking_new_date_from_format_l.'</h6>
          </div>   
      </div>

      <div class="nd_booking_width_50_percentage nd_booking_float_left  nd_booking_padding_left_10 nd_booking_box_sizing_border_box ">
           <div id="nd_booking_book_bg_check_out" class="nd_booking_section nd_booking_bg_greydark_2 nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_text_align_center">
              <h6 class="nd_options_color_white nd_booking_color_yellow_important nd_options_second_font nd_booking_letter_spacing_2 nd_booking_font_size_12 nd_booking_font_weight_lighter">'.__('CHECK-OUT','nd-booking').'</h6>
              <div class="nd_booking_section nd_booking_height_15"></div> 
              <h1 class="nd_booking_font_size_50 nd_booking_color_yellow_important">'.$nd_booking_new_date_to_format_d.'</h1>
              <div class="nd_booking_section nd_booking_height_15"></div>
              <h6 class="nd_options_color_white nd_booking_font_size_11"><i>'.$nd_booking_new_date_to_format_M.', '.$nd_booking_new_date_to_format_Y.'</i></h6>
              <div class="nd_booking_section nd_booking_height_5"></div>
              <h6 class="nd_options_second_font nd_options_color_grey nd_booking_font_size_11 nd_booking_letter_spacing_2 nd_booking_font_weight_lighter nd_booking_text_transform_uppercase">'.$nd_booking_new_date_to_format_l.'</h6>
          </div>    
      </div>

      <div class="nd_booking_section nd_booking_height_20"></div> 

      <div class="nd_booking_width_50_percentage nd_booking_float_left  nd_booking_padding_right_10 nd_booking_box_sizing_border_box ">
           <div id="nd_booking_book_bg_guests" class="nd_booking_section nd_booking_bg_greydark_2 nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_text_align_center">
              <h1 class=" nd_options_color_white">'.$nd_booking_form_booking_guests.'</h1>
              <div class="nd_booking_section nd_booking_height_10"></div> 
              <h6 class="nd_options_second_font nd_options_color_grey nd_booking_font_size_11 nd_booking_letter_spacing_2 nd_booking_font_weight_lighter">'.__('GUESTS','nd-booking').'</h6>
              
          </div>   
      </div>

      <div class="nd_booking_width_50_percentage nd_booking_float_left  nd_booking_padding_left_10 nd_booking_box_sizing_border_box ">
           <div id="nd_booking_book_bg_nights" class="nd_booking_section nd_booking_bg_greydark_2 nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_text_align_center">
              <h1 class=" nd_options_color_white">'.nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to).'</h1>
              <div class="nd_booking_section nd_booking_height_10"></div> 
              <h6 class="nd_options_second_font nd_options_color_grey nd_booking_font_size_11 nd_booking_letter_spacing_2 nd_booking_font_weight_lighter">'.__('NIGHTS','nd-booking').'</h6>
          </div>    
      </div>

  </div>

  <div id="nd_booking_book_bg_total" class="nd_booking_section nd_booking_bg_greydark_2 nd_booking_padding_30 nd_booking_box_sizing_border_box nd_booking_text_align_center">
      <div class="nd_booking_section nd_booking_box_sizing_border_box nd_booking_text_align_center">

          <div class="nd_booking_display_inline_block ">
              <div id="nd_booking_final_trip_price_content" class="nd_booking_float_left nd_booking_text_align_right">
                  <h1 id="nd_booking_final_trip_price" class="nd_options_color_white nd_booking_font_size_50"><span>'.$nd_booking_initial_total_formatted.'</span></h1>
              </div>
              <div class="nd_booking_float_right nd_booking_text_align_left nd_booking_margin_left_10">
                  <h5 class="nd_options_second_font nd_options_color_white nd_booking_margin_top_7 nd_booking_font_size_14 nd_booking_font_weight_lighter">'.$nd_booking_currency.'<p></p>
                  <div class="nd_booking_section nd_booking_height_5"></div>
                  </h5><h3 class="nd_options_second_font nd_options_color_white nd_booking_font_size_14 nd_booking_letter_spacing_2 nd_booking_font_weight_lighter">/ '.__('TOTAL','nd-booking').'</h3>
              </div>
          </div>

      </div>

  </div>'.$nd_booking_tax_lines.'

  <!--END black section-->';


$nd_booking_shortcode_left_content .= '
</div>


';






