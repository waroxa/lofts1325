<?php

$nd_booking_shortcode_left_content = '';

$loft_toolbar_update_label = esc_html__( 'Update search', 'nd-booking' );
$loft_toolbar_arrival_label = esc_html__( 'Arrival', 'nd-booking' );
$loft_toolbar_departure_label = esc_html__( 'Departure', 'nd-booking' );
$loft_toolbar_nights_label = esc_html__( 'Nights', 'nd-booking' );
$loft_toolbar_guests_label = esc_html__( 'Guests', 'nd-booking' );
$loft_toolbar_edit_dates_label = esc_html__( 'Change dates', 'nd-booking' );
$loft_toolbar_edit_guests_label = esc_html__( 'Update guests', 'nd-booking' );
$loft_toolbar_nights_suffix = esc_html__( 'nights', 'nd-booking' );
$loft_toolbar_night_single = esc_html__( 'night', 'nd-booking' );

$nd_booking_date_from_value = esc_attr( $nd_booking_date_from );
$nd_booking_date_to_value   = esc_attr( $nd_booking_date_to );
$nd_booking_guests_value    = esc_attr( $nd_booking_archive_form_guests );
$nd_booking_nights_value    = esc_html( $nd_booking_nights_number );
$nd_booking_branches_value  = esc_attr( $nd_booking_archive_form_branches );
$nd_booking_price_value     = esc_attr( $nd_booking_archive_form_max_price_for_day );

$nd_booking_shortcode_left_content .= '
  <div class="loft-search-toolbar" aria-label="'.esc_attr__( 'Search controls', 'nd-booking' ).'">
    <form id="nd_booking_search_cpt_1_form_sidebar" class="loft-search-toolbar__form" autocomplete="off">
      <div class="loft-search-toolbar__grid">
        <div class="loft-search-toolbar__field" data-toolbar-field="arrival">
          <label class="loft-search-toolbar__label" for="nd_booking_archive_form_date_range_from">'.$loft_toolbar_arrival_label.'</label>
          <input type="text" class="loft-search-toolbar__input loft-search-toolbar__input--date" name="nd_booking_archive_form_date_range_from" id="nd_booking_archive_form_date_range_from" value="'.$nd_booking_date_from_value.'" />
          <span class="loft-search-toolbar__hint">'.$loft_toolbar_edit_dates_label.'</span>
        </div>
        <div class="loft-search-toolbar__field" data-toolbar-field="departure">
          <label class="loft-search-toolbar__label" for="nd_booking_archive_form_date_range_to">'.$loft_toolbar_departure_label.'</label>
          <input type="text" class="loft-search-toolbar__input loft-search-toolbar__input--date" name="nd_booking_archive_form_date_range_to" id="nd_booking_archive_form_date_range_to" value="'.$nd_booking_date_to_value.'" />
          <span class="loft-search-toolbar__hint">'.$loft_toolbar_edit_dates_label.'</span>
        </div>
        <div class="loft-search-toolbar__field" data-toolbar-field="nights">
          <label class="loft-search-toolbar__label">'.$loft_toolbar_nights_label.'</label>
          <div class="loft-search-toolbar__nights" role="status">
            <span class="loft-search-toolbar__nights-count nd_booking_nights_number">'.$nd_booking_nights_value.'</span>
            <span class="loft-search-toolbar__nights-text" data-night-singular="'.$loft_toolbar_night_single.'" data-night-plural="'.$loft_toolbar_nights_suffix.'">'.$loft_toolbar_nights_suffix.'</span>
          </div>
        </div>
        <div class="loft-search-toolbar__field" data-toolbar-field="guests">
          <label class="loft-search-toolbar__label" for="nd_booking_archive_form_guests">'.$loft_toolbar_guests_label.'</label>
          <div class="loft-search-toolbar__stepper" aria-live="polite">
            <button type="button" class="loft-search-toolbar__stepper-btn" data-direction="down" aria-label="'.esc_attr__( 'Decrease guests', 'nd-booking' ).'">&minus;</button>
            <span class="loft-search-toolbar__stepper-value">'.$nd_booking_guests_value.'</span>
            <button type="button" class="loft-search-toolbar__stepper-btn" data-direction="up" aria-label="'.esc_attr__( 'Increase guests', 'nd-booking' ).'">+</button>
          </div>
          <span class="loft-search-toolbar__hint">'.$loft_toolbar_edit_guests_label.'</span>
          <input type="hidden" name="nd_booking_archive_form_guests" id="nd_booking_archive_form_guests" value="'.$nd_booking_guests_value.'" />
        </div>
        <div class="loft-search-toolbar__field loft-search-toolbar__field--submit">
          <button type="submit" class="loft-search-toolbar__submit">'.$loft_toolbar_update_label.'</button>
        </div>
      </div>
      <input type="hidden" name="nd_booking_archive_form_branches" id="nd_booking_archive_form_branches" value="'.$nd_booking_branches_value.'" />
      <input type="hidden" name="nd_booking_archive_form_max_price_for_day" id="nd_booking_archive_form_max_price_for_day" value="'.$nd_booking_price_value.'" />
      <input type="hidden" name="nd_booking_archive_form_services" id="nd_booking_archive_form_services" value="" />
      <input type="hidden" name="nd_booking_archive_form_additional_services" id="nd_booking_archive_form_additional_services" value="" />
      <input type="hidden" name="nd_booking_archive_form_branch_stars" id="nd_booking_archive_form_branch_stars" value="" />
    </form>
  </div>
