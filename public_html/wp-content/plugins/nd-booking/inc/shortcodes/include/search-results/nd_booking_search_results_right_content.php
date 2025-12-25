<?php


$nd_booking_shortcode_right_content = '';

if ( ! defined( 'ND_BOOKING_LOFT_SEARCH_RESULTS_STYLES' ) ) {
  define( 'ND_BOOKING_LOFT_SEARCH_RESULTS_STYLES', true );

  $nd_booking_shortcode_right_content .= '
  <style>
    [id^="nd_booking_search_cpt_"][id$="_content"] {
      position: relative;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_masonry_content {
      display: grid;
      gap: 32px;
      grid-template-columns: 1fr;
      align-items: stretch;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_masonry_item {
      width: 100% !important;
      margin: 0;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__outer {
      width: 100%;
      margin: 0;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_bg_yellow,
    [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_bg_color_3 {
      background: #76b1c4 !important;
      color: #ffffff !important;
      border-color: #76b1c4 !important;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_search_results_stage {
      position: relative;
      min-height: 320px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader {
      position: fixed;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.86);
      z-index: 9999;
      transition: opacity 0.4s ease, visibility 0.4s ease;
      min-height: 100vh;
      min-height: 100dvh;
      width: 100vw;
      padding: 24px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader .nd_booking_search_results_loader_inner {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 16px;
      padding: 40px 48px;
      border-radius: 28px;
      background: rgba(255, 255, 255, 0.92);
      box-shadow: 0 28px 60px rgba(15, 23, 42, 0.18);
      border: 1px solid rgba(209, 213, 219, 0.6);
      text-align: center;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader p {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      letter-spacing: 0.4px;
      color: #1F2937;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader .nd_booking_loader_spinner {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      border: 4px solid rgba(118, 177, 196, 0.22);
      border-top-color: #76b1c4;
      animation: ndBookingLoaderSpin 1s linear infinite;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader.nd_booking_search_results_loader--hidden {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }

    @keyframes ndBookingLoaderSpin {
      to { transform: rotate(360deg); }
    }

    @media (max-width: 600px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader {
        padding: 16px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader .nd_booking_search_results_loader_inner {
        width: 100%;
        padding: 32px 24px;
      }
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card {
      background: #FFFFFF;
      border-radius: 14px;
      border: 1px solid rgba(16, 24, 40, 0.08);
      box-shadow: 0 16px 36px rgba(16, 24, 40, 0.12);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__media {
      position: relative;
      overflow: hidden;
      flex: 1 1 auto;
      min-height: 200px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__media-img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card:hover .loft-search-card__media-img {
      transform: scale(1.03);
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__media-overlay {
      position: absolute;
      inset: 18px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      pointer-events: none;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__badge {
      align-self: flex-start;
      background: rgba(15, 23, 42, 0.78);
      border-radius: 999px;
      color: #FFFFFF;
      font-family: inherit;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.4px;
      padding: 6px 14px;
      text-transform: uppercase;
    }

    /* --- BEST VALUE RIBBON --- */
    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__ribbon {
      position: absolute;
      top: 30px;
      left: -50px;
      background: linear-gradient(135deg, #76b1c4 0%, #5fa0b6 100%);
      color: #ffffff;
      font-weight: 800;
      font-size: 11px;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      padding: 10px 66px;
      transform: rotate(-45deg);
      box-shadow: 0 6px 14px rgba(118, 177, 196, 0.45);
      z-index: 15;
      text-align: center;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__ribbon-text {
      display: inline-block;
      position: relative;
      top: 2px;
    }

    /* --- Card highlight stays --- */
    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card.has-best-value {
      border: 2px solid #76b1c4 !important;
      box-shadow: 0 0 28px rgba(118, 177, 196, 0.32);
      transition: all 0.3s ease-in-out;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card.has-best-value:hover {
      box-shadow: 0 0 32px rgba(118, 177, 196, 0.4);
    }

    .nd-booking-sort-bar {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
      font-size: 14px;
    }

    .nd-booking-sort-bar label {
      font-weight: 600;
      color: #1F2937;
    }

    .nd-booking-sort-bar select {
      border-radius: 999px;
      border: 1px solid rgba(15, 23, 42, 0.12);
      padding: 8px 28px 8px 16px;
      background: #FFFFFF;
      font-size: 14px;
      font-weight: 500;
      color: #111827;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__stars {
      display: flex;
      gap: 4px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__content {
      display: flex;
      flex-direction: column;
      padding: 24px 22px;
      gap: 20px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__body {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__details {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__title-link {
      color: #111827;
      text-decoration: none;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__title {
      font-size: clamp(22px, 2.5vw, 28px);
      letter-spacing: -0.02em;
      margin: 0;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__meta {
      border-top: 1px solid rgba(148, 163, 184, 0.28);
      border-bottom: 1px solid rgba(148, 163, 184, 0.28);
      padding: 14px 0;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__feature-list {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px 18px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__feature-icon {
      margin-right: 8px;
      flex: 0 0 auto;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__feature-text {
      color: #475467;
      font-size: 14px;
      letter-spacing: 0.2px;
      text-transform: uppercase;
      flex: 1 1 auto;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__excerpt {
      color: #1F2937;
      font-size: 15px;
      line-height: 1.6;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__amenities {
      align-items: center;
      display: flex;
      flex-wrap: wrap;
      gap: 14px 18px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__amenities-icons {
      display: flex;
      gap: 12px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__amenity {
      display: inline-flex;
      width: 36px;
      height: 36px;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: rgba(118, 177, 196, 0.08);
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__amenity-icon {
      display: block;
      max-width: 20px;
      max-height: 20px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__details-link {
      align-items: center;
      color: #111827;
      display: inline-flex;
      font-weight: 600;
      gap: 6px;
      letter-spacing: 0.6px;
      text-decoration: none;
      text-transform: uppercase;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__details-link:hover {
      color: #0F172A;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__sidebar {
      align-items: flex-start;
      border-top: 1px solid rgba(148, 163, 184, 0.28);
      display: flex;
      flex-direction: column;
      gap: 16px;
      padding-top: 20px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__rate {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__rate-label {
      color: #475467;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.4px;
      margin: 0;
      text-transform: uppercase;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__rate-amount {
      color: #0F172A;
      font-size: clamp(24px, 4vw, 30px);
      font-weight: 700;
      margin: 0;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__rate-sub {
      color: #475467;
      font-size: 13px;
      margin: 0;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__actions {
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 100%;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__form {
      width: 100%;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__btn {
      appearance: none;
      background: #76b1c4;
      border: none;
      border-radius: 999px;
      box-shadow: 0 14px 26px rgba(118, 177, 196, 0.30);
      color: #0b2f3d !important;
      cursor: pointer;
      display: inline-flex;
      font-size: clamp(13px, 1.8vw, 15px);
      font-weight: 700;
      justify-content: center;
      letter-spacing: 0.6px;
      padding: 14px 22px;
      text-transform: uppercase;
      transition: all 0.2s ease-in-out;
      width: 100%;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__btn:hover,
    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__btn:focus {
      background: #5fa0b6;
      box-shadow: 0 18px 32px rgba(118, 177, 196, 0.36);
      color: #0b2f3d !important;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__btn.nd_booking_display_none_important {
      display: none !important;
    }

    [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__unavailable {
      background: rgba(15, 23, 42, 0.06);
      border-radius: 12px;
      color: #0F172A;
      font-weight: 600;
      letter-spacing: 0.4px;
      margin: 0;
      padding: 14px 18px;
      text-transform: uppercase;
    }

    @media (min-width: 768px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card {
        flex-direction: row;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__media {
        flex: 0 0 48%;
        min-height: 100%;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__content {
        flex: 1 1 52%;
        padding: 30px 34px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__body {
        gap: 26px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__sidebar {
        border-top: none;
        border-left: 1px solid rgba(148, 163, 184, 0.28);
        padding: 0 0 0 28px;
        align-self: stretch;
        justify-content: center;
        min-width: 240px;
      }
    }

    @media (min-width: 1024px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_masonry_content {
        grid-template-columns: minmax(0, 1fr);
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__content {
        padding: 34px 38px;
      }
    }

    @media (max-width: 1024px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_masonry_content {
        gap: 24px;
      }
    }

    @media (max-width: 767px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_masonry_content {
        gap: 20px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] #nd_booking_search_results_loader .nd_booking_search_results_loader_inner {
        padding: 28px 32px;
        border-radius: 22px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__media {
        min-height: 190px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__title {
        font-size: clamp(20px, 6vw, 24px);
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__content {
        padding: 22px 20px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__meta {
        padding: 12px 0;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__feature-text {
        font-size: 13px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__amenities {
        gap: 12px 16px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__sidebar {
        padding-top: 18px;
      }
    }

    @media (max-width: 599px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__media {
        min-height: 180px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__content {
        gap: 20px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__body {
        gap: 20px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__feature-list {
        gap: 10px 16px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__feature-icon {
        width: 20px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__rate-amount {
        font-size: clamp(22px, 7vw, 28px);
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__amenities {
        flex-direction: column;
        align-items: flex-start;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__amenities-icons {
        flex-wrap: wrap;
        row-gap: 10px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__sidebar {
        align-items: stretch;
      }
    }

    @media (max-width: 479px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__content {
        padding: 20px 18px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__meta {
        padding: 12px 0;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__feature-text {
        flex-basis: 100%;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__btn {
        padding: 14px 22px;
      }

      [id^="nd_booking_search_cpt_"][id$="_content"] .loft-search-card__rate {
        gap: 2px;
      }
    }

    @media (min-width: 1440px) {
      [id^="nd_booking_search_cpt_"][id$="_content"] .nd_booking_search_results_stage {
        max-width: 1240px;
        margin: 0 auto;
      }
    }
  </style>';
}

//START RIGHT CONTENT
$nd_booking_shortcode_right_content .= '

  <div id="nd_booking_archive_search_masonry_container" class="nd_booking_section nd_booking_position_relative">
    
    <div id="nd_booking_content_result" class="nd_booking_section">

        <!--<h3>'.__('Results Founded : ','nd-booking').''.$nd_booking_qnt_results_posts.'</h3>-->';

        $nd_booking_sort_by = 'best';
        if ( isset( $_GET['sort_by'] ) ) {
          $nd_booking_sort_by = sanitize_key( wp_unslash( $_GET['sort_by'] ) );
        }

        if ( ! in_array( $nd_booking_sort_by, array( 'best', 'low', 'high' ), true ) ) {
          $nd_booking_sort_by = 'best';
        }

        $posts_with_price             = array();
        $lowest_price                 = null;
        $nd_booking_best_value_post_id = null;

        if ( $nd_booking_qnt_results_posts > 0 ) {
          while ( $the_query->have_posts() ) {
            $the_query->the_post();

            $nd_booking_post_id    = get_the_ID();
            $nd_booking_post_price = 0.0;

            if ( isset( $nd_booking_pricing_cache ) && isset( $nd_booking_pricing_cache[ $nd_booking_post_id ] ) ) {
              $nd_booking_cached_pricing = $nd_booking_pricing_cache[ $nd_booking_post_id ];

              if ( ! empty( $nd_booking_cached_pricing['has_cta'] ) && null !== $nd_booking_cached_pricing['trip_price'] ) {
                $nd_booking_post_price = (float) $nd_booking_cached_pricing['trip_price'];
              }
            }

            if ( $nd_booking_post_price <= 0 ) {
              $nd_booking_post_price = (float) get_post_meta( $nd_booking_post_id, 'nd_booking_meta_price_total', true );
            }

            $posts_with_price[] = array(
              'post'  => get_post(),
              'price' => $nd_booking_post_price,
            );
          }

          wp_reset_postdata();

          if ( ! empty( $posts_with_price ) ) {
            usort(
              $posts_with_price,
              static function ( $a, $b ) {
                return $a['price'] <=> $b['price'];
              }
            );

            $lowest_price = $posts_with_price[0]['price'];

            if ( 'high' === $nd_booking_sort_by ) {
              usort(
                $posts_with_price,
                static function ( $a, $b ) {
                  return $b['price'] <=> $a['price'];
                }
              );
            }
          }
        }

        $nd_booking_sort_bar_markup = '';
        if ( ! empty( $posts_with_price ) ) {
          $nd_booking_sort_bar_markup .= '<div class="nd-booking-sort-bar">';
          $nd_booking_sort_bar_markup .= '<form method="get">';

          foreach ( $_GET as $nd_booking_query_key => $nd_booking_query_value ) {
            if ( 'sort_by' === $nd_booking_query_key ) {
              continue;
            }

            if ( is_scalar( $nd_booking_query_value ) ) {
              $nd_booking_sort_bar_markup .= '<input type="hidden" name="' . esc_attr( $nd_booking_query_key ) . '" value="' . esc_attr( wp_unslash( $nd_booking_query_value ) ) . '">';
            }
          }

          $nd_booking_sort_bar_markup .= '<label for="nd_booking_sort_by">' . esc_html__( 'Trier par :', 'nd-booking' ) . '</label>';
          $nd_booking_sort_bar_markup .= '<select id="nd_booking_sort_by" name="sort_by" onchange="this.form.submit()">';

          $nd_booking_sort_options = array(
            'best' => __( 'Meilleur rapport qualité-prix', 'nd-booking' ),
            'low'  => __( 'Prix : du plus bas au plus élevé', 'nd-booking' ),
            'high' => __( 'Prix : du plus élevé au plus bas', 'nd-booking' ),
          );

          foreach ( $nd_booking_sort_options as $nd_booking_sort_value => $nd_booking_sort_label ) {
            $nd_booking_selected_attr = selected( $nd_booking_sort_by, $nd_booking_sort_value, false );
            $nd_booking_sort_bar_markup .= '<option value="' . esc_attr( $nd_booking_sort_value ) . '" ' . $nd_booking_selected_attr . '>' . esc_html( $nd_booking_sort_label ) . '</option>';
          }

          $nd_booking_sort_bar_markup .= '</select>';
          $nd_booking_sort_bar_markup .= '</form>';
          $nd_booking_sort_bar_markup .= '</div>';

          if ( 'low' === $nd_booking_sort_by || 'best' === $nd_booking_sort_by ) {
            usort(
              $posts_with_price,
              static function ( $a, $b ) {
                return $a['price'] <=> $b['price'];
              }
            );
          }

          if ( null !== $nd_booking_best_value_price ) {
            foreach ( $posts_with_price as $nd_booking_post_with_price ) {
              if ( abs( (float) $nd_booking_post_with_price['price'] - (float) $nd_booking_best_value_price ) < 0.01 ) {
                $nd_booking_best_value_post_id = $nd_booking_post_with_price['post']->ID;
                break;
              }
            }
          }

          if ( null === $nd_booking_best_value_post_id && ! empty( $posts_with_price ) ) {
            $nd_booking_best_value_post_id = $posts_with_price[0]['post']->ID;
          }
        }

        $nd_booking_shortcode_right_content .= '
        <div class="nd_booking_search_results_stage">

          <div id="nd_booking_search_results_loader" class="nd_booking_search_results_loader">
            <div class="nd_booking_search_results_loader_inner">
              <div class="nd_booking_loader_spinner"></div>
              <p>'.__('Vérification des disponibilités pour vos dates…','nd-booking').'</p>
            </div>
          </div>

          '.$nd_booking_sort_bar_markup.'

          <div class="nd_booking_section nd_booking_masonry_content">';

        if ( $nd_booking_qnt_results_posts == 0 ) {

          $nd_booking_shortcode_right_content .= '

            <div class="nd_booking_section nd_booking_padding_15 nd_booking_box_sizing_border_box">
              <div class="nd_booking_section nd_booking_bg_yellow nd_booking_padding_15_20 nd_booking_box_sizing_border_box">
                <img class="nd_booking_float_left nd_booking_display_none_all_iphone" width="20" src="'.esc_url(plugins_url('icon-warning-white.svg', __FILE__ )).'">
                <h3 class="nd_booking_float_left nd_options_color_white nd_booking_color_white nd_options_first_font nd_booking_margin_left_10">'.__('Aucun résultat pour cette recherche','nd-booking').'</h3>
              </div>
            </div>

          ';

        }

          //START loop
          if ( ! empty( $posts_with_price ) ) {
            global $post;

            foreach ( $posts_with_price as $nd_booking_post_with_price ) {
              $post  = $nd_booking_post_with_price['post'];
              $price = isset( $nd_booking_post_with_price['price'] ) ? (float) $nd_booking_post_with_price['price'] : 0.0;
              $nd_booking_is_best_value_card = ( null !== $nd_booking_best_value_post_id && (int) $nd_booking_best_value_post_id === (int) $post->ID );

              setup_postdata( $post );

              include realpath(dirname( __FILE__ ).'/nd_booking_post_preview-1.php');
            }

            wp_reset_postdata();
          }
          //END loop

        $nd_booking_shortcode_right_content .= '
          </div>
        </div>';


      include realpath(dirname( __FILE__ ).'/nd_booking_search_results_pagination.php');

    $nd_booking_shortcode_right_content .= '
    </div>
  </div>
';
//END RIGHT CONTENT