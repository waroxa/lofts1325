<?php


if ( get_option('nicdark_theme_author') == 1 ){
   
add_action('admin_menu','nd_booking_add_settings_menu_addons');
function nd_booking_add_settings_menu_addons(){

  add_submenu_page( 'nd-booking-settings','Addons Manager', __('Addons Manager','nd-booking'), 'manage_options', 'nd-booking-settings-addons-manager', 'nd_booking_settings_menu_addons_manager' );
  add_action( 'admin_init', 'nd_booking_addons_settings' );

}


function nd_booking_addons_settings() {
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_visualcomposer_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_message_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_price_range_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_branch_select_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_info_price_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_services_filter_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_extra_services_filter_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_packages_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_similar_rooms_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_paypal_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_booking_request_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_payment_on_arrive_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_integration_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_coupon_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_alert_msg_enable' );
  register_setting( 'nd_booking_addons_settings_group', 'nd_booking_elementor_enable' );

  //custom hook
  do_action("nd_booking_add_addons_settings_group");

}


function nd_booking_settings_menu_addons_manager() { ?>


<form method="post" action="options.php">
    
  <?php settings_fields( 'nd_booking_addons_settings_group' ); ?>
  <?php do_settings_sections( 'nd_booking_addons_settings_group' ); ?>
  
  <div class="nd_booking_section nd_booking_padding_right_20 nd_booking_padding_left_2 nd_booking_box_sizing_border_box nd_booking_margin_top_25 ">

    

    <div style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(0)); ?>; border-bottom:3px solid <?php echo esc_attr(nd_booking_get_profile_bg_color(2)); ?>;" class="nd_booking_section nd_booking_padding_20  nd_booking_box_sizing_border_box">
      <h2 class="nd_booking_color_ffffff nd_booking_display_inline_block"><?php _e('ND Booking','nd-booking'); ?></h2><span class="nd_booking_margin_left_10 nd_booking_color_a0a5aa"><?php echo esc_html(nd_booking_get_plugin_version()); ?></span>
    </div>

    

    <div class="nd_booking_section  nd_booking_box_shadow_0_1_1_000_04 nd_booking_background_color_ffffff nd_booking_border_1_solid_e5e5e5 nd_booking_border_top_width_0 nd_booking_border_left_width_0 nd_booking_overflow_hidden nd_booking_position_relative">
    
      <!--START menu-->
      <div style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(1)); ?>;" class="nd_booking_width_20_percentage nd_booking_float_left nd_booking_box_sizing_border_box nd_booking_min_height_3000 nd_booking_position_absolute">

        <ul class="nd_booking_navigation">
          <li><a class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-settings')); ?>"><?php _e('Plugin Settings','nd-booking'); ?></a></li>
          <li><a style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(2)); ?>;" class="" href="" ><?php _e('Addons Manager','nd-booking'); ?></a></li>
          <li><a class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-paypal-settings')); ?>"><?php _e('Payment Settings','nd-booking'); ?></a></li>
          <li><a class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-settings-import-export')); ?>"><?php _e('Import Export','nd-booking'); ?></a></li>
          <li><a <?php if ( get_option('nicdark_theme_author') == 1 ){ ?> style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(2)); ?>;" <?php } ?> class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-settings-demos')); ?>"><?php _e('Themes','nd-booking'); ?></a></li>
        </ul>
      </div>
      <!--END menu-->


      <!--START content-->
      <div class="nd_booking_width_80_percentage nd_booking_margin_left_20_percentage nd_booking_float_left nd_booking_box_sizing_border_box nd_booking_padding_20">


        <!--START-->
        <div class="nd_booking_section">
          <div class="nd_booking_width_40_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
            <h2 class="nd_booking_section nd_booking_margin_0"><?php _e('Addons Manager','nd-booking'); ?></h2>
            <p class="nd_booking_color_666666 nd_booking_section nd_booking_margin_0 nd_booking_margin_top_10"><?php _e('Here you can remove some plugins addons.','nd-booking'); ?></p>
          </div>
        </div>
        <!--END-->

        <div class="nd_booking_section nd_booking_height_1 nd_booking_background_color_E7E7E7 nd_booking_margin_top_10 nd_booking_margin_bottom_10"></div>


         <!--START-->
          <div class="nd_booking_section">
            <div class="nd_booking_width_40_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              <h2 class="nd_booking_section nd_booking_margin_0"><?php _e('Main Addons','nd-booking'); ?></h2>
              <p class="nd_booking_color_666666 nd_booking_section nd_booking_margin_0 nd_booking_margin_top_10"><?php _e('Manage your plugin addons','nd-booking'); ?></p>
            </div>
            <div class="nd_booking_width_60_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_visualcomposer_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_visualcomposer_enable" type="checkbox" value="1"> <?php _e('WP Bakery Page Builder','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_message_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_message_enable" type="checkbox" value="1"> <?php _e('Mail On Booking','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_coupon_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_coupon_enable" type="checkbox" value="1"> <?php _e('Coupon','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_alert_msg_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_alert_msg_enable" type="checkbox" value="1"> <?php _e('Alert Messages','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_elementor_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_elementor_enable" type="checkbox" value="1"> <?php _e('Elementor','nd-booking'); ?></label>
              
              <?php do_action("nd_booking_add_setting_on_main_addons"); ?>
              
            </div>
          </div>
          <!--END-->
          <div class="nd_booking_section nd_booking_height_1 nd_booking_background_color_E7E7E7 nd_booking_margin_top_10 nd_booking_margin_bottom_10"></div>



          <!--START-->
          <div class="nd_booking_section">
            <div class="nd_booking_width_40_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              <h2 class="nd_booking_section nd_booking_margin_0"><?php _e('Search Page','nd-booking'); ?></h2>
              <p class="nd_booking_color_666666 nd_booking_section nd_booking_margin_0 nd_booking_margin_top_10"><?php _e('Manage your addons on search page','nd-booking'); ?></p>
            </div>
            <div class="nd_booking_width_60_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_price_range_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_price_range_enable" type="checkbox" value="1"> <?php _e('Price Range','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_services_filter_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_services_filter_enable" type="checkbox" value="1"> <?php _e('Services Filter','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_extra_services_filter_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_extra_services_filter_enable" type="checkbox" value="1"> <?php _e('Extra Services Filter','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_branch_select_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_branch_select_enable" type="checkbox" value="1"> <?php _e('Branch Select Filter','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_info_price_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_info_price_enable" type="checkbox" value="1"> <?php _e('Price Details on Button Hover','nd-booking'); ?></label>

            </div>
          </div>
          <!--END-->
          <div class="nd_booking_section nd_booking_height_1 nd_booking_background_color_E7E7E7 nd_booking_margin_top_10 nd_booking_margin_bottom_10"></div>



          <!--START-->
          <div class="nd_booking_section">
            <div class="nd_booking_width_40_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              <h2 class="nd_booking_section nd_booking_margin_0"><?php _e('Single','nd-booking'); ?> <span style="text-transform: capitalize;"><?php echo esc_html(nd_booking_get_slug('singular')); ?></span></h2>
              <p class="nd_booking_color_666666 nd_booking_section nd_booking_margin_0 nd_booking_margin_top_10"><?php _e('Manage your addons on single','nd-booking'); ?> <?php echo esc_html(nd_booking_get_slug('singular')); ?> <?php _e('page','nd-booking'); ?></p>
            </div>
            <div class="nd_booking_width_60_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_packages_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_packages_enable" type="checkbox" value="1"> <?php _e('Packages','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_similar_rooms_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_similar_rooms_enable" type="checkbox" value="1"> <?php _e('Similar','nd-booking'); ?> <span style="text-transform: capitalize;"><?php echo esc_html(nd_booking_get_slug('plural')); ?></span></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_integration_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_integration_enable" type="checkbox" value="1"> <?php _e('External Booking System Integration ( Booking, Airbnb etc. )','nd-booking'); ?></label>

            </div>
          </div>
          <!--END-->
          <div class="nd_booking_section nd_booking_height_1 nd_booking_background_color_E7E7E7 nd_booking_margin_top_10 nd_booking_margin_bottom_10"></div>




          <!--START-->
          <div class="nd_booking_section">
            <div class="nd_booking_width_40_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              <h2 class="nd_booking_section nd_booking_margin_0"><?php _e('Payment Methods','nd-booking'); ?></h2>
              <p class="nd_booking_color_666666 nd_booking_section nd_booking_margin_0 nd_booking_margin_top_10"><?php _e('Manage your payment methods','nd-booking'); ?></p>
            </div>
            <div class="nd_booking_width_60_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
              
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_paypal_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_paypal_enable" type="checkbox" value="1"> <?php _e('Paypal','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_booking_request_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_booking_request_enable" type="checkbox" value="1"> <?php _e('Booking Request','nd-booking'); ?></label>
              <div class="nd_booking_section nd_booking_height_20"></div>
              <label class="nd_booking_section"><input <?php if( get_option('nd_booking_payment_on_arrive_enable') == 1 ) { echo esc_attr('checked="checked"'); } ?> name="nd_booking_payment_on_arrive_enable" type="checkbox" value="1"> <?php _e('Payment On Arrive','nd-booking'); ?></label>              

              <?php do_action("nd_booking_add_setting_on_payment_methods_addons"); ?>

            </div>
          </div>
          <!--END-->
          <div class="nd_booking_section nd_booking_height_1 nd_booking_background_color_E7E7E7 nd_booking_margin_top_10 nd_booking_margin_bottom_10"></div>


        <div class="nd_booking_section nd_booking_padding_left_20 nd_booking_padding_right_20 nd_booking_box_sizing_border_box">
          <?php submit_button(); ?>
        </div>



      </div>
      <!--END content-->


    </div>

  </div>
</form>


<?php } 
/*END 1*/

}
