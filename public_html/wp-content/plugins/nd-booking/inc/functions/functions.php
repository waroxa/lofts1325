<?php


function nd_booking_get_final_price($nd_booking_id,$nd_booking_date){

	$nd_booking_get_final_price = '';

	//date
	$nd_booking_new_date = new DateTime($nd_booking_date);
	$nd_booking_new_date_format_mdy = date_format($nd_booking_new_date, 'm/d/Y');
	$nd_booking_new_date_format_n = date_format($nd_booking_new_date, 'N');

	//default price
	$nd_booking_price = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_price', true );

	//week price
	$nd_booking_price_mon = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_week_price_mon', true );
    $nd_booking_price_tue = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_week_price_tue', true );
    $nd_booking_price_wed = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_week_price_wed', true );
    $nd_booking_price_thu = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_week_price_thu', true );
    $nd_booking_price_fri = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_week_price_fri', true );
    $nd_booking_price_sat = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_week_price_sat', true );
    $nd_booking_price_sun = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_week_price_sun', true );
    $nd_booking_price_week = array($nd_booking_price_mon,$nd_booking_price_tue,$nd_booking_price_wed,$nd_booking_price_thu,$nd_booking_price_fri,$nd_booking_price_sat,$nd_booking_price_sun);

	//exception
    $nd_booking_exceptions = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_exceptions', true );


    if ( $nd_booking_exceptions != '' ) {

    	$nd_booking_meta_box_exceptions_array = explode(',', $nd_booking_exceptions );
    	
    	//START CICLE
		for ($nd_booking_meta_box_exceptions_array_i = 0; $nd_booking_meta_box_exceptions_array_i < count($nd_booking_meta_box_exceptions_array)-1; $nd_booking_meta_box_exceptions_array_i++) {
		    
		    $nd_booking_page_by_path = get_page_by_path($nd_booking_meta_box_exceptions_array[$nd_booking_meta_box_exceptions_array_i],OBJECT,'nd_booking_cpt_3');
		    
		    //info service
		    $nd_booking_exception_id = $nd_booking_page_by_path->ID;
		    $nd_booking_exception_name = get_the_title($nd_booking_exception_id);

		    //metabox
		    $nd_booking_meta_box_cpt_3_exceptions_type = get_post_meta( $nd_booking_exception_id, 'nd_booking_meta_box_cpt_3_exceptions_type', true );
		    if ( $nd_booking_meta_box_cpt_3_exceptions_type == '' ) { $nd_booking_meta_box_cpt_3_exceptions_type = 'nd_booking_custom_price'; }
		    $nd_booking_meta_box_cpt_3_price = get_post_meta( $nd_booking_exception_id, 'nd_booking_meta_box_cpt_3_price', true ); 
		    $nd_booking_meta_box_cpt_3_date_range_from = get_post_meta( $nd_booking_exception_id, 'nd_booking_meta_box_cpt_3_date_range_from', true ); 
		    $nd_booking_meta_box_cpt_3_date_range_to = get_post_meta( $nd_booking_exception_id, 'nd_booking_meta_box_cpt_3_date_range_to', true ); 

		    //convert date for override wp format
		    $nd_booking_meta_box_cpt_3_date_range_from = date('m/d/Y', strtotime($nd_booking_meta_box_cpt_3_date_range_from));
		    $nd_booking_meta_box_cpt_3_date_range_to = date('m/d/Y', strtotime($nd_booking_meta_box_cpt_3_date_range_to));

		    //calculate if the date is between the range
			$nd_booking_new_date_from = new DateTime($nd_booking_meta_box_cpt_3_date_range_from  );
			$nd_booking_new_date_from_format = date_format($nd_booking_new_date_from, 'm/d/Y');

			$nd_booking_new_date_to = new DateTime($nd_booking_meta_box_cpt_3_date_range_to);
			$nd_booking_new_date_to_format = date_format($nd_booking_new_date_to, 'm/d/Y');

			$nd_booking_date_new = new DateTime($nd_booking_date);
			$nd_booking_date_new_format = date_format($nd_booking_date_new, 'm/d/Y');

			if ( $nd_booking_date_new_format >= $nd_booking_new_date_from_format && $nd_booking_date_new_format  <= $nd_booking_new_date_to_format AND $nd_booking_meta_box_cpt_3_exceptions_type == 'nd_booking_custom_price' ) {
				
				#printtest 'id: '.$nd_booking_id.' - data passata '.$nd_booking_date_new_format.' inclusa nel range ( da '.$nd_booking_new_date_from_format.' a '.$nd_booking_new_date_to_format.' ) -> COSTO FINALE : '.$nd_booking_meta_box_cpt_3_price.'<br/>';

				$nd_booking_get_final_price = $nd_booking_meta_box_cpt_3_price;

				return $nd_booking_get_final_price;

			}else{
				
				if ( $nd_booking_price_week[$nd_booking_new_date_format_n-1] != '' ) {

			    	$nd_booking_get_final_price = $nd_booking_price_week[$nd_booking_new_date_format_n-1];	

			    }else{

			    	$nd_booking_get_final_price = $nd_booking_price;

			    }
				#printtest 'id: '.$nd_booking_id.' - data passata '.$nd_booking_date_new_format.' NON inclusa nel range ( da '.$nd_booking_new_date_from_format.' a '.$nd_booking_new_date_to_format.' ) -> COSTO FINALE : '.$nd_booking_get_final_price.'<br/>';	

			}


		}
		//END CICLE

    	return  $nd_booking_get_final_price;

    }else{

    	if ( $nd_booking_price_week[$nd_booking_new_date_format_n-1] != '' ) {

	    	$nd_booking_get_final_price = $nd_booking_price_week[$nd_booking_new_date_format_n-1];	

	    }else{

	    	$nd_booking_get_final_price = $nd_booking_price;

	    }

	    #printtest 'id: '.$nd_booking_id.' - data passata '.$nd_booking_new_date_format_mdy.' non soggetta ad eccezzione  -> COSTO FINALE : '.$nd_booking_get_final_price.'<br/>';	

	    return  $nd_booking_get_final_price;

    }



}



