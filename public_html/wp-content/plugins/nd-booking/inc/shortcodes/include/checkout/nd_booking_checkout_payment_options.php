<?php


//tabs js
wp_enqueue_script('jquery-ui-tabs');

// Force Stripe (credit card) payment option to remain available
$nd_booking_stripe_enable = 1;

$nd_booking_shortcode_right_content .= '

    <script type="text/javascript">
    <!--//--><![CDATA[//><!--
        jQuery(document).ready(function($) {
            $(".nd_booking_tabs").tabs();
        });
    //--><!]]>
    </script>

    <style>
        #nd_booking_checkout_payment_tab_list li.ui-state-active a { font-weight:bolder; }
    </style>
        
    <div class="nd_booking_section nd_booking_height_40"></div>
    <div class="nd_booking_section nd_booking_height_2 nd_booking_bg_grey"></div>
    <div class="nd_booking_section nd_booking_height_40"></div>
    

    <h1>'.__('Payment Options','nd-booking').' :</h1>
    <div class="nd_booking_section nd_booking_height_30"></div>

    
    <!--START Tabs-->
    <div class="nd_booking_tabs nd_booking_section">

        <ul id="nd_booking_checkout_payment_tab_list" class="nd_booking_list_style_none nd_booking_margin_0 nd_booking_padding_0 nd_booking_section">

            <li id="nd_booking_s_payment" class="nd_booking_display_inline_block nd_booking_margin_right_20">
                <h4>
                    <a class="nd_booking_outline_0 nd_booking_padding_10_0 nd_booking_letter_spacing_2 nd_booking_font_weight_lighter nd_booking_font_size_14 nd_booking_display_inline_block nd_options_second_font nd_options_color_greydark" href="#nd_booking_checkout_payment_5_tab">'.__('CREDIT CARD','nd-booking').'</a>
                </h4>
            </li>

        </ul>

        <div class="nd_booking_section nd_booking_height_20"></div>
        <div class="nd_booking_section" id="nd_booking_checkout_payment_5_tab">
            
            '.do_shortcode(get_option('nd_booking_stripe_checkout')).'

            <div class="nd_booking_section nd_booking_height_20"></div>

            <script src="https://js.stripe.com/v3/"></script>

            <form action="'.nd_booking_checkout_page().'" method="post" id="payment-form">
                
                <div class="form-row nd_booking_margin_top_20 nd_booking_margin_bottom_20">
                    <div id="card-element"></div>
                    <div class="nd_booking_margin_top_10" id="card-errors" role="alert"></div>
                </div>

                <input type="hidden" id="nd_booking_form_checkout_arrive" name="nd_booking_form_checkout_arrive" value="2">
                <input type="hidden" id="nd_booking_checkout_form_date_from" name="nd_booking_checkout_form_date_from" value="'.$nd_booking_booking_form_date_from.'">
                <input type="hidden" id="nd_booking_checkout_form_date_top" name="nd_booking_checkout_form_date_top" value="'.$nd_booking_booking_form_date_to.'">
                <input type="hidden" id="nd_booking_checkout_form_guests" name="nd_booking_checkout_form_guests" value="'.$nd_booking_booking_form_guests.'">
                <input type="hidden" id="nd_booking_checkout_form_final_price" name="nd_booking_checkout_form_final_price" value="'.$nd_booking_booking_form_final_price.'">
                <input type="hidden" id="nd_booking_checkout_form_post_id" name="nd_booking_checkout_form_post_id" value="'.$nd_booking_booking_form_post_id.'-'.$nd_booking_id_room.'">
                <input type="hidden" id="nd_booking_checkout_form_post_title" name="nd_booking_checkout_form_post_title" value="'.$nd_booking_booking_form_post_title.'">
                <input type="hidden" id="nd_booking_checkout_form_name" name="nd_booking_checkout_form_name" value="'.$nd_booking_booking_form_name.'">
                <input type="hidden" id="nd_booking_checkout_form_surname" name="nd_booking_checkout_form_surname" value="'.$nd_booking_booking_form_surname.'">
                <input type="hidden" id="nd_booking_checkout_form_email" name="nd_booking_checkout_form_email" value="'.$nd_booking_booking_form_email.'">
                <input type="hidden" id="nd_booking_checkout_form_phone" name="nd_booking_checkout_form_phone" value="'.$nd_booking_booking_form_phone.'">
                <input type="hidden" id="nd_booking_checkout_form_address" name="nd_booking_checkout_form_address" value="'.$nd_booking_booking_form_address.'">
                <input type="hidden" id="nd_booking_checkout_form_city" name="nd_booking_checkout_form_city" value="'.$nd_booking_booking_form_city.'">
                <input type="hidden" id="nd_booking_checkout_form_country" name="nd_booking_checkout_form_country" value="'.$nd_booking_booking_form_country.'">
                <input type="hidden" id="nd_booking_checkout_form_zip" name="nd_booking_checkout_form_zip" value="'.$nd_booking_booking_form_zip.'">
                <input type="hidden" id="nd_booking_checkout_form_requets" name="nd_booking_checkout_form_requets" value="'.$nd_booking_booking_form_requests.'">
                <input type="hidden" id="nd_booking_checkout_form_arrival" name="nd_booking_checkout_form_arrival" value="'.$nd_booking_booking_form_arrival.'">
                <input type="hidden" id="nd_booking_checkout_form_coupon" name="nd_booking_checkout_form_coupon" value="'.$nd_booking_booking_form_coupon.'">
                <input type="hidden" id="nd_booking_checkout_form_guest_id_front" name="nd_booking_checkout_form_guest_id_front" value="'.$nd_booking_guest_id_front_token.'">
                <input type="hidden" id="nd_booking_checkout_form_guest_id_back" name="nd_booking_checkout_form_guest_id_back" value="'.$nd_booking_guest_id_back_token.'">
                <input type="hidden" id="nd_booking_checkout_form_guest_id_number" name="nd_booking_checkout_form_guest_id_number" value="'.$nd_booking_guest_id_number.'">
                <input type="hidden" id="nd_booking_checkout_form_guest_id_type" name="nd_booking_checkout_form_guest_id_type" value="'.$nd_booking_guest_id_type.'">
                <input type="hidden" id="nd_booking_checkout_form_term" name="nd_booking_checkout_form_term" value="'.$nd_booking_booking_form_term.'">
                <input type="hidden" id="nd_booking_booking_form_services" name="nd_booking_booking_form_services" value="'.$nd_booking_booking_form_services.'">
                <input type="hidden" id="nd_booking_booking_form_action_type" name="nd_booking_booking_form_action_type" value="stripe">
                <input type="hidden" id="nd_booking_booking_form_payment_status" name="nd_booking_booking_form_payment_status" value="Pending Payment">

                <input class="nd_booking_font_size_11 nd_options_second_font_important nd_booking_font_weight_bold nd_booking_letter_spacing_2 nd_booking_padding_15_35_important" type="submit" id="" name="" value="'.__('SUBMIT PAYMENT','nd-booking').'">

            </form>


            <script type="text/javascript">
            <!--//--><![CDATA[//><!--
                
                // Create a Stripe client
                var stripe = Stripe("'.get_option('nd_booking_stripe_public_key').'");

                // Create an instance of Elements
                var elements = stripe.elements();

                // Custom styling can be passed to options when creating an Element.
                // (Note that this demo uses a wider set of styles than the guide below.)
                var style = {
                  base: {
                    color: "#878787",
                    lineHeight: "18px",
                    fontFamily: "Roboto, sans-serif",
                    fontSmoothing: "antialiased",
                    fontSize: "16px",
                    "::placeholder": {
                      color: "#878787"
                    }
                  },
                  invalid: {
                    color: "#F44336",
                    iconColor: "#F44336"
                  }
                };

                // Create an instance of the card Element
                var card = elements.create("card", {style: style});

                // Add an instance of the card Element into the `card-element` <div>
                card.mount("#card-element");

                // Handle real-time validation errors from the card Element.
                card.addEventListener("change", function(event) {
                  var displayError = document.getElementById("card-errors");
                  if (event.error) {
                    displayError.textContent = event.error.message;
                  } else {
                    displayError.textContent = "";
                  }
                });

                // Handle form submission
                var form = document.getElementById("payment-form");
                form.addEventListener("submit", function(event) {
                  event.preventDefault();

                  stripe.createToken(card).then(function(result) {
                    if (result.error) {
                      // Inform the user if there was an error
                      var errorElement = document.getElementById("card-errors");
                      errorElement.textContent = result.error.message;
                    } else {
                      // Send the token to your server
                      stripeTokenHandler(result.token);
                    }
                  });
                });

                function stripeTokenHandler(token) {
                  // Insert the token ID into the form so it gets submitted to the server
                  var form = document.getElementById("payment-form");
                  var hiddenInput = document.createElement("input");
                  hiddenInput.setAttribute("type", "hidden");
                  hiddenInput.setAttribute("name", "stripeToken");
                  hiddenInput.setAttribute("value", token.id);
                  form.appendChild(hiddenInput);

                  // Submit the form
                  form.submit();
                }

            //--><!]]>
            </script>




        </div>





        

    </div>
    <!--END Tabs-->



';