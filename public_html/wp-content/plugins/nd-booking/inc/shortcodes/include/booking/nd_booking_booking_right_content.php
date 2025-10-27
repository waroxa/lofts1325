<?php

$nd_booking_initial_breakdown     = nd_booking_calculate_tax_breakdown( $nd_booking_trip_price );
$nd_booking_initial_final_price   = $nd_booking_initial_breakdown['total'];
$nd_booking_initial_base_price    = $nd_booking_initial_breakdown['base'];

$nd_booking_currency_symbol            = nd_booking_get_currency();
$nd_booking_initial_total_formatted     = nd_booking_format_decimal( $nd_booking_initial_final_price );
$nd_booking_hold_minutes                = 10;
$nd_booking_check_in_display            = '';
$nd_booking_check_out_display           = '';
$nd_booking_nights_count                = 0;
$nd_booking_guest_label                 = '';
$nd_booking_night_label                 = '';

if ( ! empty( $nd_booking_date_from ) ) {
    $nd_booking_check_in_display = date_i18n( 'D, M j', strtotime( $nd_booking_date_from ) );
}

if ( ! empty( $nd_booking_date_to ) ) {
    $nd_booking_check_out_display = date_i18n( 'D, M j', strtotime( $nd_booking_date_to ) );
} elseif ( ! empty( $nd_booking_date_tooo ) ) {
    $nd_booking_check_out_display = date_i18n( 'D, M j', strtotime( $nd_booking_date_tooo ) );
}

$nd_booking_nights_count = max( 1, nd_booking_get_number_night( $nd_booking_date_from, $nd_booking_date_to ) );
$nd_booking_night_label  = sprintf( _n( '%d nuit', '%d nuits', $nd_booking_nights_count, 'nd-booking' ), $nd_booking_nights_count );

$nd_booking_guest_total = absint( $nd_booking_form_booking_guests );
if ( $nd_booking_guest_total > 0 ) {
    $nd_booking_guest_label = sprintf( _n( '%d invité', '%d invités', $nd_booking_guest_total, 'nd-booking' ), $nd_booking_guest_total );
}

$nd_booking_urgency_headline = esc_html__( 'Un choix populaire! Remplissez vos renseignements pour sécuriser ce séjour.', 'nd-booking' );
if ( $nd_booking_check_in_display ) {
    $nd_booking_urgency_headline = sprintf(
        esc_html__( 'Un choix très prisé pour les arrivées le %s — réservez-le dès maintenant.', 'nd-booking' ),
        esc_html( $nd_booking_check_in_display )
    );
}

$nd_booking_rate_hold_text = sprintf(
    esc_html__( 'Nous réservons %1$s %2$s pendant %3$d minutes le temps que vous ajoutiez les renseignements des invités.', 'nd-booking' ),
    esc_html( $nd_booking_initial_total_formatted ),
    esc_html( $nd_booking_currency_symbol ),
    absint( $nd_booking_hold_minutes )
);

$nd_booking_urgency_list_markup = '';
if ( $nd_booking_check_in_display || $nd_booking_check_out_display || $nd_booking_guest_label || $nd_booking_night_label ) {
    $nd_booking_urgency_list_markup .= '              <ul class="loft-urgency-banner__list">';

    if ( $nd_booking_check_in_display || $nd_booking_check_out_display ) {
        $nd_booking_urgency_list_markup .= '                  <li><span class="label">' . esc_html__( 'Séjour', 'nd-booking' ) . '</span><span class="value">';

        if ( $nd_booking_check_in_display && $nd_booking_check_out_display ) {
            $nd_booking_urgency_list_markup .= esc_html( $nd_booking_check_in_display ) . ' → ' . esc_html( $nd_booking_check_out_display );
        } elseif ( $nd_booking_check_in_display ) {
            $nd_booking_urgency_list_markup .= esc_html( $nd_booking_check_in_display );
        } else {
            $nd_booking_urgency_list_markup .= esc_html( $nd_booking_check_out_display );
        }

        $nd_booking_urgency_list_markup .= '</span></li>';
    }

    if ( $nd_booking_night_label ) {
        $nd_booking_urgency_list_markup .= '                  <li><span class="label">' . esc_html__( 'Nuits', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_night_label ) . '</span></li>';
    }

    if ( $nd_booking_guest_label ) {
        $nd_booking_urgency_list_markup .= '                  <li><span class="label">' . esc_html__( 'Invités', 'nd-booking' ) . '</span><span class="value">' . esc_html( $nd_booking_guest_label ) . '</span></li>';
    }

    $nd_booking_urgency_list_markup .= '              </ul>';
}

