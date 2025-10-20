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
                <div class="nd_booking_booking_alert_login_register">
                  <p>'.__('You are booking as guest,','nd-booking').' <a target="_blank" href="'.nd_booking_account_page().'">'.__('LOGIN','nd-booking').'</a> '.__('or','nd-booking').' <a target="_blank" href="'.nd_booking_account_page().'">'.__('REGISTER','nd-booking').'</a> '.__('if you want to save your reservation on your account.','nd-booking').'</p>
                </div>
              ';

            }

            include realpath(dirname( __FILE__ ).'/include/booking/nd_booking_booking_additional_services.php');
            include realpath(dirname( __FILE__ ).'/include/booking/nd_booking_booking_left_content.php');
            include realpath(dirname( __FILE__ ).'/include/booking/nd_booking_booking_right_content.php');

            $nd_booking_shortcode_result = '';

            if ( ! defined( 'ND_BOOKING_BOOKING_PAGE_LAYOUT_STYLES' ) ) {
                define( 'ND_BOOKING_BOOKING_PAGE_LAYOUT_STYLES', true );
                $nd_booking_shortcode_result .= '<style>
                .ndb-booking-wrapper {
                    display: flex;
                    flex-direction: row;
                    align-items: flex-start;
                    justify-content: center;
                    gap: 2.5rem;
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 2.5rem;
                    background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
                    border-radius: 1.75rem;
                    box-shadow: 0 32px 90px rgba(15, 23, 42, 0.08);
                    box-sizing: border-box;
                }

                .ndb-booking-layout__sidebar {
                    flex: 0 0 320px;
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                }

                .ndb-booking-layout__main {
                    flex: 1 1 520px;
                    min-width: 0;
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                }

                .ndb-booking-summary,
                .ndb-booking-form,
                .ndb-booking-extras,
                .nd_booking_booking_alert_login_register {
                    background: #ffffff;
                    border-radius: 1.5rem;
                    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
                    border: 1px solid rgba(148, 163, 184, 0.18);
                    padding: 2rem;
                    box-sizing: border-box;
                }

                .nd_booking_booking_alert_login_register {
                    color: #1f2937;
                    background: #fef3c7;
                    border: 1px solid rgba(245, 158, 11, 0.25);
                    box-shadow: none;
                }

                .nd_booking_booking_alert_login_register p {
                    margin: 0;
                }

                .nd_booking_booking_alert_login_register a {
                    color: #92400e;
                    font-weight: 600;
                }

                .ndb-booking-extras {
                    padding: 0;
                    overflow: hidden;
                }

                .ndb-booking-extras > * {
                    width: 100%;
                }

                .ndb-booking-summary__card {
                    position: relative;
                    overflow: hidden;
                    border-radius: 1.25rem;
                    border: 1px solid rgba(255, 255, 255, 0.25);
                    background: linear-gradient(150deg, #0f172a 0%, #1e293b 100%);
                    color: #f8fafc;
                    display: flex;
                    flex-direction: column;
                    gap: 1.25rem;
                }

                .ndb-booking-summary__media {
                    position: relative;
                    overflow: hidden;
                    border-radius: 1.25rem 1.25rem 0 0;
                }

                .ndb-booking-summary__media-overlay {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    padding: 1rem;
                    background: linear-gradient(180deg, rgba(15, 23, 42, 0.4) 0%, rgba(15, 23, 42, 0) 100%);
                }

                .ndb-booking-summary__badge {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0.35rem 0.85rem;
                    border-radius: 999px;
                    background: rgba(255, 255, 255, 0.25);
                    font-size: 0.7rem;
                    letter-spacing: 0.1em;
                    text-transform: uppercase;
                    font-weight: 700;
                }

                .ndb-booking-summary__stars img {
                    display: inline-block;
                    filter: drop-shadow(0 4px 8px rgba(15, 23, 42, 0.4));
                }

                .ndb-booking-summary__title {
                    margin: 0;
                    font-size: 1.6rem;
                    font-weight: 700;
                }

                .ndb-booking-summary__excerpt {
                    color: rgba(248, 250, 252, 0.85);
                    font-size: 0.95rem;
                }

                .ndb-booking-summary__meta {
                    list-style: none;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.75rem 1rem;
                    color: rgba(248, 250, 252, 0.85);
                    font-size: 0.9rem;
                }

                .ndb-booking-summary__details {
                    margin-top: 1.75rem;
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                }

                .ndb-booking-summary__list {
                    list-style: none;
                    margin: 0;
                    padding: 0;
                    display: grid;
                    gap: 0.75rem;
                }

                .ndb-booking-summary__list .label {
                    display: block;
                    font-size: 0.75rem;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    color: rgba(15, 23, 42, 0.65);
                }

                .ndb-booking-summary__list .value {
                    font-weight: 600;
                    color: #111827;
                }

                .ndb-booking-summary__total {
                    background: linear-gradient(145deg, #0f172a 0%, #1d4ed8 100%);
                    color: #f8fafc;
                    border-radius: 1.25rem;
                    padding: 1.5rem;
                    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
                }

                .ndb-booking-summary__total .summary-amount {
                    display: flex;
                    align-items: baseline;
                    gap: 0.35rem;
                    font-size: 2.25rem;
                    font-weight: 700;
                }

                .ndb-booking-summary__total .summary-note {
                    margin: 0.75rem 0 0;
                    font-size: 0.85rem;
                    color: rgba(248, 250, 252, 0.85);
                }

                .ndb-booking-summary__breakdown {
                    display: grid;
                    gap: 0.5rem;
                    font-size: 0.95rem;
                }

                .ndb-booking-summary__breakdown .label {
                    color: #6b7280;
                }

                .ndb-booking-summary__breakdown .value {
                    font-weight: 600;
                    color: #111827;
                }

                .ndb-booking-summary__taxes {
                    margin: 0;
                    font-size: 0.85rem;
                    color: #475569;
                }

                .ndb-booking-summary__support {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    font-size: 0.9rem;
                    color: #0f172a;
                }

                .ndb-booking-summary__support .support-link {
                    font-weight: 600;
                    color: #0ea5e9;
                    text-decoration: none;
                }

                .ndb-booking-form {
                    display: flex;
                    flex-direction: column;
                    gap: 1.75rem;
                }

                .ndb-booking-form__progress {
                    font-size: 0.75rem;
                    letter-spacing: 0.12em;
                    text-transform: uppercase;
                    color: #9ca3af;
                    margin: 0;
                }

                .ndb-booking-form__title {
                    margin: 0;
                    font-size: 1.85rem;
                    font-weight: 700;
                    color: #0f172a;
                }

                .ndb-booking-form__subtitle {
                    margin: 0;
                    font-size: 1rem;
                    color: #4b5563;
                    max-width: 38ch;
                }

                .ndb-booking-form__body {
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                }

                .ndb-form-section {
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                    background: #f9fafb;
                    border-radius: 1.25rem;
                    padding: 1.5rem 1.75rem;
                    border: 1px solid rgba(209, 213, 219, 0.6);
                }

                .ndb-form-section h2 {
                    margin: 0;
                    font-size: 1.05rem;
                    color: #0f172a;
                    font-weight: 600;
                }

                .ndb-form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 1rem 1.25rem;
                }

                .ndb-form-field {
                    display: flex;
                    flex-direction: column;
                    gap: 0.5rem;
                }

                .ndb-input {
                    width: 100%;
                    border-radius: 0.9rem;
                    border: 1px solid rgba(148, 163, 184, 0.6);
                    background: #ffffff;
                    padding: 0.75rem 0.9rem;
                    font-size: 0.95rem;
                    color: #0f172a;
                    transition: border-color 0.2s ease, box-shadow 0.2s ease;
                }

                .ndb-input:focus {
                    outline: none;
                    border-color: #f4b942;
                    box-shadow: 0 0 0 4px rgba(244, 185, 66, 0.2);
                }

                .ndb-form-field--file input[type="file"] {
                    padding: 0.65rem;
                }

                .ndb-help-text {
                    margin: 0;
                    font-size: 0.9rem;
                    color: #4b5563;
                }

                .ndb-checkbox {
                    display: flex;
                    gap: 0.75rem;
                    align-items: flex-start;
                }

                .ndb-checkbox__input {
                    margin-top: 0.3rem;
                    width: 1.1rem;
                    height: 1.1rem;
                }

                .ndb-checkbox__label a {
                    color: #1d4ed8;
                }

                .ndb-form-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 1rem;
                    align-items: center;
                }

                .ndb-button {
                    border: none;
                    border-radius: 999px;
                    padding: 0.85rem 2rem;
                    font-size: 1rem;
                    font-weight: 600;
                    cursor: pointer;
                    color: #1f2937;
                    background: linear-gradient(135deg, #facc15 0%, #f4b942 100%);
                    box-shadow: 0 16px 32px rgba(244, 185, 66, 0.28);
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                }

                .ndb-button:hover,
                .ndb-button:focus {
                    transform: translateY(-1px);
                    box-shadow: 0 20px 40px rgba(244, 185, 66, 0.35);
                }

                .ndb-button--ghost {
                    display: none;
                }

                @media (max-width: 1100px) {
                    .ndb-booking-wrapper {
                        flex-direction: column;
                        padding: 2rem;
                    }

                    .ndb-booking-layout__sidebar,
                    .ndb-booking-layout__main {
                        width: 100%;
                    }
                }

                @media (max-width: 640px) {
                    .ndb-booking-summary,
                    .ndb-booking-form,
                    .nd_booking_booking_alert_login_register,
                    .ndb-booking-extras {
                        border-radius: 1.1rem;
                        padding: 1.5rem;
                    }

                    .ndb-form-grid {
                        grid-template-columns: 1fr;
                    }
                }
                </style>';
            }

            $nd_booking_additional_services_markup = '';
            if ( trim( $nd_booking_additional_services ) !== '' ) {
                $nd_booking_additional_services_markup = '<div class="ndb-booking-extras">' . $nd_booking_additional_services . '</div>';
            }

            $nd_booking_shortcode_result .= '
            <div class="ndb-booking-wrapper">
                <div class="ndb-booking-layout__sidebar">
                    ' . $nd_booking_shortcode_left_content . '
                </div>
                <div class="ndb-booking-layout__main">
                    ' . $nd_booking_alert_login . '
                    ' . $nd_booking_additional_services_markup . '
                    ' . $nd_booking_shortcode_right_content . '
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





