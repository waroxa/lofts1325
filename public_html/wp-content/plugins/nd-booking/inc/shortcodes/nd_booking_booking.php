<?php


//START  nd_booking_booking
function nd_booking_shortcode_booking() {

    $nd_booking_min_days_check = 0;

    //ajax results
    $nd_travel_sorting_params = array(
        'nd_booking_ajaxurl_form_validate_fields' => admin_url('admin-ajax.php'),
        'nd_booking_ajaxnonce_sorting_form_validate_fields' => wp_create_nonce('nd_booking_form_validate_fields_nonce'),
    );

    wp_enqueue_script( 'nd_booking_form_validate_fields', esc_url( plugins_url( 'validate_fields.js', __FILE__ ) ), array( 'jquery' ) ); 
    wp_localize_script( 'nd_booking_form_validate_fields', 'nd_booking_my_vars_form_validate_fields', $nd_travel_sorting_params ); 

    if( isset( $_POST['nd_booking_form_booking_arrive_advs'] ) ) {  $nd_booking_form_booking_arrive_advs = sanitize_text_field($_POST['nd_booking_form_booking_arrive_advs']); }else{ $nd_booking_form_booking_arrive_advs = '';} 
   
    if ( $nd_booking_form_booking_arrive_advs != 1 ) {

         $nd_booking_shortcode_result = '';


         $nd_booking_slug_to_insert = nd_booking_get_slug('singular');


        $nd_booking_shortcode_result .= '

            <div class="nd_booking_section">

                <div class="nd_booking_float_left nd_booking_width_100_percentage nd_booking_box_sizing_border_box">
                    <p>'.__('Please select a','nd-booking').' '.$nd_booking_slug_to_insert.' '.__('to make a reservation','nd-booking').'</p>
                    <div class="nd_booking_section nd_booking_height_20"></div>
                    <a href="'.nd_booking_search_page().'" class="nd_booking_bg_yellow nd_booking_padding_15_30_important nd_options_second_font_important nd_booking_border_radius_0_important nd_options_color_white nd_booking_cursor_pointer nd_booking_display_inline_block nd_booking_font_size_11 nd_booking_font_weight_bold nd_booking_letter_spacing_2">'.__('RETURN TO SEARCH PAGE','nd-booking').'</a>
                </div>

            </div>

        ';  

    }else{

        $nd_booking_room_available = 1;

        if( isset( $_POST['nd_booking_form_booking_arrive_sr'] ) ) {  $nd_booking_form_booking_arrive_sr = sanitize_text_field($_POST['nd_booking_form_booking_arrive_sr']); }else{ $nd_booking_form_booking_arrive_sr = 0;}



        //ARRIVE FROM SINGLE ROOM
        if ( $nd_booking_form_booking_arrive_sr == 1 ) {

          //parameters
          $nd_booking_id = sanitize_text_field($_POST['nd_booking_archive_form_id']);
          $nd_booking_form_booking_id = sanitize_text_field($_POST['nd_booking_archive_form_id']);
          $nd_booking_date_from = sanitize_text_field($_POST['nd_booking_archive_form_date_range_from']);
          $nd_booking_date_to = sanitize_text_field($_POST['nd_booking_archive_form_date_range_to']);
          $nd_booking_form_booking_guests = sanitize_text_field($_POST['nd_booking_archive_form_guests']);

          //convert date
          $nd_booking_date_too = new DateTime($nd_booking_date_to);
          $nd_booking_date_tooo = date_format($nd_booking_date_too, 'm/d/Y');

          //ids
          $nd_booking_ids_array = explode('-', $nd_booking_form_booking_id ); 
          $nd_booking_form_booking_id = $nd_booking_ids_array[0];
          $nd_booking_id_room = $nd_booking_ids_array[1];
          

          if ( nd_booking_is_available_block($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to) == 1 ) {

            if ( nd_booking_is_qnt_available(nd_booking_is_available($nd_booking_id_room,$nd_booking_date_from,$nd_booking_date_to),$nd_booking_date_from,$nd_booking_date_to,$nd_booking_id_room) == 1 ){

              //check the options min booking days
              $nd_booking_meta_box_min_booking_day = get_post_meta( $nd_booking_id_room, 'nd_booking_meta_box_min_booking_day', true );
              if ( $nd_booking_meta_box_min_booking_day == '' ) { $nd_booking_meta_box_min_booking_day = 1; }
              if ( nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to) >= $nd_booking_meta_box_min_booking_day ) {

                $nd_booking_room_available = 1;

              }else{

                $nd_booking_min_days_check = 1;
                $nd_booking_room_available = 0; 

              }
            
            }else{

              $nd_booking_room_available = 0;

            }

          }else{

            $nd_booking_room_available = 0; 

          }

        //ARRIVE FROM ADV SEARCH
        }else{

          //get all passed datas
          $nd_booking_form_booking_id = sanitize_text_field($_POST['nd_booking_form_booking_id']);
          $nd_booking_date_from = sanitize_text_field($_POST['nd_booking_form_booking_date_from']);
          $nd_booking_date_to = sanitize_text_field($_POST['nd_booking_form_booking_date_to']);
          $nd_booking_form_booking_guests = sanitize_text_field($_POST['nd_booking_form_booking_guests']);

          //convert date
          $nd_booking_date_too = new DateTime($nd_booking_date_to);
          $nd_booking_date_tooo = date_format($nd_booking_date_too, 'm/d/Y');


          //ids
          $nd_booking_form_booking_id = sanitize_text_field($_POST['nd_booking_form_booking_id']);
          $nd_booking_ids_array = explode('-', $nd_booking_form_booking_id ); 
          $nd_booking_form_booking_id = $nd_booking_ids_array[0];
          $nd_booking_id_room = $nd_booking_ids_array[1];


        }


        if ( $nd_booking_room_available == 1 ) {

            //ajax results
            $nd_booking_final_price_params = array(
                'nd_booking_ajaxurl_final_price' => admin_url('admin-ajax.php'),
                'nd_booking_ajaxnonce_final_price' => wp_create_nonce('nd_booking_final_price_nonce'),
            );

            wp_enqueue_script( 'nd_booking_booking_final_price', esc_url( plugins_url( 'final_price.js', __FILE__ ) ), array( 'jquery' ) ); 
            wp_localize_script( 'nd_booking_booking_final_price', 'nd_booking_my_vars_final_price', $nd_booking_final_price_params );


            //register login info
            if ( is_user_logged_in() ) {

              $nd_booking_alert_login = '';

            }else{

              $nd_booking_alert_login = '
                <div class="nd_booking_booking_alert_login_register nd_booking_section nd_booking_bg_red nd_booking_padding_5_10 nd_booking_box_sizing_border_box">
                  <p class="nd_options_color_white">'.__('You are booking as guest,','nd-booking').' <a target="_blank" class="nd_options_color_white nd_booking_border_bottom_1_solid_white" href="'.nd_booking_account_page().'">'.__('LOGIN','nd-booking').'</a> '.__('or','nd-booking').' <a target="_blank" class="nd_options_color_white nd_booking_border_bottom_1_solid_white" href="'.nd_booking_account_page().'">'.__('REGISTER','nd-booking').'</a> '.__('if you want to save your reservation on your account.','nd-booking').'</p>
                </div>
                <div class="nd_booking_booking_alert_login_register nd_booking_section nd_booking_height_40"></div>
              ';

            }

            include realpath(dirname( __FILE__ ).'/include/booking/nd_booking_booking_additional_services.php'); 
            include realpath(dirname( __FILE__ ).'/include/booking/nd_booking_booking_left_content.php'); 
            include realpath(dirname( __FILE__ ).'/include/booking/nd_booking_booking_right_content.php'); 
            
            $nd_booking_shortcode_result = '';
            $nd_booking_shortcode_result .= '

            <div class="nd_booking_section">
            
                <div class="nd_booking_float_left nd_booking_width_33_percentage nd_booking_width_100_percentage_responsive nd_booking_padding_right_15 nd_booking_padding_0_responsive nd_booking_box_sizing_border_box">
                    
                    '.$nd_booking_shortcode_left_content.'

                </div>

                <div class="nd_booking_float_left nd_booking_width_66_percentage nd_booking_width_100_percentage_responsive nd_booking_padding_left_15 nd_booking_padding_0_responsive nd_booking_box_sizing_border_box">
                    
                    '.$nd_booking_alert_login.'
                    '.$nd_booking_additional_services.'
                    '.$nd_booking_shortcode_right_content.'

                </div>

            </div>
            ';

        }else{

          $nd_booking_shortcode_result = '';


          if ( $nd_booking_min_days_check == 1 ){

            $nd_booking_meta_box_min_booking_day = get_post_meta( $nd_booking_id_room, 'nd_booking_meta_box_min_booking_day', true );
            if ( $nd_booking_meta_box_min_booking_day == '' ) { $nd_booking_meta_box_min_booking_day = 1; }

            $nd_booking_shortcode_result .= '

                <div class="nd_booking_section">

                    <div class="nd_booking_float_left nd_booking_width_100_percentage nd_booking_box_sizing_border_box">
                        <p>'.__('Minimum booking days','nd-booking').' : '.$nd_booking_meta_box_min_booking_day.'</p>
                        <div class="nd_booking_section nd_booking_height_20"></div>
                        <a href="'.nd_booking_search_page().'" class="nd_booking_bg_yellow nd_booking_padding_15_30_important nd_options_second_font_important nd_booking_border_radius_0_important nd_options_color_white nd_booking_cursor_pointer nd_booking_display_inline_block nd_booking_font_size_11 nd_booking_font_weight_bold nd_booking_letter_spacing_2">'.__('RETURN TO SEARCH PAGE','nd-booking').'</a>
                    </div>

                </div>

            ';

          }else{


            $nd_booking_slug_to_insert_2 = nd_booking_get_slug('singular');


            $nd_booking_shortcode_result .= '

                <div class="nd_booking_section">

                    <div class="nd_booking_float_left nd_booking_width_100_percentage nd_booking_box_sizing_border_box">
                        <p>'.__('The','nd-booking').' '.$nd_booking_slug_to_insert_2.' '.__('is not available','nd-booking').'</p>
                        <div class="nd_booking_section nd_booking_height_20"></div>
                        <a href="'.nd_booking_search_page().'" class="nd_booking_bg_yellow nd_booking_padding_15_30_important nd_options_second_font_important nd_booking_border_radius_0_important nd_options_color_white nd_booking_cursor_pointer nd_booking_display_inline_block nd_booking_font_size_11 nd_booking_font_weight_bold nd_booking_letter_spacing_2">'.__('RETURN TO SEARCH PAGE','nd-booking').'</a>
                    </div>

                </div>

            ';

          }

          


        }

        

    }


    

    return $nd_booking_shortcode_result;
		


}
add_shortcode('nd_booking_booking', 'nd_booking_shortcode_booking');
//END nd_booking_booking





