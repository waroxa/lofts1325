<?php


$nd_booking_initial_breakdown = nd_booking_calculate_tax_breakdown( $nd_booking_trip_price );
$nd_booking_initial_final_price = $nd_booking_initial_breakdown['total'];
$nd_booking_initial_base_price = $nd_booking_initial_breakdown['base'];

$nd_booking_shortcode_right_content = '

<div class="nd_booking_section nd_booking_height_2 nd_booking_bg_grey"></div>
<div class="nd_booking_section">

  <div class="nd_booking_section nd_booking_height_40"></div>
  <h1>'.__('Add Your Informations','nd-booking').' :</h1>
  <div class="nd_booking_section nd_booking_height_30"></div>

  <form method="post" enctype="multipart/form-data" action="'.nd_booking_checkout_page().'">
      
      <input type="hidden" id="nd_booking_form_booking_arrive" name="nd_booking_form_booking_arrive" value="1">
      <input type="hidden" id="nd_booking_booking_form_final_price" name="nd_booking_booking_form_final_price" value="'.nd_booking_format_decimal( $nd_booking_initial_final_price ).'">
      <input type="hidden" id="nd_booking_booking_form_base_price" name="nd_booking_booking_form_base_price" value="'.nd_booking_format_decimal( $nd_booking_initial_base_price ).'">
      <input type="hidden" id="nd_booking_booking_form_trip_price" name="nd_booking_booking_form_trip_price" value="'.nd_booking_format_decimal( $nd_booking_trip_price ).'">
      <input type="hidden" id="nd_booking_booking_form_date_from" name="nd_booking_booking_form_date_from" value="'.$nd_booking_date_from.'">
      <input type="hidden" id="nd_booking_booking_form_date_to" name="nd_booking_booking_form_date_to" value="'.$nd_booking_date_tooo.'">
      <input type="hidden" id="nd_booking_booking_form_guests" name="nd_booking_booking_form_guests" value="'.$nd_booking_form_booking_guests.'">
      <input type="hidden" id="nd_booking_booking_form_post_id" name="nd_booking_booking_form_post_id" value="'.$nd_booking_form_booking_id.'-'.$nd_booking_id_room.'">
      <input type="hidden" id="nd_booking_booking_form_post_title" name="nd_booking_booking_form_post_title" value="'.get_the_title($nd_booking_form_booking_id).'">
      <input type="hidden" id="nd_booking_booking_checkbox_services_id" name="nd_booking_booking_checkbox_services_id" readonly value="">

      <div id="nd_booking_booking_form_name_container" class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_right_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Name','nd-booking').' *</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_name" name="nd_booking_booking_form_name" type="text" >
      </div>
      <div id="nd_booking_booking_form_surname_container"  class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_left_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Surname','nd-booking').' *</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_surname" name="nd_booking_booking_form_surname" type="text" >
      </div>
      <div class="nd_booking_section nd_booking_height_20"></div>
      <div id="nd_booking_booking_form_email_container"  class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_right_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Email','nd-booking').' *</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_email" name="nd_booking_booking_form_email" type="text" >
      </div>
      <div id="nd_booking_booking_form_phone_container" class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_left_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Telephone','nd-booking').' *</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_phone" name="nd_booking_booking_form_phone" type="text" >
      </div>
      <div class="nd_booking_section nd_booking_height_20"></div>
      <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_right_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Address','nd-booking').'</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_address" name="nd_booking_booking_form_address" type="text" >
      </div>
      <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_left_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('City','nd-booking').'</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_city" name="nd_booking_booking_form_city" type="text" >
      </div>
      <div class="nd_booking_section nd_booking_height_20"></div>
      <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_right_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Country','nd-booking').'</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_country" name="nd_booking_booking_form_country" type="text" >
      </div>
      <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_left_10 nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('ZIP','nd-booking').'</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_zip" name="nd_booking_booking_form_zip" type="text" >
      </div>
        <div class="nd_booking_section nd_booking_height_20"></div>
        <h3>'.__('Upload ID','nd-booking').'</h3>
        <div class="nd_booking_section nd_booking_height_20"></div>
        <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_right_10 nd_booking_box_sizing_border_box nd_booking_float_left">
            <p>'.__('ID Number','nd-booking').'</p>
            <div class="nicdark_section nicdark_height_5"></div>
            <input class="nd_booking_section" id="guest_id_number" name="guest_id_number" type="text" >
        </div>
        <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_left_10 nd_booking_box_sizing_border_box nd_booking_float_left">
            <p>'.__('ID Type','nd-booking').'</p>
            <div class="nicdark_section nicdark_height_5"></div>
            <select class="nd_booking_section" id="guest_id_type" name="guest_id_type">
                <option>'.__('Driver\'s License','nd-booking').'</option>
                <option>'.__('Passport','nd-booking').'</option>
            </select>
        </div>
        <div class="nd_booking_section nd_booking_height_20"></div>

        <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_right_10 nd_booking_box_sizing_border_box nd_booking_float_left">
            <p>'.__('Guest ID Front','nd-booking').'</p>
            <div class="nicdark_section nicdark_height_5"></div>
            <input class="nd_booking_section" type="file" name="guest_id_front" accept="image/*" />
        </div>
        <div class="nd_booking_width_50_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone nd_booking_padding_left_10 nd_booking_box_sizing_border_box nd_booking_float_left">
            <p>'.__('Guest ID Back','nd-booking').'</p>
            <div class="nicdark_section nicdark_height_5"></div>
            <input class="nd_booking_section" type="file" name="guest_id_back" accept="image/*" />
        </div>
        <div class="nd_booking_section nd_booking_height_20"></div>

        <div id="nd_booking_booking_form_requests_container"  class="nd_booking_width_100_percentage nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Requests','nd-booking').'</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <textarea class="nd_booking_section" id="nd_booking_booking_form_requests" rows="6" name="nd_booking_booking_form_requests"></textarea>
      </div>
      <div class="nd_booking_section nd_booking_height_20"></div>
      <div class=" nd_booking_width_100_percentage nd_booking_width_100_percentage_all_iphone nd_booking_padding_0_all_iphone  nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Arrival','nd-booking').'</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <p><small><em>'.__('Check-in starts at 4 PM; checkout is at 12 PM.','nd-booking').'</em></small></p>
          <input type="hidden" class="nd_booking_section" name="nd_booking_booking_form_arrival" id="nd_booking_booking_form_arrival" value="4:00 - 5:00 '. __('pm','nd-booking').'" >
      </div>
      <div class="nd_booking_section nd_booking_height_20 '.nd_booking_get_coupon_enable_class().' "></div>
      <div id="nd_booking_booking_form_coupon_container" class="nd_booking_width_100_percentage '.nd_booking_get_coupon_enable_class().' nd_booking_box_sizing_border_box nd_booking_float_left">
          <p>'.__('Coupon','nd-booking').'</p>
          <div class="nicdark_section nicdark_height_5"></div>
          <input class="nd_booking_section" id="nd_booking_booking_form_coupon" name="nd_booking_booking_form_coupon" type="text" >  
      </div>
      <div class="nd_booking_section nd_booking_height_20"></div>
      <div id="nd_booking_booking_form_term_container" class="nd_booking_width_100_percentage nd_booking_box_sizing_border_box nd_booking_float_left">
          <p class="nd_booking_margin_0 nd_booking_section">
            <input class="nd_booking_float_left nd_booking_margin_top_8 nd_booking_margin_right_10" id="nd_booking_booking_form_term" name="nd_booking_booking_form_term" type="checkbox" checked value="1">
            <a class="nd_booking_float_left" target="_blank" href="'.nd_booking_terms_page().'">'.__('Terms and conditions','nd-booking').' *</a>
          </p>
      </div> 
      <div class="nd_booking_section nd_booking_height_20"></div>
      <div class="nd_booking_width_100_percentage nd_booking_box_sizing_border_box nd_booking_float_left">
          <a onclick="nd_booking_validate_fields()" class="nd_booking_bg_yellow nd_options_color_white nd_booking_cursor_pointer nd_booking_font_size_11 nd_options_second_font_important nd_booking_font_weight_bolder nd_booking_letter_spacing_2 nd_booking_padding_15_35_important">'.__('CHECKOUT','nd-booking').'</a>
          <input id="nd_booking_submit_go_to_checkout" class="nd_booking_display_none nd_booking_font_size_11 nd_options_second_font_important nd_booking_font_weight_bolder nd_booking_letter_spacing_2 nd_booking_padding_15_35_important" type="submit" value="'.__('CHECKOUT','nd-booking').'"> 
      </div>  


  </form>

</div>';