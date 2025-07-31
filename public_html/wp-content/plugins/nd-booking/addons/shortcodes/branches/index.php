<?php

//shortcode nd_social
function nd_booking_ss_branches( $nd_booking_atts ) {
    
    $nd_booking_ss_branches = shortcode_atts( 
    	array(
	        'qnt' => '',
	        'order' => '',
	        'orderby' => '',
	        'id' => '',
    	), 
    $nd_booking_atts );

    //start
    $nd_booking_str = '';

    //text
    if ( $nd_booking_ss_branches['qnt'] == '' ) { $nd_booking_ss_branches_qnt = -1; }else { $nd_booking_ss_branches_qnt = $nd_booking_ss_branches['qnt']; }
    if ( $nd_booking_ss_branches['order'] == '' ) { $nd_booking_ss_branches_order = 'ASC'; }else { $nd_booking_ss_branches_order = $nd_booking_ss_branches['order']; }
    if ( $nd_booking_ss_branches['orderby'] == '' ) { $nd_booking_ss_branches_orderby = 'title'; }else { $nd_booking_ss_branches_orderby = $nd_booking_ss_branches['orderby']; }
    if ( $nd_booking_ss_branches['id'] == '' ) { $nd_booking_ss_branches_id = ''; }else { $nd_booking_ss_branches_id = $nd_booking_ss_branches['id']; }

    //args
    $args = array(
      'post_type' => 'nd_booking_cpt_4',
      'posts_per_page' => $nd_booking_ss_branches_qnt,
      'order' => $nd_booking_ss_branches_order,
      'orderby' => $nd_booking_ss_branches_orderby,
      'p' => $nd_booking_ss_branches_id,
    );
    $the_query = new WP_Query( $args );


    //START
    $nd_booking_str .= '
    <div class="nd_booking_section">';

    	 while ( $the_query->have_posts() ) : $the_query->the_post();

    	 	//info
	        $nd_booking_id = get_the_ID(); 
	        $nd_booking_title = get_the_title();
	        $nd_booking_permalink = get_permalink( $nd_booking_id );

            //metabox
            $nd_booking_meta_box_cpt_4_stars = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_cpt_4_stars', true );
            $nd_booking_meta_box_cpt_4_state = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_cpt_4_state', true );
            $nd_booking_meta_box_cpt_4_city = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_cpt_4_city', true );

            //stars icons
            $nd_booking_stars = '';
            for ($nd_booking_meta_box_cpt_4_stars_i = 0; $nd_booking_meta_box_cpt_4_stars_i < $nd_booking_meta_box_cpt_4_stars; $nd_booking_meta_box_cpt_4_stars_i++) {
                                     
                $nd_booking_stars .= '<img alt="" class="nd_booking_margin_right_5 nd_booking_float_left" width="13" src="'.esc_url(plugins_url('img/icon-star-full-grey.svg', __FILE__ )).'">';

            }

            //city and state
            $nd_booking_city_state = '';
            if ( $nd_booking_meta_box_cpt_4_state != '' AND $nd_booking_meta_box_cpt_4_city != '' ) {
              $nd_booking_city_state .= '
                <div class="nd_booking_section nd_booking_height_10"></div>
                <p class="">'.$nd_booking_meta_box_cpt_4_city.' ( '.$nd_booking_meta_box_cpt_4_state.' )</p>
              ';
            }
	        
	        //image
	        $nd_booking_image_id = get_post_thumbnail_id( $nd_booking_id );
			$nd_booking_image_attributes = wp_get_attachment_image_src( $nd_booking_image_id, 'thumbnail' );

    	 	$nd_booking_str .= '
			<div id="nd_booking_ss_branches_'.$nd_booking_id.'" class="nd_booking_ss_branches nd_booking_width_100_percentage">

				<div style="padding:0px 0px 20px 0px;" class="nd_booking_section nd_booking_box_sizing_border_box">

					<div class="nd_booking_section nd_booking_position_relative">

						<img alt="" class="nd_booking_position_absolute nd_booking_left_0 nd_booking_top_0" width="100" src="'.$nd_booking_image_attributes[0].'">

						<div class="nd_booking_section nd_booking_padding_left_120 nd_booking_min_height_100 nd_booking_box_sizing_border_box">

							<div class="nd_booking_section nd_booking_height_5"></div>
                            <a href="'.$nd_booking_permalink.'"><h4>'.$nd_booking_title.'</h4></a>
							'.$nd_booking_city_state.'
							<div class="nd_booking_section nd_booking_height_10"></div>
							<div class="nd_booking_section">
                                '.$nd_booking_stars.'   
							</div>

						</div>
					</div>

				</div>

			</div>';


    	 endwhile;

    $nd_booking_str .= '
    </div>';
    //END

	wp_reset_postdata();

    return $nd_booking_str;
}
add_shortcode( 'nd_booking_ss_branches', 'nd_booking_ss_branches' );
