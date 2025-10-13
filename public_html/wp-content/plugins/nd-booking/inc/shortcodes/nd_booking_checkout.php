<?php

//START  nd_booking_checkout
function nd_booking_shortcode_checkout() {

    $nd_booking_shortcode_result = '';
    
    if( isset( $_POST['nd_booking_form_booking_arrive'] ) ) {  $nd_booking_form_booking_arrive = sanitize_text_field($_POST['nd_booking_form_booking_arrive']); }else{ $nd_booking_form_booking_arrive = '';} 
    if( isset( $_POST['nd_booking_form_checkout_arrive'] ) ) {  $nd_booking_form_checkout_arrive = sanitize_text_field($_POST['nd_booking_form_checkout_arrive']); }else{ $nd_booking_form_checkout_arrive = '';} 


    //ARRIVE FROM BOOKING FORM
    if ( $nd_booking_form_booking_arrive == 1 ) {


        //get value
        $nd_booking_booking_form_final_price = sanitize_text_field($_POST['nd_booking_booking_form_final_price']);
        $nd_booking_booking_form_date_from = sanitize_text_field($_POST['nd_booking_booking_form_date_from']);
        $nd_booking_booking_form_date_to = sanitize_text_field($_POST['nd_booking_booking_form_date_to']);
        $nd_booking_booking_form_guests = sanitize_text_field($_POST['nd_booking_booking_form_guests']);
        $nd_booking_booking_form_name = sanitize_text_field($_POST['nd_booking_booking_form_name']);
        $nd_booking_booking_form_surname = sanitize_text_field($_POST['nd_booking_booking_form_surname']);
        $nd_booking_booking_form_email = sanitize_email($_POST['nd_booking_booking_form_email']);
        $nd_booking_booking_form_phone = sanitize_text_field($_POST['nd_booking_booking_form_phone']);
        $nd_booking_booking_form_address = sanitize_text_field($_POST['nd_booking_booking_form_address']);
        $nd_booking_booking_form_city = sanitize_text_field($_POST['nd_booking_booking_form_city']);
        $nd_booking_booking_form_country = sanitize_text_field($_POST['nd_booking_booking_form_country']);
        $nd_booking_booking_form_zip = sanitize_text_field($_POST['nd_booking_booking_form_zip']);
        $nd_booking_booking_form_requests = sanitize_text_field($_POST['nd_booking_booking_form_requests']);
        $nd_booking_booking_form_arrival = sanitize_text_field($_POST['nd_booking_booking_form_arrival']);
        $nd_booking_booking_form_coupon = sanitize_text_field($_POST['nd_booking_booking_form_coupon']);
        $nd_booking_booking_form_term = sanitize_text_field($_POST['nd_booking_booking_form_term']);
        $nd_booking_booking_form_post_id = sanitize_text_field($_POST['nd_booking_booking_form_post_id']);
        $nd_booking_booking_form_post_title = sanitize_text_field($_POST['nd_booking_booking_form_post_title']);
        $nd_booking_booking_form_services = sanitize_text_field($_POST['nd_booking_booking_checkbox_services_id']);

        //ids
        $nd_booking_booking_form_post_id = sanitize_text_field($_POST['nd_booking_booking_form_post_id']);
        $nd_booking_ids_array = explode('-', $nd_booking_booking_form_post_id ); 
        $nd_booking_booking_form_post_id = $nd_booking_ids_array[0];
        $nd_booking_id_room = $nd_booking_ids_array[1];

        //city tax
        if ( get_option('nd_booking_city_tax') != '' ) {
            $nd_booking_total_city_tax = get_option('nd_booking_city_tax') * $nd_booking_booking_form_guests * nd_booking_get_number_night($nd_booking_booking_form_date_from,$nd_booking_booking_form_date_to);
            $nd_booking_booking_form_final_price = $nd_booking_booking_form_final_price + $nd_booking_total_city_tax;
        }
    
        include realpath(dirname( __FILE__ ).'/include/checkout/nd_booking_checkout_left_content.php'); 
        include realpath(dirname( __FILE__ ).'/include/checkout/nd_booking_checkout_right_content.php'); 
        include realpath(dirname( __FILE__ ).'/include/checkout/nd_booking_checkout_payment_options.php'); 
        
        $nd_booking_shortcode_result .= '

        <div class="nd_booking_section">
        

            <div class="nd_booking_float_left nd_booking_width_33_percentage nd_booking_width_100_percentage_responsive nd_booking_padding_0_responsive nd_booking_padding_right_15 nd_booking_box_sizing_border_box">
                
                '.$nd_booking_shortcode_left_content.'

            </div>

            <div class="nd_booking_float_left nd_booking_width_66_percentage nd_booking_width_100_percentage_responsive nd_booking_padding_0_responsive nd_booking_padding_left_15 nd_booking_box_sizing_border_box">
                
                '.$nd_booking_shortcode_right_content.'

            </div>

        </div>
        ';

    //START PAYMENT ON CHECKOUT PAGE
    }elseif ( $nd_booking_form_checkout_arrive == 1 OR isset($_GET['tx']) OR $nd_booking_form_checkout_arrive == 2 ) {


        
        
        //START BUILT VARIABLES DEPENDING ON PAYMENT METHODS
        if ( $nd_booking_form_checkout_arrive == 1 ) {

            //transaction TX id
            $nd_booking_paypal_tx = rand(100000000,999999999);

            //get current date
            $nd_booking_date = date('H:m:s F j Y');

            //get currency
            $nd_booking_booking_form_currency = nd_booking_get_currency();

            $nd_booking_paypal_error = 0;
        
            //get value
            $nd_booking_booking_form_date_from = sanitize_text_field($_POST['nd_booking_checkout_form_date_from']);
            $nd_booking_booking_form_date_to = sanitize_text_field($_POST['nd_booking_checkout_form_date_top']);
            $nd_booking_booking_form_guests = sanitize_text_field($_POST['nd_booking_checkout_form_guests']);
            $nd_booking_booking_form_final_price = sanitize_text_field($_POST['nd_booking_checkout_form_final_price']);
            $nd_booking_checkout_form_post_id = sanitize_text_field($_POST['nd_booking_checkout_form_post_id']);
            $nd_booking_checkout_form_post_title = sanitize_text_field($_POST['nd_booking_checkout_form_post_title']);
            $nd_booking_booking_form_name = sanitize_text_field($_POST['nd_booking_checkout_form_name']);
            $nd_booking_booking_form_surname = sanitize_text_field($_POST['nd_booking_checkout_form_surname']);
            $nd_booking_booking_form_email = sanitize_email($_POST['nd_booking_checkout_form_email']);
            $nd_booking_booking_form_phone = sanitize_text_field($_POST['nd_booking_checkout_form_phone']);
            $nd_booking_booking_form_address = sanitize_text_field($_POST['nd_booking_checkout_form_address']);
            $nd_booking_booking_form_city = sanitize_text_field($_POST['nd_booking_checkout_form_city']);
            $nd_booking_booking_form_country = sanitize_text_field($_POST['nd_booking_checkout_form_country']);
            $nd_booking_booking_form_zip = sanitize_text_field($_POST['nd_booking_checkout_form_zip']);
            $nd_booking_booking_form_requests = sanitize_text_field($_POST['nd_booking_checkout_form_requets']);
            $nd_booking_booking_form_arrival = sanitize_text_field($_POST['nd_booking_checkout_form_arrival']);
            $nd_booking_booking_form_coupon = sanitize_text_field($_POST['nd_booking_checkout_form_coupon']);
            $nd_booking_booking_form_term = sanitize_text_field($_POST['nd_booking_checkout_form_term']);
            $nd_booking_booking_form_services = sanitize_text_field($_POST['nd_booking_booking_form_services']);
            $nd_booking_booking_form_action_type = sanitize_text_field($_POST['nd_booking_booking_form_action_type']);
            $nd_booking_booking_form_payment_status = sanitize_text_field($_POST['nd_booking_booking_form_payment_status']);

            //ids
            $nd_booking_checkout_form_post_id = sanitize_text_field($_POST['nd_booking_checkout_form_post_id']);
            $nd_booking_ids_array = explode('-', $nd_booking_checkout_form_post_id ); 
            $nd_booking_checkout_form_post_id = $nd_booking_ids_array[0];
            $nd_booking_id_room = $nd_booking_ids_array[1];



        //START STRIPE
        }elseif ( $nd_booking_form_checkout_arrive == 2 ) {

            //default
            $nd_booking_paypal_tx = rand(100000000,999999999);
            $nd_booking_date = date('H:m:s F j Y');
            $nd_booking_booking_form_currency = nd_booking_get_currency();
           
            //get datas
            $nd_booking_booking_form_date_from = sanitize_text_field($_POST['nd_booking_checkout_form_date_from']);
            $nd_booking_booking_form_date_to = sanitize_text_field($_POST['nd_booking_checkout_form_date_top']);
            $nd_booking_booking_form_guests = sanitize_text_field($_POST['nd_booking_checkout_form_guests']);
            $nd_booking_booking_form_final_price = sanitize_text_field($_POST['nd_booking_checkout_form_final_price']);
            $nd_booking_checkout_form_post_id = sanitize_text_field($_POST['nd_booking_checkout_form_post_id']);
            $nd_booking_checkout_form_post_title = sanitize_text_field($_POST['nd_booking_checkout_form_post_title']);
            $nd_booking_booking_form_name = sanitize_text_field($_POST['nd_booking_checkout_form_name']);
            $nd_booking_booking_form_surname = sanitize_text_field($_POST['nd_booking_checkout_form_surname']);
            $nd_booking_booking_form_email = sanitize_email($_POST['nd_booking_checkout_form_email']);
            $nd_booking_booking_form_phone = sanitize_text_field($_POST['nd_booking_checkout_form_phone']);
            $nd_booking_booking_form_address = sanitize_text_field($_POST['nd_booking_checkout_form_address']);
            $nd_booking_booking_form_city = sanitize_text_field($_POST['nd_booking_checkout_form_city']);
            $nd_booking_booking_form_country = sanitize_text_field($_POST['nd_booking_checkout_form_country']);
            $nd_booking_booking_form_zip = sanitize_text_field($_POST['nd_booking_checkout_form_zip']);
            $nd_booking_booking_form_requests = sanitize_text_field($_POST['nd_booking_checkout_form_requets']);
            $nd_booking_booking_form_arrival = sanitize_text_field($_POST['nd_booking_checkout_form_arrival']);
            $nd_booking_booking_form_coupon = sanitize_text_field($_POST['nd_booking_checkout_form_coupon']);
            $nd_booking_booking_form_term = sanitize_text_field($_POST['nd_booking_checkout_form_term']);
            $nd_booking_booking_form_services = sanitize_text_field($_POST['nd_booking_booking_form_services']);
            $nd_booking_booking_form_action_type = sanitize_text_field($_POST['nd_booking_booking_form_action_type']);
            $nd_booking_booking_form_payment_status = sanitize_text_field($_POST['nd_booking_booking_form_payment_status']);

            //ids
            $nd_booking_checkout_form_post_id = sanitize_text_field($_POST['nd_booking_checkout_form_post_id']);
            $nd_booking_ids_array = explode('-', $nd_booking_checkout_form_post_id ); 
            $nd_booking_checkout_form_post_id = $nd_booking_ids_array[0];
            $nd_booking_id_room = $nd_booking_ids_array[1];


            $nd_booking_stripe_token = sanitize_text_field($_POST['stripeToken']);

            //call the api stripe only if we are not in dev mode
            if ( get_option('nd_booking_plugin_dev_mode') == 1 ){

                $nd_booking_paypal_tx = rand(100000000,999999999);   

            }else{

                //stripe data
                $nd_booking_amount = $nd_booking_booking_form_final_price*100;
                $nd_booking_currency = get_option('nd_booking_stripe_currency');
                $nd_booking_description = $nd_booking_checkout_form_post_title.' - '.$nd_booking_booking_form_name.' '.$nd_booking_booking_form_surname.' - '.$nd_booking_booking_form_date_from.' '.$nd_booking_booking_form_date_to;
                $nd_booking_source = $nd_booking_stripe_token;
                $nd_booking_stripe_secret_key = get_option('nd_booking_stripe_secret_key');
                $nd_booking_url = 'https://api.stripe.com/v1/charges';


                //prepare the request
                $nd_booking_response = wp_remote_post( 

                    $nd_booking_url, 

                    array(
                    
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(
                            'Authorization' => 'Bearer '.$nd_booking_stripe_secret_key
                        ),
                        'body' => array( 
                            'amount' => $nd_booking_amount,
                            'currency' => $nd_booking_currency,
                            'description' => $nd_booking_description,
                            'source' => $nd_booking_source,
                            'metadata[date_from]' => $nd_booking_booking_form_date_from,
                            'metadata[date_to]' => $nd_booking_booking_form_date_to,
                            'metadata[guests]' => $nd_booking_booking_form_guests,
                            'metadata[name]' => $nd_booking_booking_form_name.' '.$nd_booking_booking_form_surname,
                            'metadata[email]' => $nd_booking_booking_form_email,
                            'metadata[phone]' => $nd_booking_booking_form_phone,
                            'metadata[address]' => $nd_booking_booking_form_address.' '.$nd_booking_booking_form_city.' '.$nd_booking_booking_form_country.' '.$nd_booking_booking_form_zip,
                            'metadata[requests]' => $nd_booking_booking_form_requests
                        ),
                        'cookies' => array()
                    
                    )
                );


                // START check the response
                $nd_booking_http_response_code = wp_remote_retrieve_response_code( $nd_booking_response );

                if ( $nd_booking_http_response_code == 200 ) {

                    $nd_booking_response_body = wp_remote_retrieve_body( $nd_booking_response );
                    $nd_booking_stripe_data = json_decode( $nd_booking_response_body );

                    if ( $nd_booking_stripe_data->paid == 1 ) { $nd_booking_booking_form_payment_status = 'Completed'; }

                    //transaction TX id
                    $nd_booking_paypal_tx = $nd_booking_stripe_data->id;

                    //get current date
                    $nd_booking_date = date('H:m:s F j Y');

                    //get currency
                    $nd_booking_booking_form_currency = nd_booking_get_currency();

                    $nd_booking_paypal_error = 0;

                }else
                {
                    //$error_message = $nd_booking_response->get_error_message();
                    $nd_booking_paypal_error = 1;
                }
                //END check the response

            }
            //end call





        //START PAYPAL
        }else{

            

            //recover datas from plugin settings
            $nd_booking_paypal_email = get_option('nd_booking_paypal_email');
            $nd_booking_paypal_currency = get_option('nd_booking_paypal_currency');
            $nd_booking_paypal_token = get_option('nd_booking_paypal_token');

            $nd_booking_paypal_developer = get_option('nd_booking_paypal_developer');
            if ( $nd_booking_paypal_developer == 1) {
              $nd_booking_paypal_action_1 = 'https://www.sandbox.paypal.com/cgi-bin';
              $nd_booking_paypal_action_2 = 'https://www.sandbox.paypal.com/cgi-bin/webscr'; 
            }
            else{  
              $nd_booking_paypal_action_1 = 'https://www.paypal.com/cgi-bin';
              $nd_booking_paypal_action_2 = 'https://www.paypal.com/cgi-bin/webscr';
            }

            //transaction TX id
            $nd_booking_paypal_tx = sanitize_text_field($_GET['tx']);
            $nd_booking_paypal_url = $nd_booking_paypal_action_2;



            //prepare the request
            $nd_booking_paypal_response = wp_remote_post( 

                $nd_booking_paypal_url, 

                array(
                
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => array( 
                        'cmd' => '_notify-synch',
                        'tx' => $nd_booking_paypal_tx,
                        'at' => $nd_booking_paypal_token
                    ),
                    'cookies' => array()
                
                )
            );

            $nd_booking_http_paypal_response_code = wp_remote_retrieve_response_code( $nd_booking_paypal_response );

            //START if is 200
            if ( $nd_booking_http_paypal_response_code == 200 ) {

                $nd_booking_paypal_response_body = wp_remote_retrieve_body( $nd_booking_paypal_response );

                //START if is success
                if ( strpos($nd_booking_paypal_response_body, 'SUCCESS') === 0 ) {

                    $nd_booking_paypal_response = substr($nd_booking_paypal_response_body, 7);
                    $nd_booking_paypal_response = urldecode($nd_booking_paypal_response);
                    preg_match_all('/^([^=\s]++)=(.*+)/m', $nd_booking_paypal_response, $m, PREG_PATTERN_ORDER);
                    $nd_booking_paypal_response = array_combine($m[1], $m[2]);


                    if(isset($nd_booking_paypal_response['charset']) AND strtoupper($nd_booking_paypal_response['charset']) !== 'UTF-8')
                    {
                      foreach($nd_booking_paypal_response as $key => &$value)
                      {
                        $value = mb_convert_encoding($value, 'UTF-8', $nd_booking_paypal_response['charset']);
                      }
                      $nd_booking_paypal_response['charset_original'] = $nd_booking_paypal_response['charset'];
                      $nd_booking_paypal_response['charset'] = 'UTF-8';
                    }

                    ksort($nd_booking_paypal_response);

                    //get value
                    $nd_booking_date = $nd_booking_paypal_response['payment_date'];
                    $nd_booking_booking_form_final_price = $nd_booking_paypal_response['mc_gross'];
                    
                    //ids
                    $nd_booking_checkout_form_post_id = $nd_booking_paypal_response['item_number'];
                    $nd_booking_ids_array = explode('-', $nd_booking_checkout_form_post_id ); 
                    $nd_booking_checkout_form_post_id = $nd_booking_ids_array[0];
                    $nd_booking_id_room = $nd_booking_ids_array[1];

                    $nd_booking_checkout_form_post_title = get_the_title($nd_booking_checkout_form_post_id);
                    
                    //user info
                    $nd_booking_booking_form_name = $nd_booking_paypal_response['first_name'];
                    $nd_booking_booking_form_surname = $nd_booking_paypal_response['last_name'];
                    $nd_booking_booking_form_email = $nd_booking_paypal_response['payer_email'];
                    $nd_booking_booking_form_address = $nd_booking_paypal_response['address_street'];
                    $nd_booking_booking_form_city = $nd_booking_paypal_response['address_city'];
                    $nd_booking_booking_form_country = $nd_booking_paypal_response['address_country'];
                    $nd_booking_booking_form_zip = $nd_booking_paypal_response['address_zip'];

                    //transiction details
                    $nd_booking_booking_form_currency = $nd_booking_paypal_response['mc_currency'];
                    $nd_booking_booking_form_action_type = 'paypal';
                    $nd_booking_booking_form_payment_status = $nd_booking_paypal_response['payment_status'];

                    //null
                    $nd_booking_booking_form_term = '';
                    $nd_booking_paypal_error = 0;

                    //START extract custom filed
                    $nd_booking_custom_field_array = explode('[ndbcpm]', $nd_booking_paypal_response['custom']);
                    $nd_booking_booking_form_date_from = $nd_booking_custom_field_array[0];
                    $nd_booking_booking_form_date_to = $nd_booking_custom_field_array[1];
                    $nd_booking_booking_form_guests = $nd_booking_custom_field_array[2];
                    $nd_booking_booking_form_phone = $nd_booking_custom_field_array[3];
                    $nd_booking_booking_form_arrival = $nd_booking_custom_field_array[4];
                    $nd_booking_booking_form_services = $nd_booking_custom_field_array[5];
                    $nd_booking_booking_form_requests = $nd_booking_custom_field_array[6];
                    $nd_booking_booking_form_coupon = $nd_booking_custom_field_array[7];

                }else{
                    
                    $nd_booking_paypal_error = 1;

                }
                //END if is success


            }else
            {
                //$error_message = $nd_booking_paypal_response->get_error_message();
                $nd_booking_paypal_error = 1;
            }
            //END if is 200



        }
        //END BUILT VARIABLES DEPENDING ON PAYMENT METHODS





        //START extra services
        $nd_booking_booking_form_extra_services = '';

        $nd_booking_additional_services_array = explode(',', $nd_booking_booking_form_services );
        for ($nd_booking_additional_services_array_i = 0; $nd_booking_additional_services_array_i < count($nd_booking_additional_services_array)-1; $nd_booking_additional_services_array_i++) {
            
            $nd_booking_service_id = $nd_booking_additional_services_array[$nd_booking_additional_services_array_i];

            //metabox
            $nd_booking_meta_box_cpt_2_price = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_price', true );
            $nd_booking_meta_box_cpt_2_price_type_1 = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_price_type_1', true );
            if ( $nd_booking_meta_box_cpt_2_price_type_1 == '' ) { $nd_booking_meta_box_cpt_2_price_type_1 = 'nd_booking_price_type_person'; }
            $nd_booking_meta_box_cpt_2_price_type_2 = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_price_type_2', true );
            if ( $nd_booking_meta_box_cpt_2_price_type_2 == '' ) { $nd_booking_meta_box_cpt_2_price_type_2 = 'nd_booking_price_type_day'; }

            //operator
            if ( $nd_booking_meta_box_cpt_2_price_type_1 == 'nd_booking_price_type_person' ) {
                $nd_booking_operator_1 = $nd_booking_booking_form_guests;
            }else{
                $nd_booking_operator_1 = 1; 
            }
            if ( $nd_booking_meta_box_cpt_2_price_type_2 == 'nd_booking_price_type_day' ) {
                $nd_booking_operator_2 = nd_booking_get_number_night($nd_booking_booking_form_date_from,$nd_booking_booking_form_date_to);
            }else{
                $nd_booking_operator_2 = 1; 
            }
            
            $nd_booking_additional_service_total_price = $nd_booking_meta_box_cpt_2_price*$nd_booking_operator_1*$nd_booking_operator_2;

            $nd_booking_booking_form_extra_services .= $nd_booking_service_id.'['.$nd_booking_additional_service_total_price.'],';

        }
        //END extra services

        
        //translations action type
        if ( $nd_booking_booking_form_action_type == 'bank_transfer' ) {
            $nd_booking_booking_form_action_type_lang = __('Bank Transfer','nd-booking');
        }elseif ( $nd_booking_booking_form_action_type == 'payment_on_arrive' ) {
            $nd_booking_booking_form_action_type_lang = __('Payment on arrive','nd-booking');
        }elseif ( $nd_booking_booking_form_action_type == 'booking_request' ) {
            $nd_booking_booking_form_action_type_lang = __('Booking Request','nd-booking');
        }elseif ( $nd_booking_booking_form_action_type == 'stripe' ) {
            $nd_booking_booking_form_action_type_lang = __('Stripe','nd-booking');
        }else{
            $nd_booking_booking_form_action_type_lang = __('Paypal','nd-booking');   
        }

        include realpath(dirname( __FILE__ ).'/include/thankyou/nd_booking_thankyou_left_content.php'); 
        include realpath(dirname( __FILE__ ).'/include/thankyou/nd_booking_thankyou_right_content.php'); 
        
        $nd_booking_shortcode_result .= '

        <div class="nd_booking_section">
        

            <div class="nd_booking_float_left nd_booking_width_33_percentage nd_booking_width_100_percentage_responsive nd_booking_padding_0_responsive nd_booking_padding_right_15 nd_booking_box_sizing_border_box">
                
                '.$nd_booking_shortcode_left_content.'

            </div>

            <div class="nd_booking_float_left nd_booking_width_66_percentage nd_booking_width_100_percentage_responsive nd_booking_padding_0_responsive nd_booking_padding_left_15 nd_booking_box_sizing_border_box">
                
                '.$nd_booking_shortcode_right_content.'

            </div>

        </div>
        ';


        //START check if user is logged
        if ( is_user_logged_in() == 1 ) {
          $nd_booking_current_user = wp_get_current_user();
          $nd_booking_current_user_id = $nd_booking_current_user->ID;
        }else{
          $nd_booking_current_user_id = 0; 
        }
        //END check if user is logged


        nd_booking_add_booking_in_db(
  
          $nd_booking_id_room,
          get_the_title($nd_booking_id_room),
          $nd_booking_date,
          $nd_booking_booking_form_date_from,
          $nd_booking_booking_form_date_to,
          $nd_booking_booking_form_guests,
          $nd_booking_booking_form_final_price,
          $nd_booking_booking_form_extra_services,
          $nd_booking_current_user_id,
          $nd_booking_booking_form_name,
          $nd_booking_booking_form_surname,
          $nd_booking_booking_form_email,
          $nd_booking_booking_form_phone,
          $nd_booking_booking_form_address.' '.$nd_booking_booking_form_zip,
          $nd_booking_booking_form_city,
          $nd_booking_booking_form_country,
          $nd_booking_booking_form_requests,
          $nd_booking_booking_form_arrival,
          $nd_booking_booking_form_coupon,
          $nd_booking_booking_form_payment_status,
          $nd_booking_booking_form_currency,
          $nd_booking_paypal_tx,
          $nd_booking_booking_form_action_type

        );
    //END EASY PAYMENT
    }else{
    



        $nd_booking_shortcode_result .= '

            <div class="nd_booking_section">
            
                <div class="nd_booking_float_left nd_booking_width_100_percentage nd_booking_box_sizing_border_box">
                    <p>'.__('Please select a room to make a reservation','nd-booking').'</p>
                    <div class="nd_booking_section nd_booking_height_20"></div>
                    <a href="'.nd_booking_search_page().'" class="nd_booking_bg_yellow nd_booking_padding_15_30_important nd_options_second_font_important nd_booking_border_radius_0_important nd_options_color_white nd_booking_cursor_pointer nd_booking_display_inline_block nd_booking_font_size_11 nd_booking_font_weight_bold nd_booking_letter_spacing_2">'.__('RETURN TO SEARCH PAGE','nd-booking').'</a>
                </div>

            </div>
        
        '; 

    }


    return $nd_booking_shortcode_result;
		


}
add_shortcode('nd_booking_checkout', 'nd_booking_shortcode_checkout');
//END nd_booking_checkout






