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

/**
 * Custom pricing rules for Lofts1325.
 *
 * Update the numeric values below to adjust seasonal and long-stay pricing for
 * each loft type. Seasons use MM-DD boundaries and cover a full calendar year.
 * Long-stay tiers apply a flat nightly rate (or a flat total for monthly stays)
 * once the guest's night count falls in the configured range.
 *
 * @return array<string,array{matches:array<int,string>,seasons:array<int,array{label:string,start:string,end:string,rate:float}>,long_stay:array<int,array{min_nights:int,max_nights:int,nightly_rate?:float,flat_total?:float}>}>
 */
function nd_booking_get_loft_pricing_rules() {
        return array(
                'loft-simple' => array(
                        'matches' => array('loft-simple', 'simple'),
                        'seasons' => array(
                                array(
                                        'label' => 'fall_winter',
                                        'start' => '09-01',
                                        'end'   => '03-30',
                                        'rate'  => 285.0,
                                ),
                                array(
                                        'label' => 'spring',
                                        'start' => '04-01',
                                        'end'   => '06-30',
                                        'rate'  => 275.0,
                                ),
                                array(
                                        'label' => 'summer',
                                        'start' => '07-01',
                                        'end'   => '08-31',
                                        'rate'  => 250.0,
                                ),
                        ),
                        'long_stay' => array(
                                array(
                                        'min_nights'  => 11,
                                        'max_nights'  => 21,
                                        'nightly_rate'=> 120.0,
                                ),
                                array(
                                        'min_nights'  => 22,
                                        'max_nights'  => 27,
                                        'nightly_rate'=> 110.0,
                                ),
                                array(
                                        'min_nights' => 28,
                                        'max_nights' => 30,
                                        'flat_total' => 2850.0,
                                ),
                        ),
                ),
                'loft-double' => array(
                        'matches' => array('loft-double', 'double'),
                        'seasons' => array(
                                array(
                                        'label' => 'fall_winter',
                                        'start' => '09-01',
                                        'end'   => '03-30',
                                        'rate'  => 385.0,
                                ),
                                array(
                                        'label' => 'spring',
                                        'start' => '04-01',
                                        'end'   => '06-30',
                                        'rate'  => 365.0,
                                ),
                                array(
                                        'label' => 'summer',
                                        'start' => '07-01',
                                        'end'   => '08-31',
                                        'rate'  => 350.0,
                                ),
                        ),
                        'long_stay' => array(
                                array(
                                        'min_nights'  => 11,
                                        'max_nights'  => 21,
                                        'nightly_rate'=> 175.0,
                                ),
                                array(
                                        'min_nights'  => 22,
                                        'max_nights'  => 27,
                                        'nightly_rate'=> 160.0,
                                ),
                                array(
                                        'min_nights' => 28,
                                        'max_nights' => 30,
                                        'flat_total' => 3850.0,
                                ),
                        ),
                ),
        );
}

/**
 * Normalize a post into an identifier usable for matching custom pricing rules.
 *
 * @param WP_Post|int $room_post Room post or ID.
 *
 * @return array{slug:string,title:string}|null
 */
function nd_booking_normalize_loft_identifier( $room_post ) {
        $post = get_post( $room_post );

        if ( ! $post instanceof WP_Post ) {
                return null;
        }

        $title = strtolower( (string) $post->post_title );
        $slug  = sanitize_title( $post->post_name ?: $post->post_title );

        return array(
                'slug'  => $slug,
                'title' => $title,
        );
}

/**
 * Attempt to find a loft pricing rule for a given room.
 *
 * @param int $room_id Room post ID.
 *
 * @return array|null
 */
function nd_booking_find_loft_pricing_rule( $room_id ) {
        $identifier = nd_booking_normalize_loft_identifier( $room_id );

        if ( null === $identifier ) {
                return null;
        }

        $rules = nd_booking_get_loft_pricing_rules();

        foreach ( $rules as $rule ) {
                if ( empty( $rule['matches'] ) || ! is_array( $rule['matches'] ) ) {
                        continue;
                }

                foreach ( $rule['matches'] as $match ) {
                        $match = strtolower( (string) $match );

                        if ( '' === $match ) {
                                continue;
                        }

                        if ( false !== strpos( $identifier['slug'], $match ) || false !== strpos( $identifier['title'], $match ) ) {
                                return $rule;
                        }
                }
        }

        return null;
}

