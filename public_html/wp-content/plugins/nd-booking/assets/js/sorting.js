//START function
function ndBookingInitializeResultsLoader() {
  var jq = window.jQuery || null;
  var loader = document.getElementById('nd_booking_search_results_loader');
  var loaderRemoved = false;
  var masonryContentSelector = '.nd_booking_masonry_content';

  if (!loader) {
    window.ndBookingHideResultsLoader = function() {};
    return;
  }

  var removeLoaderElement = function() {
    if (!loader || loaderRemoved) {
      return;
    }

    loaderRemoved = true;

    setTimeout(function() {
      if (loader && loader.parentNode) {
        loader.parentNode.removeChild(loader);
      }

      loader = null;
    }, 320);
  };

  var hideLoader = function(forceRemove) {
    if (!loader) {
      return;
    }

    var shouldRemove = forceRemove !== false;

    loader.classList.add('nd_booking_search_results_loader--hidden');

    if (shouldRemove) {
      removeLoaderElement();
    }
  };

  window.ndBookingHideResultsLoader = function(forceRemove) {
    hideLoader(forceRemove);
  };

  var triggerHide = function(delay, forceRemove) {
    window.setTimeout(function() {
      hideLoader(forceRemove);
    }, delay);
  };

  var nativeHideHandler = function() {
    hideLoader(true);
  };

  if (window.ndBookingNativeHideResultsLoader) {
    document.removeEventListener('ndBooking:hideResultsLoader', window.ndBookingNativeHideResultsLoader);
  }

  window.ndBookingNativeHideResultsLoader = nativeHideHandler;
  document.addEventListener('ndBooking:hideResultsLoader', nativeHideHandler);

  if (jq && jq(document)) {
    jq(document)
      .off('ndBooking:hideResultsLoader')
      .on('ndBooking:hideResultsLoader', function() {
        hideLoader(true);
      });
  }

  if (jq && jq.fn) {
    var $content = jq(masonryContentSelector);

    if (typeof jq.fn.imagesLoaded === 'function' && $content.length) {
      $content.imagesLoaded().always(function() {
        triggerHide(150, true);
      });
    } else {
      triggerHide(200, true);
    }

    jq(window)
      .off('load.ndBookingResults')
      .on('load.ndBookingResults', function() {
        triggerHide(120, true);
      });

    if (typeof jq.fn.tooltip === 'function') {
      jq('.nd_booking_tooltip_jquery').tooltip({
        tooltipClass: 'nd_booking_tooltip_jquery_content',
        position: {
          my: 'center top',
          at: 'center-7 top-33',
        }
      });
    }
  } else {
    triggerHide(200, true);

    if (window.ndBookingResultsLoaderOnLoadHandler) {
      window.removeEventListener('load', window.ndBookingResultsLoaderOnLoadHandler);
    }

    var onLoad = function() {
      triggerHide(120, true);

      if (window.ndBookingResultsLoaderOnLoadHandler === onLoad) {
        window.ndBookingResultsLoaderOnLoadHandler = null;
      }

      window.removeEventListener('load', onLoad);
    };

    window.ndBookingResultsLoaderOnLoadHandler = onLoad;
    window.addEventListener('load', onLoad);
  }

  triggerHide(2000, true);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', ndBookingInitializeResultsLoader);
} else {
  ndBookingInitializeResultsLoader();
}

