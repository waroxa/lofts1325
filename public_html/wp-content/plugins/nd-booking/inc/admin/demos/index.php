<?php

add_action('admin_menu','nd_booking_add_settings_menu_demos');
function nd_booking_add_settings_menu_demos(){

  add_submenu_page( 'nd-booking-settings','Themes', __('Themes','nd-booking'), 'manage_options', 'nd-booking-settings-demos', 'nd_booking_settings_menu_demos' );

}



function nd_booking_settings_menu_demos() {

?>




  
  <div class="nd_booking_section nd_booking_padding_right_20 nd_booking_padding_left_2 nd_booking_box_sizing_border_box nd_booking_margin_top_25 ">

    

    <div style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(0)); ?>; border-bottom:3px solid <?php echo esc_attr(nd_booking_get_profile_bg_color(2)); ?>;" class="nd_booking_section nd_booking_padding_20  nd_booking_box_sizing_border_box">
      <h2 class="nd_booking_color_ffffff nd_booking_display_inline_block"><?php _e('ND Booking','nd-booking'); ?></h2><span class="nd_booking_margin_left_10 nd_booking_color_a0a5aa"><?php echo esc_html(nd_booking_get_plugin_version()); ?></span>
    </div>

    

    <div class="nd_booking_section nd_booking_min_height_400  nd_booking_box_shadow_0_1_1_000_04 nd_booking_background_color_ffffff nd_booking_border_1_solid_e5e5e5 nd_booking_border_top_width_0 nd_booking_border_left_width_0 nd_booking_overflow_hidden nd_booking_position_relative">
    
      <!--START menu-->
      <div style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(1)); ?>;" class="nd_booking_width_20_percentage nd_booking_float_left nd_booking_box_sizing_border_box nd_booking_min_height_3000 nd_booking_position_absolute">

        <ul class="nd_booking_navigation">
          <li><a class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-settings')); ?>"><?php _e('Plugin Settings','nd-booking'); ?></a></li>
          
          <?php 

          if ( get_option('nicdark_theme_author') == 1 ){ ?>

            <li><a class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-settings-addons-manager')); ?>"><?php _e('Addons Manager','nd-booking'); ?></a></li>

          <?php }
          
          ?>
          
          <li><a class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-paypal-settings')); ?>"><?php _e('Payment Settings','nd-booking'); ?></a></li>
          <li><a class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-settings-import-export')); ?>"><?php _e('Import Export','nd-booking'); ?></a></li>


          <li><a style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(2)); ?>;" class="" href="#"><?php _e('Themes','nd-booking'); ?></a></li>

          <?php 

          if ( get_option('nicdark_theme_author') != 1 ){ ?>

          <li><a style="background-color:<?php echo esc_attr(nd_booking_get_profile_bg_color(2)); ?>;" class="" href="<?php echo esc_url(admin_url('admin.php?page=nd-booking-settings-premium-addons')); ?>" ><?php _e('Premium Addons','nd-booking'); ?></a></li>

          <?php }

          ?>


        </ul>
      </div>
      <!--END menu-->


      <!--START content-->
      <div class="nd_booking_width_80_percentage nd_booking_margin_left_20_percentage nd_booking_float_left nd_booking_box_sizing_border_box nd_booking_padding_20">


        <!--START-->
      <div class="nd_booking_section">
        <div class="nd_booking_width_100_percentage nd_booking_padding_20 nd_booking_box_sizing_border_box nd_booking_float_left">
          <h2 class="nd_booking_section nd_booking_margin_0"><?php _e('All Themes','nd-booking'); ?></h2>
          <p class="nd_booking_color_666666 nd_booking_section nd_booking_margin_0 nd_booking_margin_top_10"><?php _e('Below are just a few examples of premium themes that used our "ND Hotel Booking" plugin.','nd-booking'); ?></p>
        </div>
      </div>
      <!--END-->

      <div class="nd_booking_section nd_booking_height_1 nd_booking_background_color_E7E7E7 nd_booking_margin_top_10 nd_booking_margin_bottom_10"></div>
      <div class="nd_booking_section nd_booking_height_20"></div>


      <style>

      .theme-browser .theme .theme-screenshot:after {
        display: none !important;
      }

      .theme-browser .theme .theme-screenshot img {
        float: left;
        position: relative;
      }

      </style>  


<div class="theme-browser rendered">
<div class="themes wp-clearfix">



          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/1.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/hotel/wp/demo/intro/"><?php _e('Theme Details','nd-booking'); ?></a></span>
          
          </div>
          <!--theme-->

          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/2.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/hotel/wp/demo/intro/"><?php _e('Theme Details','nd-booking'); ?></a></span>
          
          </div>
          <!--theme-->

          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/3.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/hotel/wp/demo/intro/"><?php _e('Theme Details','nd-booking'); ?></a></span>
          
          </div>
          <!--theme-->

          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/4.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/resort/wp/demo/intro/"><?php _e('Theme Details','nd-booking'); ?></a></span>
          
          </div>
          <!--theme-->

          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/5.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/resort/wp/demo/intro/"><?php _e('Theme Details','nd-booking'); ?></a></span>
          
          </div>
          <!--theme-->


          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/6.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/resort/wp/demo/intro/"><?php _e('Theme Details','nd-booking'); ?></a></span>
        
          </div>
          <!--theme-->


          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/7.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/camping/wp/demo/"><?php _e('Theme Details','nd-booking'); ?></a></span>
          
          </div>
          <!--theme-->

          <!--theme-->
          <div class="theme">

            <div class="theme-screenshot">
              <img src="<?php echo esc_url(plugins_url('img/8.jpg', __FILE__ )); ?>" alt="">
            </div>

            <span class="more-details"><a style="color:#fff; text-decoration:none;" target="_blank" href="http://www.nicdarkthemes.com/themes/hotel-inn/wp/demo/intro/"><?php _e('Theme Details','nd-booking'); ?></a></span>
          
          </div>
          <!--theme-->

</div>
</div>
        


      </div>
      <!--END content-->


    </div>

  </div>

<?php } 
/*END 1*/