/**
 * Determine whether a date falls within a season range, handling wrapped years.
 *
 * @param string $date    Date string in Y/m/d or Y-m-d format.
 * @param string $start   Season start in MM-DD format.
 * @param string $end     Season end in MM-DD format.
 *
 * @return bool
 */
function nd_booking_loft_date_in_range( $date, $start, $end ) {
        $target_md      = (int) date( 'md', strtotime( $date ) );
        $start_md       = (int) str_replace( '-', '', $start );
        $end_md         = (int) str_replace( '-', '', $end );
        $wraps_calendar = $start_md > $end_md;

        if ( $wraps_calendar ) {
                return ( $target_md >= $start_md ) || ( $target_md <= $end_md );
        }

        return ( $target_md >= $start_md ) && ( $target_md <= $end_md );
}

/**
 * Resolve the seasonal nightly rate for a given date.
 *
 * @param array  $rule Pricing rule payload.
 * @param string $date Date in Y/m/d or Y-m-d format.
 *
 * @return float
 */
function nd_booking_get_loft_seasonal_rate( array $rule, $date ) {
        if ( empty( $rule['seasons'] ) || ! is_array( $rule['seasons'] ) ) {
                return 0.0;
        }

        foreach ( $rule['seasons'] as $season ) {
                if ( empty( $season['start'] ) || empty( $season['end'] ) || ! isset( $season['rate'] ) ) {
                        continue;
                }

                if ( nd_booking_loft_date_in_range( $date, $season['start'], $season['end'] ) ) {
                        return (float) $season['rate'];
                }
        }

        return isset( $rule['seasons'][0]['rate'] ) ? (float) $rule['seasons'][0]['rate'] : 0.0;
}

/**
 * Return the long-stay tier (if any) that matches the provided night count.
 *
 * @param array $rule        Pricing rule payload.
 * @param int   $night_count Number of nights in the booking.
 *
 * @return array|null
 */
function nd_booking_get_loft_long_stay_tier( array $rule, $night_count ) {
        if ( empty( $rule['long_stay'] ) || ! is_array( $rule['long_stay'] ) ) {
                return null;
        }

        foreach ( $rule['long_stay'] as $tier ) {
                $min = isset( $tier['min_nights'] ) ? (int) $tier['min_nights'] : 0;
                $max = isset( $tier['max_nights'] ) ? (int) $tier['max_nights'] : 0;

                if ( $night_count >= $min && ( 0 === $max || $night_count <= $max ) ) {
                        return $tier;
                }
        }

        return null;
}

/**
 * Determine the nightly rate for a specific date, honoring long-stay tiers.
 *
 * @param array      $rule            Pricing rule payload.
 * @param string     $date            Nightly date in Y/m/d or Y-m-d format.
 * @param array|null $long_stay_tier  Active long-stay tier, if any.
 * @param int|null   $night_count     Total nights in the booking (used for flat totals).
 *
 * @return float
 */
function nd_booking_get_loft_nightly_rate( array $rule, $date, ?array $long_stay_tier = null, $night_count = null ) {
        if ( null !== $long_stay_tier ) {
                if ( isset( $long_stay_tier['nightly_rate'] ) ) {
                        return (float) $long_stay_tier['nightly_rate'];
                }

                if ( isset( $long_stay_tier['flat_total'] ) && $night_count ) {
                        return (float) $long_stay_tier['flat_total'] / max( 1, (int) $night_count );
                }
        }

        return nd_booking_get_loft_seasonal_rate( $rule, $date );
}

