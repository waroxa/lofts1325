<?php
$nd_booking_checkout_tax_breakdown = isset( $nd_booking_tax_breakdown ) && is_array( $nd_booking_tax_breakdown )
    ? $nd_booking_tax_breakdown
    : nd_booking_calculate_tax_breakdown_from_total( $nd_booking_booking_form_final_price );

$nd_booking_booking_original_price = isset( $nd_booking_booking_original_price ) ? $nd_booking_booking_original_price : $nd_booking_booking_form_final_price;

$nd_booking_checkout_currency              = nd_booking_get_currency();
$nd_booking_checkout_subtotal_formatted    = nd_booking_format_decimal( $nd_booking_checkout_tax_breakdown['base'] );
$nd_booking_checkout_total_formatted       = nd_booking_format_decimal( $nd_booking_checkout_tax_breakdown['total'] );
$nd_booking_checkout_tax_total_formatted   = nd_booking_format_decimal( $nd_booking_checkout_tax_breakdown['total_tax'] );
$nd_booking_checkout_nights                = absint( nd_booking_get_number_night( $nd_booking_booking_form_date_from, $nd_booking_booking_form_date_to ) );
$nd_booking_checkout_nightly_rate          = 0;

if ( $nd_booking_checkout_nights > 0 ) {
    $nd_booking_checkout_nightly_rate = floatval( $nd_booking_checkout_tax_breakdown['base'] ) / $nd_booking_checkout_nights;
}

$nd_booking_checkout_nightly_rate_formatted = nd_booking_format_decimal( $nd_booking_checkout_nightly_rate );
$nd_booking_checkout_nights_label          = _n( 'night', 'nights', $nd_booking_checkout_nights, 'nd-booking' );

$nd_booking_checkout_known_tax_labels = array(
    'lodging' => __( 'Lodging Tax', 'nd-booking' ),
    'gst'     => __( 'GST', 'nd-booking' ),
    'qst'     => __( 'QST', 'nd-booking' ),
);

$nd_booking_checkout_tax_lines  = '<ul class="loft-price-breakdown">';
$nd_booking_checkout_tax_lines .= '<li class="breakdown-row" data-tax-key="nightly_rate" data-nights="' . esc_attr( $nd_booking_checkout_nights ) . '">';
$nd_booking_checkout_tax_lines .= '<span class="label">' . esc_html__( 'Nightly Rate', 'nd-booking' ) . '</span>';
$nd_booking_checkout_tax_lines .= '<span class="value">' . esc_html( $nd_booking_checkout_nightly_rate_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_checkout_currency ) . '</span> &times; ' . esc_html( $nd_booking_checkout_nights ) . ' ' . esc_html( $nd_booking_checkout_nights_label ) . '</span>';
$nd_booking_checkout_tax_lines .= '</li>';
$nd_booking_checkout_tax_lines .= '<li class="breakdown-row" data-tax-key="subtotal">';
$nd_booking_checkout_tax_lines .= '<span class="label">' . esc_html__( 'Subtotal', 'nd-booking' ) . '</span>';
$nd_booking_checkout_tax_lines .= '<span class="value">' . esc_html( $nd_booking_checkout_subtotal_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_checkout_currency ) . '</span></span>';
$nd_booking_checkout_tax_lines .= '</li>';

foreach ( $nd_booking_checkout_known_tax_labels as $nd_booking_tax_key => $nd_booking_tax_label ) {
    $nd_booking_line_style          = '';
    $nd_booking_tax_amount_formatted = nd_booking_format_decimal( 0 );
    $nd_booking_display_label       = $nd_booking_tax_label;

    if ( isset( $nd_booking_checkout_tax_breakdown['taxes'][ $nd_booking_tax_key ] ) ) {
        $nd_booking_tax_rate             = nd_booking_format_percentage( $nd_booking_checkout_tax_breakdown['taxes'][ $nd_booking_tax_key ]['rate'] );
        $nd_booking_tax_amount_formatted = nd_booking_format_decimal( $nd_booking_checkout_tax_breakdown['taxes'][ $nd_booking_tax_key ]['amount'] );
        $nd_booking_display_label        = sprintf( __( '%1$s (%2$s%%)', 'nd-booking' ), $nd_booking_checkout_tax_breakdown['taxes'][ $nd_booking_tax_key ]['label'], $nd_booking_tax_rate );
    } else {
        $nd_booking_line_style = ' class="breakdown-row breakdown-row--hidden"';
    }

    $nd_booking_checkout_tax_lines .= '<li' . $nd_booking_line_style . ' data-tax-key="' . esc_attr( $nd_booking_tax_key ) . '">';
    $nd_booking_checkout_tax_lines .= '<span class="label">' . esc_html( $nd_booking_display_label ) . '</span>';
    $nd_booking_checkout_tax_lines .= '<span class="value">' . esc_html( $nd_booking_tax_amount_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_checkout_currency ) . '</span></span>';
    $nd_booking_checkout_tax_lines .= '</li>';
}