function nd_booking_get_next_prev_month_year($nd_booking_date,$nd_booking_month_year,$nd_booking_next_prev){

	if ($nd_booking_next_prev == 'next') {
		$nd_booking_get_next_month_year = date('Y-m-d', strtotime($nd_booking_date.' + 1 month'));
	}else{
		$nd_booking_get_next_month_year = date('Y-m-d', strtotime($nd_booking_date.' - 1 month'));	
	}

	$nd_booking_get_next_month_year_new_date = new DateTime($nd_booking_get_next_month_year);

	if ($nd_booking_month_year == 'month') {
		$nd_booking_next_m_y = date_format($nd_booking_get_next_month_year_new_date,'m');
	}else{
		$nd_booking_next_m_y = date_format($nd_booking_get_next_month_year_new_date,'Y');
	}

    return $nd_booking_next_m_y;

}


function nd_booking_get_month_name($nd_booking_date){

	$nd_booking_get_month_name = date('Y-m-d', strtotime($nd_booking_date));	
	$nd_booking_get_month_name_new = new DateTime($nd_booking_get_month_name);
	$nd_booking_get_month = date_format($nd_booking_get_month_name_new,'F');
	
    return $nd_booking_get_month;

}

function nd_booking_get_day_number($nd_booking_date){

	$nd_booking_get_day_number = date('Y-m-d', strtotime($nd_booking_date));	
	$nd_booking_get_day_number_new = new DateTime($nd_booking_get_day_number);
	$nd_booking_get_day = date_format($nd_booking_get_day_number_new,'j');
	
    return $nd_booking_get_day;

}


function nd_booking_is_correct_date($nd_booking_date,$nd_booking_format)
{
	$nd_booking_d = DateTime::createFromFormat($nd_booking_format, $nd_booking_date);
	return $nd_booking_d && $nd_booking_d->format($nd_booking_format) == $nd_booking_date;
}


function nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to){

	$nd_booking_get_number_night = 0;

	$nd_booking_date_from_2 = new DateTime($nd_booking_date_from);
	$nd_booking_date_to_2 = new DateTime($nd_booking_date_to);
	
	$nd_booking_date_from_format = date_format($nd_booking_date_from_2, 'Y/m/d');
	$nd_booking_date_to_format = date_format($nd_booking_date_to_2, 'Y/m/d');

	$nd_booking_date_cicle = $nd_booking_date_from_format;


	while( $nd_booking_date_cicle <= $nd_booking_date_to_format ) {
	    
	    $nd_booking_date_cicle = date('Y/m/d', strtotime($nd_booking_date_cicle.' + 1 days'));
	    
	    #printtest $nd_booking_get_number_night.' - '.$nd_booking_date_cicle.' - '.$nd_booking_date_to_format.'<br/>';
	    $nd_booking_get_number_night = $nd_booking_get_number_night+1;

	} 

	return $nd_booking_get_number_night-1;

}



