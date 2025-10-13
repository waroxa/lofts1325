<?php

if (function_exists('wp_loft_booking_full_sync')) {
    wp_loft_booking_full_sync();
} elseif (function_exists('wp_loft_booking_sync_units')) {
    wp_loft_booking_sync_units();
}

//START ajax function for woo go to the woo checkout with the product in the cart and price passed
function nd_booking_woo_php() {

    check_ajax_referer( 'nd_booking_woo_nonce', 'nd_booking_woo_security' );

    //get datas
    $nd_booking_trip_price = sanitize_text_field($_GET['nd_booking_trip_price']);
    $nd_booking_rid = sanitize_text_field($_GET['nd_booking_rid']);
    $nd_booking_meta_box_room_woo_product = get_post_meta( $nd_booking_rid, 'nd_booking_meta_box_room_woo_product', true );

    //clear cart
    WC()->cart->empty_cart();

    //add to cart the product
    WC()->cart->add_to_cart($nd_booking_meta_box_room_woo_product);
    
    //set the price
    $product = wc_get_product($nd_booking_meta_box_room_woo_product);
    $product->set_regular_price($nd_booking_trip_price);
    $product->set_price($nd_booking_trip_price);
    $product->save();

    $nd_booking_book_room_woo_id = 'nd_booking_book_room_'.$nd_booking_rid;
    echo esc_attr($nd_booking_book_room_woo_id);

    die();

}
add_action( 'wp_ajax_nd_booking_woo_php', 'nd_booking_woo_php' );
add_action( 'wp_ajax_nopriv_nd_booking_woo_php', 'nd_booking_woo_php' );



//custom content in thankyou page when the order is already processed
add_action( 'woocommerce_thankyou', 'nd_booking_woo_thankyou_content', 10, 1 );
function nd_booking_woo_thankyou_content( $order_id ) {

    //$nd_booking_woo_order = new WC_Order( $order_id );
    $nd_booking_woo_order = wc_get_order( $order_id );

    //ids
    $nd_booking_booking_form_post_id = sanitize_text_field(get_post_meta( $order_id, 'nd_booking_form_booking_id', true ));
    $nd_booking_ids_array = explode('-', $nd_booking_booking_form_post_id ); 
    $nd_booking_booking_form_post_id = $nd_booking_ids_array[0];

    //datas
    $nd_booking_booking_form_extra_services = '';
    $nd_booking_id_room = $nd_booking_ids_array[1];
    
    if ( $nd_booking_id_room != '' ) {

        $nd_booking_result_woo_info = '
        <h2>'.__('Room Information','nd-booking').'</h2>
        <br/>
        <p>'.__('Room Id','nd-booking').' : '.get_post_meta( $order_id, 'nd_booking_form_booking_id', true ).'</p>
        <p>'.__('Guests','nd-booking').' : '.get_post_meta( $order_id, 'nd_booking_form_booking_guests', true ).'</p>
        <p>'.__('Date From','nd-booking').' : '.get_post_meta( $order_id, 'nd_booking_form_booking_date_from', true ).'</p>
        <p>'.__('Date To','nd-booking').' : '.get_post_meta( $order_id, 'nd_booking_form_booking_date_to', true ).'</p>
        <br/>
        ';

        $nd_booking_allowed_html = [
            'h2' => [],
            'br' => [],
            'p' => [],
        ];

        echo wp_kses( $nd_booking_result_woo_info, $nd_booking_allowed_html );

    }

    //get all datas
    $nd_booking_id_room = sanitize_text_field($nd_booking_id_room);
    $nd_booking_room_title = sanitize_text_field(get_the_title($nd_booking_id_room));
    $nd_booking_date = sanitize_text_field($nd_booking_woo_order->get_date_created()->date('H:m:s F j Y'));
    $nd_booking_booking_form_date_from = sanitize_text_field(get_post_meta( $order_id, 'nd_booking_form_booking_date_from', true ));
    $nd_booking_booking_form_date_to = sanitize_text_field(get_post_meta( $order_id, 'nd_booking_form_booking_date_to', true ));
    $nd_booking_booking_form_guests = sanitize_text_field(get_post_meta( $order_id, 'nd_booking_form_booking_guests', true ));
    $nd_booking_booking_form_final_price = sanitize_text_field($nd_booking_woo_order->get_subtotal());
    $nd_booking_booking_form_extra_services = '';
    $nd_booking_current_user_id = sanitize_text_field($nd_booking_woo_order->get_user_id());
    $nd_booking_booking_form_name = sanitize_text_field($nd_booking_woo_order->get_billing_first_name());
    $nd_booking_booking_form_surname = sanitize_text_field($nd_booking_woo_order->get_billing_last_name());
    $nd_booking_booking_form_email = sanitize_text_field($nd_booking_woo_order->get_billing_email());
    $nd_booking_booking_form_phone = sanitize_text_field($nd_booking_woo_order->get_billing_phone());
    $nd_booking_booking_form_address = sanitize_text_field($nd_booking_woo_order->get_billing_address_1());
    $nd_booking_booking_form_zip = sanitize_text_field($nd_booking_woo_order->get_billing_postcode());
    $nd_booking_booking_form_city = sanitize_text_field($nd_booking_woo_order->get_billing_city());
    $nd_booking_booking_form_country = sanitize_text_field($nd_booking_woo_order->get_billing_country());
    $nd_booking_booking_form_requests = sanitize_text_field($nd_booking_woo_order->get_customer_note());
    $nd_booking_booking_form_arrival = sanitize_text_field(__('I do not know','nd-booking'));
    $nd_booking_booking_form_coupon = '';
    $nd_booking_booking_form_currency = sanitize_text_field($nd_booking_woo_order->get_currency());
    $nd_booking_paypal_tx = sanitize_text_field($order_id);
    $nd_booking_booking_form_payment_method = sanitize_text_field($nd_booking_woo_order->get_payment_method());
    $nd_booking_booking_form_payment_method_title = sanitize_text_field($nd_booking_woo_order->get_payment_method_title());
    $nd_booking_booking_form_action_type = 'woo '.$nd_booking_booking_form_payment_method_title;
    $nd_booking_booking_form_payment_statuss = sanitize_text_field($nd_booking_woo_order->get_status());

    //the booking plugin accept 3 order status ( Pending,Pending Payment,Completed )
    $nd_booking_booking_form_payment_status = 'Pending';
    if ( $nd_booking_booking_form_payment_statuss == 'failed' ) { $nd_booking_booking_form_payment_status = 'Pending'; }
    if ( $nd_booking_booking_form_payment_statuss == 'canceled' ) { $nd_booking_booking_form_payment_status = 'Pending'; }

    if ( $nd_booking_booking_form_payment_statuss == 'on-hold' ) { $nd_booking_booking_form_payment_status = 'Pending Payment'; }
    if ( $nd_booking_booking_form_payment_statuss == 'pending-payment' ) { $nd_booking_booking_form_payment_status = 'Pending Payment'; }
    if ( $nd_booking_booking_form_payment_statuss == 'pending payment' ) { $nd_booking_booking_form_payment_status = 'Pending Payment'; }
    if ( $nd_booking_booking_form_payment_statuss == 'pending' ) { $nd_booking_booking_form_payment_status = 'Pending Payment'; }
    if ( $nd_booking_booking_form_payment_statuss == 'processing' ) { $nd_booking_booking_form_payment_status = 'Pending Payment'; }

    if ( $nd_booking_booking_form_payment_statuss == 'completed' ) { $nd_booking_booking_form_payment_status = 'Completed'; }


    if ( $nd_booking_id_room != '' ) {

        //add woo order in db
        nd_booking_add_booking_in_db(
            $nd_booking_id_room,
            $nd_booking_room_title,
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
            $nd_booking_booking_form_action_type,
            '',
            ''
        );
        
    }


}