function nd_booking_calculate_airbnb_style_discount( $base_total, $night_count ) {
        $weekly_percent  = min( 100, max( 0, floatval( get_option( 'nd_booking_airbnb_weekly_discount', 0 ) ) ) );
        $monthly_percent = min( 100, max( 0, floatval( get_option( 'nd_booking_airbnb_monthly_discount', 0 ) ) ) );

        $discount = array(
                'amount'  => 0.0,
                'percent' => 0.0,
                'label'   => '',
        );

        if ( $night_count >= 28 && $monthly_percent > 0 ) {
                $discount['percent'] = $monthly_percent;
                $discount['label']   = __( 'Monthly discount', 'nd-booking' );
        } elseif ( $night_count >= 7 && $weekly_percent > 0 ) {
                $discount['percent'] = $weekly_percent;
                $discount['label']   = __( 'Weekly discount', 'nd-booking' );
        }

        if ( $discount['percent'] > 0 ) {
                $discount['amount'] = round( $base_total * ( $discount['percent'] / 100 ), 2 );
        }

        return $discount;
}

/**
 * Calculate loft pricing (including seasonal rates and Airbnb-style discounts) for a date range.
 *
 * @param array  $rule        Pricing rule payload.
 * @param string $date_from   Arrival date (Y/m/d or Y-m-d).
 * @param string $date_to     Departure date (Y/m/d or Y-m-d).
 * @param int    $guest_count Number of guests to factor when the plugin is set to price per guest.
 *
 * @return array{total:float,nightly_rate:float,night_count:int,long_stay_tier:array|null}
 */
function nd_booking_calculate_loft_pricing( array $rule, $date_from, $date_to, $guest_count = 1 ) {
        $night_count = nd_booking_get_number_night( $date_from, $date_to );

        if ( $night_count <= 0 ) {
                return array(
                        'total'          => 0.0,
                        'nightly_rate'   => 0.0,
                        'night_count'    => 0,
                        'long_stay_tier' => null,
                        'discount'       => null,
                );
        }

        $running_total = 0.0;
        $current_date  = $date_from;

        for ( $index = 1; $index <= $night_count; $index++ ) {
                $running_total += nd_booking_get_loft_seasonal_rate( $rule, $current_date );
                $current_date   = date( 'Y/m/d', strtotime( $current_date . ' + 1 days' ) );
        }

        if ( get_option( 'nd_booking_price_guests' ) == 1 ) {
                $running_total = $running_total * max( 1, (int) $guest_count );
        }

        $discount = nd_booking_calculate_airbnb_style_discount( $running_total, $night_count );

        if ( $discount['amount'] > 0 ) {
                $running_total = max( 0.0, $running_total - $discount['amount'] );
        }

        $effective_nightly_rate = $night_count > 0 ? $running_total / $night_count : 0.0;
        $long_stay_tier         = null;

        if ( $discount['amount'] > 0 ) {
                $long_stay_tier = array(
                        'nightly_rate'     => $effective_nightly_rate,
                        'discount_amount'  => $discount['amount'],
                        'discount_percent' => $discount['percent'],
                        'label'            => $discount['label'],
                );
        }

        return array(
                'total'          => round( $running_total, 2 ),
                'nightly_rate'   => round( $effective_nightly_rate, 2 ),
                'night_count'    => (int) $night_count,
                'long_stay_tier' => $long_stay_tier,
                'discount'       => $discount,
        );
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


                    $nd_booking_locale      = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
                    $nd_booking_lang_prefix = substr( $nd_booking_locale, 0, 2 );

                    $nd_booking_last_room_text = ( 'fr' === $nd_booking_lang_prefix )
                        ? __( 'DERNIÈRE CHAMBRE À CE PRIX', 'nd-booking' )
                        : __( 'LAST ROOM AT THIS PRICE', 'nd-booking' );

                    $nd_booking_only_label = ( 'fr' === $nd_booking_lang_prefix )
                        ? __( 'PLUS QUE', 'nd-booking' )
                        : __( 'ONLY', 'nd-booking' );

                    $nd_booking_rooms_left_label = ( 'fr' === $nd_booking_lang_prefix )
                        ? __( 'CHAMBRES RESTANTES À CE PRIX', 'nd-booking' )
                        : __( 'ROOMS LEFT AT THIS PRICE', 'nd-booking' );

                    if ( $nd_booking_room_left == 1 ){

                        return '<span class="nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_padding_top_5 nd_booking_top_10 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_color_3">'.$nd_booking_last_room_text.'</span>';

                    }elseif ( $nd_booking_room_left <= 3 ){

                        return '<span class="nd_options_color_white nd_booking_font_size_10 nd_booking_line_height_10 nd_booking_letter_spacing_2 nd_booking_padding_3_5 nd_booking_top_10 nd_booking_padding_top_5 nd_booking_position_absolute nd_booking_right_10 nd_booking_bg_color_3">'.$nd_booking_only_label.' '.$nd_booking_room_left.' '.$nd_booking_rooms_left_label.'</span>';

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

                $nd_booking_final_trip_price = nd_booking_format_decimal( $nd_booking_final_trip_price );


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

                        ),
                        array(
                                '%d',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%d',
                                '%s',
                                '%s',
                                '%d',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s'
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


}
//END add order if the plugin is not in dev mode


/* **************************************** END DATABASE **************************************** */








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

function nd_booking_inject_core_page_shortcodes( $content ) {

  if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
    return $content;
  }

  if ( ! in_the_loop() || ! is_main_query() || ! is_singular() ) {
    return $content;
  }

  $current_page_id = get_the_ID();

  if ( ! $current_page_id ) {
    return $content;
  }

  $shortcode_pages = array(
    intval( get_option( 'nd_booking_booking_page' ) )  => 'nd_booking_booking',
    intval( get_option( 'nd_booking_checkout_page' ) ) => 'nd_booking_checkout',
  );

  foreach ( $shortcode_pages as $page_id => $shortcode_tag ) {
    if ( $page_id && $current_page_id === $page_id ) {
      if ( has_shortcode( $content, $shortcode_tag ) ) {
        return $content;
      }

      $shortcode_output = do_shortcode( '[' . $shortcode_tag . ']' );

      if ( '' === trim( $shortcode_output ) ) {
        return $content;
      }

      return $shortcode_output . $content;
    }
  }

  return $content;

}
add_filter( 'the_content', 'nd_booking_inject_core_page_shortcodes', 5 );

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

