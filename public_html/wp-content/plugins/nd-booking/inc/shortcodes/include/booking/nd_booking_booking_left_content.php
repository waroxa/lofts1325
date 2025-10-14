<?php

// Calculate base trip price
$nd_booking_trip_price_for_person = 0;
$nd_booking_index                = 1;
$nd_booking_date_cicle           = $nd_booking_date_from;

while ( $nd_booking_index <= nd_booking_get_number_night( $nd_booking_date_from, $nd_booking_date_to ) ) {
    $nd_booking_trip_price_for_person += nd_booking_get_final_price( $nd_booking_form_booking_id, $nd_booking_date_cicle );
    $nd_booking_date_cicle            = date( 'Y/m/d', strtotime( $nd_booking_date_cicle . ' + 1 days' ) );
    $nd_booking_index++;
}

$nd_booking_price_guests_enable = get_option( 'nd_booking_price_guests' );
if ( 1 == $nd_booking_price_guests_enable ) {
    $nd_booking_trip_price = $nd_booking_trip_price_for_person * $nd_booking_form_booking_guests;
} else {
    $nd_booking_trip_price = $nd_booking_trip_price_for_person;
}

$nd_booking_tax_breakdown                = nd_booking_calculate_tax_breakdown( $nd_booking_trip_price );
$nd_booking_currency                     = nd_booking_get_currency();
$nd_booking_initial_total_formatted      = nd_booking_format_decimal( $nd_booking_tax_breakdown['total'] );
$nd_booking_initial_subtotal_formatted   = nd_booking_format_decimal( $nd_booking_tax_breakdown['base'] );
$nd_booking_initial_tax_total_formatted  = nd_booking_format_decimal( $nd_booking_tax_breakdown['total_tax'] );
$nd_booking_total_tax_rate_descriptions  = array();

if ( isset( $nd_booking_tax_breakdown['taxes'] ) && is_array( $nd_booking_tax_breakdown['taxes'] ) ) {
    foreach ( $nd_booking_tax_breakdown['taxes'] as $nd_booking_tax_key => $nd_booking_tax_data ) {
        $nd_booking_tax_rate_descriptions[] = sprintf(
            /* translators: 1: Tax label, 2: Tax rate */
            __( '%1$s %2$s%%', 'nd-booking' ),
            $nd_booking_tax_data['label'],
            nd_booking_format_percentage( $nd_booking_tax_data['rate'] )
        );
    }
}

$nd_booking_total_tax_rate_text = '';
if ( ! empty( $nd_booking_tax_breakdown['taxes'] ) ) {
    $nd_booking_total_tax_rate_text = implode( ' · ', $nd_booking_tax_rate_descriptions );
}

$nd_booking_nights              = max( 1, nd_booking_get_number_night( $nd_booking_date_from, $nd_booking_date_to ) );
$nd_booking_nightly_rate        = $nd_booking_trip_price / $nd_booking_nights;
$nd_booking_nightly_rate_format = nd_booking_format_decimal( $nd_booking_nightly_rate );

// Retrieve room meta information
$nd_booking_meta_box_max_people   = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_max_people', true );
$nd_booking_meta_box_room_size    = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_room_size', true );
$nd_booking_meta_box_text_preview = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_text_preview', true );
$nd_booking_meta_box_branches     = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_branches', true );
$nd_booking_meta_box_cpt_4_stars  = get_post_meta( $nd_booking_meta_box_branches, 'nd_booking_meta_box_cpt_4_stars', true );
$nd_booking_meta_box_color        = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_color', true );

if ( '' === $nd_booking_meta_box_color ) {
    $nd_booking_meta_box_color = '#111827';
}

$nd_booking_room_excerpt = wp_kses_post( $nd_booking_meta_box_text_preview );
if ( '' === $nd_booking_room_excerpt ) {
    $nd_booking_room_excerpt = wp_kses_post( get_the_excerpt( $nd_booking_form_booking_id ) );
}

$loft_branch_title = '';
if ( ! empty( $nd_booking_meta_box_branches ) ) {
    $loft_branch_title = get_the_title( $nd_booking_meta_box_branches );
}

$nd_booking_plugin_root_file = dirname( __FILE__, 5 ) . '/nd-booking.php';