//insert room custom fields passed on woo chekout page
add_action( 'woocommerce_after_order_notes', 'nd_booking_custom_checkout_room_fields' );
function nd_booking_custom_checkout_room_fields( $checkout ) {

    $nd_booking_id_room_passed = $checkout->get_value('nd_booking_form_booking_id');

    if ( $nd_booking_id_room_passed != '' ) {

        $nd_booking_custom_checkout_room_info = '
        <div id="nd_booking_custom_checkout_room_fields">
        <br/>
        <h2>'. __('Room Information','nd-booking').'</h2>
        <br/>
        <p>'. __('Room ID','nd-booking').' : '.$checkout->get_value( 'nd_booking_form_booking_id' ).'</p>
        <p>'. __('Guests','nd-booking').' : '.$checkout->get_value( 'nd_booking_form_booking_guests' ).'</p>
        <p>'. __('Date From','nd-booking').' : '.$checkout->get_value( 'nd_booking_form_booking_date_from' ).'</p>
        <p>'. __('Date To','nd-booking').' : '.$checkout->get_value( 'nd_booking_form_booking_date_to' ).'</p>';


        $nd_booking_allowed_html = [
            'div' => [ 
                'id' => [], 
            ],
            'h2' => [],
            'br' => [],
            'p' => [],
        ];

        echo wp_kses( $nd_booking_custom_checkout_room_info, $nd_booking_allowed_html );

        woocommerce_form_field( 'nd_booking_form_booking_id', array(
        'type'          => 'hidden',
        'class'         => array('my-field-class form-row-wide'),
        ), $checkout->get_value( 'nd_booking_form_booking_id' ));

        woocommerce_form_field( 'nd_booking_form_booking_guests', array(
        'type'          => 'hidden',
        'class'         => array('my-field-class form-row-wide'),
        ), $checkout->get_value( 'nd_booking_form_booking_guests' ));

        woocommerce_form_field( 'nd_booking_form_booking_date_from', array(
        'type'          => 'hidden',
        'class'         => array('my-field-class form-row-wide'),
        ), $checkout->get_value( 'nd_booking_form_booking_date_from' ));

        woocommerce_form_field( 'nd_booking_form_booking_date_to', array(
        'type'          => 'hidden',
        'class'         => array('my-field-class form-row-wide'),
        ), $checkout->get_value( 'nd_booking_form_booking_date_to' ));


        $nd_booking_custom_checkout_room_info_close = '</div>';

        $nd_booking_allowed_html = [
            'div' => [ 
                'id' => [], 
            ],
        ];

        echo wp_kses( $nd_booking_custom_checkout_room_info_close, $nd_booking_allowed_html );

    }

}


//check if the new mandatory fields are filled 
add_action('woocommerce_checkout_process', 'nd_booking_custom_checkout_room_fields_process');
function nd_booking_custom_checkout_room_fields_process() {

    if ( $_POST['nd_booking_form_booking_id'] != '') {

        if ( ! $_POST['nd_booking_form_booking_id'] )
        wc_add_notice( __( 'ID Room is mandatory' ), 'error' );
        if ( ! $_POST['nd_booking_form_booking_guests'] )
        wc_add_notice( __( 'Guests is mandatory' ), 'error' );
        if ( ! $_POST['nd_booking_form_booking_date_from'] )
        wc_add_notice( __( 'Date From is mandatory' ), 'error' );
        if ( ! $_POST['nd_booking_form_booking_date_to'] )
        wc_add_notice( __( 'Date To is mandatory' ), 'error' );

    }
    
}