$nd_booking_checkout_tax_lines .= '<li class="breakdown-row breakdown-row--total" data-tax-key="total_tax">';
$nd_booking_checkout_tax_lines .= '<span class="label">' . esc_html__( 'Total Tax', 'nd-booking' ) . '</span>';
$nd_booking_checkout_tax_lines .= '<span class="value">' . esc_html( $nd_booking_checkout_tax_total_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_checkout_currency ) . '</span></span>';
$nd_booking_checkout_tax_lines .= '</li>';
$nd_booking_checkout_tax_lines .= '<li class="breakdown-row breakdown-row--grand" data-tax-key="grand_total">';
$nd_booking_checkout_tax_lines .= '<span class="label">' . esc_html__( 'Grand Total', 'nd-booking' ) . '</span>';
$nd_booking_checkout_tax_lines .= '<span class="value">' . esc_html( $nd_booking_checkout_total_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_checkout_currency ) . '</span></span>';
$nd_booking_checkout_tax_lines .= '</li>';
$nd_booking_checkout_tax_lines .= '</ul>';

$nd_booking_contact_fields = array(
    array(
        'label' => __( 'Name', 'nd-booking' ),
        'value' => $nd_booking_booking_form_name,
    ),
    array(
        'label' => __( 'Surname', 'nd-booking' ),
        'value' => $nd_booking_booking_form_surname,
    ),
    array(
        'label' => __( 'Email', 'nd-booking' ),
        'value' => $nd_booking_booking_form_email,
    ),
    array(
        'label' => __( 'Phone', 'nd-booking' ),
        'value' => $nd_booking_booking_form_phone,
    ),
);

$nd_booking_address_fields = array(
    array(
        'label' => __( 'Address', 'nd-booking' ),
        'value' => $nd_booking_booking_form_address,
    ),
    array(
        'label' => __( 'City', 'nd-booking' ),
        'value' => $nd_booking_booking_form_city,
    ),
    array(
        'label' => __( 'Country', 'nd-booking' ),
        'value' => $nd_booking_booking_form_country,
    ),
    array(
        'label' => __( 'ZIP', 'nd-booking' ),
        'value' => $nd_booking_booking_form_zip,
    ),
);

$nd_booking_requests_value = trim( (string) $nd_booking_booking_form_requests );
$nd_booking_requests_markup = $nd_booking_requests_value !== ''
    ? '<p>' . nl2br( esc_html( $nd_booking_requests_value ) ) . '</p>'
    : '<p class="loft-empty">' . esc_html__( 'No special requests added.', 'nd-booking' ) . '</p>';

$nd_booking_arrival_value = trim( (string) $nd_booking_booking_form_arrival );
$nd_booking_arrival_markup = $nd_booking_arrival_value !== ''
    ? esc_html( $nd_booking_arrival_value )
    : esc_html__( 'Not specified', 'nd-booking' );

$nd_booking_services_markup = '';
if ( '' === $nd_booking_booking_form_services ) {
    $nd_booking_services_markup = '<p class="loft-empty">' . esc_html__( 'No additional services selected.', 'nd-booking' ) . '</p>';
} else {
    $nd_booking_services_markup .= '<ul class="loft-service-list">';
    $nd_booking_services_array = explode( ',', $nd_booking_booking_form_services );
    foreach ( $nd_booking_services_array as $nd_booking_services_array_value ) {
        $nd_booking_service_id = absint( $nd_booking_services_array_value );
        if ( ! $nd_booking_service_id ) {
            continue;
        }
        $nd_booking_service_name = get_the_title( $nd_booking_service_id );
        if ( '' === $nd_booking_service_name ) {
            continue;
        }
        $nd_booking_services_markup .= '<li>' . esc_html( $nd_booking_service_name ) . '</li>';
    }
    $nd_booking_services_markup .= '</ul>';
    if ( '<ul class="loft-service-list"></ul>' === $nd_booking_services_markup ) {
        $nd_booking_services_markup = '<p class="loft-empty">' . esc_html__( 'No additional services selected.', 'nd-booking' ) . '</p>';
    }
}

$nd_booking_coupon_section_markup = '';
$nd_booking_coupon_class = nd_booking_get_coupon_enable_class();
if ( '' === $nd_booking_coupon_class ) {
    $nd_booking_coupon_section_markup .= '<div class="loft-info-item">';
    $nd_booking_coupon_section_markup .= '<label>' . esc_html__( 'Coupon', 'nd-booking' ) . '</label>';
    if ( $nd_booking_booking_original_price != $nd_booking_booking_form_final_price ) {
        $nd_booking_coupon_section_markup .= '<p>' . esc_html( $nd_booking_booking_form_coupon ) . ' · ' . esc_html__( 'Discount applied', 'nd-booking' ) . '</p>';
    } else {
        $nd_booking_coupon_section_markup .= '<p class="loft-empty">' . esc_html__( 'No coupon applied.', 'nd-booking' ) . '</p>';
    }
    $nd_booking_coupon_section_markup .= '</div>';
}