function nd_booking_get_room_link($nd_booking_id,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_archive_form_guests){

	$nd_booking_permalink = get_permalink( $nd_booking_id );
	$nd_booking_meta_box_room_custom_link = get_post_meta( get_the_ID(), 'nd_booking_meta_box_room_custom_link', true );
	$nd_booking_meta_box_room_integration = get_post_meta( get_the_ID(), 'nd_booking_meta_box_room_integration', true );

	//woo integration
	$nd_booking_meta_box_room_woo_product = get_post_meta( get_the_ID(), 'nd_booking_meta_box_room_woo_product', true );
	if ( $nd_booking_meta_box_room_woo_product == '' ){ $nd_booking_meta_box_room_woo_product = 0; }
	
	//format date
	$nd_booking_date_from_1 = new DateTime($nd_booking_date_from);
	$nd_booking_date_to_1 = new DateTime($nd_booking_date_to);
	$nd_booking_date_1_from = date_format($nd_booking_date_from_1, 'Y-m-d');
	$nd_booking_date_1_to = date_format($nd_booking_date_to_1, 'Y-m-d');


	if ( $nd_booking_meta_box_room_custom_link == '' ) {
		$nd_booking_get_room_link = $nd_booking_permalink;
	}else{

		//booking
		if ( $nd_booking_meta_box_room_integration == 'nd_booking_meta_box_room_integration_booking' ) {

			$nd_booking_get_room_link = $nd_booking_meta_box_room_custom_link.'?checkin='.$nd_booking_date_1_from.';checkout='.$nd_booking_date_1_to.';group_adults='.$nd_booking_archive_form_guests.';';

		//airbnb
		}elseif ( $nd_booking_meta_box_room_integration == 'nd_booking_meta_box_room_integration_airbnb' ){

			$nd_booking_get_room_link = $nd_booking_meta_box_room_custom_link.'?check_in='.$nd_booking_date_1_from.'&guests='.$nd_booking_archive_form_guests.'&check_out='.$nd_booking_date_1_to;	

		//hostelworld
		}elseif ( $nd_booking_meta_box_room_integration == 'nd_booking_meta_box_room_integration_hostelworld' ){

			$nd_booking_get_room_link = $nd_booking_meta_box_room_custom_link.'?dateFrom='.$nd_booking_date_1_from.'&dateTo='.$nd_booking_date_1_to.'&number_of_guests='.$nd_booking_archive_form_guests;

		//tripadvisor
		}elseif ( $nd_booking_meta_box_room_integration == 'nd_booking_meta_box_room_integration_tripadvisor' ){

			$nd_booking_get_room_link = $nd_booking_meta_box_room_custom_link;
		
		//custom
		}else{

			$nd_booking_get_room_link = $nd_booking_meta_box_room_custom_link;

		}

	}

	//woo
	if ( $nd_booking_meta_box_room_woo_product != 0 ) {
		$nd_booking_get_room_link = wc_get_checkout_url();	
	}


	return $nd_booking_get_room_link;

}


