jQuery(document).ready(function() {

  jQuery( function ( $ ) {

    $(".nd_booking_guests_increase").click(function() {
      var value = $(".nd_booking_guests_number").text();
      value++;
      $(".nd_booking_guests_number").text(value);
      $("#nd_booking_archive_form_guests").val(value);
    }); 

    $(".nd_booking_guests_decrease").click(function() {
      var value = $(".nd_booking_guests_number").text();
      
      if ( value > 1 ) {
        value--;
        $(".nd_booking_guests_number").text(value);
        $("#nd_booking_archive_form_guests").val(value);
      }
      
    }); 
    
  });

});