$nd_booking_shortcode_right_content  = '';

if ( ! defined( 'ND_BOOKING_LOFT_BOOKING_FORM_STYLES' ) ) {
    define( 'ND_BOOKING_LOFT_BOOKING_FORM_STYLES', true );
    $nd_booking_shortcode_right_content .= '<style>
      .loft-booking-form {
        background: #FFFFFF;
        border-radius: 28px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
        padding: 36px 40px;
        display: flex;
        flex-direction: column;
        gap: 28px;
      }

      .loft-progress-indicator {
        font-size: 13px;
        letter-spacing: 0.32px;
        font-weight: 600;
        text-transform: uppercase;
        color: #9CA3AF;
      }

      .loft-booking-form__title {
        font-size: clamp(24px, 3vw, 34px);
        font-weight: 700;
        color: #111827;
        margin: 0;
      }

      .loft-booking-form__subtitle {
        font-size: 17px;
        color: #4B5563;
        margin: -12px 0 0 0;
        max-width: 38ch;
      }

      .loft-booking-form__form {
        display: flex;
        flex-direction: column;
        gap: 32px;
      }

      .loft-form-section {
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding: 24px 28px;
        background: #F9FAFB;
        border-radius: 20px;
        border: 1px solid rgba(209, 213, 219, 0.6);
      }

      .loft-form-section h2 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 18px;
        color: #111827;
        margin: 0;
      }

      .loft-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px 24px;
      }

      .loft-form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }

      .loft-form-field label {
        font-weight: 600;
        color: #111827;
        font-size: 14px;
      }

      .loft-input,
      .loft-checkbox__label,
      .loft-button {
        font-family: inherit;
      }

      .loft-input {
        width: 100%;
        border-radius: 14px;
        border: 1px solid rgba(156, 163, 175, 0.6);
        background: #FFFFFF;
        padding: 12px 16px;
        font-size: 15px;
        color: #111827;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
      }

      .loft-input:focus {
        outline: none;
        border-color: #f4b942;
        box-shadow: 0 0 0 4px rgba(244, 185, 66, 0.22);
      }

      .loft-form-field--file input[type="file"] {
        padding: 10px 14px;
      }

      .loft-form-field textarea.loft-input {
        min-height: 140px;
        resize: vertical;
      }

      .loft-help-text {
        font-size: 14px;
        color: #6B7280;
        margin: 0;
      }

      .loft-checkbox {
        display: inline-flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
      }

      .loft-checkbox__input {
        width: 20px;
        height: 20px;
        margin-top: 2px;
      }

      .loft-checkbox__label {
        font-size: 14px;
        color: #111827;
        line-height: 1.5;
      }

      .loft-form-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
      }

      .loft-button {
        border: 0;
        cursor: pointer;
        border-radius: 999px;
        padding: 14px 32px;
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        background: linear-gradient(135deg, #facc15 0%, #f4b942 100%);
        box-shadow: 0 16px 32px rgba(244, 185, 66, 0.38);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .loft-button:hover,
      .loft-button:focus {
        transform: translateY(-1px);
        box-shadow: 0 20px 36px rgba(244, 185, 66, 0.45);
        outline: none;
      }

      .loft-button--hidden {
        display: none;
      }

      .loft-urgency-banner {
        position: relative;
        display: flex;
        gap: 18px;
        padding: 22px 26px;
        border-radius: 22px;
        background: linear-gradient(135deg, #FFF1E6 0%, #FFE0D0 100%);
        border: 1px solid rgba(255, 122, 69, 0.4);
        box-shadow: 0 18px 48px rgba(255, 122, 69, 0.25);
        overflow: hidden;
        isolation: isolate;
      }

      .loft-urgency-banner::before {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.55), transparent 55%);
        opacity: 0;
        animation: loftAlertGlow 2.6s ease-in-out infinite;
        pointer-events: none;
      }

      .loft-urgency-banner__signal {
        position: relative;
        flex: 0 0 48px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .loft-urgency-banner__dot {
        width: 16px;
        height: 16px;
        background: #FF5A26;
        border-radius: 999px;
        box-shadow: 0 0 18px rgba(255, 90, 38, 0.55);
        animation: loftAlertBlink 1.8s ease-in-out infinite;
      }

      .loft-urgency-banner__ring {
        position: absolute;
        width: 38px;
        height: 38px;
        border: 2px solid rgba(255, 90, 38, 0.35);
        border-radius: 999px;
        animation: loftAlertPulse 1.8s ease-out infinite;
      }

      .loft-urgency-banner__content {
        display: flex;
        flex-direction: column;
        gap: 12px;
        color: #8B4513;
      }

      .loft-urgency-banner__headline {
        font-size: 18px;
        font-weight: 700;
        color: #8B1D00;
        margin: 0;
      }

      .loft-urgency-banner__hold {
        font-size: 15px;
        font-weight: 500;
        color: #8B4513;
        margin: 0;
      }

      .loft-urgency-banner__list {
        list-style: none;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 8px 16px;
        padding: 0;
        margin: 0;
      }

      .loft-urgency-banner__list li {
        display: flex;
        gap: 8px;
        align-items: center;
        font-size: 14px;
        color: #7C2D12;
      }

      .loft-urgency-banner__list .label {
        font-weight: 600;
        color: #8B1D00;
      }

      .loft-urgency-banner__timer {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 4px;
      }

      .loft-urgency-banner__timer-bar {
        position: relative;
        height: 6px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.6);
        overflow: hidden;
      }

      .loft-urgency-banner__timer-bar::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, #FF5A26 0%, #FF8554 100%);
        transform-origin: left center;
        animation: loftAlertTimer 12s ease-out infinite;
      }

      .loft-urgency-banner__timer-label {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 600;
        color: #8B1D00;
      }

      .loft-urgency-banner__footnote {
        font-size: 13px;
        color: #8B4513;
        margin: 0;
      }

      @keyframes loftAlertGlow {
        0%, 100% {
          opacity: 0.15;
        }
        50% {
          opacity: 0.35;
        }
      }

      @keyframes loftAlertPulse {
        0% {
          transform: scale(0.8);
          opacity: 0.65;
        }
        70% {
          transform: scale(1.35);
          opacity: 0;
        }
        100% {
          transform: scale(1.35);
          opacity: 0;
        }
      }

      @keyframes loftAlertBlink {
        0%, 100% {
          transform: scale(1);
          opacity: 1;
        }
        50% {
          transform: scale(0.85);
          opacity: 0.65;
        }
      }

      @keyframes loftAlertTimer {
        0% {
          transform: scaleX(1);
        }
        85% {
          transform: scaleX(0.12);
        }
        100% {
          transform: scaleX(0.12);
        }
      }

      @media (max-width: 768px) {
        .loft-booking-form {
          padding: 28px 20px;
          border-radius: 22px;
        }

        .loft-form-section {
          padding: 20px;
        }

        .loft-urgency-banner {
          flex-direction: column;
          align-items: flex-start;
        }

        .loft-urgency-banner__signal {
          flex: 0 0 auto;
        }
      }

      @media (max-width: 480px) {
        .loft-form-grid {
          grid-template-columns: 1fr;
        }

        .loft-booking-form__title {
          font-size: 26px;
        }
      }
    </style>';
}
$nd_booking_shortcode_right_content .= '<div class="loft-booking-form">';
$nd_booking_shortcode_right_content .= '  <div class="loft-progress-indicator">' . esc_html__( 'Étape 1 sur 3', 'nd-booking' ) . '</div>';
$nd_booking_shortcode_right_content .= '  <h1 class="loft-booking-form__title">' . esc_html__( 'Confirmez votre réservation', 'nd-booking' ) . '</h1>';
$nd_booking_shortcode_right_content .= '  <p class="loft-booking-form__subtitle">' . esc_html__( 'Indiquez-nous qui séjournera afin que nous finalisions votre réservation.', 'nd-booking' ) . '</p>';

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