$loft_star_count        = intval( $nd_booking_meta_box_cpt_4_stars );
$loft_star_icon_markup  = '';
if ( $loft_star_count > 0 ) {
    $loft_star_icon_url = esc_url( plugins_url( 'inc/shortcodes/include/search-results/icon-star-full-white.svg', $nd_booking_plugin_root_file ) );
    for ( $loft_star_index = 0; $loft_star_index < $loft_star_count; $loft_star_index++ ) {
        $loft_star_icon_markup .= '<img alt="" class="loft-search-card__star" width="12" src="' . $loft_star_icon_url . '">';
    }
}

$nd_booking_image_src = nd_booking_get_post_img_src( $nd_booking_form_booking_id );
$nd_booking_media      = '';

if ( $nd_booking_image_src ) {
    $loft_best_value_ribbon_markup = '';
    $nd_booking_media_overlay      = '';

    if ( $loft_branch_title !== '' || $loft_star_icon_markup !== '' ) {
        $nd_booking_media_overlay .= '<div class="loft-search-card__media-overlay">';
        if ( '' !== $loft_branch_title ) {
            $nd_booking_media_overlay .= '<span class="loft-search-card__badge">' . esc_html( $loft_branch_title ) . '</span>';
        }
        if ( '' !== $loft_star_icon_markup ) {
            $nd_booking_media_overlay .= '<span class="loft-search-card__stars">' . $loft_star_icon_markup . '</span>';
        }
        $nd_booking_media_overlay .= '</div>';
    }

    $nd_booking_media = '<div class="loft-search-card__media">'
        . '<img alt="" class="loft-search-card__media-img" src="' . esc_url( $nd_booking_image_src ) . '">' . $nd_booking_media_overlay . '</div>';
}

$nd_booking_capacity_markup = '';
if ( '' !== $nd_booking_meta_box_max_people ) {
    $nd_booking_capacity_markup .= '<div class="loft-search-card__feature">'
        . '<img alt="" class="loft-search-card__feature-icon" width="20" src="' . esc_url( plugins_url( 'inc/shortcodes/include/search-results/icon-user-grey.svg', $nd_booking_plugin_root_file ) ) . '">'
        . '<span class="loft-search-card__feature-text">' . esc_html( sprintf( _n( '%d Guest', '%d Guests', $nd_booking_meta_box_max_people, 'nd-booking' ), $nd_booking_meta_box_max_people ) ) . '</span>'
        . '</div>';
}

if ( '' !== $nd_booking_meta_box_room_size ) {
    $nd_booking_capacity_markup .= '<div class="loft-search-card__feature">'
        . '<img alt="" class="loft-search-card__feature-icon" width="20" src="' . esc_url( plugins_url( 'inc/shortcodes/include/search-results/icon-plan-grey.svg', $nd_booking_plugin_root_file ) ) . '">'
        . '<span class="loft-search-card__feature-text">' . esc_html( $nd_booking_meta_box_room_size ) . ' ' . esc_html( nd_booking_get_units_of_measure() ) . '</span>'
        . '</div>';
}

$nd_booking_check_in_date  = new DateTime( $nd_booking_date_from );
$nd_booking_check_out_date = new DateTime( $nd_booking_date_to );

$nd_booking_check_in_label  = date_i18n( 'D, M j, Y', $nd_booking_check_in_date->getTimestamp() );
$nd_booking_check_out_label = date_i18n( 'D, M j, Y', $nd_booking_check_out_date->getTimestamp() );

$nd_booking_shortcode_left_content  = '';
$nd_booking_shortcode_left_content .= '<aside class="loft-booking-sidebar">';
$nd_booking_shortcode_left_content .= '  <div class="loft-booking-sidebar__card loft-search-card loft-search-card--summary">';
$nd_booking_shortcode_left_content .=        $nd_booking_media;
$nd_booking_shortcode_left_content .= '    <div class="loft-search-card__content">';
$nd_booking_shortcode_left_content .= '      <h2 class="loft-search-card__title" style="color:' . esc_attr( $nd_booking_meta_box_color ) . ';">' . esc_html( get_the_title( $nd_booking_form_booking_id ) ) . '</h2>';

if ( '' !== $nd_booking_room_excerpt ) {
    $nd_booking_shortcode_left_content .= '      <div class="loft-search-card__excerpt">' . $nd_booking_room_excerpt . '</div>';
}

if ( '' !== $nd_booking_capacity_markup ) {
    $nd_booking_shortcode_left_content .= '      <div class="loft-search-card__meta">' . $nd_booking_capacity_markup . '</div>';
}

