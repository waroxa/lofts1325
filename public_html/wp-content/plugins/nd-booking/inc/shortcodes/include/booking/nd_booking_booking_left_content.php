<?php

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
    $nd_booking_trip_price = $nd_booking_trip_price_for_person * max( 1, absint( $nd_booking_form_booking_guests ) );
} else {
    $nd_booking_trip_price = $nd_booking_trip_price_for_person;
}

$nd_booking_tax_breakdown               = nd_booking_calculate_tax_breakdown( $nd_booking_trip_price );
$nd_booking_currency                    = nd_booking_get_currency();
$nd_booking_initial_total_formatted     = nd_booking_format_decimal( $nd_booking_tax_breakdown['total'] );
$nd_booking_initial_subtotal_formatted  = nd_booking_format_decimal( $nd_booking_tax_breakdown['base'] );
$nd_booking_initial_tax_total_formatted = nd_booking_format_decimal( $nd_booking_tax_breakdown['total_tax'] );

$nd_booking_tax_rate_descriptions = array();
if ( isset( $nd_booking_tax_breakdown['taxes'] ) && is_array( $nd_booking_tax_breakdown['taxes'] ) ) {
    foreach ( $nd_booking_tax_breakdown['taxes'] as $nd_booking_tax_data ) {
        $nd_booking_tax_rate_descriptions[] = sprintf(
            /* translators: 1: Tax label, 2: Tax rate */
            __( '%1$s %2$s%%', 'nd-booking' ),
            $nd_booking_tax_data['label'],
            nd_booking_format_percentage( $nd_booking_tax_data['rate'] )
        );
    }
}

$nd_booking_total_tax_rate_text = '';
if ( ! empty( $nd_booking_tax_rate_descriptions ) ) {
    $nd_booking_total_tax_rate_text = implode( ' · ', $nd_booking_tax_rate_descriptions );
}

$nd_booking_nights       = max( 1, nd_booking_get_number_night( $nd_booking_date_from, $nd_booking_date_to ) );
$nd_booking_nightly_rate = $nd_booking_nights > 0 ? $nd_booking_trip_price / $nd_booking_nights : 0;

$nd_booking_meta_box_max_people   = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_max_people', true );
$nd_booking_meta_box_room_size    = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_room_size', true );
$nd_booking_meta_box_text_preview = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_text_preview', true );
$nd_booking_meta_box_branches     = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_branches', true );
$nd_booking_meta_box_color        = get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_color', true );
$nd_booking_meta_box_cpt_4_stars  = get_post_meta( $nd_booking_meta_box_branches, 'nd_booking_meta_box_cpt_4_stars', true );

if ( '' === $nd_booking_meta_box_color ) {
    $nd_booking_meta_box_color = '#111827';
}

$nd_booking_room_excerpt = wp_kses_post( $nd_booking_meta_box_text_preview );
if ( '' === $nd_booking_room_excerpt ) {
    $nd_booking_room_excerpt = wp_kses_post( get_the_excerpt( $nd_booking_form_booking_id ) );
}

$nd_booking_plugin_root_file = dirname( __FILE__, 5 ) . '/nd-booking.php';
$nd_booking_image_src        = nd_booking_get_post_img_src( $nd_booking_form_booking_id );
$nd_booking_branch_title     = '';

if ( ! empty( $nd_booking_meta_box_branches ) ) {
    $nd_booking_branch_title = get_the_title( $nd_booking_meta_box_branches );
}

$nd_booking_star_count = intval( $nd_booking_meta_box_cpt_4_stars );
$nd_booking_star_icon  = esc_url( plugins_url( 'inc/shortcodes/include/search-results/icon-star-full-white.svg', $nd_booking_plugin_root_file ) );

$nd_booking_check_in_date  = new DateTime( $nd_booking_date_from );
$nd_booking_check_out_date = new DateTime( $nd_booking_date_to );

$nd_booking_check_in_label  = date_i18n( 'D, M j, Y', $nd_booking_check_in_date->getTimestamp() );
$nd_booking_check_out_label = date_i18n( 'D, M j, Y', $nd_booking_check_out_date->getTimestamp() );

