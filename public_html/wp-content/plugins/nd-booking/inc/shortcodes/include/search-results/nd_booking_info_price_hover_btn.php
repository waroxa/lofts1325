<?php

//START IF PRICE INFO
if ( get_option('nd_booking_info_price_enable') == 1 ) {

    //days limit
    if ( get_option('nd_booking_info_price_value') == '' ) { $nd_booking_info_price_value = 6; }else{ $nd_booking_info_price_value = get_option('nd_booking_info_price_value'); }

    //add hide class
    if ( nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to) >= $nd_booking_info_price_value ) {
        $nd_booking_info_price_value_class = 'nd_booking_display_none';
    }else{
        $nd_booking_info_price_value_class = '';
    }

    $nd_booking_shortcode_right_content .= '
    <!--START all info-->
    <style>
    .nd_booking_info_btn { width:45px; height:45px; float:left; position:relative; }
    .nd_booking_info_btn_image { width:12px; position:absolute; left:15px; top:15px; }
    .nd_booking_info_btn:hover .nd_booking_info_content{ display:block; }
    .nd_booking_info_content{ display:none; width:250px; position:absolute; bottom:45px; z-index:9; margin-left:-104px; padding-bottom:20px; }
    .nd_booking_info_table{ width:100%; float:left; padding:0px; }
    .nd_booking_info_table table{ width:100%; float:left; text-align:center; font-size:10px; letter-spacing:2px; color:#fff; }
    .nd_booking_info_table tr td{ padding: 5px; }
    .nd_booking_info_table_triangle { width: 100%; overflow: hidden; box-sizing: border-box; text-align: center; line-height: 10px; margin-bottom:-10px; }
    .nd_booking_info_table_triangle:after { content: ""; display: inline-block; width: 0px; height: 0px; border-left: 10px solid transparent; border-right: 10px solid transparent; border-top: 10px solid '.get_option( 'nd_booking_customizer_color_dark_1', '#1c1c1c' ).'; line-height: 10px; }
    .nd_booking_info_table table tr.nd_booking_info_table_first { font-weight:bolder; }
    .nd_booking_info_table table tr.nd_booking_info_table_last td:last-child { border-top: double; }
    </style>

    <div style="background-color:'.$nd_booking_meta_box_color.'" class="nd_booking_info_btn '.$nd_booking_info_price_value_class.' nd_booking_display_none_responsive">

        <img class="nd_booking_info_btn_image" alt="" src="'.esc_url(plugins_url('icon-info.png', __FILE__ )).'">

        <!--START popup-->
        <div class="nd_booking_info_content">
            <div class="nd_booking_info_table nd_booking_bg_greydark">

                <table>

                    <tr class="nd_booking_info_table_first nd_booking_bg_greydark_2">
                        <td>'.__('NIGHT','nd-booking').'</td>
                        <td>'.__('PRICE','nd-booking').'</td>
                        <td>'.__('GUEST','nd-booking').'</td>
                        <td>'.__('TOTAL','nd-booking').'</td>
                    </tr>';


                    //check id the price should be multiply for guests number
                    $nd_booking_price_guests = get_option('nd_booking_price_guests');
                    if ( $nd_booking_price_guests == 1 ) {
                        $nd_booking_price_for_guests = $nd_booking_archive_form_guests;
                    }else{
                        $nd_booking_price_for_guests = 1;
                    }

                    $nd_booking_tot_info = 0;
                    $nd_booking_date_from_info = $nd_booking_date_from;
                    for ($nd_booking_i_night_info = 1; $nd_booking_i_night_info <= nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to); $nd_booking_i_night_info++) {

                        //format
                        $nd_booking_date_from_info_new = new DateTime($nd_booking_date_from_info);
                        $nd_booking_date_from_info_v = date_format($nd_booking_date_from_info_new,'d M');
                    
                        //middle content
                        $nd_booking_shortcode_right_content .= '

                            <tr class="nd_booking_info_table_middle">
                                <td>'.$nd_booking_date_from_info_v.'</td>
                                <td>'.nd_booking_get_final_price($nd_booking_id,$nd_booking_date_from_info).' '.nd_booking_get_currency().'</td>
                                <td>'.$nd_booking_archive_form_guests.'</td>
                                <td>'.nd_booking_get_final_price($nd_booking_id,$nd_booking_date_from_info)*$nd_booking_price_for_guests.' '.nd_booking_get_currency().'</td>
                            </tr>

                        ';

                        $nd_booking_tot_info = $nd_booking_tot_info + nd_booking_get_final_price($nd_booking_id,$nd_booking_date_from_info)*$nd_booking_price_for_guests;
                        $nd_booking_date_from_info = date('m/d/Y', strtotime($nd_booking_date_from_info.' + 1 days'));

                    }

                    $nd_booking_shortcode_right_content .= '
                    <tr class="nd_booking_info_table_last">
                        <td>'.__('','nd-booking').'</td>
                        <td>'.__('','nd-booking').'</td>
                        <td>'.__('','nd-booking').'</td>
                        <td>'.$nd_booking_tot_info.' '.nd_booking_get_currency().'</td>
                    </tr>

                </table>

                <div class="nd_booking_info_table_triangle"></div>

            </div>
        </div>
        <!--END popup-->

    </div>
    <!--END all info-->';

}
//END IF PRICE INFO