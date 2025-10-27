<?php
$nd_booking_image_src = nd_booking_get_post_img_src( $nd_booking_booking_form_post_id );
$nd_booking_room_title = get_the_title( $nd_booking_booking_form_post_id );

$nd_booking_checkin_timestamp  = strtotime( $nd_booking_booking_form_date_from );
$nd_booking_checkout_timestamp = strtotime( $nd_booking_booking_form_date_to );

$nd_booking_checkin_formatted  = $nd_booking_checkin_timestamp ? date_i18n( get_option( 'date_format', 'M j, Y' ), $nd_booking_checkin_timestamp ) : '';
$nd_booking_checkout_formatted = $nd_booking_checkout_timestamp ? date_i18n( get_option( 'date_format', 'M j, Y' ), $nd_booking_checkout_timestamp ) : '';

$nd_booking_total_nights = absint( nd_booking_get_number_night( $nd_booking_booking_form_date_from, $nd_booking_booking_form_date_to ) );
$nd_booking_nights_label = sprintf( _n( '%s nuit', '%s nuits', $nd_booking_total_nights, 'nd-booking' ), number_format_i18n( $nd_booking_total_nights ) );

$nd_booking_guest_total = absint( $nd_booking_booking_form_guests );
$nd_booking_guest_label = sprintf( _n( '%s invité', '%s invités', $nd_booking_guest_total, 'nd-booking' ), number_format_i18n( $nd_booking_guest_total ) );

$nd_booking_booking_original_price = $nd_booking_booking_form_final_price;
if ( '' !== $nd_booking_booking_form_coupon ) {
    $nd_booking_booking_form_final_price = $nd_booking_booking_form_final_price - ( $nd_booking_booking_form_final_price * nd_booking_get_coupon_value( $nd_booking_booking_form_coupon ) / 100 );
}

$nd_booking_currency      = nd_booking_get_currency();
$nd_booking_display_total = nd_booking_format_decimal( $nd_booking_booking_form_final_price );
$nd_booking_original_total = nd_booking_format_decimal( $nd_booking_booking_original_price );

$nd_booking_average_rate = $nd_booking_total_nights > 0 ? $nd_booking_booking_form_final_price / $nd_booking_total_nights : 0;
$nd_booking_average_rate_formatted = nd_booking_format_decimal( $nd_booking_average_rate );

$nd_booking_summary_details = array(
    __( 'Arrivée', 'nd-booking' )  => $nd_booking_checkin_formatted,
    __( 'Départ', 'nd-booking' ) => $nd_booking_checkout_formatted,
    __( 'Nuits', 'nd-booking' )    => $nd_booking_nights_label,
    __( 'Invités', 'nd-booking' )    => $nd_booking_guest_label,
);

$nd_booking_shortcode_left_content  = '<div class="loft-booking-summary">';

if ( ! empty( $nd_booking_image_src ) ) {
    $nd_booking_shortcode_left_content .= '<div class="summary-image-wrapper"><img class="summary-image" src="' . esc_url( $nd_booking_image_src ) . '" alt="' . esc_attr( $nd_booking_room_title ) . '"></div>';
}

if ( ! empty( $nd_booking_room_title ) ) {
    $nd_booking_shortcode_left_content .= '<h3 class="summary-title">' . esc_html( $nd_booking_room_title ) . '</h3>';
}

$nd_booking_shortcode_left_content .= '<ul class="summary-details">';
foreach ( $nd_booking_summary_details as $nd_booking_detail_label => $nd_booking_detail_value ) {
    if ( '' === $nd_booking_detail_value ) {
        continue;
    }
    $nd_booking_shortcode_left_content .= '<li><span class="label">' . esc_html( $nd_booking_detail_label ) . '</span><span class="value">' . esc_html( $nd_booking_detail_value ) . '</span></li>';
}
$nd_booking_shortcode_left_content .= '</ul>';

$nd_booking_shortcode_left_content .= '<div class="summary-total">';
$nd_booking_shortcode_left_content .= '<p>' . esc_html__( 'Total', 'nd-booking' ) . '</p>';
$nd_booking_shortcode_left_content .= '<h2><span class="amount">' . esc_html( $nd_booking_display_total ) . '</span> <span class="currency">' . esc_html( $nd_booking_currency ) . '</span></h2>';
if ( $nd_booking_booking_original_price != $nd_booking_booking_form_final_price ) {
    $nd_booking_shortcode_left_content .= '<p class="summary-discount">' . sprintf(
        esc_html__( 'Prix original %1$s %2$s • Coupon %3$s', 'nd-booking' ),
        esc_html( $nd_booking_original_total ),
        esc_html( $nd_booking_currency ),
        esc_html( $nd_booking_booking_form_coupon )
    ) . '</p>';
}
$nd_booking_shortcode_left_content .= '</div>';

if ( $nd_booking_average_rate > 0 ) {
    $nd_booking_shortcode_left_content .= '<p class="summary-meta">' . sprintf(
        esc_html__( 'Environ %1$s %2$s par nuit', 'nd-booking' ),
        esc_html( $nd_booking_average_rate_formatted ),
        esc_html( $nd_booking_currency )
    ) . '</p>';
}

$nd_booking_shortcode_left_content .= '</div>';