function nd_booking_is_available_block($nd_booking_id,$nd_booking_date_from,$nd_booking_date_to){

	//get dates
	$nd_booking_new_date_from = new DateTime($nd_booking_date_from);
	$nd_booking_new_date_from_format = date_format($nd_booking_new_date_from, 'Y/m/d');
	$nd_booking_new_date_to = new DateTime($nd_booking_date_to);
	$nd_booking_new_date_to_format = date_format($nd_booking_new_date_to, 'Y/m/d');
	$nd_booking_number_night_range_1 = nd_booking_get_number_night($nd_booking_new_date_from_format,$nd_booking_new_date_to_format);

	//set result
	$nd_booking_is_available_block = 1;

	//get exception of selected room
    $nd_booking_exceptions_block = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_exceptions_block', true );


    if ( $nd_booking_exceptions_block != '' ) {

    	$nd_booking_meta_box_exceptions_array = explode(',', $nd_booking_exceptions_block );
    	
    	//START CICLE per numero eccezzioni
		for ($nd_booking_meta_box_exceptions_array_i = 0; $nd_booking_meta_box_exceptions_array_i < count($nd_booking_meta_box_exceptions_array)-1; $nd_booking_meta_box_exceptions_array_i++) {
		    
			$nd_booking_new_date_from = new DateTime($nd_booking_date_from);
			$nd_booking_new_date_from_format = date_format($nd_booking_new_date_from, 'Y/m/d');
			$nd_booking_new_date_to = new DateTime($nd_booking_date_to);
			$nd_booking_new_date_to_format = date_format($nd_booking_new_date_to, 'Y/m/d');


		    $nd_booking_page_by_path = get_page_by_path($nd_booking_meta_box_exceptions_array[$nd_booking_meta_box_exceptions_array_i],OBJECT,'nd_booking_cpt_3');
		    
		    //info exception
		    $nd_booking_exception_id = $nd_booking_page_by_path->ID;
		    $nd_booking_exception_name = get_the_title($nd_booking_exception_id);
		    $nd_booking_meta_box_cpt_3_date_range_from = get_post_meta( $nd_booking_exception_id, 'nd_booking_meta_box_cpt_3_date_range_from', true ); 
		    $nd_booking_meta_box_cpt_3_date_range_to = get_post_meta( $nd_booking_exception_id, 'nd_booking_meta_box_cpt_3_date_range_to', true ); 

		    //calculate if the date is between the range
			$nd_booking_new_date_from_ex = new DateTime($nd_booking_meta_box_cpt_3_date_range_from  );
			$nd_booking_new_date_from_ex_format = date_format($nd_booking_new_date_from_ex, 'Y/m/d');
			$nd_booking_new_date_to_ex = new DateTime($nd_booking_meta_box_cpt_3_date_range_to);
			$nd_booking_new_date_to_ex_format = date_format($nd_booking_new_date_to_ex, 'Y/m/d');
			$nd_booking_number_night_range_2 = nd_booking_get_number_night($nd_booking_new_date_from_ex_format,$nd_booking_new_date_to_ex_format)+1;
			

			//start cicle  per date utente
			for ($nd_booking_i_1 = 1; $nd_booking_i_1 <= $nd_booking_number_night_range_1; $nd_booking_i_1++ ) {
			    
			    $nd_booking_new_date_from_ex_format = date_format($nd_booking_new_date_from_ex, 'Y/m/d');

				//start cicle per date eccezzioni
				for ($nd_booking_i_2 = 1; $nd_booking_i_2 <= $nd_booking_number_night_range_2; $nd_booking_i_2++) {


					if ( $nd_booking_new_date_from_format == $nd_booking_new_date_from_ex_format ) {

						$nd_booking_is_available_block = 0;
						return $nd_booking_is_available_block;
						
					}


					$nd_booking_new_date_from_ex_format = date('Y/m/d', strtotime($nd_booking_new_date_from_ex_format.' + 1 days'));	

				}
				//end cicle 2

				$nd_booking_new_date_from_format = date('Y/m/d', strtotime($nd_booking_new_date_from_format.' + 1 days'));	
					
			}
			//end cicle 1
			
		}
		//END CICLE

    	

    }else{

		return $nd_booking_is_available_block;  

    }


	return $nd_booking_is_available_block;  
    

}