//save metabox
add_action( 'woocommerce_checkout_update_order_meta', 'nd_booking_custom_checkout_room_fields_update' );
function nd_booking_custom_checkout_room_fields_update( $order_id ) {
    if ( ! empty( $_POST['nd_booking_form_booking_id'] ) ) {
        update_post_meta( $order_id, 'nd_booking_form_booking_id', sanitize_text_field( $_POST['nd_booking_form_booking_id'] ) );
    }
    if ( ! empty( $_POST['nd_booking_form_booking_guests'] ) ) {
        update_post_meta( $order_id, 'nd_booking_form_booking_guests', sanitize_text_field( $_POST['nd_booking_form_booking_guests'] ) );
    }
    if ( ! empty( $_POST['nd_booking_form_booking_date_from'] ) ) {
        update_post_meta( $order_id, 'nd_booking_form_booking_date_from', sanitize_text_field( $_POST['nd_booking_form_booking_date_from'] ) );
    }
    if ( ! empty( $_POST['nd_booking_form_booking_date_to'] ) ) {
        update_post_meta( $order_id, 'nd_booking_form_booking_date_to', sanitize_text_field( $_POST['nd_booking_form_booking_date_to'] ) );
    }
}

//show the fields in admin
add_action( 'woocommerce_admin_order_data_after_billing_address', 'nd_booking_custom_checkout_room_fields_admin_order', 10, 1 );
function nd_booking_custom_checkout_room_fields_admin_order($order_id){

    if ( get_post_meta($order_id->id,'nd_booking_form_booking_id',true) != '' ) {

        $nd_booking_allowed_html = [
            'p' => [],
            'strong' => [],
        ];

        $nd_booking_woo_fields_admin_order_id = '<p><strong>'.__('Room Id').':</strong> ' . get_post_meta( $order_id->id, 'nd_booking_form_booking_id', true ) . '</p>';
        $nd_booking_woo_fields_admin_order_guests = '<p><strong>'.__('Guests').':</strong> ' . get_post_meta( $order_id->id, 'nd_booking_form_booking_guests', true ) . '</p>';
        $nd_booking_woo_fields_admin_order_date_from = '<p><strong>'.__('Date From').':</strong> ' . get_post_meta( $order_id->id, 'nd_booking_form_booking_date_from', true ) . '</p>';
        $nd_booking_woo_fields_admin_order_date_to = '<p><strong>'.__('Date To').':</strong> ' . get_post_meta( $order_id->id, 'nd_booking_form_booking_date_to', true ) . '</p>'; 

        echo wp_kses( $nd_booking_woo_fields_admin_order_id, $nd_booking_allowed_html );
        echo wp_kses( $nd_booking_woo_fields_admin_order_guests, $nd_booking_allowed_html );
        echo wp_kses( $nd_booking_woo_fields_admin_order_date_from, $nd_booking_allowed_html );
        echo wp_kses( $nd_booking_woo_fields_admin_order_date_to, $nd_booking_allowed_html );

    }
    
}
//END woo












