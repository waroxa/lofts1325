//START woo function
(function($){
  'use strict';

  window.nd_booking_woo = function(nd_booking_trip_price, nd_booking_rid){

    if (typeof nd_booking_my_vars_woo === 'undefined') {
      console.error('nd_booking_my_vars_woo is not defined.');
      return;
    }

    var requestData = {
      action: 'nd_booking_woo_php',
      nd_booking_trip_price: nd_booking_trip_price,
      nd_booking_rid: nd_booking_rid,
      nd_booking_woo_security: nd_booking_my_vars_woo.nd_booking_ajaxnonce_woo
    };

    var fallbackFormId = 'nd_booking_book_room_' + nd_booking_rid;
    var errorMessage = nd_booking_my_vars_woo.error_message || 'Nous n\'avons pas pu lancer la réservation. Veuillez actualiser la page et réessayer.';

    $.ajax({
      url: nd_booking_my_vars_woo.nd_booking_ajaxurl_woo,
      type: 'POST',
      dataType: 'json',
      data: requestData
    }).done(function(response){
      var targetFormId = fallbackFormId;

      if (response && response.success && response.data && response.data.formId) {
        targetFormId = response.data.formId;
      }

      var targetForm = document.getElementById(targetFormId);

      if (targetForm) {
        targetForm.submit();
        return;
      }

      window.alert(errorMessage);
    }).fail(function(jqXHR){
      console.error('nd_booking_woo AJAX request failed', jqXHR);
      window.alert(errorMessage);
    });
  };
})(jQuery);
//END woo function