function nd_booking_is_available($nd_booking_id,$nd_booking_date_from,$nd_booking_date_to){

	//date_2 are already booked dates
	//date_1 are the dates of the search

	//converte date_1
	$nd_booking_date_from_1 = new DateTime($nd_booking_date_from);
	$nd_booking_date_to_1 = new DateTime($nd_booking_date_to);
	$nd_booking_date_1_from = date_format($nd_booking_date_from_1, 'Y/m/d');
	$nd_booking_date_1_to = date_format($nd_booking_date_to_1, 'Y/m/d');

	//range date_1
	$nd_booking_number_night_range_1 = nd_booking_get_number_night($nd_booking_date_1_from,$nd_booking_date_1_to);

	global $wpdb;

	$nd_booking_table_name = $wpdb->prefix . 'nd_booking_booking';
	$nd_booking_booking_form_payment_status = 'Pending';

	$nd_booking_dates_query = $wpdb->prepare( "SELECT date_from,date_to FROM $nd_booking_table_name WHERE id_post = %d AND paypal_payment_status <> %s", array( $nd_booking_id, $nd_booking_booking_form_payment_status ) );
	$nd_booking_dates = $wpdb->get_results( $nd_booking_dates_query ); 

	$nd_booking_avaiability_string = '';

	//no results
	if ( empty($nd_booking_dates) ) { 

	return $nd_booking_avaiability_string;

	}else{

		foreach ( $nd_booking_dates as $nd_booking_date ) 
	    {
			
	    	$nd_booking_date_1_from = date_format($nd_booking_date_from_1, 'Y/m/d');

	    	//converte date_2
			$nd_booking_date_from_booked = $nd_booking_date->date_from; 
			$nd_booking_date_to_booked = $nd_booking_date->date_to; 
			$nd_booking_date_from_2 = new DateTime($nd_booking_date_from_booked);
			$nd_booking_date_to_2 = new DateTime($nd_booking_date_to_booked);
			$nd_booking_date_2_from = date_format($nd_booking_date_from_2, 'Y/m/d');
			$nd_booking_date_2_to = date_format($nd_booking_date_to_2, 'Y/m/d');

			//range date_2
			$nd_booking_number_night_range_2 = nd_booking_get_number_night($nd_booking_date_2_from,$nd_booking_date_2_to);
			
			//start cicle 1
			for ($nd_booking_i_1 = 1; $nd_booking_i_1 <= $nd_booking_number_night_range_1; $nd_booking_i_1++ ) {
			    
			    $nd_booking_date_2_from = date_format($nd_booking_date_from_2, 'Y/m/d');

				//start cicle 2
				for ($nd_booking_i_2 = 1; $nd_booking_i_2 <= $nd_booking_number_night_range_2; $nd_booking_i_2++) {

					if ( $nd_booking_date_1_from == $nd_booking_date_2_from ) {
						$nd_booking_avaiability_string .= $nd_booking_date_1_from.'-';
					}

					$nd_booking_date_2_from = date('Y/m/d', strtotime($nd_booking_date_2_from.' + 1 days'));	

				}
				//end cicle 2

				$nd_booking_date_1_from = date('Y/m/d', strtotime($nd_booking_date_1_from.' + 1 days'));	
					
			}
			//end cicle 1
			

	    }

	    return $nd_booking_avaiability_string;
	     
	}



}




function nd_booking_is_qnt_available($nd_booking_strings_dates_orders,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_id){

    //range date
    $nd_booking_range_night = nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to);

    //get room qnt
    $nd_booking_meta_box_qnt = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_qnt', true );
    if ( $nd_booking_meta_box_qnt == '' ) { $nd_booking_meta_box_qnt = 1; }

    //convert date
    $nd_booking_new_date = new DateTime($nd_booking_date_from);
    $nd_booking_date_incr = date_format($nd_booking_new_date, 'Y/m/d');
    

    if ( $nd_booking_strings_dates_orders != '' ) {

    	for ($nd_booking_i = 1; $nd_booking_i <= $nd_booking_range_night; $nd_booking_i++) {

	        $nd_booking_num_reservations_per_day = substr_count($nd_booking_strings_dates_orders,$nd_booking_date_incr); 

	        if ( $nd_booking_num_reservations_per_day >= $nd_booking_meta_box_qnt ) {
	            return 0;
	        }  

	        $nd_booking_date_incr = date('Y/m/d', strtotime($nd_booking_date_incr.' + 1 days'));

	    }

    }

    return 1;

}




function nd_booking_qnt_room_bookable($nd_booking_strings_dates_orders,$nd_booking_id,$nd_booking_date_from,$nd_booking_date_to){


	if ( get_post_meta($nd_booking_id,'nd_booking_meta_box_qnt', true ) != '' ) {

		if ( $nd_booking_strings_dates_orders != '' ) {

			$nd_booking_qnt_room = 0;

			//range date
    		$nd_booking_range_night = nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to);

    		//get room qnt
		    $nd_booking_meta_box_qnt = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_qnt', true );
		    if ( $nd_booking_meta_box_qnt == '' ) { $nd_booking_meta_box_qnt = 1; }

			//convert date
		    $nd_booking_new_date = new DateTime($nd_booking_date_from);
		    $nd_booking_date_incr = date_format($nd_booking_new_date, 'Y/m/d');


		    for ($nd_booking_i = 1; $nd_booking_i <= $nd_booking_range_night; $nd_booking_i++) {

		        $nd_booking_num_reservations_per_day = substr_count($nd_booking_strings_dates_orders,$nd_booking_date_incr); 

		        if ( $nd_booking_num_reservations_per_day >= $nd_booking_qnt_room ) {
		        	$nd_booking_qnt_room = $nd_booking_num_reservations_per_day;
		        }
		     	
		        $nd_booking_date_incr = date('Y/m/d', strtotime($nd_booking_date_incr.' + 1 days'));

		    }


		    $nd_booking_room_left = $nd_booking_meta_box_qnt - $nd_booking_qnt_room;


		    if ( $nd_booking_room_left == 1 ){

		    	return '<span class="nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_color_3">'.__('THIS IS THE LAST ROOM AT THIS PRICE','nd-booking').'</span>';

		    }elseif ( $nd_booking_room_left <= 3 ){

		    	return '<span class="nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_top_10 nd_booking_padding_top_5 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_color_3">'.__('ONLY','nd-booking').' '.$nd_booking_room_left.' '.__('ROOM LEFT AT THIS PRICE','nd-booking').'</span>';

		    }

		}

	}

}