//START  nd_booking_search_results
function nd_booking_shortcode_search_results() {

    if ( ! wp_style_is( 'nd_booking_style', 'enqueued' ) ) {
        if ( wp_style_is( 'nd_booking_style', 'registered' ) ) {
            wp_enqueue_style( 'nd_booking_style' );
        } else {
            $nd_booking_plugin_root = dirname( dirname( dirname( __FILE__ ) ) );
            $nd_booking_style_path  = $nd_booking_plugin_root . '/assets/css/style.css';
            $nd_booking_style_url   = plugins_url( 'assets/css/style.css', $nd_booking_plugin_root . '/nd-booking.php' );
            $nd_booking_style_version = file_exists( $nd_booking_style_path ) ? filemtime( $nd_booking_style_path ) : false;

            wp_enqueue_style( 'nd_booking_style', $nd_booking_style_url, array(), $nd_booking_style_version );
        }
    }

    wp_enqueue_script('masonry');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-datepicker-css', esc_url(plugins_url('jquery-ui-datepicker.css', __FILE__ )) );
    wp_enqueue_script('jquery-ui-slider');
    wp_enqueue_script('jquery-ui-tooltip');

    //ajax results
    $nd_booking_sorting_params = array(
        'nd_booking_ajaxurl_sorting' => admin_url('admin-ajax.php'),
        'nd_booking_ajaxnonce_sorting' => wp_create_nonce('nd_booking_sorting_nonce'),
    );

    wp_enqueue_script( 'nd_booking_search_sorting', esc_url( plugins_url( 'sorting.js', __FILE__ ) ), array( 'jquery' ) ); 
    wp_localize_script( 'nd_booking_search_sorting', 'nd_booking_my_vars_sorting', $nd_booking_sorting_params ); 


    //ajax results woo
    $nd_booking_woo_params = array(
        'nd_booking_ajaxurl_woo' => admin_url('admin-ajax.php'),
        'nd_booking_ajaxnonce_woo' => wp_create_nonce('nd_booking_woo_nonce'),
    );

    wp_enqueue_script( 'nd_booking_search_woo', esc_url( plugins_url( 'woo.js', __FILE__ ) ), array( 'jquery' ) ); 
    wp_localize_script( 'nd_booking_search_woo', 'nd_booking_my_vars_woo', $nd_booking_woo_params ); 
    //end ajax woo


    //START if dates are set
    if( isset( $_GET['nd_booking_archive_form_date_range_from']) && isset( $_GET['nd_booking_archive_form_date_range_to'])  ) { 
    
        $nd_booking_date_from = sanitize_text_field($_GET['nd_booking_archive_form_date_range_from']);
        $nd_booking_date_to = sanitize_text_field($_GET['nd_booking_archive_form_date_range_to']);
        
        $nd_booking_archive_form_guests = sanitize_text_field($_GET['nd_booking_archive_form_guests']);
        if ( $nd_booking_archive_form_guests == '' ) { $nd_booking_archive_form_guests = 1; }

        $nd_booking_nights_number = nd_booking_get_number_night($nd_booking_date_from,$nd_booking_date_to);

        //for calendar
        $nd_booking_new_date_from = new DateTime($nd_booking_date_from);
        $nd_booking_date_number_from_front = date_format($nd_booking_new_date_from, 'd');
        $nd_booking_date_month_from_front = date_format($nd_booking_new_date_from, 'M');
        $nd_booking_date_month_from_front = date_i18n('M',strtotime($nd_booking_date_from));
        $nd_booking_new_date_to = new DateTime($nd_booking_date_to);
        $nd_booking_date_number_to_front = date_format($nd_booking_new_date_to, 'd');
        $nd_booking_date_month_to_front = date_format($nd_booking_new_date_to, 'M');
        $nd_booking_date_month_to_front = date_i18n('M',strtotime($nd_booking_date_to));
        
    } else {

        $nd_booking_date_from = date('m/d/Y');
        $nd_booking_date_to = date('Y-m-d', strtotime(' + 1 days'));
        $nd_booking_archive_form_guests = 1;
        $nd_booking_nights_number = 1;

        //for calendar
        $nd_booking_date_number_from_front = date('d');
        $nd_booking_date_month_from_front = date('M');

        $nd_booking_date_month_from_front = date_i18n('M');

        $nd_booking_date_tomorrow = new DateTime('tomorrow');
        $nd_booking_date_number_to_front = $nd_booking_date_tomorrow->format('d');
        $nd_booking_date_month_to_front = $nd_booking_date_tomorrow->format('M');

        $nd_booking_todayy = date('Y/m/d');
        $nd_booking_tomorroww = date('Y/m/d', strtotime($nd_booking_todayy.' + 1 days'));
        $nd_booking_date_month_to_front = date_i18n('M',strtotime($nd_booking_tomorroww));
        
    }
    //END if dates are set
        
    
    //default price range
    if ( get_option('nd_booking_price_range_default_value') == '' ) { $nd_booking_price_range_default_value = 300; }else{ $nd_booking_price_range_default_value = get_option('nd_booking_price_range_default_value'); }
    $nd_booking_archive_form_max_price_for_day = $nd_booking_price_range_default_value;
    if ( isset( $_GET['nd_booking_archive_form_max_price_for_day'] ) && $_GET['nd_booking_archive_form_max_price_for_day'] !== '' ) {
        $nd_booking_archive_form_max_price_for_day = sanitize_text_field( $_GET['nd_booking_archive_form_max_price_for_day'] );
    }
    

    //branches
    if( isset( $_GET['nd_booking_archive_form_branches'] ) ) { 
        
        $nd_booking_archive_form_branches = sanitize_text_field($_GET['nd_booking_archive_form_branches']);

    }else{

        $nd_booking_archive_form_branches = 0;

    }
    
    if ( $nd_booking_archive_form_branches == 0 ) {
        $nd_booking_archive_form_branches_value = 0;
        $nd_booking_archive_form_branches_compare = '>';
    }else{
        $nd_booking_archive_form_branches_value = $nd_booking_archive_form_branches;
        $nd_booking_archive_form_branches_compare = 'IN';
    }
    //end branches

    $nd_booking_archive_form_services_default = isset( $_GET['nd_booking_archive_form_services'] ) ? sanitize_text_field( $_GET['nd_booking_archive_form_services'] ) : '';
    $nd_booking_archive_form_additional_services_default = isset( $_GET['nd_booking_archive_form_additional_services'] ) ? sanitize_text_field( $_GET['nd_booking_archive_form_additional_services'] ) : '';
    $nd_booking_archive_form_branch_stars_default = isset( $_GET['nd_booking_archive_form_branch_stars'] ) ? sanitize_text_field( $_GET['nd_booking_archive_form_branch_stars'] ) : '';


    $nd_booking_new_date_to = new DateTime($nd_booking_date_to);
    $nd_booking_new_date_to_format_mdy = date_format($nd_booking_new_date_to, 'm/d/Y');

    //for pagination
    $nd_booking_qnt_posts_per_page = 4;

    //prepare query
    $nd_booking_paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1 ;

    $args = array(
        'post_type' => 'nd_booking_cpt_1',
        'posts_per_page' => $nd_booking_qnt_posts_per_page,
        'meta_query' => array(
            array(
                'key'     => 'nd_booking_meta_box_max_people',
                'type' => 'numeric',
                'value'   => $nd_booking_archive_form_guests,
                'compare' => '>=',
            ),
            array(
                'key'     => 'nd_booking_meta_box_min_price',
                'type' => 'numeric',
                'value'   => $nd_booking_archive_form_max_price_for_day,
                'compare' => '<=',
            ),
            array(
                'key' => 'nd_booking_meta_box_branches',
                'value'   => $nd_booking_archive_form_branches_value,
                'compare' => $nd_booking_archive_form_branches_compare,
            ),
        ),
        'paged' => $nd_booking_paged
    );
    $the_query = new WP_Query( $args );

    //pagination
    $nd_booking_qnt_results_posts = $the_query->found_posts;
    $nd_booking_qnt_pagination = ceil($nd_booking_qnt_results_posts / $nd_booking_qnt_posts_per_page);

    

    if ( get_option('nicdark_theme_author') == 1 and get_option('nd_options_page_enable') ) {} else {

        include realpath(dirname( __FILE__ ).'/include/search-results/nd_booking_search_results_order_options.php'); 

    }

    include realpath(dirname( __FILE__ ).'/include/search-results/nd_booking_search_results_right_content.php');

    global $wp;

    $nd_booking_form_action = home_url();
    if ( isset( $wp ) && isset( $wp->request ) ) {
        $nd_booking_form_action = home_url( add_query_arg( array(), $wp->request ) );
    }

    $nd_booking_nights_label = sprintf( _n( '%s Night', '%s Nights', $nd_booking_nights_number, 'nd-booking' ), number_format_i18n( $nd_booking_nights_number ) );
    $nd_booking_guests_label = sprintf( _n( '%s Guest', '%s Guests', $nd_booking_archive_form_guests, 'nd-booking' ), number_format_i18n( $nd_booking_archive_form_guests ) );

    ob_start();
    ?>
    <form id="nd_booking_search_cpt_1_form_sidebar" class="loft-search-toolbar__form" action="<?php echo esc_url( $nd_booking_form_action ); ?>" method="get" onsubmit="return false;">
        <div id="nd_booking_search_main_bg" class="loft-search-toolbar">

            <div class="loft-search-toolbar__field loft-search-toolbar__field--date">
                <label for="nd_booking_archive_form_date_range_from" class="loft-search-toolbar__label"><?php esc_html_e( 'Arrival', 'nd-booking' ); ?></label>
                <div class="loft-search-toolbar__control loft-search-toolbar__control--date">
                    <input type="text" id="nd_booking_archive_form_date_range_from" name="nd_booking_archive_form_date_range_from" class="loft-search-toolbar__input" value="<?php echo esc_attr( $nd_booking_date_from ); ?>" autocomplete="off" readonly />
                </div>
            </div>

            <div class="loft-search-toolbar__field loft-search-toolbar__field--date">
                <label for="nd_booking_archive_form_date_range_to" class="loft-search-toolbar__label"><?php esc_html_e( 'Departure', 'nd-booking' ); ?></label>
                <div class="loft-search-toolbar__control loft-search-toolbar__control--date">
                    <input type="text" id="nd_booking_archive_form_date_range_to" name="nd_booking_archive_form_date_range_to" class="loft-search-toolbar__input" value="<?php echo esc_attr( $nd_booking_new_date_to_format_mdy ); ?>" autocomplete="off" readonly />
                </div>
            </div>

            <div class="loft-search-toolbar__field loft-search-toolbar__field--guests">
                <label class="loft-search-toolbar__label" for="nd_booking_archive_form_guests"><?php esc_html_e( 'Guests', 'nd-booking' ); ?></label>
                <div class="loft-search-toolbar__control loft-search-toolbar__control--guests">
                    <button type="button" class="loft-search-toolbar__guest-btn" data-direction="down" aria-label="<?php esc_attr_e( 'Decrease guests', 'nd-booking' ); ?>">−</button>
                    <span class="loft-search-toolbar__guests-value" id="loft_search_guest_display"><?php echo esc_html( $nd_booking_guests_label ); ?></span>
                    <button type="button" class="loft-search-toolbar__guest-btn" data-direction="up" aria-label="<?php esc_attr_e( 'Increase guests', 'nd-booking' ); ?>">+</button>
                </div>
                <input type="hidden" id="nd_booking_archive_form_guests" name="nd_booking_archive_form_guests" value="<?php echo esc_attr( $nd_booking_archive_form_guests ); ?>" />
            </div>

            <div class="loft-search-toolbar__field loft-search-toolbar__field--summary">
                <span class="loft-search-toolbar__label"><?php esc_html_e( 'Nights', 'nd-booking' ); ?></span>
                <div class="loft-search-toolbar__summary" id="nd_booking_nights_display"><?php echo esc_html( $nd_booking_nights_label ); ?></div>
            </div>

            <div class="loft-search-toolbar__field loft-search-toolbar__field--actions">
                <span class="loft-search-toolbar__label">&nbsp;</span>
                <button type="button" class="loft-search-card__btn loft-search-card__btn--primary loft-search-toolbar__submit"><?php esc_html_e( 'Search', 'nd-booking' ); ?></button>
            </div>
        </div>

        <input type="hidden" id="nd_booking_archive_form_branches" name="nd_booking_archive_form_branches" value="<?php echo esc_attr( $nd_booking_archive_form_branches ); ?>" />
        <input type="hidden" id="nd_booking_archive_form_max_price_for_day" name="nd_booking_archive_form_max_price_for_day" value="<?php echo esc_attr( $nd_booking_archive_form_max_price_for_day ); ?>" />
        <input type="hidden" id="nd_booking_archive_form_services" name="nd_booking_archive_form_services" value="<?php echo esc_attr( $nd_booking_archive_form_services_default ); ?>" />
        <input type="hidden" id="nd_booking_archive_form_additional_services" name="nd_booking_archive_form_additional_services" value="<?php echo esc_attr( $nd_booking_archive_form_additional_services_default ); ?>" />
        <input type="hidden" id="nd_booking_archive_form_branch_stars" name="nd_booking_archive_form_branch_stars" value="<?php echo esc_attr( $nd_booking_archive_form_branch_stars_default ); ?>" />
    </form>

    <script type="text/javascript">
    jQuery(document).ready(function($){
        var nightsLabelSingular = '<?php echo esc_js( __( 'Night', 'nd-booking' ) ); ?>';
        var nightsLabelPlural = '<?php echo esc_js( __( 'Nights', 'nd-booking' ) ); ?>';
        var guestsLabelSingular = '<?php echo esc_js( __( 'Guest', 'nd-booking' ) ); ?>';
        var guestsLabelPlural = '<?php echo esc_js( __( 'Guests', 'nd-booking' ) ); ?>';

        var $checkIn = $('#nd_booking_archive_form_date_range_from');
        var $checkOut = $('#nd_booking_archive_form_date_range_to');
        var $guestInput = $('#nd_booking_archive_form_guests');
        var $guestDisplay = $('#loft_search_guest_display');
        var $nightsDisplay = $('#nd_booking_nights_display');
        var $searchButton = $('.loft-search-toolbar__submit');

        function formatGuests(value){
            return value + ' ' + (value === 1 ? guestsLabelSingular : guestsLabelPlural);
        }

        function formatNights(value){
            return value + ' ' + (value === 1 ? nightsLabelSingular : nightsLabelPlural);
        }

        function updateNightsDisplay(){
            var checkInVal = $checkIn.val();
            var checkOutVal = $checkOut.val();

            if (!checkInVal || !checkOutVal) {
                $nightsDisplay.text('—');
                return;
            }

            var start = $.datepicker.parseDate('mm/dd/yy', checkInVal);
            var end = $.datepicker.parseDate('mm/dd/yy', checkOutVal);

            if (!start) {
                return;
            }

            if (!end || end <= start) {
                end = new Date(start.getTime());
                end.setDate(end.getDate() + 1);
                $checkOut.datepicker('setDate', end);
            }

            var nights = Math.max(1, Math.round((end - start) / 86400000));
            $nightsDisplay.text(formatNights(nights));
        }

        $checkIn.datepicker({
            defaultDate: '+0',
            minDate: 0,
            dateFormat: 'mm/dd/yy',
            firstDay: 0,
            numberOfMonths: 1,
            onClose: function(){
                var minDate = $checkIn.datepicker('getDate');
                if (minDate) {
                    var minCheckout = new Date(minDate.getTime());
                    minCheckout.setDate(minCheckout.getDate() + 1);
                    $checkOut.datepicker('option', 'minDate', minCheckout);
                    var currentCheckout = $checkOut.datepicker('getDate');
                    if (!currentCheckout || currentCheckout <= minDate) {
                        $checkOut.datepicker('setDate', minCheckout);
                    }
                }
                updateNightsDisplay();
                nd_booking_sorting(1);
            }
        });

        $checkOut.datepicker({
            defaultDate: '+1',
            minDate: '+1d',
            dateFormat: 'mm/dd/yy',
            firstDay: 0,
            numberOfMonths: 1,
            onClose: function(){
                updateNightsDisplay();
                nd_booking_sorting(1);
            }
        });

        var initialCheckIn = '<?php echo esc_js( $nd_booking_date_from ); ?>';
        if (initialCheckIn) {
            $checkIn.datepicker('setDate', $.datepicker.parseDate('mm/dd/yy', initialCheckIn));
        }

        var initialCheckOut = '<?php echo esc_js( $nd_booking_new_date_to_format_mdy ); ?>';
        if (initialCheckOut) {
            $checkOut.datepicker('setDate', $.datepicker.parseDate('mm/dd/yy', initialCheckOut));
            $checkOut.datepicker('option', 'minDate', $checkIn.datepicker('getDate') ? new Date($checkIn.datepicker('getDate').getTime() + 86400000) : '+1d');
        }

        updateNightsDisplay();
        $guestDisplay.text(formatGuests(parseInt($guestInput.val(), 10) || 1));

        $('.loft-search-toolbar__control--date').on('click', function(){
            $(this).find('.loft-search-toolbar__input').datepicker('show');
        });

        $('.loft-search-toolbar__guest-btn').on('click', function(){
            var direction = $(this).data('direction');
            var current = parseInt($guestInput.val(), 10) || 1;
            current = direction === 'up' ? current + 1 : Math.max(1, current - 1);
            $guestInput.val(current);
            $guestDisplay.text(formatGuests(current));
            nd_booking_sorting(1);
        });

        $searchButton.on('click', function(){
            nd_booking_sorting(1);
        });
    });
    </script>
    <?php
    $nd_booking_shortcode_left_content = ob_get_clean();

    //START final result
    $nd_booking_shortcode_result = '';

    if ( ! defined( 'ND_BOOKING_SEARCH_RESULTS_LAYOUT_STYLES' ) ) {
        define( 'ND_BOOKING_SEARCH_RESULTS_LAYOUT_STYLES', true );

        $nd_booking_shortcode_result .= '
        <style>
          .nd_booking_search_results_layout {
            display: flex;
            flex-direction: column;
            gap: 36px;
          }

          #nd_booking_search_cpt_1_filters {
            background: transparent;
            padding: 0;
            border: 0;
            box-shadow: none;
            width: 100%;
          }

          #nd_booking_search_cpt_1_filters form {
            margin: 0;
            width: 100%;
          }

          .loft-search-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: flex-end;
            padding: 26px 30px;
            background: #ffffff;
            border-radius: 26px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 30px 70px rgba(15, 23, 42, 0.14);
          }

          .loft-search-toolbar__field {
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1 1 200px;
            min-width: 190px;
          }

          .loft-search-toolbar__label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #475467;
          }

          .loft-search-toolbar__control,
          .loft-search-toolbar__summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: #f8fafc;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            min-height: 64px;
          }

          .loft-search-toolbar__control:focus-within {
            border-color: #d4af37;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.18);
            background: #ffffff;
          }

          .loft-search-toolbar__input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            outline: none;
            padding: 0;
          }

          .loft-search-toolbar__control--date {
            cursor: pointer;
          }

          .loft-search-toolbar__control--date .loft-search-toolbar__input {
            cursor: pointer;
          }

          .loft-search-toolbar__control--guests {
            justify-content: center;
          }

          .loft-search-toolbar__guest-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid rgba(212, 175, 55, 0.4);
            background: #fff7e0;
            color: #a16207;
            font-weight: 700;
            font-size: 22px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
          }

          .loft-search-toolbar__guest-btn:hover,
          .loft-search-toolbar__guest-btn:focus {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(212, 175, 55, 0.25);
            outline: none;
          }

          .loft-search-toolbar__guests-value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
          }

          .loft-search-toolbar__summary {
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            background: #fef3c7;
            border-color: rgba(212, 175, 55, 0.4);
          }

          .loft-search-toolbar__field--summary {
            flex: 0.8 1 180px;
          }

          .loft-search-toolbar__field--guests {
            flex: 0.8 1 200px;
          }

          .loft-search-toolbar__field--actions {
            align-self: stretch;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            flex: 0 0 auto;
            min-width: 190px;
          }

          .loft-search-toolbar__field--actions .loft-search-toolbar__label {
            visibility: hidden;
          }

          button.loft-search-toolbar__submit {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            padding: 18px 28px;
            appearance: none;
            background: #FFDA44;
            border: none;
            border-radius: 999px;
            box-shadow: 0 18px 32px rgba(255, 218, 68, 0.30);
            color: #1A1A1A;
            cursor: pointer;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            transition: all 0.2s ease-in-out;
          }

          button.loft-search-toolbar__submit:hover,
          button.loft-search-toolbar__submit:focus {
            background: #FFD029;
            box-shadow: 0 22px 40px rgba(255, 218, 68, 0.36);
            color: #1A1A1A;
          }

          @media (max-width: 1200px) {
            .loft-search-toolbar {
              padding: 24px 26px;
              gap: 16px;
            }

            .loft-search-toolbar__field {
              flex: 1 1 180px;
              min-width: 180px;
            }
          }

          @media (max-width: 768px) {
            .loft-search-toolbar {
              padding: 22px;
            }

            .loft-search-toolbar__field--actions {
              min-width: 100%;
              order: 99;
            }

            button.loft-search-toolbar__submit {
              justify-content: center;
            }
          }

          @media (max-width: 480px) {
            .loft-search-toolbar__field {
              min-width: 100%;
            }
          }
        </style>';
    }

    $nd_booking_shortcode_result .='

    <div class="nd_booking_section nd_booking_search_results_layout">

        <div id="nd_booking_search_cpt_1_filters" class="nd_booking_search_filters_wrapper">

            '.$nd_booking_shortcode_left_content.'

        </div>

        <div id="nd_booking_search_cpt_1_content" class="nd_booking_search_results_content">

            '.$nd_booking_shortcode_right_content.'

        </div>

    </div>';
    //END final result


    return $nd_booking_shortcode_result;
        


}
add_shortcode('nd_booking_search_results', 'nd_booking_shortcode_search_results');
//END nd_booking_search_results