//START function for AJAX
function nd_booking_final_price_php() {

    check_ajax_referer( 'nd_booking_final_price_nonce', 'nd_booking_final_price_security' );

    //recover var
    $nd_booking_booking_checkbox_services = sanitize_text_field($_GET['nd_booking_booking_checkbox_services']);
    $nd_booking_booking_form_final_price = sanitize_text_field($_GET['nd_booking_booking_form_final_price']);

    //declare
    $nd_booking_final_price_result = $nd_booking_booking_form_final_price;

    $nd_booking_additional_services_value_array = explode(',', $nd_booking_booking_checkbox_services );
    for ($nd_booking_i = 0; $nd_booking_i < count($nd_booking_additional_services_value_array)-1; $nd_booking_i++) {
        
        $nd_booking_final_price_result = $nd_booking_final_price_result + $nd_booking_additional_services_value_array[$nd_booking_i];   

    }

    $nd_booking_booking_result = $nd_booking_final_price_result;

    echo esc_html($nd_booking_booking_result);

    die();

}
add_action( 'wp_ajax_nd_booking_final_price_php', 'nd_booking_final_price_php' );
add_action( 'wp_ajax_nopriv_nd_booking_final_price_php', 'nd_booking_final_price_php' );









/* **************************************** START AJAX **************************************** */