$nd_booking_shortcode_right_content .= '      <div class="loft-urgency-banner" role="status" aria-live="polite">';
$nd_booking_shortcode_right_content .= '          <div class="loft-urgency-banner__signal" aria-hidden="true">';
$nd_booking_shortcode_right_content .= '              <span class="loft-urgency-banner__dot"></span>';
$nd_booking_shortcode_right_content .= '              <span class="loft-urgency-banner__ring"></span>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '          <div class="loft-urgency-banner__content">';
$nd_booking_shortcode_right_content .= '              <p class="loft-urgency-banner__headline">' . $nd_booking_urgency_headline . '</p>';
$nd_booking_shortcode_right_content .= '              <p class="loft-urgency-banner__hold">' . $nd_booking_rate_hold_text . '</p>';
if ( '' !== $nd_booking_urgency_list_markup ) {
    $nd_booking_shortcode_right_content .= $nd_booking_urgency_list_markup;
}
$nd_booking_shortcode_right_content .= '              <div class="loft-urgency-banner__timer">';
$nd_booking_shortcode_right_content .= '                  <span class="loft-urgency-banner__timer-bar" aria-hidden="true"></span>';
        $nd_booking_shortcode_right_content .= '                  <span class="loft-urgency-banner__timer-label">' . esc_html( sprintf( __( 'Terminez en %d minutes pour conserver ce tarif', 'nd-booking' ), $nd_booking_hold_minutes ) ) . '</span>';
