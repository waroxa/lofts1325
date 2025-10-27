(function ($) {
  'use strict';

  var settings = window.ndBookingCheckoutEnhancements || {};
  var CTA_LABEL = settings.ctaLabel || 'Confirmer ma réservation de luxe';

  function normalizeField($field) {
    if (!$field.length || $field.hasClass('loft-checkout-form__field')) {
      return;
    }

    $field.addClass('loft-checkout-form__field');

    if ($field.is('.form-row-first, .form-row-last, .nd_booking_form_half')) {
      $field.addClass('loft-checkout-form__field--split');
    }

    if ($field.find('input[type="file"]').length) {
      $field.addClass('loft-checkout-form__field--file');
    }

    if ($field.find('textarea').length) {
      $field.addClass('loft-checkout-form__field--textarea');
    }
  }

  function updateCta($form) {
    var $submit = $form
      .find('button[type="submit"], input[type="submit"], .button-primary[type="submit"], button.button-primary')
      .first();

    if (!$submit.length) {
      return;
    }

    if ($submit.is('input')) {
      $submit.val(CTA_LABEL);
    } else {
      $submit.text(CTA_LABEL);
    }

    $submit.addClass('loft-checkout-form__submit');
  }

  function enhanceCheckoutForm() {
    var $containers = $('.loft-checkout-wrapper .checkout-form');

    if (!$containers.length) {
      return;
    }

    $containers.each(function () {
      var $wrapper = $(this);
      var $form = $wrapper.find('form').first();

      if (!$form.length) {
        return;
      }

      if (!$form.hasClass('loft-checkout-form')) {
        $form.addClass('loft-checkout-form loft-checkout-form--enhanced');
      }

      $form.find('p, .form-row, .nd_booking_form_row, .woocommerce-billing-fields__field-wrapper > *').each(function () {
        normalizeField($(this));
      });

      updateCta($form);
    });
  }

  function setupMutationObserver() {
    var root = document.querySelector('.loft-checkout-wrapper .checkout-form');

    if (!root || !('MutationObserver' in window)) {
      return;
    }

    var observer = new MutationObserver(function () {
      enhanceCheckoutForm();
    });

    observer.observe(root, { childList: true, subtree: true });
  }

  $(document).ready(function () {
    enhanceCheckoutForm();
    setupMutationObserver();

    $(document).on('updated_checkout nd_booking_checkout_refreshed', function () {
      enhanceCheckoutForm();
    });
  });
})(jQuery);
