<?php
wp_enqueue_script( 'jquery-ui-tabs' );

$nd_booking_shortcode_right_content .= '
    <div class="section loft-section-payment">
        <h3><span class="section-icon" aria-hidden="true">💳</span> ' . esc_html__( 'Détails de paiement', 'nd-booking' ) . '</h3>
        <div class="section-body">
            <div class="loft-payment-info">' . do_shortcode( get_option( 'nd_booking_stripe_checkout' ) ) . '</div>
            <div class="loft-payment-card">
                <script src="https://js.stripe.com/v3/"></script>
                <form action="' . esc_url( nd_booking_checkout_page() ) . '" method="post" id="payment-form" class="loft-payment-form">
                    <div class="form-row">
                        <div id="card-element"></div>
                        <div class="loft-card-errors" id="card-errors" role="alert"></div>
                    </div>
                    <input type="hidden" id="nd_booking_form_checkout_arrive" name="nd_booking_form_checkout_arrive" value="2">
                    <input type="hidden" id="nd_booking_checkout_form_date_from" name="nd_booking_checkout_form_date_from" value="' . esc_attr( $nd_booking_booking_form_date_from ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_date_top" name="nd_booking_checkout_form_date_top" value="' . esc_attr( $nd_booking_booking_form_date_to ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_guests" name="nd_booking_checkout_form_guests" value="' . esc_attr( $nd_booking_booking_form_guests ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_final_price" name="nd_booking_checkout_form_final_price" value="' . esc_attr( nd_booking_format_decimal( $nd_booking_booking_form_final_price ) ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_base_price" name="nd_booking_checkout_form_base_price" value="' . esc_attr( nd_booking_format_decimal( $nd_booking_tax_breakdown['base'] ) ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_post_id" name="nd_booking_checkout_form_post_id" value="' . esc_attr( $nd_booking_booking_form_post_id . '-' . $nd_booking_id_room ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_post_title" name="nd_booking_checkout_form_post_title" value="' . esc_attr( $nd_booking_booking_form_post_title ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_name" name="nd_booking_checkout_form_name" value="' . esc_attr( $nd_booking_booking_form_name ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_surname" name="nd_booking_checkout_form_surname" value="' . esc_attr( $nd_booking_booking_form_surname ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_email" name="nd_booking_checkout_form_email" value="' . esc_attr( $nd_booking_booking_form_email ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_phone" name="nd_booking_checkout_form_phone" value="' . esc_attr( $nd_booking_booking_form_phone ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_address" name="nd_booking_checkout_form_address" value="' . esc_attr( $nd_booking_booking_form_address ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_city" name="nd_booking_checkout_form_city" value="' . esc_attr( $nd_booking_booking_form_city ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_country" name="nd_booking_checkout_form_country" value="' . esc_attr( $nd_booking_booking_form_country ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_zip" name="nd_booking_checkout_form_zip" value="' . esc_attr( $nd_booking_booking_form_zip ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_requets" name="nd_booking_checkout_form_requets" value="' . esc_attr( $nd_booking_booking_form_requests ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_arrival" name="nd_booking_checkout_form_arrival" value="' . esc_attr( $nd_booking_booking_form_arrival ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_coupon" name="nd_booking_checkout_form_coupon" value="' . esc_attr( $nd_booking_booking_form_coupon ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_guest_id_front" name="nd_booking_checkout_form_guest_id_front" value="' . esc_attr( $nd_booking_guest_id_front_token ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_guest_id_back" name="nd_booking_checkout_form_guest_id_back" value="' . esc_attr( $nd_booking_guest_id_back_token ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_guest_id_number" name="nd_booking_checkout_form_guest_id_number" value="' . esc_attr( $nd_booking_guest_id_number ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_guest_id_type" name="nd_booking_checkout_form_guest_id_type" value="' . esc_attr( $nd_booking_guest_id_type ) . '">
                    <input type="hidden" id="nd_booking_checkout_form_term" name="nd_booking_checkout_form_term" value="' . esc_attr( $nd_booking_booking_form_term ) . '">
                    <input type="hidden" id="nd_booking_booking_form_services" name="nd_booking_booking_form_services" value="' . esc_attr( $nd_booking_booking_form_services ) . '">
                    <input type="hidden" id="nd_booking_booking_form_action_type" name="nd_booking_booking_form_action_type" value="stripe">
                    <input type="hidden" id="nd_booking_booking_form_payment_status" name="nd_booking_booking_form_payment_status" value="Paiement en attente">
                    <button type="submit" class="button-primary">' . esc_html__( 'Confirmer la réservation', 'nd-booking' ) . '</button>
                </form>
                <script type="text/javascript">
                    (function($) {
                        var stripe = Stripe("' . esc_js( get_option( 'nd_booking_stripe_public_key' ) ) . '");
                        var elements = stripe.elements();
                        var style = {
                            base: {
                                color: "#1f2933",
                                lineHeight: "18px",
                                fontFamily: "Roboto, sans-serif",
                                fontSmoothing: "antialiased",
                                fontSize: "16px",
                                "::placeholder": { color: "#9AA5B1" }
                            },
                            invalid: {
                                color: "#EF4444",
                                iconColor: "#EF4444"
                            }
                        };
                        var card = elements.create("card", { style: style });
                        card.mount("#card-element");
                        card.addEventListener("change", function(event) {
                            var displayError = document.getElementById("card-errors");
                            if (event.error) {
                                displayError.textContent = event.error.message;
                            } else {
                                displayError.textContent = "";
                            }
                        });
                        var form = document.getElementById("payment-form");
                        form.addEventListener("submit", function(event) {
                            event.preventDefault();
                            stripe.createToken(card).then(function(result) {
                                if (result.error) {
                                    var errorElement = document.getElementById("card-errors");
                                    errorElement.textContent = result.error.message;
                                } else {
                                    stripeTokenHandler(result.token);
                                }
                            });
                        });
                        function stripeTokenHandler(token) {
                            var form = document.getElementById("payment-form");
                            var hiddenInput = document.createElement("input");
                            hiddenInput.setAttribute("type", "hidden");
                            hiddenInput.setAttribute("name", "stripeToken");
                            hiddenInput.setAttribute("value", token.id);
                            form.appendChild(hiddenInput);
                            form.submit();
                        }
                    })(jQuery);
                </script>
            </div>
        </div>
    </div>
';