$nd_booking_shortcode_right_content .= '              </div>';
            $nd_booking_shortcode_right_content .= '              <p class="loft-urgency-banner__footnote">' . esc_html__( 'Des suites comme celle-ci se réservent rapidement aujourd’hui — terminez cette étape pour la garantir.', 'nd-booking' ) . '</p>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </div>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-contact">';
        $nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🧍</span> ' . esc_html__( 'Renseignements sur les invités', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-grid">';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_name_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_name">' . esc_html__( 'Prénom', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_name" name="nd_booking_booking_form_name" type="text" autocomplete="given-name">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_surname_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_surname">' . esc_html__( 'Nom de famille', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_surname" name="nd_booking_booking_form_surname" type="text" autocomplete="family-name">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_email_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_email">' . esc_html__( 'Adresse courriel', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_email" name="nd_booking_booking_form_email" type="email" autocomplete="email">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_phone_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_phone">' . esc_html__( 'Téléphone mobile', 'nd-booking' ) . ' *</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_phone" name="nd_booking_booking_form_phone" type="tel" autocomplete="tel">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-address">';
        $nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🏠</span> ' . esc_html__( 'Adresse de facturation', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-grid">';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_address_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_address">' . esc_html__( 'Adresse', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_address" name="nd_booking_booking_form_address" type="text" autocomplete="address-line1">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_city_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_city">' . esc_html__( 'Ville', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_city" name="nd_booking_booking_form_city" type="text" autocomplete="address-level2">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_country_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_country">' . esc_html__( 'Pays', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_country" name="nd_booking_booking_form_country" type="text" autocomplete="country-name">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field" id="nd_booking_booking_form_zip_container">';
        $nd_booking_shortcode_right_content .= '                  <label for="nd_booking_booking_form_zip">' . esc_html__( 'Code postal', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="nd_booking_booking_form_zip" name="nd_booking_booking_form_zip" type="text" autocomplete="postal-code">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-identification">';
        $nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🪪</span> ' . esc_html__( 'Pièce d’identité', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-grid">';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field">';
        $nd_booking_shortcode_right_content .= '                  <label for="guest_id_number">' . esc_html__( 'Numéro d’identification', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" id="guest_id_number" name="guest_id_number" type="text">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field">';
        $nd_booking_shortcode_right_content .= '                  <label for="guest_id_type">' . esc_html__( 'Type de pièce d’identité', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <select class="loft-input" id="guest_id_type" name="guest_id_type">';
        $nd_booking_shortcode_right_content .= '                      <option value="driver_license">' . esc_html__( 'Permis de conduire', 'nd-booking' ) . '</option>';
        $nd_booking_shortcode_right_content .= '                      <option value="passport">' . esc_html__( 'Passeport', 'nd-booking' ) . '</option>';
$nd_booking_shortcode_right_content .= '                  </select>';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field loft-form-field--file">';
        $nd_booking_shortcode_right_content .= '                  <label for="guest_id_front">' . esc_html__( 'Pièce d’identité (recto)', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" type="file" id="guest_id_front" name="guest_id_front" accept="image/*">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '              <div class="loft-form-field loft-form-field--file">';
        $nd_booking_shortcode_right_content .= '                  <label for="guest_id_back">' . esc_html__( 'Pièce d’identité (verso)', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '                  <input class="loft-input" type="file" id="guest_id_back" name="guest_id_back" accept="image/*">';
$nd_booking_shortcode_right_content .= '              </div>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-requests">';
        $nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">💬</span> ' . esc_html__( 'Demandes particulières', 'nd-booking' ) . '</h2>';
$nd_booking_shortcode_right_content .= '          <div class="loft-form-field" id="nd_booking_booking_form_requests_container">';
        $nd_booking_shortcode_right_content .= '              <label for="nd_booking_booking_form_requests">' . esc_html__( 'Faites-nous part de vos préférences ou de vos remarques pour l’arrivée.', 'nd-booking' ) . '</label>';
$nd_booking_shortcode_right_content .= '              <textarea class="loft-input" id="nd_booking_booking_form_requests" name="nd_booking_booking_form_requests" rows="5"></textarea>';
$nd_booking_shortcode_right_content .= '          </div>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-arrival">';
        $nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🕓</span> ' . esc_html__( 'Détails d’arrivée', 'nd-booking' ) . '</h2>';
        $nd_booking_shortcode_right_content .= '          <p class="loft-help-text">' . esc_html__( 'L’enregistrement débute à 16 h et le départ est à 12 h.', 'nd-booking' ) . '</p>';
        $nd_booking_shortcode_right_content .= '          <input type="hidden" class="loft-input" name="nd_booking_booking_form_arrival" id="nd_booking_booking_form_arrival" value="16 h - 17 h">';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_coupon_class = nd_booking_get_coupon_enable_class();
if ( '' === $nd_booking_coupon_class ) {
    $nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-coupon" id="nd_booking_booking_form_coupon_container">';
    $nd_booking_shortcode_right_content .= '          <h2><span aria-hidden="true">🏷️</span> ' . esc_html__( 'Code promotionnel', 'nd-booking' ) . '</h2>';
    $nd_booking_shortcode_right_content .= '          <div class="loft-form-field">';
    $nd_booking_shortcode_right_content .= '              <label for="nd_booking_booking_form_coupon">' . esc_html__( 'Entrez votre coupon', 'nd-booking' ) . '</label>';
    $nd_booking_shortcode_right_content .= '              <input class="loft-input" id="nd_booking_booking_form_coupon" name="nd_booking_booking_form_coupon" type="text">';
    $nd_booking_shortcode_right_content .= '          </div>';
    $nd_booking_shortcode_right_content .= '      </section>';
}

$nd_booking_shortcode_right_content .= '      <section class="loft-form-section loft-section-terms" id="nd_booking_booking_form_term_container">';
$nd_booking_shortcode_right_content .= '          <label class="loft-checkbox">';
$nd_booking_shortcode_right_content .= '              <input class="loft-checkbox__input" id="nd_booking_booking_form_term" name="nd_booking_booking_form_term" type="checkbox" value="1" checked>';
    $nd_booking_shortcode_right_content .= '              <span class="loft-checkbox__label">' . sprintf( esc_html__( 'J’accepte les %s', 'nd-booking' ), '<a target="_blank" href="' . esc_url( nd_booking_terms_page() ) . '">' . esc_html__( 'conditions générales', 'nd-booking' ) . '</a>' ) . '</span>';
$nd_booking_shortcode_right_content .= '          </label>';
$nd_booking_shortcode_right_content .= '      </section>';

$nd_booking_shortcode_right_content .= '      <div class="loft-form-actions">';
$nd_booking_shortcode_right_content .= '          <button type="button" class="loft-button" onclick="nd_booking_validate_fields()">' . esc_html__( 'Passer au paiement', 'nd-booking' ) . '</button>';
$nd_booking_shortcode_right_content .= '          <input id="nd_booking_submit_go_to_checkout" class="loft-button loft-button--hidden" type="submit" value="' . esc_attr__( 'Passer au paiement', 'nd-booking' ) . '">';
$nd_booking_shortcode_right_content .= '      </div>';

$nd_booking_shortcode_right_content .= '  </form>';
$nd_booking_shortcode_right_content .= '</div>';

$nd_booking_shortcode_right_content = ob_get_clean();