function nd_booking_format_decimal( $amount, $decimals = 2 ) {

        $amount = floatval( $amount );

        return number_format( $amount, $decimals, '.', '' );

}

function nd_booking_format_percentage( $rate ) {

        $rate = floatval( $rate );

        if ( 0.0 === $rate ) {
                return '0';
        }

        $formatted = number_format( $rate, 3, '.', '' );
        $formatted = rtrim( rtrim( $formatted, '0' ), '.' );

        return $formatted;

}

function nd_booking_get_tax_rate_defaults() {

        static $defaults = null;

        if ( null === $defaults ) {
                $defaults = array(
                        'lodging' => 3.5,
                        'gst'     => 5,
                        'qst'     => 9.975,
                );
        }

        return $defaults;

}

function nd_booking_get_tax_rate_default( $type ) {

        $defaults = nd_booking_get_tax_rate_defaults();

        if ( isset( $defaults[ $type ] ) ) {
                return $defaults[ $type ];
        }

        return 0;

}

function nd_booking_calculate_tax_breakdown( $base_amount ) {

        $base_amount = max( 0, floatval( $base_amount ) );

        $defaults = nd_booking_get_tax_rate_defaults();

        $rates = array(
                'lodging' => floatval( get_option( 'nd_booking_lodging_tax_rate', $defaults['lodging'] ) ),
                'gst'     => floatval( get_option( 'nd_booking_gst_rate', $defaults['gst'] ) ),
                'qst'     => floatval( get_option( 'nd_booking_qst_rate', $defaults['qst'] ) ),
        );

        $labels = array(
                'lodging' => __( 'Lodging Tax', 'nd-booking' ),
                'gst'     => __( 'GST', 'nd-booking' ),
                'qst'     => __( 'QST', 'nd-booking' ),
        );

        $taxes = array();

        $lodging_amount = 0.0;
        if ( $rates['lodging'] > 0 ) {
                $lodging_amount = round( $base_amount * $rates['lodging'] / 100, 2 );
                $taxes['lodging'] = array(
                        'label'         => $labels['lodging'],
                        'rate'          => $rates['lodging'],
                        'amount'        => $lodging_amount,
                        'display_label' => sprintf( __( '%1$s (%2$s%%)', 'nd-booking' ), $labels['lodging'], nd_booking_format_percentage( $rates['lodging'] ) ),
                );
        }

        $gst_amount = 0.0;
        if ( $rates['gst'] > 0 ) {
                $gst_amount = round( $base_amount * $rates['gst'] / 100, 2 );
                $taxes['gst'] = array(
                        'label'         => $labels['gst'],
                        'rate'          => $rates['gst'],
                        'amount'        => $gst_amount,
                        'display_label' => sprintf( __( '%1$s (%2$s%%)', 'nd-booking' ), $labels['gst'], nd_booking_format_percentage( $rates['gst'] ) ),
                );
        }

        $qst_amount = 0.0;
        if ( $rates['qst'] > 0 ) {
                $qst_amount = round( $base_amount * $rates['qst'] / 100, 2 );
                $taxes['qst'] = array(
                        'label'         => $labels['qst'],
                        'rate'          => $rates['qst'],
                        'amount'        => $qst_amount,
                        'display_label' => sprintf( __( '%1$s (%2$s%%)', 'nd-booking' ), $labels['qst'], nd_booking_format_percentage( $rates['qst'] ) ),
                );
        }

        $total_tax = round( $lodging_amount + $gst_amount + $qst_amount, 2 );
        $base_amount = round( $base_amount, 2 );
        $total = round( $base_amount + $total_tax, 2 );

        return array(
                'base'      => $base_amount,
                'taxes'     => $taxes,
                'total_tax' => $total_tax,
                'total'     => $total,
        );

}

