<?php

$nd_booking_initial_breakdown   = nd_booking_calculate_tax_breakdown( $nd_booking_trip_price );
$nd_booking_initial_final_price = $nd_booking_initial_breakdown['total'];
$nd_booking_initial_base_price  = $nd_booking_initial_breakdown['base'];

$nd_booking_shortcode_right_content = '';

ob_start();
?>
<section class="ndb-booking-form" aria-label="<?php echo esc_attr__( 'Guest details form', 'nd-booking' ); ?>">
    <header class="ndb-booking-form__header">
        <p class="ndb-booking-form__progress"><?php esc_html_e( 'Step 1 of 3', 'nd-booking' ); ?></p>
        <h1 class="ndb-booking-form__title"><?php esc_html_e( 'Secure your stay', 'nd-booking' ); ?></h1>
        <p class="ndb-booking-form__subtitle"><?php esc_html_e( 'Share the guest details below so we can finalize your reservation.', 'nd-booking' ); ?></p>
    </header>

    <form class="ndb-booking-form__body" method="post" enctype="multipart/form-data" action="<?php echo esc_url( nd_booking_checkout_page() ); ?>">
        <input type="hidden" id="nd_booking_form_booking_arrive" name="nd_booking_form_booking_arrive" value="1" />
        <input type="hidden" id="nd_booking_booking_form_final_price" name="nd_booking_booking_form_final_price" value="<?php echo esc_attr( nd_booking_format_decimal( $nd_booking_initial_final_price ) ); ?>" />
        <input type="hidden" id="nd_booking_booking_form_base_price" name="nd_booking_booking_form_base_price" value="<?php echo esc_attr( nd_booking_format_decimal( $nd_booking_initial_base_price ) ); ?>" />
        <input type="hidden" id="nd_booking_booking_form_trip_price" name="nd_booking_booking_form_trip_price" value="<?php echo esc_attr( nd_booking_format_decimal( $nd_booking_trip_price ) ); ?>" />
        <input type="hidden" id="nd_booking_booking_form_date_from" name="nd_booking_booking_form_date_from" value="<?php echo esc_attr( $nd_booking_date_from ); ?>" />
        <input type="hidden" id="nd_booking_booking_form_date_to" name="nd_booking_booking_form_date_to" value="<?php echo esc_attr( $nd_booking_date_tooo ); ?>" />
        <input type="hidden" id="nd_booking_booking_form_guests" name="nd_booking_booking_form_guests" value="<?php echo esc_attr( $nd_booking_form_booking_guests ); ?>" />
        <input type="hidden" id="nd_booking_booking_form_post_id" name="nd_booking_booking_form_post_id" value="<?php echo esc_attr( $nd_booking_form_booking_id . '-' . $nd_booking_id_room ); ?>" />
        <input type="hidden" id="nd_booking_booking_form_post_title" name="nd_booking_booking_form_post_title" value="<?php echo esc_attr( get_the_title( $nd_booking_form_booking_id ) ); ?>" />
        <input type="hidden" id="nd_booking_booking_checkbox_services_id" name="nd_booking_booking_checkbox_services_id" readonly value="" />

        <section class="ndb-form-section" aria-labelledby="ndb-form-contact">
            <h2 id="ndb-form-contact"><?php esc_html_e( 'Guest information', 'nd-booking' ); ?></h2>
            <div class="ndb-form-grid">
                <div class="ndb-form-field" id="nd_booking_booking_form_name_container">
                    <label for="nd_booking_booking_form_name"><?php esc_html_e( 'First name', 'nd-booking' ); ?> *</label>
                    <input class="ndb-input" id="nd_booking_booking_form_name" name="nd_booking_booking_form_name" type="text" autocomplete="given-name" />
                </div>
                <div class="ndb-form-field" id="nd_booking_booking_form_surname_container">
                    <label for="nd_booking_booking_form_surname"><?php esc_html_e( 'Last name', 'nd-booking' ); ?> *</label>
                    <input class="ndb-input" id="nd_booking_booking_form_surname" name="nd_booking_booking_form_surname" type="text" autocomplete="family-name" />
                </div>
                <div class="ndb-form-field" id="nd_booking_booking_form_email_container">
                    <label for="nd_booking_booking_form_email"><?php esc_html_e( 'Email address', 'nd-booking' ); ?> *</label>
                    <input class="ndb-input" id="nd_booking_booking_form_email" name="nd_booking_booking_form_email" type="email" autocomplete="email" />
                </div>
                <div class="ndb-form-field" id="nd_booking_booking_form_phone_container">
                    <label for="nd_booking_booking_form_phone"><?php esc_html_e( 'Mobile phone', 'nd-booking' ); ?> *</label>
                    <input class="ndb-input" id="nd_booking_booking_form_phone" name="nd_booking_booking_form_phone" type="tel" autocomplete="tel" />
                </div>
            </div>
        </section>

        <section class="ndb-form-section" aria-labelledby="ndb-form-address">
            <h2 id="ndb-form-address"><?php esc_html_e( 'Billing address', 'nd-booking' ); ?></h2>
            <div class="ndb-form-grid">
                <div class="ndb-form-field" id="nd_booking_booking_form_address_container">
                    <label for="nd_booking_booking_form_address"><?php esc_html_e( 'Street address', 'nd-booking' ); ?></label>
                    <input class="ndb-input" id="nd_booking_booking_form_address" name="nd_booking_booking_form_address" type="text" autocomplete="address-line1" />
                </div>
                <div class="ndb-form-field" id="nd_booking_booking_form_city_container">
                    <label for="nd_booking_booking_form_city"><?php esc_html_e( 'City', 'nd-booking' ); ?></label>
                    <input class="ndb-input" id="nd_booking_booking_form_city" name="nd_booking_booking_form_city" type="text" autocomplete="address-level2" />
                </div>
                <div class="ndb-form-field" id="nd_booking_booking_form_country_container">
                    <label for="nd_booking_booking_form_country"><?php esc_html_e( 'Country', 'nd-booking' ); ?></label>
                    <input class="ndb-input" id="nd_booking_booking_form_country" name="nd_booking_booking_form_country" type="text" autocomplete="country-name" />
                </div>
                <div class="ndb-form-field" id="nd_booking_booking_form_zip_container">
                    <label for="nd_booking_booking_form_zip"><?php esc_html_e( 'Postal code', 'nd-booking' ); ?></label>
                    <input class="ndb-input" id="nd_booking_booking_form_zip" name="nd_booking_booking_form_zip" type="text" autocomplete="postal-code" />
                </div>
            </div>
        </section>

        <section class="ndb-form-section" aria-labelledby="ndb-form-id">
            <h2 id="ndb-form-id"><?php esc_html_e( 'Identification', 'nd-booking' ); ?></h2>
            <div class="ndb-form-grid">
                <div class="ndb-form-field">
                    <label for="guest_id_number"><?php esc_html_e( 'ID number', 'nd-booking' ); ?></label>
                    <input class="ndb-input" id="guest_id_number" name="guest_id_number" type="text" />
                </div>
                <div class="ndb-form-field">
                    <label for="guest_id_type"><?php esc_html_e( 'ID type', 'nd-booking' ); ?></label>
                    <select class="ndb-input" id="guest_id_type" name="guest_id_type">
                        <option value="driver_license"><?php esc_html_e( "Driver's License", 'nd-booking' ); ?></option>
                        <option value="passport"><?php esc_html_e( 'Passport', 'nd-booking' ); ?></option>
                    </select>
                </div>
                <div class="ndb-form-field ndb-form-field--file">
                    <label for="guest_id_front"><?php esc_html_e( 'Guest ID (front)', 'nd-booking' ); ?></label>
                    <input class="ndb-input" type="file" id="guest_id_front" name="guest_id_front" accept="image/*" />
                </div>
                <div class="ndb-form-field ndb-form-field--file">
                    <label for="guest_id_back"><?php esc_html_e( 'Guest ID (back)', 'nd-booking' ); ?></label>
                    <input class="ndb-input" type="file" id="guest_id_back" name="guest_id_back" accept="image/*" />
                </div>
            </div>
        </section>

        <section class="ndb-form-section" aria-labelledby="ndb-form-requests">
            <h2 id="ndb-form-requests"><?php esc_html_e( 'Special requests', 'nd-booking' ); ?></h2>
            <div class="ndb-form-field" id="nd_booking_booking_form_requests_container">
                <label for="nd_booking_booking_form_requests"><?php esc_html_e( 'Let us know if you have any preferences or arrival notes.', 'nd-booking' ); ?></label>
                <textarea class="ndb-input" id="nd_booking_booking_form_requests" name="nd_booking_booking_form_requests" rows="5"></textarea>
            </div>
        </section>

        <section class="ndb-form-section" aria-labelledby="ndb-form-arrival">
            <h2 id="ndb-form-arrival"><?php esc_html_e( 'Arrival details', 'nd-booking' ); ?></h2>
            <p class="ndb-help-text"><?php esc_html_e( 'Check-in starts at 4 PM; checkout is at 12 PM.', 'nd-booking' ); ?></p>
            <input type="hidden" class="ndb-input" name="nd_booking_booking_form_arrival" id="nd_booking_booking_form_arrival" value="4:00 - 5:00 <?php echo esc_attr__( 'pm', 'nd-booking' ); ?>" />
        </section>

        <?php $nd_booking_coupon_class = nd_booking_get_coupon_enable_class(); ?>
        <section class="ndb-form-section <?php echo esc_attr( $nd_booking_coupon_class ); ?>" id="nd_booking_booking_form_coupon_container">
            <h2><?php esc_html_e( 'Promo code', 'nd-booking' ); ?></h2>
            <div class="ndb-form-field">
                <label for="nd_booking_booking_form_coupon"><?php esc_html_e( 'Enter your coupon', 'nd-booking' ); ?></label>
                <input class="ndb-input" id="nd_booking_booking_form_coupon" name="nd_booking_booking_form_coupon" type="text" />
            </div>
        </section>

        <section class="ndb-form-section" id="nd_booking_booking_form_term_container">
            <label class="ndb-checkbox">
                <input class="ndb-checkbox__input" id="nd_booking_booking_form_term" name="nd_booking_booking_form_term" type="checkbox" value="1" checked />
                <span class="ndb-checkbox__label"><?php printf( esc_html__( 'I agree to the %s', 'nd-booking' ), '<a target="_blank" href="' . esc_url( nd_booking_terms_page() ) . '">' . esc_html__( 'terms and conditions', 'nd-booking' ) . '</a>' ); ?></span>
            </label>
        </section>

        <div class="ndb-form-actions">
            <button type="button" class="ndb-button" onclick="nd_booking_validate_fields()">
                <?php esc_html_e( 'Proceed to checkout', 'nd-booking' ); ?>
            </button>
            <input id="nd_booking_submit_go_to_checkout" class="ndb-button ndb-button--ghost" type="submit" value="<?php echo esc_attr__( 'Proceed to checkout', 'nd-booking' ); ?>" />
        </div>
    </form>
</section>
<?php

$nd_booking_shortcode_right_content = ob_get_clean();