function nd_booking_get_post_img_src($nd_booking_id){

	$nd_booking_image_id = get_post_thumbnail_id($nd_booking_id);
	$nd_booking_image_attributes = wp_get_attachment_image_src( $nd_booking_image_id, 'large' );
	$nd_booking_img_src = $nd_booking_image_attributes[0];

	return $nd_booking_img_src;

}




/* **************************************** START DATABASE **************************************** */


//function for add order in db
function nd_booking_check_if_order_is_present($nd_booking_id_post,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_paypal_email,$nd_booking_action_type){

	global $wpdb;

	$nd_booking_table_name = $wpdb->prefix . 'nd_booking_booking';

	//START query
	$nd_booking_order_ids_query = $wpdb->prepare( "SELECT id FROM $nd_booking_table_name WHERE id_post = %d AND date_from = %s AND date_to = %s AND paypal_email = %s AND action_type = %s", array( $nd_booking_id_post, $nd_booking_date_from, $nd_booking_date_to, $nd_booking_paypal_email, $nd_booking_action_type ) );
	$nd_booking_order_ids = $wpdb->get_results( $nd_booking_order_ids_query ); 

	//no results
	if ( empty($nd_booking_order_ids) ) { 

	return 0;

	}else{

	return 1;

	}

}


//function for add order in db
function nd_booking_add_booking_in_db(
  
  $nd_booking_id_post,
  $nd_booking_title_post,
  $nd_booking_date,
  $nd_booking_date_from,
  $nd_booking_date_to,
  $nd_booking_guests,
  $nd_booking_final_trip_price,
  $nd_booking_extra_services,
  $nd_booking_id_user,
  $nd_booking_user_first_name,
  $nd_booking_user_last_name,
  $nd_booking_paypal_email,
  $nd_booking_user_phone,
  $nd_booking_user_address,
  $nd_booking_user_city,
  $nd_booking_user_country,
  $nd_booking_user_message,
  $nd_booking_user_arrival,
  $nd_booking_user_coupon,
  $nd_booking_paypal_payment_status,
  $nd_booking_paypal_currency,
  $nd_booking_paypal_tx,
  $nd_booking_action_type

) {



	//START add order if the plugin is not in dev mode
	if ( get_option('nd_booking_plugin_dev_mode') == 1 ){

		//dev mode active not insert in db

	}else{
		

		if ( nd_booking_check_if_order_is_present($nd_booking_id_post,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_paypal_email,$nd_booking_action_type) == 0 ) {

			global $wpdb;
			$nd_booking_table_name = $wpdb->prefix . 'nd_booking_booking';


			//START INSERT DB
			$nd_booking_add_booking = $wpdb->insert( 

			$nd_booking_table_name, 

			array( 

				'id_post' => $nd_booking_id_post,
				'title_post' => $nd_booking_title_post,
				'date' => $nd_booking_date,
				'date_from' => $nd_booking_date_from,
				'date_to' => $nd_booking_date_to,
				'guests' => $nd_booking_guests,
				'final_trip_price' => $nd_booking_final_trip_price,
				'extra_services' => $nd_booking_extra_services,
				'id_user' => $nd_booking_id_user,
				'user_first_name' => $nd_booking_user_first_name,
				'user_last_name' => $nd_booking_user_last_name,
				'paypal_email' => $nd_booking_paypal_email,
				'user_phone' => $nd_booking_user_phone,
				'user_address' => $nd_booking_user_address,
				'user_city' => $nd_booking_user_city,
				'user_country' => $nd_booking_user_country,
				'user_message' => $nd_booking_user_message,
				'user_arrival' => $nd_booking_user_arrival,
				'user_coupon' => $nd_booking_user_coupon,
				'paypal_payment_status' => $nd_booking_paypal_payment_status,
				'paypal_currency' => $nd_booking_paypal_currency,
				'paypal_tx' => $nd_booking_paypal_tx,
				'action_type' => $nd_booking_action_type

			)

			);

			if ($nd_booking_add_booking){

				//order added in db
			
				//hook
	        	do_action('nd_booking_reservation_added_in_db',$nd_booking_id_post,$nd_booking_title_post,$nd_booking_date,$nd_booking_date_from,$nd_booking_date_to,$nd_booking_guests,$nd_booking_final_trip_price,$nd_booking_extra_services,$nd_booking_id_user,$nd_booking_user_first_name,$nd_booking_user_last_name,$nd_booking_paypal_email,$nd_booking_user_phone,$nd_booking_user_address,$nd_booking_user_city,$nd_booking_user_country,$nd_booking_user_message,$nd_booking_user_arrival,$nd_booking_user_coupon,$nd_booking_paypal_payment_status,$nd_booking_paypal_currency,$nd_booking_paypal_tx,$nd_booking_action_type);	

			}else{

			$wpdb->show_errors();
			$wpdb->print_error();

			}
			//END INSERT DB



		}

		//close the function to avoid wordpress errors
		//die();

	}

// 🔔 Email client + owner
$subject = "Nouvelle réservation - Loft {$nd_booking_room_title}";
$message = "Bonjour,

Une réservation vient d’être confirmée :

🛏️ Loft : {$nd_booking_room_title}
📅 Dates : Du {$nd_booking_booking_form_date_from} au {$nd_booking_booking_form_date_to}
👤 Invité : {$nd_booking_booking_form_name} {$nd_booking_booking_form_surname}
📧 Courriel : {$nd_booking_booking_form_email}
📞 Téléphone : {$nd_booking_booking_form_phone}";

$headers = ['Content-Type: text/plain; charset=UTF-8'];

wp_mail($nd_booking_booking_form_email, $subject, $message, $headers); // Client
wp_mail('waroxa@gmail.com', $subject, $message, $headers); // Owner

// 🧼 Notify cleaning team
$cleaning_subject = "🧼 Nettoyage requis – Loft {$nd_booking_room_title}";
$cleaning_message = "Bonjour,

Merci de prévoir le ménage pour le Loft {$nd_booking_room_title} le :
📅 {$nd_booking_booking_form_date_to}, dès 12h00.

Merci de confirmer une fois terminé.
— Lofts 1325";

wp_mail('waroxa@gmail.com', $cleaning_subject, $cleaning_message, $headers);

// 📆 Add to Google Calendar
add_booking_to_google_calendar([
    'room_title' => $nd_booking_room_title,
    'date_from' => $nd_booking_booking_form_date_from,
    'date_to' => $nd_booking_booking_form_date_to,
    'name' => $nd_booking_booking_form_name,
    'surname' => $nd_booking_booking_form_surname,
    'email' => $nd_booking_booking_form_email,
    'phone' => $nd_booking_booking_form_phone,
    'guests' => $nd_booking_booking_form_guests
]);

create_keychain_in_butterflymx([
    'room_title' => $nd_booking_title_post,
    'date_from' => $nd_booking_date_from,
    'date_to' => $nd_booking_date_to,
    'name' => $nd_booking_user_first_name,
    'surname' => $nd_booking_user_last_name
]);


}
//END add order if the plugin is not in dev mode


