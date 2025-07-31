(function($) {
"use strict";

	jQuery(document).ready(function() {

		//START masonry
		jQuery(function ($) {

			var $nd_booking_masonry_content = $(".nd_booking_masonry_content").imagesLoaded( function() {
				$nd_booking_masonry_content.masonry({ itemSelector: ".nd_booking_masonry_item" });
			});

		});
		//END masonry
		
	});


})(jQuery);