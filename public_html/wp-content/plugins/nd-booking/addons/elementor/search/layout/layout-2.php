<?php


/*START preview*/
$nd_booking_result .= '
<div class=" nd_booking_search_elem_component_l2 nd_booking_section '.$nd_booking_class.' ">

	<!--START FORM-->
	<form action="'.$nd_booking_action.'" method="get">


	        <!--START check in-->
	        <div id="nd_booking_open_calendar_from" class="nd_booking_width_25_percentage nd_booking_padding_10_15 nd_booking_width_100_percentage_all_iphone nd_booking_width_50_all_ipad nd_booking_float_left nd_booking_box_sizing_border_box">

	        	<h6 class="nd_booking_label_search">'.__('CHECK-IN','nd-booking').'</h6>
	            <div class="nd_booking_section nd_booking_height_15"></div>   
				
				<div class="nd_booking_section nd_booking_section_box_search_field nd_booking_border_style_solid nd_booking_padding_10_20 nd_booking_position_relative">
					<p id="nd_booking_date_number_from_front" class="nd_booking_field_search nd_booking_display_inline_block ">'.$nd_booking_date_number_from_front.'</p>
					<p id="nd_booking_date_month_from_front" class="nd_booking_field_search nd_booking_display_inline_block ">'.$nd_booking_date_month_from_front.'</p>
					<img class="nd_booking_position_absolute nd_booking_right_20 nd_booking_top_50_percentage nd_booking_margin_top_6_negative" alt="" width="12" src="'.esc_url($nd_booking_fields_arrow_icon).'">
				</div>

	            <input type="hidden" id="nd_booking_date_month_from" class="nd_booking_section nd_booking_margin_top_20">
	            <input type="hidden" id="nd_booking_date_number_from" class="nd_booking_section nd_booking_margin_top_20">
	            <input placeholder="Check In" class="nd_booking_section nd_booking_border_width_0_important nd_booking_padding_0_important nd_booking_height_0_important" type="text" name="nd_booking_archive_form_date_range_from" id="nd_booking_archive_form_date_range_from" value="" />
	        
	        </div>
	        <!--END check IN-->


	        <!--START check out-->
	        <div id="nd_booking_open_calendar_to" class="nd_booking_width_25_percentage nd_booking_padding_10_15 nd_booking_width_100_percentage_all_iphone nd_booking_width_50_all_ipad nd_booking_float_left nd_booking_box_sizing_border_box">

	        	<h6 class="nd_booking_label_search">'.__('CHECK-OUT','nd-booking').'</h6>
	            <div class="nd_booking_section nd_booking_height_15"></div>   
				
				<div class="nd_booking_section nd_booking_section_box_search_field nd_booking_border_style_solid nd_booking_padding_10_20 nd_booking_position_relative">
					<p id="nd_booking_date_number_to_front" class="nd_booking_field_search nd_booking_display_inline_block ">'.$nd_booking_date_number_to_front.'</p>
					<p id="nd_booking_date_month_to_front" class="nd_booking_field_search nd_booking_display_inline_block ">'.$nd_booking_date_month_to_front.'</p>
					<img class="nd_booking_position_absolute nd_booking_right_20 nd_booking_top_50_percentage nd_booking_margin_top_6_negative" alt="" width="12" src="'.esc_url($nd_booking_fields_arrow_icon).'">
				</div>

	            <input type="hidden" id="nd_booking_date_month_to" class="nd_booking_section nd_booking_margin_top_20">
	            <input type="hidden" id="nd_booking_date_number_to" class="nd_booking_section nd_booking_margin_top_20">
	            <input placeholder="Check Out" class="nd_booking_section nd_booking_border_width_0_important nd_booking_padding_0_important nd_booking_height_0_important" type="text" name="nd_booking_archive_form_date_range_to" id="nd_booking_archive_form_date_range_to" value="" />
	            
	        </div>
	        <!--END check out-->


	        <!--guests-->
	        <div class="nd_booking_width_25_percentage nd_booking_padding_10_15 nd_booking_width_100_percentage_all_iphone nd_booking_width_50_all_ipad nd_booking_float_left  nd_booking_box_sizing_border_box">

	        	<h6 class="nd_booking_label_search">'.__('GUESTS','nd-booking').'</h6>
	        	<div class="nd_booking_section nd_booking_height_15"></div> 

	        	<div class="nd_booking_section nd_booking_section_box_search_field nd_booking_border_style_solid nd_booking_padding_10_20 nd_booking_position_relative">
	        		<p class="nd_booking_guests_number nd_booking_display_inline_block nd_booking_field_search">1</p>

	        		<img class="nd_booking_position_absolute nd_booking_right_20 nd_booking_top_50_percentage nd_booking_margin_top_6_negative nd_booking_guests_increase nd_booking_cursor_pointer" style="transform: rotate(180deg);" alt="" width="12" src="'.esc_url($nd_booking_fields_arrow_icon).'">
	        		<img class="nd_booking_position_absolute nd_booking_right_42 nd_booking_top_50_percentage nd_booking_margin_top_6_negative nd_booking_guests_decrease nd_booking_cursor_pointer" alt="" width="12" src="'.esc_url($nd_booking_fields_arrow_icon).'">

	        	</div>

	            <input placeholder="Guests" class="nd_booking_section nd_booking_display_none" type="number" name="nd_booking_archive_form_guests" id="nd_booking_archive_form_guests" min="1" value="'.$nd_booking_archive_form_guests.'" />
	        
	        </div>
	        <!--guests-->


	        <div class="nd_booking_width_25_percentage nd_booking_padding_10_15 nd_booking_width_100_percentage_all_iphone nd_booking_width_50_all_ipad nd_booking_float_left  nd_booking_box_sizing_border_box">
	        	<input style="padding: '.$nd_booking_submit_padding.'; background-color:'.$nd_booking_submit_bg.';" class="nd_booking_width_100_percentage nd_booking_white_space_normal nd_booking_border_style_solid " type="submit" value="'.__('CHECK AVAILABILITY','nd-booking').'">
	        </div>
	 

	</form>
	<!--END FORM-->