//validate if a number is numeric
function nd_booking_is_numeric($nd_booking_number){

  if ( is_numeric($nd_booking_number) ) {
    return 1;
  }else{
    return 0;
  }

}


//validate if email is valid
function nd_booking_is_email($nd_booking_email){

  if (filter_var($nd_booking_email, FILTER_VALIDATE_EMAIL)) {
    return 1;  
  } else {
    return 0;
  }


}

//validate if coupon is valid
function nd_booking_is_coupon_valid($nd_booking_coupon){


  $args = array(
      'post_type' => 'nd_booking_cpt_5',
      'meta_query' => array(
          array(
              'key'     => 'nd_booking_meta_box_cpt_5_code',
              'value'   => $nd_booking_coupon,
              'compare' => '=',
          ),
      ),
  );
  $the_query = new WP_Query( $args );
  $nd_booking_qnt_results_posts = $the_query->found_posts;

  if ( $nd_booking_qnt_results_posts == 0 ) { 
    return 0;
  }else{
    return 1;
  }
  

}



//php function for validation fields on booking form
function nd_booking_validate_fields_php_function() {

  check_ajax_referer( 'nd_booking_form_validate_fields_nonce', 'nd_booking_form_validate_fields_security' );

  //recover datas
  $nd_booking_name = sanitize_text_field($_GET['nd_booking_name']);
  $nd_booking_surname = sanitize_text_field($_GET['nd_booking_surname']);
  $nd_booking_email = sanitize_email($_GET['nd_booking_email']);
  $nd_booking_message = sanitize_text_field($_GET['nd_booking_message']);
  $nd_booking_phone = sanitize_text_field($_GET['nd_booking_phone']);
  $nd_booking_term = sanitize_text_field($_GET['nd_booking_term']);
  $nd_booking_coupon = sanitize_text_field($_GET['nd_booking_coupon']);
  
  //declare
  $nd_booking_string_result = '';


  //name
  if ( $nd_booking_name == '' ) {

    $nd_booking_result_name = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('MANDATORY','nd-booking').'[divider]'.'</span>';     

  }else{

    $nd_booking_result_name = 1;

    $nd_booking_string_result .= ' [divider]';   

  }

  //surname
  if ( $nd_booking_surname == '' ) {

    $nd_booking_result_surname = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('MANDATORY','nd-booking').'[divider]'.'</span>';     

  }else{

    $nd_booking_result_surname = 1;

    $nd_booking_string_result .= ' [divider]'; 

  }


  //email
  if ( $nd_booking_email == '' ) {

    $nd_booking_result_email = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('MANDATORY','nd-booking').'[divider]'.'</span>';     

  }elseif ( nd_booking_is_email($nd_booking_email) == 0 ) {

    $nd_booking_result_email = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('NOT VALID','nd-booking').'[divider]'.'</span>';  

  }else{

    $nd_booking_result_email = 1;

    $nd_booking_string_result .= ' [divider]'; 

  }



  //phone
  if ( $nd_booking_phone == '' ) {

    $nd_booking_result_phone = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('MANDATORY','nd-booking').'[divider]'.'</span>';     

  }elseif ( nd_booking_is_numeric($nd_booking_phone) == 0 ) {

    $nd_booking_result_phone = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('NOT VALID','nd-booking').'[divider]'.'</span>';  

  }else{

    $nd_booking_result_phone = 1;

    $nd_booking_string_result .= ' [divider]'; 

  }



  //message
  if ( strlen($nd_booking_message) >= 250 ) {

    $nd_booking_result_message = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('REDUCE YOUR MESSAGE, THE MAXIMUM ALLOWED CHARACTERS IS 250','nd-booking').'[divider]'.'</span>';     

  }else{

    $nd_booking_result_message = 1;

    $nd_booking_string_result .= ' [divider]'; 

  }


  //term
  if ( $nd_booking_term == 0 ){

    $nd_booking_result_term = 0; 

    $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_left nd_booking_margin_left_20 nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('MANDATORY','nd-booking').'[divider]'.'</span>';     


  }else{

    $nd_booking_result_term = 1;

    $nd_booking_string_result .= ' [divider]'; 

  }



  //coupon
  if ( $nd_booking_coupon == '' ) {

    $nd_booking_result_coupon = 1; 

    $nd_booking_string_result .= ' [divider]'; 

  }else{

    if ( nd_booking_is_coupon_valid($nd_booking_coupon) == 1 ){

      $nd_booking_result_coupon = 1; 

      $nd_booking_string_result .= ' [divider]'; 

    }else{

      $nd_booking_result_coupon = 0;

      $nd_booking_string_result .= '<span class="nd_booking_validation_errors nd_booking_font_size_10 nd_booking_bg_red nd_options_color_white nd_booking_float_right nd_booking_padding_5_10 nd_booking_margin_top_5 nd_booking_line_height_9">'.__('NOT VALID','nd-booking').'[divider]'.'</span>';     

    }
    
  }



  //Determiante the final result
  if ( $nd_booking_result_name == 1 AND  $nd_booking_result_surname == 1 AND $nd_booking_result_email == 1 AND $nd_booking_result_phone == 1 AND $nd_booking_result_message == 1 AND $nd_booking_result_term == 1 AND $nd_booking_result_coupon == 1 ){
    echo esc_attr(1);
  }else{
    
    $nd_booking_allowed_html = [
      'span' => [
        'class' => [],
      ],
    ];

    echo wp_kses( $nd_booking_string_result, $nd_booking_allowed_html );

  }

  
     
  //close the function to avoid wordpress errors
  die();

}
add_action( 'wp_ajax_nd_booking_validate_fields_php_function', 'nd_booking_validate_fields_php_function' );
add_action( 'wp_ajax_nopriv_nd_booking_validate_fields_php_function', 'nd_booking_validate_fields_php_function' );
/* **************************************** END AJAX **************************************** */





