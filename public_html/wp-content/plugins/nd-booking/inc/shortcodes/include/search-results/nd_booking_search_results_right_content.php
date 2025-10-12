<?php


$nd_booking_shortcode_right_content = '';

if ( ! defined( 'ND_BOOKING_LOFT_SEARCH_RESULTS_STYLES' ) ) {
  define( 'ND_BOOKING_LOFT_SEARCH_RESULTS_STYLES', true );

  $nd_booking_shortcode_right_content .= '
  <style>
    .loft-search-results-layout {
      background: #F5F7FB;
      padding: 32px 20px 64px;
    }

    #nd_booking_search_cpt_1_content {
      margin: 0 auto;
      max-width: 1180px;
      display: flex;
      flex-direction: column;
      gap: 32px;
    }

    #nd_booking_search_cpt_1_content #nd_booking_archive_search_masonry_container {
      margin-top: 0;
    }

    .loft-search-toolbar {
      background: #FFFFFF;
      border-radius: 22px;
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
      padding: 28px 32px;
      width: 100%;
    }

    .loft-search-toolbar__form {
      width: 100%;
    }

    .loft-search-toolbar__grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 20px;
      align-items: end;
    }

    .loft-search-toolbar__field {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .loft-search-toolbar__field[data-toolbar-field="nights"] .loft-search-toolbar__hint,
    .loft-search-toolbar__field[data-toolbar-field="nights"] .loft-search-toolbar__input {
      display: none;
    }

    .loft-search-toolbar__label {
      color: #475467;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.18em;
      text-transform: uppercase;
    }

    .loft-search-toolbar__input {
      background: #F9FAFB;
      border: 1px solid #D0D5DD;
      border-radius: 16px;
      color: #0F172A;
      font-size: 16px;
      padding: 16px 18px;
      transition: all 0.2s ease;
      width: 100%;
    }

    .loft-search-toolbar__input:focus {
      background: #FFFFFF;
      border-color: #2563EB;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.18);
      outline: none;
    }

    .loft-search-toolbar__hint {
      color: #98A2B3;
      font-size: 12px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .loft-search-toolbar__nights {
      align-items: center;
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(79, 70, 229, 0.12));
      border-radius: 16px;
      color: #1D4ED8;
      display: flex;
      flex-direction: column;
      gap: 6px;
      justify-content: center;
      min-height: 62px;
      padding: 12px 18px;
    }

    .loft-search-toolbar__nights-count {
      font-size: 30px;
      font-weight: 700;
      line-height: 1;
    }

    .loft-search-toolbar__nights-text {
      color: #475467;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.18em;
      text-transform: uppercase;
    }

    .loft-search-toolbar__stepper {
      align-items: center;
      background: #F9FAFB;
      border: 1px solid #D0D5DD;
      border-radius: 16px;
      display: flex;
      gap: 12px;
      justify-content: center;
      padding: 10px;
    }

    .loft-search-toolbar__stepper-btn {
      align-items: center;
      background: #E0EAFF;
      border: none;
      border-radius: 12px;
      color: #1E3A8A;
      cursor: pointer;
      display: inline-flex;
      font-size: 22px;
      font-weight: 700;
      height: 40px;
      justify-content: center;
      transition: background 0.2s ease, transform 0.2s ease;
      width: 40px;
    }

    .loft-search-toolbar__stepper-btn:hover,
    .loft-search-toolbar__stepper-btn:focus {
      background: #C7D6FF;
      outline: none;
      transform: translateY(-1px);
    }

    .loft-search-toolbar__stepper-value {
      color: #101828;
      font-size: 24px;
      font-weight: 700;
      min-width: 32px;
      text-align: center;
    }

    .loft-search-toolbar__field--submit {
      display: flex;
      justify-content: flex-end;
    }

    .loft-search-toolbar__submit {
      align-items: center;
      background: linear-gradient(135deg, #2563EB, #22D3EE);
      border: none;
      border-radius: 999px;
      box-shadow: 0 24px 44px rgba(37, 99, 235, 0.32);
      color: #FFFFFF;
      cursor: pointer;
      display: inline-flex;
      font-size: 14px;
      font-weight: 700;
      gap: 8px;
      justify-content: center;
      letter-spacing: 0.18em;
      padding: 18px 30px;
      text-transform: uppercase;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .loft-search-toolbar__submit:hover,
    .loft-search-toolbar__submit:focus {
      box-shadow: 0 26px 54px rgba(37, 99, 235, 0.4);
      outline: none;
      transform: translateY(-1px);
    }

    #nd_booking_search_cpt_1_content .nd_booking_masonry_item {
      width: 100% !important;
    }

    #nd_booking_search_cpt_1_content .nd_booking_masonry_item {
      width: 100% !important;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__outer {
      margin: 0 auto;
      max-width: 1040px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card {
      background: #FFFFFF;
      border-radius: 18px;
      border: 1px solid rgba(16, 24, 40, 0.08);
      box-shadow: 0 24px 60px rgba(16, 24, 40, 0.16);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      position: relative;
    }

    #nd_booking_search_cpt_1_content .loft-search-card--best {
      border-color: rgba(239, 126, 20, 0.52);
      box-shadow: 0 28px 68px rgba(239, 126, 20, 0.28);
    }

    #nd_booking_search_cpt_1_content .loft-search-card--best::after {
      content: "";
      position: absolute;
      inset: 0;
      border-radius: 18px;
      pointer-events: none;
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.16);
    }

    #nd_booking_search_cpt_1_content .loft-search-card__best-badge {
      position: absolute;
      top: 20px;
      left: 20px;
      z-index: 3;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #ef7e14 0%, #f6b343 100%);
      color: #fff9ed;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.18em;
      padding: 9px 16px;
      border-radius: 999px;
      text-transform: uppercase;
      box-shadow: 0 18px 32px rgba(239, 126, 20, 0.36);
    }

    #nd_booking_search_cpt_1_content .loft-search-card__best-badge-icon {
      font-size: 14px;
      line-height: 1;
      color: #fff1cc;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__media {
      position: relative;
      overflow: hidden;
      flex: 1 1 auto;
      min-height: 240px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__media-img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    #nd_booking_search_cpt_1_content .loft-search-card:hover .loft-search-card__media-img {
      transform: scale(1.03);
    }

    #nd_booking_search_cpt_1_content .loft-search-card__media-overlay {
      position: absolute;
      inset: 18px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      pointer-events: none;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__badge {
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

    #nd_booking_search_cpt_1_content .loft-search-card__stars {
      display: flex;
      gap: 4px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__content {
      display: flex;
      flex-direction: column;
      padding: 28px 24px;
      gap: 24px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__body {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__details {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__title-link {
      color: #111827;
      text-decoration: none;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__title {
      font-size: 28px;
      letter-spacing: -0.02em;
      margin: 0;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__meta {
      border-top: 1px solid rgba(148, 163, 184, 0.28);
      border-bottom: 1px solid rgba(148, 163, 184, 0.28);
      padding: 16px 0;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__feature-list {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__feature-icon {
      margin-right: 8px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__feature-text {
      color: #475467;
      font-size: 14px;
      letter-spacing: 0.2px;
      text-transform: uppercase;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__excerpt {
      color: #1F2937;
      font-size: 15px;
      line-height: 1.6;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__amenities {
      align-items: center;
      display: flex;
      flex-wrap: wrap;
      gap: 14px 18px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__amenities-icons {
      display: flex;
      gap: 12px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__amenity {
      display: inline-flex;
      width: 36px;
      height: 36px;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: rgba(15, 23, 42, 0.04);
    }

    #nd_booking_search_cpt_1_content .loft-search-card__amenity-icon {
      display: block;
      max-width: 20px;
      max-height: 20px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__details-link {
      align-items: center;
      color: #111827;
      display: inline-flex;
      font-weight: 600;
      gap: 6px;
      letter-spacing: 0.6px;
      text-decoration: none;
      text-transform: uppercase;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__details-link:hover {
      color: #0F172A;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__sidebar {
      align-items: flex-start;
      border-top: 1px solid rgba(148, 163, 184, 0.28);
      display: flex;
      flex-direction: column;
      gap: 18px;
      padding-top: 24px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__rate {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__rate-label {
      color: #475467;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.4px;
      margin: 0;
      text-transform: uppercase;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__rate-amount {
      color: #0F172A;
      font-size: 30px;
      font-weight: 700;
      margin: 0;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__rate-sub {
      color: #475467;
      font-size: 13px;
      margin: 0;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__rate-sub--best {
      color: #ef7e14;
      font-weight: 600;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__actions {
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 100%;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__form {
      width: 100%;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__btn {
      appearance: none;
      background: linear-gradient(110deg, #ef7e14, #f6b343);
      border: none;
      border-radius: 999px;
      box-shadow: 0 26px 54px rgba(239, 126, 20, 0.32);
      color: #FFFFFF !important;
      cursor: pointer;
      display: inline-flex;
      font-weight: 700;
      justify-content: center;
      letter-spacing: 0.6px;
      padding: 16px 24px;
      text-transform: uppercase;
      transition: all 0.2s ease-in-out;
      width: 100%;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__btn:hover,
    #nd_booking_search_cpt_1_content .loft-search-card__btn:focus {
      background: linear-gradient(110deg, #f08c2c, #f7c15b);
      box-shadow: 0 30px 62px rgba(239, 126, 20, 0.4);
      color: #FFFFFF !important;
    }

    #nd_booking_search_cpt_1_content .loft-search-card--best .loft-search-card__btn {
      background: linear-gradient(110deg, #ef7e14, #f6b343);
      box-shadow: 0 32px 64px rgba(239, 126, 20, 0.38);
      color: #FFFFFF !important;
    }

    #nd_booking_search_cpt_1_content .loft-search-card--best .loft-search-card__btn:hover,
    #nd_booking_search_cpt_1_content .loft-search-card--best .loft-search-card__btn:focus {
      background: linear-gradient(110deg, #f08c2c, #f7c15b);
      box-shadow: 0 36px 72px rgba(239, 126, 20, 0.45);
      color: #FFFFFF !important;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__btn.nd_booking_display_none_important {
      display: none !important;
    }

    #nd_booking_search_cpt_1_content .loft-search-card__unavailable {
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
      #nd_booking_search_cpt_1_content .loft-search-card {
        flex-direction: row;
      }

      #nd_booking_search_cpt_1_content .loft-search-card__media {
        flex: 0 0 48%;
        min-height: 100%;
      }

      #nd_booking_search_cpt_1_content .loft-search-card__content {
        flex: 1 1 52%;
        padding: 36px 40px;
      }

      #nd_booking_search_cpt_1_content .loft-search-card__body {
        gap: 32px;
      }

      #nd_booking_search_cpt_1_content .loft-search-card__sidebar {
        border-top: none;
        border-left: 1px solid rgba(148, 163, 184, 0.28);
        padding: 0 0 0 32px;
        align-self: stretch;
        justify-content: center;
        min-width: 240px;
      }
    }

    .loft-search-loader {
      align-items: center;
      display: flex;
      flex-direction: column;
      gap: 16px;
      justify-content: center;
      min-height: 280px;
    }

    #nd_booking_sorting_result_loader {
      align-items: center;
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(4px);
      display: flex;
      justify-content: center;
      padding: 32px;
    }

    .loft-search-loader__icon {
      align-items: flex-end;
      background: linear-gradient(135deg, #ef7e14 0%, #f6b343 100%);
      border-radius: 22px;
      display: flex;
      justify-content: center;
      height: 96px;
      padding-bottom: 18px;
      position: relative;
      width: 96px;
      animation: loftLoaderBob 2.1s ease-in-out infinite;
      box-shadow: 0 22px 44px rgba(239, 126, 20, 0.28);
    }

    .loft-search-loader__building {
      background: #FFFFFF;
      border-radius: 16px 16px 10px 10px;
      box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
      display: grid;
      gap: 6px;
      grid-template-columns: repeat(2, 12px);
      justify-content: center;
      padding: 12px 14px 16px;
      position: relative;
    }

    .loft-search-loader__building::before {
      content: "";
      position: absolute;
      top: -18px;
      left: 50%;
      transform: translateX(-50%);
      width: 18px;
      height: 18px;
      background: #FFFFFF;
      border-radius: 6px 6px 0 0;
      box-shadow: 0 10px 18px rgba(15, 23, 42, 0.18);
    }

    .loft-search-loader__window {
      background: #ffe4c4;
      border-radius: 4px;
      height: 12px;
      width: 12px;
      animation: loftWindowGlow 1.6s ease-in-out infinite;
    }

    .loft-search-loader__window:nth-child(2) {
      animation-delay: 0.2s;
    }

    .loft-search-loader__window:nth-child(3) {
      animation-delay: 0.4s;
    }

    .loft-search-loader__window:nth-child(4) {
      animation-delay: 0.6s;
    }

    .loft-search-loader__text {
      color: #334155;
      font-size: 16px;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    @keyframes loftLoaderBob {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    @keyframes loftWindowGlow {
      0%, 100% { background: #ffe4c4; }
      50% { background: #ffd26a; }
    }

    @media (max-width: 1200px) {
      .loft-search-toolbar__grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }

      .loft-search-toolbar__field--submit {
        grid-column: span 3;
        justify-content: flex-start;
      }
    }

    @media (max-width: 900px) {
      .loft-search-toolbar__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .loft-search-toolbar__field--submit {
        grid-column: span 2;
      }
    }

    @media (max-width: 640px) {
      .loft-search-results-layout {
        padding: 24px 16px 48px;
      }

      .loft-search-toolbar {
        padding: 22px;
        border-radius: 18px;
      }

      .loft-search-toolbar__grid {
        grid-template-columns: 1fr;
      }

      .loft-search-toolbar__field--submit {
        justify-content: center;
      }

      .loft-search-toolbar__submit {
        width: 100%;
      }

      #nd_booking_search_cpt_1_content .loft-search-card__media {
        min-height: 220px;
      }
    }

    @media (max-width: 767px) {
      #nd_booking_search_cpt_1_content .loft-search-card__media {
        min-height: 220px;
      }

      #nd_booking_search_cpt_1_content .loft-search-card__title {
        font-size: 24px;
      }
    }
  </style>';
}

//START RIGHT CONTENT
$nd_booking_shortcode_right_content .= '

  <div id="nd_booking_archive_search_masonry_container" class="nd_booking_section nd_booking_position_relative">
    
    <div id="nd_booking_content_result" class="nd_booking_section">

        <!--<h3>'.__('Results Founded : ','nd-booking').''.$nd_booking_qnt_results_posts.'</h3>-->';

        //if NO RESULT
        if ( $nd_booking_qnt_results_posts == 0 ) { 

          $nd_booking_shortcode_right_content .= '

          <div class="nd_booking_section nd_booking_padding_15 nd_booking_box_sizing_border_box">
            <div class="nd_booking_section nd_booking_bg_yellow nd_booking_padding_15_20 nd_booking_box_sizing_border_box">
              <img class="nd_booking_float_left nd_booking_display_none_all_iphone" width="20" src="'.esc_url(plugins_url('icon-warning-white.svg', __FILE__ )).'">
              <h3 class="nd_booking_float_left nd_options_color_white nd_booking_color_white nd_options_first_font nd_booking_margin_left_10">'.__('No results for this search','nd-booking').'</h3>
            </div>
          </div>
          
          '; 

        }
        //END if

        $nd_booking_shortcode_right_content .= '
        <div class="nd_booking_section nd_booking_masonry_content">';

          $loft_result_index = 0;

          //START loop
          while ( $the_query->have_posts() ) : $the_query->the_post();

              $loft_is_best_result = ( $loft_result_index === 0 );

              include realpath(dirname( __FILE__ ).'/nd_booking_post_preview-1.php');

              $loft_result_index++;

          endwhile;
          //END loop

        $nd_booking_shortcode_right_content .= '
        </div>


        <script type="text/javascript">

        jQuery(document).ready(function() {

          jQuery(function ($) {
            
            var $nd_booking_masonry_content = $(".nd_booking_masonry_content").imagesLoaded( function() {
              // init Masonry after all images have loaded
              $nd_booking_masonry_content.masonry({
                itemSelector: ".nd_booking_masonry_item"
              });
            });

            $( ".nd_booking_tooltip_jquery" ).tooltip({ 
              tooltipClass: "nd_booking_tooltip_jquery_content",
              position: {
                my: "center top",
                at: "center-7 top-33",
              }
            });


          });

        });

      </script>';


      include realpath(dirname( __FILE__ ).'/nd_booking_search_results_pagination.php');

    $nd_booking_shortcode_right_content .= '
    </div>
  </div>
';
//END RIGHT CONTENT