//START function for AJAX
function nd_booking_sorting_php() {


    check_ajax_referer( 'nd_booking_sorting_nonce', 'nd_booking_sorting_security' );


    //for pagination
    $nd_booking_qnt_posts_per_page = 4;

    //recover var
    $nd_booking_paged = sanitize_text_field($_GET['nd_booking_paged']);
    $nd_booking_archive_form_branches = sanitize_text_field($_GET['nd_booking_archive_form_branches']);
    $nd_booking_date_from = sanitize_text_field($_GET['nd_booking_archive_form_date_range_from']);
    $nd_booking_date_to = sanitize_text_field($_GET['nd_booking_archive_form_date_range_to']);
    $nd_booking_archive_form_guests = sanitize_text_field($_GET['nd_booking_archive_form_guests']);
    $nd_booking_archive_form_max_price_for_day = sanitize_text_field($_GET['nd_booking_archive_form_max_price_for_day']);
    $nd_booking_archive_form_services = sanitize_text_field($_GET['nd_booking_archive_form_services']);
    $nd_booking_archive_form_additional_services = sanitize_text_field($_GET['nd_booking_archive_form_additional_services']);
    $nd_booking_search_filter_layout = sanitize_text_field($_GET['nd_booking_search_filter_layout']);
    $nd_booking_archive_form_branch_stars = sanitize_text_field($_GET['nd_booking_archive_form_branch_stars']);
    
    

    //order
    $nd_booking_search_filter_options_meta_key = sanitize_text_field($_GET['nd_booking_search_filter_options_meta_key']);
    $nd_booking_search_filter_options_order = sanitize_text_field($_GET['nd_booking_search_filter_options_order']);
    if ( $nd_booking_search_filter_options_meta_key == '' ) { 
        $nd_booking_orderby = 'date';
        $nd_booking_order = 'DESC';
    }else{
        $nd_booking_orderby = 'meta_value_num';
        $nd_booking_order = $nd_booking_search_filter_options_order;
    }
    
    //branch
    if ( $nd_booking_archive_form_branches == 0 ) { 
        $nd_booking_archive_form_branches_value = 0;
        $nd_booking_archive_form_branches_compare = '>'; 
    }else{  
        $nd_booking_archive_form_branches_value = $nd_booking_archive_form_branches;
        $nd_booking_archive_form_branches_compare = 'IN';
    }


    $args = array(
        'post_type' => 'nd_booking_cpt_1',
        'posts_per_page' => $nd_booking_qnt_posts_per_page,
        'orderby' => $nd_booking_orderby,
        'meta_key' => $nd_booking_search_filter_options_meta_key,
        'order' => $nd_booking_order,
        'meta_query' => array(
            array(
                'key'     => 'nd_booking_meta_box_max_people',
                'type' => 'numeric',
                'value'   => $nd_booking_archive_form_guests,
                'compare' => '>=',
            ),
            array(
                    'key'     => 'nd_booking_meta_box_min_price',
                    'type' => 'numeric',
                    'value'   => $nd_booking_archive_form_max_price_for_day,
                    'compare' => '<=',
                ),
            array(
                'key' => 'nd_booking_meta_box_branches',
                'type' => 'numeric',
                'value'   => $nd_booking_archive_form_branches_value,
                'compare' => $nd_booking_archive_form_branches_compare,
            ),
        ),
        'paged' => $nd_booking_paged
    );

    //START add new service to args
    $nd_booking_services_array = explode(',', $nd_booking_archive_form_services );

    for ($nd_booking_services_i = 0; $nd_booking_services_i < count($nd_booking_services_array)-1; $nd_booking_services_i++) {
        
        $nd_booking_service_slug = get_post_field( 'post_name', $nd_booking_services_array[$nd_booking_services_i] );
        $nd_booking_add_new_service_to_meta_query_position = 3+$nd_booking_services_i;
        
        $args['meta_query'][$nd_booking_add_new_service_to_meta_query_position] = array(
            'key' => 'nd_booking_meta_box_normal_services',
            'value'   => $nd_booking_service_slug,
            'compare' => 'LIKE',
        );

    }
    //END

    //START add new additional service to args
    $nd_booking_start_array_position_for_additional_services = 3+count($nd_booking_services_array)-1;
    $nd_booking_additional_services_array = explode(',', $nd_booking_archive_form_additional_services );

    for ($nd_booking_additional_services_i = 0; $nd_booking_additional_services_i < count($nd_booking_additional_services_array)-1; $nd_booking_additional_services_i++) {
        
        $nd_booking_additional_service_slug = get_post_field( 'post_name', $nd_booking_additional_services_array[$nd_booking_additional_services_i] );
        $nd_booking_add_new_additional_service_to_meta_query_position = $nd_booking_start_array_position_for_additional_services+$nd_booking_additional_services_i;
        
        $args['meta_query'][$nd_booking_add_new_additional_service_to_meta_query_position] = array(
            'key' => 'nd_booking_meta_box_additional_services',
            'value'   => $nd_booking_additional_service_slug,
            'compare' => 'LIKE',
        );

    }
    //END

    $the_query = new WP_Query( $args );

    //pagination
    $nd_booking_qnt_results_posts = $the_query->found_posts;
    $nd_booking_qnt_pagination = ceil($nd_booking_qnt_results_posts / $nd_booking_qnt_posts_per_page);


    //start output AJAX content
    $nd_booking_shortcode_right_content = '

    <div id="nd_booking_content_result" class="nd_booking_section">
        <div class="nd_booking_search_results_stage">
            <div id="nd_booking_search_results_loader" class="nd_booking_search_results_loader">
                <div class="nd_booking_search_results_loader_inner">
                    <div class="nd_booking_loader_spinner"></div>
                    <p>'.__('Vérification des disponibilités pour vos dates…','nd-booking').'</p>
                </div>
            </div>
            <div class="nd_booking_section nd_booking_masonry_content">';


        if ( $nd_booking_qnt_results_posts == 0 ) { $nd_booking_shortcode_right_content .= '


                <div id="nd_booking_search_cpt_1_no_results" class="nd_booking_section nd_booking_padding_15 nd_booking_box_sizing_border_box">
                    <div class="nd_booking_section nd_booking_bg_yellow nd_booking_padding_15_20 nd_booking_box_sizing_border_box">
                      <img class="nd_booking_float_left nd_booking_display_none_all_iphone" width="20" src="'.esc_url(plugins_url('icon-warning-white.svg', __FILE__ )).'">
                      <h3 class="nd_booking_float_left nd_options_color_white nd_booking_color_white nd_options_first_font nd_booking_margin_left_10">'.__('Aucun résultat pour cette recherche','nd-booking').'</h3>
                    </div>
                </div>


        '; }

        //START loop
        while ( $the_query->have_posts() ) : $the_query->the_post();

            #$nd_booking_layout_selected = dirname( __FILE__ ).'/include/search-results/nd_booking_post_preview-'.$nd_booking_search_filter_layout.'.php';
            $nd_booking_layout_selected = dirname( __FILE__ ).'/include/search-results/nd_booking_post_preview-1.php';
            include realpath($nd_booking_layout_selected);

        endwhile;
        //END loop

        $nd_booking_shortcode_right_content .= '</div>
        </div>';


            include realpath(dirname( __FILE__ ).'/include/search-results/nd_booking_search_results_pagination.php'); 


        $nd_booking_shortcode_right_content .= '</div>';


    wp_reset_postdata();

    $nd_booking_allowed_html = [
        'div' => [
            'id' => [],
            'class' => [],
            'style' => [],
        ],
        'img' => [ 
            'alt' => [],
            'class' => [], 
            'src' => [],
            'width' => [],
            'height' => [],
            'loading' => [],
            'style' => [],
        ],
        'p' => [ 
            'class' => [],
            'style' => [],
        ],
        'a' => [ 
            'href' => [],
            'class' => [],
            'style' => [],
            'title' => [],
            'onclick' => [],
        ],
        'h1' => [
            'id' => [],
            'class' => [],
            'style' => [],
        ],              
        'form' => [ 
            'id' => [],
            'method' => [],
            'action' => [],
            'style' => [],
        ],
        'input' => [ 
            'type' => [],
            'name' => [],
            'value' => [],
            'style' => [],
            'class' => [],
        ],
        'style' => [],
        'table' => [
            'id' => [],
            'class' => [],
            'style' => [],
        ],
        'tbody' => [],
        'tr' => [ 
            'id' => [],
            'class' => [],
            'style' => [],
        ],
        'td' => [
            'id' => [],
            'class' => [],
            'style' => [],
        ],
        'span' => [
            'id' => [],
            'class' => [],
            'style' => [],
        ],
    ];

    echo wp_kses( $nd_booking_shortcode_right_content, $nd_booking_allowed_html );

    die();

}
add_action( 'wp_ajax_nd_booking_sorting_php', 'nd_booking_sorting_php' );
add_action( 'wp_ajax_nopriv_nd_booking_sorting_php', 'nd_booking_sorting_php' );
//END