function nd_booking_calculate_tax_breakdown_from_total( $total_amount ) {

        $total_amount = max( 0, floatval( $total_amount ) );

        $defaults = nd_booking_get_tax_rate_defaults();

        $rates = array(
                'lodging' => floatval( get_option( 'nd_booking_lodging_tax_rate', $defaults['lodging'] ) ),
                'gst'     => floatval( get_option( 'nd_booking_gst_rate', $defaults['gst'] ) ),
                'qst'     => floatval( get_option( 'nd_booking_qst_rate', $defaults['qst'] ) ),
        );

        $l = $rates['lodging'] / 100;
        $g = $rates['gst'] / 100;
        $q = $rates['qst'] / 100;

        $denominator = 1 + $l + $g + $q;

        if ( $denominator <= 0 ) {
                $breakdown = nd_booking_calculate_tax_breakdown( $total_amount );
                $breakdown['total'] = round( $total_amount, 2 );
                $breakdown['total_tax'] = round( $breakdown['total'] - $breakdown['base'], 2 );

                return $breakdown;
        }

        $base_amount = $total_amount / $denominator;
        $breakdown = nd_booking_calculate_tax_breakdown( $base_amount );

        $expected_total = round( $total_amount, 2 );
        $difference = $expected_total - $breakdown['total'];

        if ( abs( $difference ) >= 0.01 && ! empty( $breakdown['taxes'] ) ) {
                $last_key = array_key_last( $breakdown['taxes'] );
                $breakdown['taxes'][ $last_key ]['amount'] = round( $breakdown['taxes'][ $last_key ]['amount'] + $difference, 2 );
        }

        if ( ! empty( $breakdown['taxes'] ) ) {
                $total_tax = 0.0;
                foreach ( $breakdown['taxes'] as $tax ) {
                        $total_tax += floatval( $tax['amount'] );
                }
                $breakdown['total_tax'] = round( $total_tax, 2 );
        }

        $breakdown['total'] = $expected_total;
        $breakdown['base'] = round( $expected_total - $breakdown['total_tax'], 2 );

        return $breakdown;

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



