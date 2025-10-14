<?php

function nd_booking_get_upload_path_from_token( $token ) {
    if ( empty( $token ) ) {
        return '';
    }

    $path    = get_transient( 'nd_booking_upload_' . $token );
    $uploads = wp_upload_dir();
    $base    = wp_normalize_path( $uploads['basedir'] );
    $file    = wp_normalize_path( $path );

    delete_transient( 'nd_booking_upload_' . $token );

    if ( $path && strpos( $file, $base ) === 0 ) {
        return $file;
    }

    error_log( 'nd_booking: invalid upload token ' . $token );

    return '';
}

//START  nd_booking_checkout
function nd_booking_shortcode_checkout() {

    $nd_booking_shortcode_result = '';

    $nd_booking_tax_lodging = 0.0;
    $nd_booking_tax_gst     = 0.0;
    $nd_booking_tax_qst     = 0.0;

    if ( ! headers_sent() ) {
        if ( function_exists( 'session_status' ) ) {
            if ( PHP_SESSION_NONE === session_status() ) {
                session_start();
            }
        } elseif ( ! session_id() ) {
            session_start();
        }
    }

    if ( isset( $_SESSION ) && is_array( $_SESSION ) ) {
        if ( isset( $_SESSION['nd_booking_tax_lodging'] ) ) {
            $nd_booking_tax_lodging = round( floatval( $_SESSION['nd_booking_tax_lodging'] ), 2 );
        }
        if ( isset( $_SESSION['nd_booking_tax_gst'] ) ) {
            $nd_booking_tax_gst = round( floatval( $_SESSION['nd_booking_tax_gst'] ), 2 );
        }
        if ( isset( $_SESSION['nd_booking_tax_qst'] ) ) {
            $nd_booking_tax_qst = round( floatval( $_SESSION['nd_booking_tax_qst'] ), 2 );
        }
    }

    $nd_booking_total_tax_amount = round( $nd_booking_tax_lodging + $nd_booking_tax_gst + $nd_booking_tax_qst, 2 );

    if( isset( $_POST['nd_booking_form_booking_arrive'] ) ) {  $nd_booking_form_booking_arrive = sanitize_text_field($_POST['nd_booking_form_booking_arrive']); }else{ $nd_booking_form_booking_arrive = '';}
    if( isset( $_POST['nd_booking_form_checkout_arrive'] ) ) {  $nd_booking_form_checkout_arrive = sanitize_text_field($_POST['nd_booking_form_checkout_arrive']); }else{ $nd_booking_form_checkout_arrive = '';}


    //ARRIVE FROM BOOKING FORM
    if ( $nd_booking_form_booking_arrive == 1 ) {


        //get value
        $nd_booking_booking_form_final_price = sanitize_text_field($_POST['nd_booking_booking_form_final_price']);
        if( isset( $_POST['nd_booking_booking_form_base_price'] ) ) {  $nd_booking_booking_form_base_price = sanitize_text_field($_POST['nd_booking_booking_form_base_price']); }else{ $nd_booking_booking_form_base_price = '';}
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

        $nd_booking_booking_form_final_price = floatval( $nd_booking_booking_form_final_price );
        $nd_booking_booking_form_base_price = floatval( $nd_booking_booking_form_base_price );

        if ( isset( $_POST['guest_id_number'] ) ) {
            $nd_booking_guest_id_number = sanitize_text_field( $_POST['guest_id_number'] );
        } else {
            $nd_booking_guest_id_number = '';
        }
        if ( isset( $_POST['guest_id_type'] ) ) {
            $nd_booking_guest_id_type = sanitize_text_field( $_POST['guest_id_type'] );
        } else {
            $nd_booking_guest_id_type = '';
        }

        $nd_booking_guest_id_front = '';
        $nd_booking_guest_id_back = '';

        if ( ! empty( $_FILES['guest_id_front']['name'] ) || ! empty( $_FILES['guest_id_back']['name'] ) ) {
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $upload_overrides = [
                'test_form' => false,
                'mimes'     => [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png'          => 'image/png',
                ],
            ];

            $max_size = 2 * 1024 * 1024; // 2MB

            if ( ! empty( $_FILES['guest_id_front']['name'] ) && $_FILES['guest_id_front']['size'] <= $max_size ) {
                $front = wp_handle_upload( $_FILES['guest_id_front'], $upload_overrides );
                if ( empty( $front['error'] ) ) {
                    $nd_booking_guest_id_front = $front['file'];
                }
            }
            if ( ! empty( $_FILES['guest_id_back']['name'] ) && $_FILES['guest_id_back']['size'] <= $max_size ) {
                $back = wp_handle_upload( $_FILES['guest_id_back'], $upload_overrides );
                if ( empty( $back['error'] ) ) {
                    $nd_booking_guest_id_back = $back['file'];
                }
            }
        }

        $nd_booking_guest_id_front_token = '';
        if ( $nd_booking_guest_id_front ) {
            $nd_booking_guest_id_front_token = wp_generate_password( 12, false );
            set_transient( 'nd_booking_upload_' . $nd_booking_guest_id_front_token, $nd_booking_guest_id_front, HOUR_IN_SECONDS );
        }

        $nd_booking_guest_id_back_token = '';
        if ( $nd_booking_guest_id_back ) {
            $nd_booking_guest_id_back_token = wp_generate_password( 12, false );
            set_transient( 'nd_booking_upload_' . $nd_booking_guest_id_back_token, $nd_booking_guest_id_back, HOUR_IN_SECONDS );
        }

        $nd_booking_guest_id_front = $nd_booking_guest_id_front_token;
        $nd_booking_guest_id_back  = $nd_booking_guest_id_back_token;

        //ids
        $nd_booking_booking_form_post_id = sanitize_text_field($_POST['nd_booking_booking_form_post_id']);
        $nd_booking_ids_array = explode('-', $nd_booking_booking_form_post_id ); 
        $nd_booking_booking_form_post_id = $nd_booking_ids_array[0];
        $nd_booking_id_room = $nd_booking_ids_array[1];

        $nd_booking_tax_base_amount = $nd_booking_booking_form_base_price;
        if ( $nd_booking_tax_base_amount <= 0 && isset( $_SESSION['nd_booking_tax_base'] ) ) {
            $nd_booking_tax_base_amount = floatval( $_SESSION['nd_booking_tax_base'] );
        }
        if ( $nd_booking_tax_base_amount <= 0 && $nd_booking_total_tax_amount > 0 ) {
            $nd_booking_tax_base_amount = round( $nd_booking_booking_form_final_price - $nd_booking_total_tax_amount, 2 );
        }

        if ( $nd_booking_tax_base_amount > 0 ) {
            $nd_booking_tax_breakdown = nd_booking_calculate_tax_breakdown( $nd_booking_tax_base_amount );
        } else {
            $nd_booking_tax_breakdown = nd_booking_calculate_tax_breakdown_from_total( $nd_booking_booking_form_final_price );
        }

        $nd_booking_tax_base_amount = $nd_booking_tax_breakdown['base'];
        $nd_booking_tax_lodging = isset( $nd_booking_tax_breakdown['taxes']['lodging'] ) ? $nd_booking_tax_breakdown['taxes']['lodging']['amount'] : 0.0;
        $nd_booking_tax_gst = isset( $nd_booking_tax_breakdown['taxes']['gst'] ) ? $nd_booking_tax_breakdown['taxes']['gst']['amount'] : 0.0;
        $nd_booking_tax_qst = isset( $nd_booking_tax_breakdown['taxes']['qst'] ) ? $nd_booking_tax_breakdown['taxes']['qst']['amount'] : 0.0;
        $nd_booking_total_tax_amount = $nd_booking_tax_breakdown['total_tax'];
        $nd_booking_booking_form_final_price = $nd_booking_tax_breakdown['total'];

        if ( isset( $_SESSION ) && is_array( $_SESSION ) ) {
            $_SESSION['nd_booking_tax_base'] = $nd_booking_tax_base_amount;
            $_SESSION['nd_booking_tax_lodging'] = $nd_booking_tax_lodging;
            $_SESSION['nd_booking_tax_gst'] = $nd_booking_tax_gst;
            $_SESSION['nd_booking_tax_qst'] = $nd_booking_tax_qst;
            $_SESSION['nd_booking_tax_total'] = $nd_booking_total_tax_amount;
            $_SESSION['nd_booking_final_price'] = $nd_booking_booking_form_final_price;
        }

        include realpath(dirname( __FILE__ ).'/include/checkout/nd_booking_checkout_left_content.php');
        include realpath(dirname( __FILE__ ).'/include/checkout/nd_booking_checkout_right_content.php');
        include realpath(dirname( __FILE__ ).'/include/checkout/nd_booking_checkout_payment_options.php');

        if ( isset( $nd_booking_conditions_markup ) ) {
            $nd_booking_shortcode_right_content .= $nd_booking_conditions_markup;
        }

        $nd_booking_shortcode_right_content .= '</div>';

        $nd_booking_checkin_timestamp  = $nd_booking_booking_form_date_from ? strtotime( $nd_booking_booking_form_date_from ) : false;
        $nd_booking_checkout_timestamp = $nd_booking_booking_form_date_to ? strtotime( $nd_booking_booking_form_date_to ) : false;

        $nd_booking_meta_data = array(
            'check_in'  => $nd_booking_checkin_timestamp ? date_i18n( get_option( 'date_format', 'M j, Y' ), $nd_booking_checkin_timestamp ) : '',
            'check_out' => $nd_booking_checkout_timestamp ? date_i18n( get_option( 'date_format', 'M j, Y' ), $nd_booking_checkout_timestamp ) : '',
            'nights'    => '',
            'guests'    => '',
        );

        $nd_booking_total_nights = absint( nd_booking_get_number_night( $nd_booking_booking_form_date_from, $nd_booking_booking_form_date_to ) );
        if ( $nd_booking_total_nights > 0 ) {
            $nd_booking_meta_data['nights'] = sprintf(
                _n( '%s nuit', '%s nuits', $nd_booking_total_nights, 'nd-booking' ),
                number_format_i18n( $nd_booking_total_nights )
            );
        }

        $nd_booking_total_guests = absint( $nd_booking_booking_form_guests );
        if ( $nd_booking_total_guests > 0 ) {
            $nd_booking_meta_data['guests'] = sprintf(
                _n( '%s invité', '%s invités', $nd_booking_total_guests, 'nd-booking' ),
                number_format_i18n( $nd_booking_total_guests )
            );
        }

        $nd_booking_price_total      = floatval( $nd_booking_booking_form_final_price );
        $nd_booking_price_per_night  = $nd_booking_total_nights > 0 ? $nd_booking_price_total / $nd_booking_total_nights : $nd_booking_price_total;
        $nd_booking_price_total_form = nd_booking_format_decimal( $nd_booking_price_total );
        $nd_booking_price_night_form = nd_booking_format_decimal( $nd_booking_price_per_night );
        $nd_booking_currency         = nd_booking_get_currency();

        $nd_booking_booking_title = get_the_title();
        $nd_booking_image_src      = get_the_post_thumbnail_url( get_the_ID(), 'large' );
        $nd_booking_image_alt      = $nd_booking_booking_title ? $nd_booking_booking_title : __( 'Room', 'nd-booking' );

        
        $nd_booking_checkout_form_markup = $nd_booking_shortcode_right_content;
        remove_shortcode( 'nd_booking_form_checkout' );
        add_shortcode(
            'nd_booking_form_checkout',
            function () use ( $nd_booking_checkout_form_markup ) {
                return $nd_booking_checkout_form_markup;
            }
        );

        ob_start();
        ?>
        <div class="loft-checkout-wrapper">
          <div class="checkout-header">
            <h2>Finalisez votre réservation</h2>
            <p>Réservez votre séjour en toute sécurité.</p>
            <div class="checkout-countdown">
              <span class="timer-icon">⏳</span>
              <span class="timer-text">9:41 restantes pour sécuriser ce tarif.</span>
            </div>
          </div>

          <div class="checkout-main">
            <div class="checkout-summary">
              <div class="summary-card">
                <?php if ( $nd_booking_image_src ) : ?>
                <img src="<?php echo esc_url( $nd_booking_image_src ); ?>" alt="<?php echo esc_attr( $nd_booking_image_alt ); ?>" class="summary-image">
                <?php endif; ?>
                <div class="summary-details">
                  <h3><?php echo esc_html( $nd_booking_booking_title ); ?></h3>
                  <ul>
                    <?php if ( ! empty( $nd_booking_meta_data['check_in'] ) ) : ?>
                    <li><strong>Arrivée:</strong> <?php echo esc_html( $nd_booking_meta_data['check_in'] ); ?></li>
                    <?php endif; ?>
                    <?php if ( ! empty( $nd_booking_meta_data['check_out'] ) ) : ?>
                    <li><strong>Départ:</strong> <?php echo esc_html( $nd_booking_meta_data['check_out'] ); ?></li>
                    <?php endif; ?>
                    <?php if ( ! empty( $nd_booking_meta_data['nights'] ) ) : ?>
                    <li><strong>Nuits:</strong> <?php echo esc_html( $nd_booking_meta_data['nights'] ); ?></li>
                    <?php endif; ?>
                    <?php if ( ! empty( $nd_booking_meta_data['guests'] ) ) : ?>
                    <li><strong>Invités:</strong> <?php echo esc_html( $nd_booking_meta_data['guests'] ); ?></li>
                    <?php endif; ?>
                  </ul>
                  <div class="summary-total">
                    <div class="summary-badge">Meilleur tarif garanti</div>
                    <p>Total</p>
                    <h2><?php echo esc_html( $nd_booking_price_total_form ); ?> <?php echo esc_html( $nd_booking_currency ); ?></h2>
                    <p class="per-night">Taxes incluses</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="checkout-form">
              <div class="secure-banner">
                <span class="lock-icon">🔒</span> Paiement sécurisé SSL 256-bit
              </div>
              <div class="card-logos">
                <span class="card-logo">Visa</span>
                <span class="card-logo">Mastercard</span>
                <span class="card-logo">Amex</span>
                <span class="card-logo">Interac</span>
              </div>
              <h3>Informations du client</h3>
              <?php echo do_shortcode( '[nd_booking_form_checkout]' ); ?>
            </div>
          </div>
        </div>

        <style>
        body {
          background: #f8fafc;
          font-family: "Poppins", sans-serif;
          color: #222;
        }

        .loft-checkout-wrapper {
          padding: 0 24px 80px;
        }

        /* HEADER */
        .checkout-header {
          text-align: center;
          margin: 60px 0 40px;
        }
        .checkout-header h2 {
          font-size: 28px;
          font-weight: 700;
          color: #1b1b1b;
        }
        .checkout-header p {
          color: #666;
          font-size: 15px;
          margin-bottom: 18px;
        }

        .checkout-countdown {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          background: #fff;
          padding: 10px 18px;
          border-radius: 999px;
          border: 1px solid rgba(27,27,27,0.1);
          box-shadow: 0 6px 20px rgba(0,0,0,0.08);
          font-weight: 600;
          color: #1b1b1b;
        }

        .timer-icon {
          font-size: 16px;
        }

        /* MAIN LAYOUT */
        .checkout-main {
          display: flex;
          flex-wrap: wrap;
          gap: 40px;
          max-width: 1100px;
          margin: 0 auto;
        }

        /* LEFT SUMMARY */
        .checkout-summary {
          flex: 0 0 35%;
        }
        .summary-card {
          background: #fff;
          border-radius: 16px;
          box-shadow: 0 4px 16px rgba(0,0,0,0.08);
          overflow: hidden;
          border: 1px solid #f0f0f0;
        }
        .summary-image {
          width: 100%;
          height: auto;
          display: block;
        }
        .summary-details {
          padding: 24px;
        }
        .summary-details h3 {
          font-size: 20px;
          font-weight: 600;
          margin-bottom: 12px;
          color: #111;
        }
        .summary-details ul {
          list-style: none;
          padding: 0;
          margin: 0 0 20px;
        }
        .summary-details li {
          font-size: 14px;
          color: #555;
          margin-bottom: 6px;
        }
        .summary-total {
          background: #fff8dc;
          border: 1px solid #FFD43B;
          border-radius: 12px;
          text-align: center;
          padding: 18px 16px;
          box-shadow: 0 4px 10px rgba(255,212,59,0.3);
        }
        .summary-total h2 {
          margin: 6px 0 4px;
          font-size: 26px;
          color: #1a1a1a;
          letter-spacing: 0.5px;
        }
        .summary-total p {
          margin: 0;
        }
        .summary-total .per-night {
          font-size: 13px;
          color: #6b6b6b;
          margin-top: 6px;
        }
        .summary-badge {
          display: inline-block;
          background: #FFD43B;
          color: #1a1a1a;
          font-weight: 600;
          padding: 6px 16px;
          border-radius: 999px;
          margin-bottom: 12px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          box-shadow: 0 6px 16px rgba(255,212,59,0.4);
        }

        /* RIGHT FORM */
        .checkout-form {
          flex: 1;
          background: #fff;
          border-radius: 16px;
          box-shadow: 0 4px 16px rgba(0,0,0,0.06);
          padding: 40px;
        }
        .secure-banner {
          background: #f1fff4;
          color: #1d7a1d;
          font-weight: 600;
          border: 1px solid #b6e3b7;
          border-radius: 10px;
          padding: 12px 18px;
          display: inline-flex;
          align-items: center;
          box-shadow: 0 2px 10px rgba(0,0,0,0.05);
          margin-bottom: 18px;
          gap: 8px;
        }
        .lock-icon {
          font-size: 16px;
        }
        .card-logos {
          display: flex;
          gap: 10px;
          margin-bottom: 25px;
          flex-wrap: wrap;
        }
        .card-logo {
          padding: 8px 14px;
          border-radius: 8px;
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          font-size: 13px;
          font-weight: 600;
          letter-spacing: 0.4px;
          color: #1b1b1b;
          box-shadow: inset 0 1px 0 rgba(255,255,255,0.7), 0 1px 4px rgba(15,23,42,0.08);
        }
        .checkout-form h3 {
          font-size: 20px;
          font-weight: 700;
          color: #1b1b1b;
          margin-bottom: 25px;
        }

        /* INPUTS */
        .checkout-form input,
        .checkout-form select,
        .checkout-form textarea {
          width: 100%;
          border: 1px solid #ddd;
          border-radius: 8px;
          padding: 12px 14px;
          margin-bottom: 16px;
          font-size: 15px;
          transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .checkout-form input:focus,
        .checkout-form select:focus,
        .checkout-form textarea:focus {
          border-color: #FFD43B;
          outline: none;
          box-shadow: 0 0 8px rgba(255,212,59,0.35);
        }

        /* BUTTON */
        .checkout-form .button-primary,
        .checkout-form button[type="submit"] {
          background: #FFD43B;
          color: #000;
          border: none;
          border-radius: 30px;
          padding: 14px 20px;
          width: 100%;
          font-weight: 700;
          text-transform: uppercase;
          cursor: pointer;
          transition: 0.3s ease;
          letter-spacing: 1px;
        }
        .checkout-form .button-primary:hover,
        .checkout-form button[type="submit"]:hover {
          background: #FFC300;
        }

        /* MOBILE */
        @media (max-width: 768px) {
          .checkout-main {
            flex-direction: column;
          }
          .checkout-summary,
          .checkout-form {
            flex: 1;
          }
          .checkout-form {
            padding: 32px;
          }
        }
        </style>
        <?php
        $nd_booking_shortcode_result .= ob_get_clean();

    //START PAYMENT ON CHECKOUT PAGE
    }elseif ( $nd_booking_form_checkout_arrive == 1 OR isset($_GET['tx']) OR $nd_booking_form_checkout_arrive == 2 ) {

        $nd_booking_guest_id_front = '';
        $nd_booking_guest_id_back = '';

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
            if( isset( $_POST['nd_booking_checkout_form_base_price'] ) ) { $nd_booking_checkout_form_base_price = sanitize_text_field($_POST['nd_booking_checkout_form_base_price']); }else{ $nd_booking_checkout_form_base_price = ''; }
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
              $nd_booking_guest_id_number = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_number']);
              $nd_booking_guest_id_type   = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_type']);
              $front_token = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_front']);
              $back_token  = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_back']);
              $nd_booking_guest_id_front = nd_booking_get_upload_path_from_token( $front_token );
              $nd_booking_guest_id_back  = nd_booking_get_upload_path_from_token( $back_token );

            //ids
            $nd_booking_checkout_form_post_id = sanitize_text_field($_POST['nd_booking_checkout_form_post_id']);
            $nd_booking_ids_array = explode('-', $nd_booking_checkout_form_post_id );
            $nd_booking_checkout_form_post_id = $nd_booking_ids_array[0];
            $nd_booking_id_room = $nd_booking_ids_array[1];

            $nd_booking_booking_form_final_price = floatval( $nd_booking_booking_form_final_price );
            $nd_booking_checkout_form_base_price = floatval( $nd_booking_checkout_form_base_price );

            $nd_booking_tax_base_amount = $nd_booking_checkout_form_base_price;
            if ( $nd_booking_tax_base_amount <= 0 && isset( $_SESSION['nd_booking_tax_base'] ) ) {
                $nd_booking_tax_base_amount = floatval( $_SESSION['nd_booking_tax_base'] );
            }
            if ( $nd_booking_tax_base_amount <= 0 && $nd_booking_total_tax_amount > 0 ) {
                $nd_booking_tax_base_amount = round( $nd_booking_booking_form_final_price - $nd_booking_total_tax_amount, 2 );
            }

            if ( $nd_booking_tax_base_amount > 0 ) {
                $nd_booking_tax_breakdown = nd_booking_calculate_tax_breakdown( $nd_booking_tax_base_amount );
            } else {
                $nd_booking_tax_breakdown = nd_booking_calculate_tax_breakdown_from_total( $nd_booking_booking_form_final_price );
            }

            $nd_booking_tax_base_amount = $nd_booking_tax_breakdown['base'];
            $nd_booking_tax_lodging = isset( $nd_booking_tax_breakdown['taxes']['lodging'] ) ? $nd_booking_tax_breakdown['taxes']['lodging']['amount'] : 0.0;
            $nd_booking_tax_gst = isset( $nd_booking_tax_breakdown['taxes']['gst'] ) ? $nd_booking_tax_breakdown['taxes']['gst']['amount'] : 0.0;
            $nd_booking_tax_qst = isset( $nd_booking_tax_breakdown['taxes']['qst'] ) ? $nd_booking_tax_breakdown['taxes']['qst']['amount'] : 0.0;
            $nd_booking_total_tax_amount = $nd_booking_tax_breakdown['total_tax'];
            $nd_booking_booking_form_final_price = $nd_booking_tax_breakdown['total'];

            if ( isset( $_SESSION ) && is_array( $_SESSION ) ) {
                $_SESSION['nd_booking_tax_base'] = $nd_booking_tax_base_amount;
                $_SESSION['nd_booking_tax_lodging'] = $nd_booking_tax_lodging;
                $_SESSION['nd_booking_tax_gst'] = $nd_booking_tax_gst;
                $_SESSION['nd_booking_tax_qst'] = $nd_booking_tax_qst;
                $_SESSION['nd_booking_tax_total'] = $nd_booking_total_tax_amount;
                $_SESSION['nd_booking_final_price'] = $nd_booking_booking_form_final_price;
            }



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
              $nd_booking_guest_id_number = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_number']);
              $nd_booking_guest_id_type   = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_type']);
              $front_token = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_front']);
              $back_token  = sanitize_text_field($_POST['nd_booking_checkout_form_guest_id_back']);
              $nd_booking_guest_id_front = nd_booking_get_upload_path_from_token( $front_token );
              $nd_booking_guest_id_back  = nd_booking_get_upload_path_from_token( $back_token );

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


                if ( is_wp_error( $nd_booking_response ) ) {
                    error_log( 'nd_booking: Stripe request error: ' . $nd_booking_response->get_error_message() );
                    return '<p>'. esc_html__( 'There was a problem processing your payment. Please try again later.', 'nd-booking' ) .'</p>';
                }

                // START check the response
                $nd_booking_http_response_code = wp_remote_retrieve_response_code( $nd_booking_response );

                if ( 200 !== $nd_booking_http_response_code ) {
                    error_log( 'nd_booking: Stripe request failed with code '. $nd_booking_http_response_code .' and body: '. wp_remote_retrieve_body( $nd_booking_response ) );
                    return '<p>'. esc_html__( 'Unable to process payment at this time. Please contact support.', 'nd-booking' ) .'</p>';
                }

                $nd_booking_response_body = wp_remote_retrieve_body( $nd_booking_response );
                $nd_booking_stripe_data   = json_decode( $nd_booking_response_body );

                if ( ! is_object( $nd_booking_stripe_data ) ) {
                    error_log( 'nd_booking: Invalid Stripe response: '. $nd_booking_response_body );
                    return '<p>'. esc_html__( 'Unexpected response from payment gateway. Please contact support.', 'nd-booking' ) .'</p>';
                }

                if ( empty( $nd_booking_stripe_data->paid ) ) {
                    error_log( 'nd_booking: Stripe charge not paid. Response: '. $nd_booking_response_body );
                    return '<p>'. esc_html__( 'Payment was not completed. Please try again.', 'nd-booking' ) .'</p>';
                }

                $nd_booking_booking_form_payment_status = 'Completed';
                // store the payment id for later use
                $nd_booking_booking_form_payment_id     = $nd_booking_stripe_data->id;

                //transaction TX id
                $nd_booking_paypal_tx = $nd_booking_stripe_data->id;

                //get current date
                $nd_booking_date = date('H:m:s F j Y');

                //get currency
                $nd_booking_booking_form_currency = nd_booking_get_currency();

                $nd_booking_paypal_error = 0;
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


        $nd_booking_booking_id = nd_booking_add_booking_in_db(
  
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
          $nd_booking_booking_form_action_type,
          $nd_booking_guest_id_front,
          $nd_booking_guest_id_back

        );

        update_post_meta( $nd_booking_booking_id, 'guest_id_front', esc_url_raw( $nd_booking_guest_id_front ) );
        update_post_meta( $nd_booking_booking_id, 'guest_id_back', esc_url_raw( $nd_booking_guest_id_back ) );
        update_post_meta( $nd_booking_booking_id, 'guest_id_number', sanitize_text_field( $nd_booking_guest_id_number ) );
        update_post_meta( $nd_booking_booking_id, 'guest_id_type', sanitize_text_field( $nd_booking_guest_id_type ) );
        update_post_meta( $nd_booking_booking_id, 'nd_booking_tax_lodging', $nd_booking_tax_lodging );
        update_post_meta( $nd_booking_booking_id, 'nd_booking_tax_gst', $nd_booking_tax_gst );
        update_post_meta( $nd_booking_booking_id, 'nd_booking_tax_qst', $nd_booking_tax_qst );

        if (
            $nd_booking_booking_form_action_type === 'stripe' &&
            $nd_booking_booking_form_payment_status === 'Completed'
        ) {
            $payload = [
                'guest_email'   => $nd_booking_booking_form_email,
                'room_type'     => $nd_booking_checkout_form_post_title,
                'check_in_date' => $nd_booking_booking_form_date_from,
                'check_out_date'=> $nd_booking_booking_form_date_to,
                'booking_id'    => $nd_booking_booking_id,
                'first_name'    => $nd_booking_booking_form_name,
                'last_name'     => $nd_booking_booking_form_surname,
            ];

            do_action( 'nd_booking_stripe_payment_complete', $payload );
        }

        if (function_exists('add_booking_to_google_calendar')) {
            $summary = sprintf(
                'Booking for %s %s',
                $nd_booking_booking_form_name,
                $nd_booking_booking_form_surname
            );

            add_booking_to_google_calendar(
                $summary,
                $nd_booking_booking_form_date_from,
                $nd_booking_booking_form_date_to
            );
        }
        if (function_exists('create_keychain_in_butterflymx')) {
            $args = [
                'name'        => $nd_booking_booking_form_name,
                'surname'     => $nd_booking_booking_form_surname,
                'email'       => $nd_booking_booking_form_email,
                'start'       => $nd_booking_booking_form_date_from,
                'end'         => $nd_booking_booking_form_date_to,
                'room_id'     => $nd_booking_id_room,
                // add other fields as needed
            ];
            create_keychain_in_butterflymx($args);
        }

        if (function_exists('trigger_amelia_booking_webhook')) {
            $args = [
                'name'        => $nd_booking_booking_form_name,
                'surname'     => $nd_booking_booking_form_surname,
                'email'       => $nd_booking_booking_form_email,
                'phone'       => $nd_booking_booking_form_phone,
                'start'       => $nd_booking_booking_form_date_from,
                'end'         => $nd_booking_booking_form_date_to,
                'room_id'     => $nd_booking_id_room,
                'payment_id'  => $nd_booking_booking_form_payment_id, // if you store Stripe id
                'loft_number' => $nd_booking_id_room,
                'key_code'    => $generated_key_code // if available
            ];
            trigger_amelia_booking_webhook($args);
        }


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