function nd_booking_sorting(paged){

  jQuery( 'body' ).append( "<div id='nd_booking_sorting_result_layer' class='nd_booking_cursor_progress nd_booking_position_fixed nd_booking_width_100_percentage nd_booking_height_100_percentage nd_booking_z_index_999'></div>" );

  var nd_booking_sorting_result_loader = jQuery('<div id="nd_booking_sorting_result_loader" class="nd_booking_position_absolute nd_booking_top_0 nd_booking_z_index_9 nd_booking_left_0 nd_booking_bg_white  nd_booking_cursor_progress nd_booking_height_100_percentage nd_booking_width_100_percentage"></div>').hide();
  jQuery( '#nd_booking_archive_search_masonry_container' ).append(nd_booking_sorting_result_loader);
  nd_booking_sorting_result_loader.fadeIn('slow');

  var nd_booking_search_filter_options_meta_key = jQuery( "#nd_booking_search_filter_options .nd_booking_search_filter_options_active").attr('data-meta-key');
  var nd_booking_search_filter_options_order = jQuery( "#nd_booking_search_filter_options .nd_booking_search_filter_options_active").attr('data-order');
  var nd_booking_search_filter_layout = jQuery( "#nd_booking_search_filter_layout .nd_booking_search_filter_layout_active").attr('data-layout');
  if ( typeof nd_booking_search_filter_layout === 'undefined' ){ nd_booking_search_filter_layout = 1; }

  //variables passed on function
  var nd_booking_paged = paged;
  if(typeof nd_booking_paged === 'undefined'){
    nd_booking_paged = jQuery( ".nd_booking_btn_pagination_active" ).text();
  }
  var nd_booking_layout = jQuery( "#nd_booking_btn_sorting_layout .nd_booking_btn_sorting_layout_active").attr('title');

  var nd_booking_archive_form_branches = jQuery( "#nd_booking_archive_form_branches").val();
  var nd_booking_archive_form_date_range_from = jQuery( "#nd_booking_archive_form_date_range_from").val();
  var nd_booking_archive_form_date_range_to = jQuery( "#nd_booking_archive_form_date_range_to").val();
  var nd_booking_archive_form_guests = jQuery( "#nd_booking_archive_form_guests").val();
  var nd_booking_archive_form_max_price_for_day = jQuery( "#nd_booking_archive_form_max_price_for_day").val();
  var nd_booking_archive_form_services = jQuery( "#nd_booking_archive_form_services").val();
  var nd_booking_archive_form_additional_services = jQuery( "#nd_booking_archive_form_additional_services").val();
  var nd_booking_archive_form_branch_stars = jQuery( "#nd_booking_archive_form_branch_stars").val();

  
  

  //START post method
  var nd_booking_request = jQuery.get(
    
  
    //ajax
    nd_booking_my_vars_sorting.nd_booking_ajaxurl_sorting,
    {
      action : 'nd_booking_sorting_php',
      nd_booking_paged : nd_booking_paged,
      nd_booking_archive_form_branches : nd_booking_archive_form_branches,
      nd_booking_archive_form_date_range_from : nd_booking_archive_form_date_range_from,
      nd_booking_archive_form_date_range_to : nd_booking_archive_form_date_range_to,
      nd_booking_archive_form_guests : nd_booking_archive_form_guests,
      nd_booking_archive_form_max_price_for_day : nd_booking_archive_form_max_price_for_day,
      nd_booking_archive_form_services : nd_booking_archive_form_services,
      nd_booking_archive_form_additional_services : nd_booking_archive_form_additional_services,
      nd_booking_search_filter_options_meta_key : nd_booking_search_filter_options_meta_key,
      nd_booking_search_filter_options_order : nd_booking_search_filter_options_order,
      nd_booking_search_filter_layout : nd_booking_search_filter_layout,
      nd_booking_archive_form_branch_stars : nd_booking_archive_form_branch_stars,
      nd_booking_sorting_security : nd_booking_my_vars_sorting.nd_booking_ajaxnonce_sorting,
    },
    //end ajax


    //START success
    function( nd_booking_sorting_result ) {


      setTimeout(function(){

        jQuery( '#nd_booking_content_result' ).remove();
        jQuery( '#nd_booking_archive_search_masonry_container' ).append(nd_booking_sorting_result);

        if (typeof window.ndBookingInitializeResultsLoader === 'function') {
          window.ndBookingInitializeResultsLoader();
        }

        jQuery( "#nd_booking_sorting_result_loader" ).fadeOut( "slow", function() {
          jQuery( "#nd_booking_sorting_result_loader" ).remove();
          jQuery( "#nd_booking_sorting_result_layer" ).remove();
        });

      },10);


    }
    //END

    

  );

  if (nd_booking_request && typeof nd_booking_request.fail === 'function') {
    nd_booking_request.fail(function() {
      jQuery( "#nd_booking_sorting_result_loader" ).fadeOut( "fast", function() {
        jQuery( "#nd_booking_sorting_result_loader" ).remove();
      });
      jQuery( "#nd_booking_sorting_result_layer" ).remove();

      if (typeof window.ndBookingHideResultsLoader === 'function') {
        window.ndBookingHideResultsLoader(true);
      } else {
        jQuery(document).trigger('ndBooking:hideResultsLoader');
      }
    });
  }
  //END

  
}
//END function