';

ob_start();
?>
  <script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function($) {
      var $form = $("#nd_booking_search_cpt_1_form_sidebar");
      if (!$form.length) {
        return;
      }

      var namespace = '.loftSearchToolbar';
      var $dateFrom = $("#nd_booking_archive_form_date_range_from");
      var $dateTo = $("#nd_booking_archive_form_date_range_to");
      var $guestsInput = $("#nd_booking_archive_form_guests");
      var $guestsValue = $form.find(".loft-search-toolbar__stepper-value");
      var $nightsValue = $form.find(".nd_booking_nights_number");
      var $nightsText = $form.find(".loft-search-toolbar__nights-text");

      function parseDate(value) {
        var parts = value.split('/');
        if (parts.length !== 3) {
          return null;
        }
        return new Date(parts[2], parts[0] - 1, parts[1]);
      }

      function formatDate(date) {
        if (!(date instanceof Date) || isNaN(date.getTime())) {
          return '';
        }
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
        return month + '/' + day + '/' + date.getFullYear();
      }

      function setMinimumDates() {
        var startDate = $dateFrom.datepicker('getDate');
        if (!startDate) {
          startDate = parseDate($dateFrom.val());
        }
        if (startDate) {
          var minCheckout = new Date(startDate.getTime());
          minCheckout.setDate(minCheckout.getDate() + 1);
          $dateTo.datepicker('option', 'minDate', minCheckout);
          var currentCheckout = $dateTo.datepicker('getDate');
          if (!currentCheckout || currentCheckout <= startDate) {
            $dateTo.datepicker('setDate', minCheckout);
          }
        }
      }

      function updateNights() {
        var startDate = parseDate($dateFrom.val());
        var endDate = parseDate($dateTo.val());
        if (!startDate || !endDate) {
          return;
        }
        var diff = Math.round((endDate - startDate) / 86400000);
        if (diff < 1) {
          diff = 1;
          var adjusted = new Date(startDate.getTime());
          adjusted.setDate(adjusted.getDate() + 1);
          $dateTo.val(formatDate(adjusted));
        }
        $nightsValue.text(diff);
        if ($nightsText.length) {
          var pluralLabel = $nightsText.data('night-plural') || '';
          var singularLabel = $nightsText.data('night-singular') || pluralLabel;
          if (diff === 1 && singularLabel) {
            $nightsText.text(singularLabel);
          } else if (pluralLabel) {
            $nightsText.text(pluralLabel);
          }
        }
      }

      function triggerSearch() {
        if (typeof nd_booking_sorting === 'function') {
          nd_booking_sorting(1);
        }
      }

      if ($.isFunction($dateFrom.datepicker)) {
        $dateFrom.datepicker({
          dateFormat: 'mm/dd/yy',
          minDate: 0,
          onClose: function() {
            setMinimumDates();
            updateNights();
          }
        });
        $dateTo.datepicker({
          dateFormat: 'mm/dd/yy',
          minDate: 1,
          onClose: function() {
            updateNights();
          }
        });
      }

      setMinimumDates();
      updateNights();

      $dateFrom.off('change' + namespace).on('change' + namespace, function() {
        setMinimumDates();
        updateNights();
      });

      $dateTo.off('change' + namespace).on('change' + namespace, function() {
        updateNights();
      });

      $form.off('submit' + namespace).on('submit' + namespace, function(event) {
        event.preventDefault();
        updateNights();
        triggerSearch();
      });

      $form.find('.loft-search-toolbar__stepper-btn').off('click' + namespace).on('click' + namespace, function(event) {
        event.preventDefault();
        var direction = $(this).attr('data-direction');
        var current = parseInt($guestsInput.val(), 10);
        if (isNaN(current) || current < 1) {
          current = 1;
        }
        if (direction === 'up') {
          current += 1;
        } else if (direction === 'down' && current > 1) {
          current -= 1;
        }
        $guestsInput.val(current);
        $guestsValue.text(current);
      });

      $guestsInput.off('change' + namespace).on('change' + namespace, function() {
        var value = parseInt($(this).val(), 10);
        if (isNaN(value) || value < 1) {
          value = 1;
          $(this).val(value);
        }
        $guestsValue.text(value);
      });
    });
    //]]>
  </script>
<?php
$nd_booking_shortcode_left_content .= ob_get_clean();
?>
