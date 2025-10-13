//START function
function nd_booking_final_price(){

  jQuery( "body" ).append( "<div id='nd_booking_sorting_result_layer' class='nd_booking_cursor_progress nd_booking_position_fixed nd_booking_width_100_percentage nd_booking_height_100_percentage nd_booking_z_index_999'></div>" );

  var nd_booking_booking_checkbox_services = jQuery( "#nd_booking_booking_checkbox_services" ).val();
  var nd_booking_booking_form_final_price = jQuery( "#nd_booking_booking_form_trip_price" ).val();

  var nd_booking_request_data = {
    action : 'nd_booking_final_price_php',
    nd_booking_booking_checkbox_services : nd_booking_booking_checkbox_services,
    nd_booking_booking_form_final_price : nd_booking_booking_form_final_price
  };

  if ( typeof nd_booking_my_vars_final_price !== 'undefined' && nd_booking_my_vars_final_price.nd_booking_ajaxnonce_final_price ) {
    nd_booking_request_data.nd_booking_final_price_security = nd_booking_my_vars_final_price.nd_booking_ajaxnonce_final_price;
  }

  jQuery.get(
    nd_booking_my_vars_final_price.nd_booking_ajaxurl_final_price,
    nd_booking_request_data,
    null,
    'json'
  )
  .done(function( response ) {

    if ( response && response.success && response.data ) {

      var data = response.data;
      var currency = data.currency || '';

      jQuery( "#nd_booking_final_trip_price span" ).empty();
      jQuery( "#nd_booking_final_trip_price span" ).text( data.total_formatted );
      jQuery( "#nd_booking_booking_form_final_price" ).val( data.total_raw );

      if ( jQuery( "#nd_booking_booking_form_base_price" ).length ) {
        jQuery( "#nd_booking_booking_form_base_price" ).val( data.base_raw );
      }

      nd_booking_update_tax_breakdown( data, currency );

    }

  })
  .always(function() {

    jQuery( "#nd_booking_sorting_result_layer" ).remove();

  });


}
//END function

function nd_booking_update_tax_breakdown( data, currency ) {

  var container = jQuery( ".nd_booking_tax_breakdown" );

  if ( ! container.length ) {
    return;
  }

  var subtotalLabel = data.subtotal_label || 'Subtotal';
  var totalTaxLabel = data.total_tax_label || 'Total Tax';
  var grandTotalLabel = data.grand_total_label || 'Grand Total';

  var subtotalLine = container.find( '[data-tax-key="subtotal"]' );
  subtotalLine.find( '.nd_booking_tax_label' ).text( subtotalLabel );
  subtotalLine.find( '.nd_booking_tax_amount' ).text( data.base_formatted || data.base_raw || '0.00' );
  subtotalLine.find( '.nd_booking_tax_currency' ).text( currency );
  subtotalLine.show();

  var handledKeys = {};
  var baseRawValue = parseFloat( data.base_raw );
  if ( isNaN( baseRawValue ) ) {
    baseRawValue = 0;
  }

  var nightlyLine = container.find( '[data-tax-key="nightly_rate"]' );
  if ( nightlyLine.length ) {
    var nights = parseInt( nightlyLine.attr( 'data-nights' ), 10 );
    if ( isNaN( nights ) || nights <= 0 ) {
      nights = 0;
    }

    var nightlyAmount = 0;
    if ( nights > 0 ) {
      nightlyAmount = baseRawValue / nights;
    }

    var nightlyFormatted = nightlyAmount.toFixed( 2 );
    nightlyLine.attr( 'data-nights', nights );
    nightlyLine.find( '.nd_booking_tax_amount' ).text( nightlyFormatted );
    nightlyLine.find( '.nd_booking_tax_currency' ).text( currency );
    nightlyLine.find( '.nd_booking_tax_nights' ).text( nights );
    nightlyLine.show();
    handledKeys.nightly_rate = true;
  }
  if ( Array.isArray( data.taxes ) ) {
    for ( var i = 0; i < data.taxes.length; i++ ) {
      var tax = data.taxes[i];
      var key = tax.key;
      handledKeys[key] = true;
      var taxLine = container.find( '[data-tax-key="' + key + '"]' );
      if ( taxLine.length ) {
        taxLine.find( '.nd_booking_tax_label' ).text( tax.display_label || tax.label );
        taxLine.find( '.nd_booking_tax_amount' ).text( tax.amount_formatted || tax.amount_raw || '0.00' );
        taxLine.find( '.nd_booking_tax_currency' ).text( currency );
        taxLine.show();
      }
    }
  }

  container.find( '[data-tax-key]' ).each(function() {
    var key = jQuery( this ).data( 'tax-key' );
    if ( key && key !== 'subtotal' && key !== 'total_tax' && key !== 'grand_total' && ! handledKeys[key] ) {
      jQuery( this ).hide();
    }
  });

  var totalTaxLine = container.find( '[data-tax-key="total_tax"]' );
  totalTaxLine.find( '.nd_booking_tax_label' ).text( totalTaxLabel );
  totalTaxLine.find( '.nd_booking_tax_amount' ).text( data.total_tax_formatted || data.total_tax_raw || '0.00' );
  totalTaxLine.find( '.nd_booking_tax_currency' ).text( currency );
  totalTaxLine.show();

  var grandTotalLine = container.find( '[data-tax-key="grand_total"]' );
  grandTotalLine.find( '.nd_booking_tax_label' ).text( grandTotalLabel );
  grandTotalLine.find( '.nd_booking_tax_amount' ).text( data.total_formatted || data.total_raw || '0.00' );
  grandTotalLine.find( '.nd_booking_tax_currency' ).text( currency );
  grandTotalLine.show();

}
