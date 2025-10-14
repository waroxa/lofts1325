<?php

$nd_booking_initial_breakdown     = nd_booking_calculate_tax_breakdown( $nd_booking_trip_price );
$nd_booking_initial_final_price   = $nd_booking_initial_breakdown['total'];
$nd_booking_initial_base_price    = $nd_booking_initial_breakdown['base'];

$nd_booking_shortcode_right_content  = '';
$nd_booking_shortcode_right_content .= '<div class="loft-booking-form">';
$nd_booking_shortcode_right_content .= '  <div class="loft-progress-indicator">' . esc_html__( 'Step 1 of 3', 'nd-booking' ) . '</div>';
$nd_booking_shortcode_right_content .= '  <h1 class="loft-booking-form__title">' . esc_html__( 'Confirm your booking', 'nd-booking' ) . '</h1>';
$nd_booking_shortcode_right_content .= '  <p class="loft-booking-form__subtitle">' . esc_html__( 'Tell us who is staying so we can finalize your reservation.', 'nd-booking' ) . '</p>';

$nd_booking_shortcode_right_content .= '  <form class="loft-booking-form__form" method="post" enctype="multipart/form-data" action="' . esc_url( nd_booking_checkout_page() ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_form_booking_arrive" name="nd_booking_form_booking_arrive" value="1">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_final_price" name="nd_booking_booking_form_final_price" value="' . esc_attr( nd_booking_format_decimal( $nd_booking_initial_final_price ) ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_base_price" name="nd_booking_booking_form_base_price" value="' . esc_attr( nd_booking_format_decimal( $nd_booking_initial_base_price ) ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_trip_price" name="nd_booking_booking_form_trip_price" value="' . esc_attr( nd_booking_format_decimal( $nd_booking_trip_price ) ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_date_from" name="nd_booking_booking_form_date_from" value="' . esc_attr( $nd_booking_date_from ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_date_to" name="nd_booking_booking_form_date_to" value="' . esc_attr( $nd_booking_date_tooo ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_guests" name="nd_booking_booking_form_guests" value="' . esc_attr( $nd_booking_form_booking_guests ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_post_id" name="nd_booking_booking_form_post_id" value="' . esc_attr( $nd_booking_form_booking_id . '-' . $nd_booking_id_room ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_form_post_title" name="nd_booking_booking_form_post_title" value="' . esc_attr( get_the_title( $nd_booking_form_booking_id ) ) . '">';
$nd_booking_shortcode_right_content .= '      <input type="hidden" id="nd_booking_booking_checkbox_services_id" name="nd_booking_booking_checkbox_services_id" readonly value="">';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-contact">';
$nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🧍</span> ' . esc_html__( 'Guest information', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-grid">';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_name_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_name">' . esc_html__( 'First name', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_name" name="nd_booking_booking_form_name" type="text" autocomplete="given-name">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_surname_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_surname">' . esc_html__( 'Last name', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_surname" name="nd_booking_booking_form_surname" type="text" autocomplete="family-name">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_email_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_email">' . esc_html__( 'Email address', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_email" name="nd_booking_booking_form_email" type="email" autocomplete="email">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_phone_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_phone">' . esc_html__( 'Mobile phone', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_phone" name="nd_booking_booking_form_phone" type="tel" autocomplete="tel">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-address">';
$nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🏠</span> ' . esc_html__( 'Billing address', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-grid">';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_address_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_address">' . esc_html__( 'Street address', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_address" name="nd_booking_booking_form_address" type="text" autocomplete="address-line1">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_city_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_city">' . esc_html__( 'City', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_city" name="nd_booking_booking_form_city" type="text" autocomplete="address-level2">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_country_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_country">' . esc_html__( 'Country', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_country" name="nd_booking_booking_form_country" type="text" autocomplete="country-name">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_zip_container">';
$nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_zip">' . esc_html__( 'Postal code', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_zip" name="nd_booking_booking_form_zip" type="text" autocomplete="postal-code">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-identification">';
$nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🪪</span> ' . esc_html__( 'Identification', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-grid">';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field">';
$nd_booking_shortcode_right_content .= '                  <label for="guest_id_number">' . esc_html__( 'ID number', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="guest_id_number" name="guest_id_number" type="text">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field">';
$nd_booking_shortcode_right_content .= '                  <label for="guest_id_type">' . esc_html__( 'ID type', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <select class="loft-input" id="guest_id_type" name="guest_id_type">';
$nd_booking_shortcode_right_content .= '                      <option value="driver_license">' . esc_html__( "Driver's License", 'nd-booking' ) . '</option>';
$nd_booking_shortcode_right_content .= '                      <option value="passport">' . esc_html__( 'Passport', 'nd-booking' ) . '</option>';
$nd_booking_shortcode_right_content .= '                  </select>';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field loft-form-field--file">';
$nd_booking_shortcode_right_content .= '                  <label for="guest_id_front">' . esc_html__( 'Guest ID (front)', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" type="file" id="guest_id_front" name="guest_id_front" accept="image/*">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field loft-form-field--file">';
$nd_booking_shortcode_right_content .= '                  <label for="guest_id_back">' . esc_html__( 'Guest ID (back)', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" type="file" id="guest_id_back" name="guest_id_back" accept="image/*">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-requests">';
$nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">💬</span> ' . esc_html__( 'Special requests', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-field" id="nd_booking_booking_form_requests_container">';
$nd_booking_shortcode_right_content .= '              <label for="nd_booking_booking_form_requests">' . esc_html__( 'Let us know if you have any preferences or arrival notes.', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '              <textarea class="loft-input" id="nd_booking_booking_form_requests" name="nd_booking_booking_form_requests" rows="5"></textarea>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-arrival">';
$nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🕓</span> ' . esc_html__( 'Arrival details', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <p class="loft-help-text">' . esc_html__( 'Check-in starts at 4 PM; checkout is at 12 PM.', 'nd-booking' ) . '</p>';
$nd_booking_shortcode_right_content .= '          <input type="hidden" class="loft-input" name="nd_booking_booking_form_arrival" id="nd_booking_booking_form_arrival" value="4:00 - 5:00 ' . esc_attr__( 'pm', 'nd-booking' ) . '">';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_coupon_class = nd_booking_get_coupon_enable_class();
if ( '' === $nd_booking_coupon_class ) {
    $nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-coupon" id="nd_booking_booking_form_coupon_container">';
    $nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🏷️</span> ' . esc_html__( 'Promo code', 'nd-booking' ) . '</h2>';
    $nd_booking_shortcode_right_content .= '          <div class="loft-form-field">';
    $nd_booking_shortcode_right_content .= '              <label for="nd_booking_booking_form_coupon">' . esc_html__( 'Enter your coupon', 'nd-booking' ) . '</label>';
    $nd_booking_shortcode_right_content .= '              <input class="loft-input" id="nd_booking_booking_form_coupon" name="nd_booking_booking_form_coupon" type="text">';
    $nd_booking_shortcode_right_content .= '          </div>';
    $nd_booking_shortcode_right_content .= '      </section>';
}

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-terms" id="nd_booking_booking_form_term_container">';
$nd_booking_shortcode_right_content .= '          <label class="loft-checkbox">';
$nd_booking_shortcode_right_content .= '              <input class="loft-checkbox__input" id="nd_booking_booking_form_term" name="nd_booking_booking_form_term" type="checkbox" value="1" checked>';
$nd_booking_shortcode_right_content .= '              <span class="loft-checkbox__label">' . sprintf( esc_html__( 'I agree to the %s', 'nd-booking' ), '<a target="_blank" href="' . esc_url( nd_booking_terms_page() ) . '">' . esc_html__( 'terms and conditions', 'nd-booking' ) . '</a>' ) . '</span>';
$nd_booking_shortcode_right_content .= '          </label>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <div class="loft-form-actions">';
$nd_booking_shortcode_right_content .= '          <button type="button" class="loft-button" onclick="nd_booking_validate_fields()">' . esc_html__( 'Proceed to checkout', 'nd-booking' ) . '</button>';
$nd_booking_shortcode_right_content .= '          <input id="nd_booking_submit_go_to_checkout" class="loft-button loft-button--hidden" type="submit" value="' . esc_attr__( 'Proceed to checkout', 'nd-booking' ) . '">';
$nd_booking_shortcode_right_content .= '      </div>';

$nd_booking_shortcode_right_content .= '  </form>';
$nd_booking_shortcode_right_content .= '</div>';