</div>




<!--CHECKIN/OUT SCRIPT-->
<script type="text/javascript">
jQuery(document).ready(function() {

jQuery( function ( $ ) {

    $( "#nd_booking_archive_form_date_range_from" ).datepicker({
      defaultDate: "+1w",
      minDate: 0,
      altField: "#nd_booking_date_month_from",
      altFormat: "M",
      firstDay: 0,
      dateFormat: "mm/dd/yy",
      monthNames: ["'.__('January','nd-booking').'","'.__('February','nd-booking').'","'.__('March','nd-booking').'","'.__('April','nd-booking').'","'.__('May','nd-booking').'","'.__('June','nd-booking').'", "'.__('July','nd-booking').'","'.__('August','nd-booking').'","'.__('September','nd-booking').'","'.__('October','nd-booking').'","'.__('November','nd-booking').'","'.__('December','nd-booking').'"],
      monthNamesShort: [ "'.__('Jan','nd-booking').'", "'.__('Feb','nd-booking').'", "'.__('Mar','nd-booking').'", "'.__('Apr','nd-booking').'", "'.__('May','nd-booking').'", "'.__('Jun','nd-booking').'", "'.__('Jul','nd-booking').'", "'.__('Aug','nd-booking').'", "'.__('Sep','nd-booking').'", "'.__('Oct','nd-booking').'", "'.__('Nov','nd-booking').'", "'.__('Dec','nd-booking').'" ],
      dayNamesMin: ["'.__('SU','nd-booking').'","'.__('MO','nd-booking').'","'.__('TU','nd-booking').'","'.__('WE','nd-booking').'","'.__('TH','nd-booking').'","'.__('FR','nd-booking').'", "'.__('SA','nd-booking').'"],
      nextText: "'.__('NEXT','nd-booking').'",
      prevText: "'.__('PREV','nd-booking').'",
      changeMonth: false,
      numberOfMonths: 1,
      onClose: function() {   
        var minDate = $(this).datepicker("getDate");
        var newMin = new Date(minDate.setDate(minDate.getDate() + 1));
        $( "#nd_booking_archive_form_date_range_to" ).datepicker( "option", "minDate", newMin );

        var nd_booking_input_date_from = $( "#nd_booking_archive_form_date_range_from" ).val();
        var nd_booking_date_number_from = nd_booking_input_date_from.substring(3, 5);
        $( "#nd_booking_date_number_from" ).val(nd_booking_date_number_from);
        var nd_booking_input_date_to = $( "#nd_booking_archive_form_date_range_to" ).val();
        var nd_booking_date_number_to = nd_booking_input_date_to.substring(3, 5);
        $( "#nd_booking_date_number_to" ).val(nd_booking_date_number_to);

        $( "#nd_booking_date_number_from_front" ).text(nd_booking_date_number_from);
        var nd_booking_date_month_from = $( "#nd_booking_date_month_from" ).val();
        $( "#nd_booking_date_month_from_front" ).text(nd_booking_date_month_from);

        $( "#nd_booking_date_number_to_front" ).text(nd_booking_date_number_to);
        var nd_booking_date_month_to = $( "#nd_booking_date_month_to" ).val();
        $( "#nd_booking_date_month_to_front" ).text(nd_booking_date_month_to);

      }
    });
    

    $( "#nd_booking_archive_form_date_range_to" ).datepicker({
      defaultDate: "+1w",
      altField: "#nd_booking_date_month_to",
      altFormat: "M",
      minDate: "+1d",
      monthNames: ["'.__('January','nd-booking').'","'.__('February','nd-booking').'","'.__('March','nd-booking').'","'.__('April','nd-booking').'","'.__('May','nd-booking').'","'.__('June','nd-booking').'", "'.__('July','nd-booking').'","'.__('August','nd-booking').'","'.__('September','nd-booking').'","'.__('October','nd-booking').'","'.__('November','nd-booking').'","'.__('December','nd-booking').'"],
      monthNamesShort: [ "'.__('Jan','nd-booking').'", "'.__('Feb','nd-booking').'", "'.__('Mar','nd-booking').'", "'.__('Apr','nd-booking').'", "'.__('May','nd-booking').'", "'.__('Jun','nd-booking').'", "'.__('Jul','nd-booking').'", "'.__('Aug','nd-booking').'", "'.__('Sep','nd-booking').'", "'.__('Oct','nd-booking').'", "'.__('Nov','nd-booking').'", "'.__('Dec','nd-booking').'" ],
      dayNamesMin: ["'.__('SU','nd-booking').'","'.__('MO','nd-booking').'","'.__('TU','nd-booking').'","'.__('WE','nd-booking').'","'.__('TH','nd-booking').'","'.__('FR','nd-booking').'", "'.__('SA','nd-booking').'"],
      nextText: "'.__('NEXT','nd-booking').'",
      prevText: "'.__('PREV','nd-booking').'",
      changeMonth: false,
      firstDay: 0,
      dateFormat: "mm/dd/yy",
      numberOfMonths: 1,
      onClose: function() {   
        
        var nd_booking_input_date_from = $( "#nd_booking_archive_form_date_range_from" ).val();
        var nd_booking_date_number_from = nd_booking_input_date_from.substring(3, 5);
        $( "#nd_booking_date_number_from" ).val(nd_booking_date_number_from);
        var nd_booking_input_date_to = $( "#nd_booking_archive_form_date_range_to" ).val();
        var nd_booking_date_number_to = nd_booking_input_date_to.substring(3, 5);
        $( "#nd_booking_date_number_to" ).val(nd_booking_date_number_to);

        $( "#nd_booking_date_number_from_front" ).text(nd_booking_date_number_from);
        var nd_booking_date_month_from = $( "#nd_booking_date_month_from" ).val();
        $( "#nd_booking_date_month_from_front" ).text(nd_booking_date_month_from);

        $( "#nd_booking_date_number_to_front" ).text(nd_booking_date_number_to);
        var nd_booking_date_month_to = $( "#nd_booking_date_month_to" ).val();
        $( "#nd_booking_date_month_to_front" ).text(nd_booking_date_month_to);

      }
    });
    
    $("#nd_booking_archive_form_date_range_from").datepicker("setDate", "+0");
    $("#nd_booking_archive_form_date_range_to").datepicker("setDate", "+1");

    function nd_booking_get_nights(){
      var nd_booking_archive_form_date_range_from = $("#nd_booking_archive_form_date_range_from").val();
      var nd_booking_archive_form_date_range_to = $("#nd_booking_archive_form_date_range_to").val();
      var nd_booking_start = new Date(nd_booking_archive_form_date_range_from);
      var nd_booking_end = new Date(nd_booking_archive_form_date_range_to);
      var nd_booking_nights_number = (nd_booking_end - nd_booking_start) / 1000 / 60 / 60 / 24; 
      $( ".nd_booking_nights_number" ).text(nd_booking_nights_number); 
    }

    $("#nd_booking_open_calendar_from").click(function () {
        $("#nd_booking_archive_form_date_range_from").datepicker("show");
    });
    $("#nd_booking_open_calendar_to").click(function () {
        $("#nd_booking_archive_form_date_range_to").datepicker("show");
    });

});

});
</script>
<!--END CHECKIN/OUT SCRIPT-->';
/*END preview*/ 