ob_start();
?>
<aside class="ndb-booking-summary" aria-label="<?php echo esc_attr__( 'Booking summary', 'nd-booking' ); ?>">
    <div class="ndb-booking-summary__card">
        <?php if ( $nd_booking_image_src ) : ?>
            <div class="ndb-booking-summary__media">
                <img class="ndb-booking-summary__image" src="<?php echo esc_url( $nd_booking_image_src ); ?>" alt="" />
                <div class="ndb-booking-summary__media-overlay">
                    <?php if ( $nd_booking_branch_title ) : ?>
                        <span class="ndb-booking-summary__badge"><?php echo esc_html( $nd_booking_branch_title ); ?></span>
                    <?php endif; ?>
                    <?php if ( $nd_booking_star_count > 0 ) : ?>
                        <span class="ndb-booking-summary__stars" aria-hidden="true">
                            <?php for ( $i = 0; $i < $nd_booking_star_count; $i++ ) : ?>
                                <img src="<?php echo $nd_booking_star_icon; ?>" alt="" width="12" />
                            <?php endfor; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="ndb-booking-summary__content">
            <h2 class="ndb-booking-summary__title" style="color: <?php echo esc_attr( $nd_booking_meta_box_color ); ?>;">
                <?php echo esc_html( get_the_title( $nd_booking_form_booking_id ) ); ?>
            </h2>

            <?php if ( $nd_booking_room_excerpt ) : ?>
                <div class="ndb-booking-summary__excerpt"><?php echo $nd_booking_room_excerpt; ?></div>
            <?php endif; ?>

            <ul class="ndb-booking-summary__meta" aria-label="<?php echo esc_attr__( 'Room features', 'nd-booking' ); ?>">
                <?php if ( $nd_booking_meta_box_max_people ) : ?>
                    <li><?php echo esc_html( sprintf( _n( '%d Guest', '%d Guests', $nd_booking_meta_box_max_people, 'nd-booking' ), $nd_booking_meta_box_max_people ) ); ?></li>
                <?php endif; ?>
                <?php if ( $nd_booking_meta_box_room_size ) : ?>
                    <li><?php echo esc_html( $nd_booking_meta_box_room_size . ' ' . nd_booking_get_units_of_measure() ); ?></li>
                <?php endif; ?>
                <li><?php echo esc_html( sprintf( _n( '%d night', '%d nights', $nd_booking_nights, 'nd-booking' ), $nd_booking_nights ) ); ?></li>
            </ul>
        </div>
    </div>

    <section class="ndb-booking-summary__details" aria-label="<?php echo esc_attr__( 'Stay details', 'nd-booking' ); ?>">
        <ul class="ndb-booking-summary__list">
            <li>
                <span class="label"><?php esc_html_e( 'Check-in', 'nd-booking' ); ?></span>
                <span class="value"><?php echo esc_html( $nd_booking_check_in_label ); ?></span>
            </li>
            <li>
                <span class="label"><?php esc_html_e( 'Check-out', 'nd-booking' ); ?></span>
                <span class="value"><?php echo esc_html( $nd_booking_check_out_label ); ?></span>
            </li>
            <li>
                <span class="label"><?php esc_html_e( 'Guests', 'nd-booking' ); ?></span>
                <span class="value"><?php echo esc_html( max( 1, absint( $nd_booking_form_booking_guests ) ) ); ?></span>
            </li>
            <li>
                <span class="label"><?php esc_html_e( 'Nightly rate', 'nd-booking' ); ?></span>
                <span class="value"><?php echo esc_html( nd_booking_format_decimal( $nd_booking_nightly_rate ) ); ?> <span class="currency"><?php echo esc_html( $nd_booking_currency ); ?></span></span>
            </li>
        </ul>

        <div class="ndb-booking-summary__total" role="presentation">
            <p class="summary-label"><?php esc_html_e( 'Pay today', 'nd-booking' ); ?></p>
            <div class="summary-amount">
                <span class="amount"><?php echo esc_html( $nd_booking_initial_total_formatted ); ?></span>
                <span class="currency"><?php echo esc_html( $nd_booking_currency ); ?></span>
            </div>
            <p class="summary-note"><?php esc_html_e( 'This rate is charged immediately and is non-refundable.', 'nd-booking' ); ?></p>
        </div>

        <div class="ndb-booking-summary__breakdown" aria-label="<?php echo esc_attr__( 'Price breakdown', 'nd-booking' ); ?>">
            <div class="breakdown-row"><span class="label"><?php esc_html_e( 'Subtotal', 'nd-booking' ); ?></span><span class="value"><?php echo esc_html( $nd_booking_initial_subtotal_formatted ); ?> <span class="currency"><?php echo esc_html( $nd_booking_currency ); ?></span></span></div>
            <div class="breakdown-row"><span class="label"><?php esc_html_e( 'Taxes & fees', 'nd-booking' ); ?></span><span class="value"><?php echo esc_html( $nd_booking_initial_tax_total_formatted ); ?> <span class="currency"><?php echo esc_html( $nd_booking_currency ); ?></span></span></div>
        </div>

        <?php if ( $nd_booking_total_tax_rate_text ) : ?>
            <p class="ndb-booking-summary__taxes"><?php echo esc_html( $nd_booking_total_tax_rate_text ); ?> · <?php esc_html_e( 'Taxes are included in the total.', 'nd-booking' ); ?></p>
        <?php endif; ?>

        <div class="ndb-booking-summary__support">
            <span class="support-label"><?php esc_html_e( 'Need assistance?', 'nd-booking' ); ?></span>
            <a class="support-link" href="tel:+18333111785">(833) 311-1785</a>
        </div>
    </section>
</aside>
<?php

$nd_booking_shortcode_left_content = ob_get_clean();
