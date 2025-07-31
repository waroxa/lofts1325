//START woo function
function nd_booking_woo(nd_booking_trip_price,nd_booking_rid){

  var nd_booking_trip_price = nd_booking_trip_price;
  var nd_booking_rid = nd_booking_rid;

  //START post method
  jQuery.get(
    
  
    //ajax
    nd_booking_my_vars_woo.nd_booking_ajaxurl_woo,
    {
      action : 'nd_booking_woo_php',
      nd_booking_trip_price : nd_booking_trip_price,
      nd_booking_rid : nd_booking_rid,
      nd_booking_woo_security : nd_booking_my_vars_woo.nd_booking_ajaxnonce_woo,
    },
    //end ajax


    //START success
    function( nd_booking_woo_result ) {
      //alert(nd_booking_woo_result);
      document.getElementById(nd_booking_woo_result).submit();
    }
    //END

    

  );
  //END

  
}
//END woo function