/* **************************************** END DATABASE **************************************** */


function add_booking_to_google_calendar($args) {
    $client = new Google_Client();
    $client->setAuthConfig('path/to/credentials.json'); // Update this with your actual credentials file
    $client->addScope(Google_Service_Calendar::CALENDAR);
    $service = new Google_Service_Calendar($client);

    // Booking Event for client and owner
    $event = new Google_Service_Calendar_Event([
        'summary' => '🔑 Réservation Loft ' . $args['room_title'],
        'description' => "Invité: {$args['name']} {$args['surname']} ({$args['email']})\nTel: {$args['phone']}\nInvités: {$args['guests']}",
        'start' => ['date' => $args['date_from'], 'timeZone' => 'America/Toronto'],
        'end' => ['date' => $args['date_to'], 'timeZone' => 'America/Toronto'],
    ]);
    $service->events->insert('a752f27cffee8c22988adb29fdc933c93184e3a5814c79dcee4f62115d69fbfd@group.calendar.google.com', $event);

    // Cleaning Event
    $cleaning_event = new Google_Service_Calendar_Event([
        'summary' => '🧼 Nettoyage - Loft ' . $args['room_title'],
        'description' => 'Préparation du loft après le départ des invités.',
        'start' => ['date' => $args['date_to'], 'timeZone' => 'America/Toronto'],
        'end' => ['date' => $args['date_to'], 'timeZone' => 'America/Toronto'],
    ]);
    $service->events->insert('e964e301b54d0e795b44a76ebfb9d2cfbd2f6517a822429c5af62bc2cb94de20@group.calendar.google.com', $cleaning_event);
}