$nd_booking_shortcode_right_content  = '<div class="loft-booking-form">';
$nd_booking_shortcode_right_content .= '<div class="loft-progress-indicator">' . esc_html__( 'Étape 2 sur 3', 'nd-booking' ) . '</div>';

$nd_booking_shortcode_right_content .= '<div class="section loft-section-contact">';
$nd_booking_shortcode_right_content .= '<h3><span class="section-icon" aria-hidden="true">🧍</span> ' . esc_html__( 'Vos informations', 'nd-booking' ) . '</h3>';
$nd_booking_shortcode_right_content .= '<div class="section-body">';
$nd_booking_shortcode_right_content .= '<div class="loft-info-grid">';
foreach ( $nd_booking_contact_fields as $nd_booking_field ) {
    if ( '' === $nd_booking_field['value'] ) {
        continue;
    }
    $nd_booking_shortcode_right_content .= '<div class="loft-info-item">';
    $nd_booking_shortcode_right_content .= '<label>' . esc_html( $nd_booking_field['label'] ) . '</label>';
    $nd_booking_shortcode_right_content .= '<p>' . esc_html( $nd_booking_field['value'] ) . '</p>';
    $nd_booking_shortcode_right_content .= '</div>';
}
$nd_booking_shortcode_right_content .= '</div>';
$nd_booking_shortcode_right_content .= '</div>';
$nd_booking_shortcode_right_content .= '</div>';

$nd_booking_shortcode_right_content .= '<div class="section loft-section-address">';
$nd_booking_shortcode_right_content .= '<h3><span class="section-icon" aria-hidden="true">🏠</span> ' . esc_html__( 'Adresse de facturation', 'nd-booking' ) . '</h3>';
$nd_booking_shortcode_right_content .= '<div class="section-body">';
$nd_booking_shortcode_right_content .= '<div class="loft-info-grid">';
foreach ( $nd_booking_address_fields as $nd_booking_field ) {
    if ( '' === $nd_booking_field['value'] ) {
        continue;
    }
    $nd_booking_shortcode_right_content .= '<div class="loft-info-item">';
    $nd_booking_shortcode_right_content .= '<label>' . esc_html( $nd_booking_field['label'] ) . '</label>';
    $nd_booking_shortcode_right_content .= '<p>' . esc_html( $nd_booking_field['value'] ) . '</p>';
    $nd_booking_shortcode_right_content .= '</div>';
}
$nd_booking_shortcode_right_content .= '</div>';
$nd_booking_shortcode_right_content .= '</div>';
$nd_booking_shortcode_right_content .= '</div>';

$nd_booking_conditions_markup  = '<div class="section loft-section-conditions">';
$nd_booking_conditions_markup .= '<h3><span class="section-icon" aria-hidden="true">📋</span> ' . esc_html__( 'Conditions de réservation', 'nd-booking' ) . '</h3>';
$nd_booking_conditions_markup .= '<div class="section-body">';
$nd_booking_conditions_markup .= '<div class="loft-info-item">';
$nd_booking_conditions_markup .= '<label>' . esc_html__( 'Arrival', 'nd-booking' ) . '</label>';
$nd_booking_conditions_markup .= '<p>' . $nd_booking_arrival_markup . '</p>';
$nd_booking_conditions_markup .= '</div>';
$nd_booking_conditions_markup .= '<div class="loft-info-item">';
$nd_booking_conditions_markup .= '<label>' . esc_html__( 'Special Requests', 'nd-booking' ) . '</label>';
$nd_booking_conditions_markup .= $nd_booking_requests_markup;
$nd_booking_conditions_markup .= '</div>';
$nd_booking_conditions_markup .= '<div class="loft-info-item">';
$nd_booking_conditions_markup .= '<label>' . esc_html__( 'Additional Services', 'nd-booking' ) . '</label>';
$nd_booking_conditions_markup .= $nd_booking_services_markup;
$nd_booking_conditions_markup .= '</div>';
if ( '' !== $nd_booking_coupon_section_markup ) {
    $nd_booking_conditions_markup .= $nd_booking_coupon_section_markup;
}
$nd_booking_conditions_markup .= '<div class="loft-price-summary">';
$nd_booking_conditions_markup .= '<h4>' . esc_html__( 'Price Summary', 'nd-booking' ) . '</h4>';
$nd_booking_conditions_markup .= $nd_booking_checkout_tax_lines;
$nd_booking_conditions_markup .= '</div>';
$nd_booking_conditions_markup .= '</div>';
$nd_booking_conditions_markup .= '</div>';