$nd_booking_shortcode_left_content .= '    </div>';
$nd_booking_shortcode_left_content .= '  </div>';

$nd_booking_shortcode_left_content .= '  <div class="loft-booking-summary-panel">';
$nd_booking_shortcode_left_content .= '    <div class="loft-booking-summary-panel__banner">';
$nd_booking_shortcode_left_content .= '      <span class="loft-booking-summary-panel__badge">' . esc_html__( "Jackpot! This is today\'s low rate.", 'nd-booking' ) . '</span>';
$nd_booking_shortcode_left_content .= '    </div>';
$nd_booking_shortcode_left_content .= '    <h3 class="loft-booking-summary-panel__title">' . esc_html__( 'Your booking summary', 'nd-booking' ) . '</h3>';
$nd_booking_shortcode_left_content .= '    <ul class="loft-booking-summary-panel__details">';
$nd_booking_shortcode_left_content .= '      <li><span class="label">' . esc_html__( 'Check-in', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_check_in_label ) . '</span></li>';
$nd_booking_shortcode_left_content .= '      <li><span class="label">' . esc_html__( 'Check-out', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_check_out_label ) . '</span></li>';
$nd_booking_shortcode_left_content .= '      <li><span class="label">' . esc_html__( 'Guests', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_form_booking_guests ) . '</span></li>';
$nd_booking_shortcode_left_content .= '      <li><span class="label">' . esc_html__( 'Nights', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_nights ) . '</span></li>';
$nd_booking_shortcode_left_content .= '      <li><span class="label">' . esc_html__( 'Nightly rate', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_nightly_rate_format ) . ' <span class="currency">' . esc_html( $nd_booking_currency ) . '</span></span></li>';
$nd_booking_shortcode_left_content .= '    </ul>';
$nd_booking_shortcode_left_content .= '    <div class="loft-booking-summary-panel__total">';
$nd_booking_shortcode_left_content .= '      <p class="summary-label">' . esc_html__( 'Pay today', 'nd-booking' ) . '</p>';
$nd_booking_shortcode_left_content .= '      <div class="summary-amount">';
$nd_booking_shortcode_left_content .= '        <span class="amount">' . esc_html( $nd_booking_initial_total_formatted ) . '</span>';
$nd_booking_shortcode_left_content .= '        <span class="currency">' . esc_html( $nd_booking_currency ) . '</span>';
$nd_booking_shortcode_left_content .= '      </div>';
$nd_booking_shortcode_left_content .= '      <p class="summary-note">' . esc_html__( 'You will be charged this amount today. This rate is non-refundable and cannot be changed or cancelled.', 'nd-booking' ) . '</p>';
$nd_booking_shortcode_left_content .= '    </div>';

if ( '' !== $nd_booking_total_tax_rate_text ) {
    $nd_booking_shortcode_left_content .= '    <p class="loft-booking-summary-panel__taxes">' . esc_html( $nd_booking_total_tax_rate_text ) . ' · ' . esc_html__( 'Taxes are included in the total.', 'nd-booking' ) . '</p>';
}

$nd_booking_shortcode_left_content .= '    <div class="loft-booking-summary-panel__breakdown">';
$nd_booking_shortcode_left_content .= '      <div class="breakdown-row"><span class="label">' . esc_html__( 'Subtotal', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_initial_subtotal_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_currency ) . '</span></span></div>';
$nd_booking_shortcode_left_content .= '      <div class="breakdown-row"><span class="label">' . esc_html__( 'Taxes & fees', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_initial_tax_total_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_currency ) . '</span></span></div>';
$nd_booking_shortcode_left_content .= '      <div class="breakdown-row breakdown-row--total"><span class="label">' . esc_html__( 'Total', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_initial_total_formatted ) . ' <span class="currency">' . esc_html( $nd_booking_currency ) . '</span></span></div>';
$nd_booking_shortcode_left_content .= '    </div>';
$nd_booking_shortcode_left_content .= '    <div class="loft-booking-summary-panel__support">';
$nd_booking_shortcode_left_content .= '      <span class="support-label">' . esc_html__( 'Need assistance?', 'nd-booking' ) . '</span>';
$nd_booking_shortcode_left_content .= '      <a class="support-link" href="tel:+18333111785">(833) 311-1785</a>';
$nd_booking_shortcode_left_content .= '    </div>';
$nd_booking_shortcode_left_content .= '  </div>';
$nd_booking_shortcode_left_content .= '</aside>';