/* **************************************** START WORDPRESS INFORMATION **************************************** */

//function for get color profile admin
function nd_booking_get_profile_bg_color($nd_booking_color){
	
	global $_wp_admin_css_colors;
	$nd_booking_admin_color = get_user_option( 'admin_color' );
	
	$nd_booking_profile_bg_colors = $_wp_admin_css_colors[$nd_booking_admin_color]->colors; 


	if ( $nd_booking_profile_bg_colors[$nd_booking_color] == '#e5e5e5' ) {

		return '#6b6b6b';

	}else{

		return $nd_booking_profile_bg_colors[$nd_booking_color];
		
	}

	
}

/* **************************************** END WORDPRESS INFORMATION **************************************** */





/* **************************************** START SETTINGS **************************************** */

function nd_booking_search_page() {

  $nd_booking_search_page = get_option('nd_booking_search_page');
  $nd_booking_search_page_url = get_permalink($nd_booking_search_page);

  return $nd_booking_search_page_url;

}

function nd_booking_booking_page() {

  $nd_booking_booking_page = get_option('nd_booking_booking_page');
  $nd_booking_booking_page_url = get_permalink($nd_booking_booking_page);

  return $nd_booking_booking_page_url;

}

function nd_booking_checkout_page() {

  $nd_booking_checkout_page = get_option('nd_booking_checkout_page');
  $nd_booking_checkout_page_url = get_permalink($nd_booking_checkout_page);

  return $nd_booking_checkout_page_url;

}

function nd_booking_terms_page() {

  $nd_booking_terms_page = get_option('nd_booking_terms_page');
  $nd_booking_terms_page_url = get_permalink($nd_booking_terms_page);

  return $nd_booking_terms_page_url;

}


function nd_booking_account_page() {

  $nd_booking_account_page = get_option('nd_booking_account_page');
  $nd_booking_account_page_url = get_permalink($nd_booking_account_page);

  return $nd_booking_account_page_url;

}


function nd_booking_order_page() {

  $nd_booking_order_page = get_option('nd_booking_order_page');
  $nd_booking_order_page_url = get_permalink($nd_booking_order_page);

  return $nd_booking_order_page_url;

}


function nd_booking_get_currency(){

	$nd_booking_currency = get_option('nd_booking_currency');

	return $nd_booking_currency;

}


function nd_booking_get_units_of_measure(){

	$nd_booking_units_of_measure = get_option('nd_booking_units_of_measure');

	return $nd_booking_units_of_measure;

}


function nd_booking_get_container(){

  $nd_booking_container = get_option('nd_booking_container');

  return $nd_booking_container;

}



function nd_booking_get_slug($type){


	if ( $type == 'plural' ) {

		//plural
		if ( get_option('nd_booking_slug') == '' ) {
	        $nd_booking_get_slug = __('rooms','nd-booking');
	    }else{
	        $nd_booking_get_slug = get_option('nd_booking_slug');
	    }

	}else{

		//singular
		if ( get_option('nd_booking_slug_singular') == '' ) {
	        $nd_booking_get_slug = __('room','nd-booking');
	    }else{
	        $nd_booking_get_slug = get_option('nd_booking_slug_singular');
	    }

	}

	return $nd_booking_get_slug;

}

/* **************************************** END SETTINGS **************************************** */


function create_keychain_in_butterflymx($args) {
    $token = get_option('butterflymx_access_token_v3');

    if (!$token) {
        error_log("❌ Missing ButterflyMX token.");
        return false;
    }

    $payload = [
        'data' => [
            'type' => 'keychains',
            'attributes' => [
                'name' => "LOFT {$args['room_title']} ({$args['name']} {$args['surname']})",
                'starts_at' => $args['date_from'] . 'T12:00:00-04:00',
                'ends_at' => $args['date_to'] . 'T16:00:00-04:00'
            ]
        ]
    ];

    $response = wp_remote_post("https://api.butterflymx.com/v3/keychains", [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/vnd.api+json',
            'Accept' => 'application/vnd.api+json',
        ],
        'body' => json_encode($payload),
        'method' => 'POST',
        'data_format' => 'body'
    ]);

    if (is_wp_error($response)) {
        error_log('❌ Error creating keychain: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('✅ Created keychain: ' . json_encode($body));

    return true;
}

