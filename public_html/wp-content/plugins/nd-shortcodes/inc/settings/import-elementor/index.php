<?php



add_action('nicdark_import_demo_nav_nd','nicdark_import_demo_nav');
function nicdark_import_demo_nav() { 

  $nd_options_demo_nav_2 = '';
  $nd_options_demo = '';
  $nd_options_step = '';

  if ( isset( $_GET['demo'] ) ) { $nd_options_demo = sanitize_text_field($_GET['demo']); }
  if ( isset( $_GET['step'] ) ) { $nd_options_step = sanitize_text_field($_GET['step']);} 

  if ( $nd_options_demo != '' ) {


  $nd_options_demo_nav_2 .= '


  <style>
  .nicdark_demo_navigation .nicdark_import_demo_nav { display:none !important; }
  .nicdark_import_demo_1_step { display:none; }
  .nicdark_import_demo_2_step { display:none; }
  .nicdark_import_demo_4_step { display:none; }
  </style>

  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover">
      '.esc_html__('1 - Install Required Plugins','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover" href="'.esc_url(admin_url('themes.php?page=nicdark-welcome-theme-page')).'">
      '.esc_html__('2 - Choose the Demo','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover nd_options_background_color_2271b1">
      '.esc_html__('3 - Import Content and Style','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover">
      '.esc_html__('4 - Enjoy your Theme','nd-shortcodes').'
    </a>
  </li>


  ';


 } elseif ( $nd_options_step == 4 ){

  $nd_options_demo_nav_2 .= '


  <style>
  .nicdark_demo_navigation .nicdark_import_demo_nav { display:none !important; }
  .nicdark_import_demo_1_step { display:none; }
  .nicdark_import_demo_2_step { display:none; }
  .nicdark_import_demo_3_step { display:none; }
  </style>

  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover">
      '.esc_html__('1 - Install Required Plugins','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover" href="'.esc_url(admin_url('themes.php?page=nicdark-welcome-theme-page')).'">
      '.esc_html__('2 - Choose the Demo','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover">
      '.esc_html__('3 - Import Content and Style','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover nd_options_background_color_2271b1">
      '.esc_html__('4 - Enjoy your Theme','nd-shortcodes').'
    </a>
  </li>


  ';


}else{


  $nd_options_demo_nav_2 .= '


  <style>
  .nicdark_demo_navigation .nicdark_import_demo_nav { display:none !important; }
  .nicdark_import_demo_1_step { display:none; }
  .nicdark_import_demo_3_step { display:none; }
  .nicdark_import_demo_4_step { display:none; }
  </style>


  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover">
      '.esc_html__('1 - Install Required Plugins','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover nd_options_background_color_2271b1" href="'.esc_url(admin_url('themes.php?page=nicdark-welcome-theme-page')).'">
      '.esc_html__('2 - Choose the Demo','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover">
      '.esc_html__('3 - Import Content and Style','nd-shortcodes').'
    </a>
  </li>
  <li class="nd_options_padding_0 nd_options_margin_0">
    <a class="nd_options_color_ffffff nd_options_text_decoration_none nd_options_font_size_14 nd_options_display_block nd_options_padding_8_20 nd_options_color_ffffff_hover">
      '.esc_html__('4 - Enjoy your Theme','nd-shortcodes').'
    </a>
  </li>


  ';


 }




  $nd_options_allowed_html_shortcodes = [
    'li' => [ 
      'class' => [],
    ],
    'a' => [ 
      'class' => [],
      'href' => [],
    ],
    'style' => [],
  ];

  echo wp_kses( $nd_options_demo_nav_2, $nd_options_allowed_html_shortcodes );


}


add_action('nicdark_import_demo_nd','nicdark_import_demo');
function nicdark_import_demo() { 


        




        //START 2 STEP
        $nd_options_step_demo_2 = '';
        $nd_options_step_demo_2 .= '

        <!--START 2-->
        <div class="nicdark_import_demo_2_step nd_options_width_100_percentage nd_options_box_sizing_border_box nd_options_padding_20">


          <div class="nd_options_section">
            <div class="nd_options_width_40_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
              <h1 class="nd_options_section nd_options_margin_0">'.esc_html__('Choose the demo','nd-shortcodes').'</h1>
              <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_20">'.esc_html__('Chose the demo that you would like to import','nd-shortcodes').'</p>
            </div>
          </div>

          <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>';



            $nd_options_xml_demos_url = esc_url( get_template_directory().'/import/demos.xml');
            
            //start IF
            if ( file_exists($nd_options_xml_demos_url) ) {

              
              $nd_options_import_demos = simplexml_load_file($nd_options_xml_demos_url);


              $nd_options_step_demo_2 .= '
              <div class="nd_options_section nd_options_padding_bottom_40">';

                //start foreach
                $nd_options_i_d = 1;
                $nd_options_step_demo_2_bakery = '';
                $nd_options_demos_list = '';

                foreach( $nd_options_import_demos->demo as $nd_options_import_demo ) { 

                  //elementorpro
                  $nd_options_elementorpro = $nd_options_import_demo->elementorpro;
                  if ( $nd_options_elementorpro == "" ) { $nd_options_elementorpro = 0; }

                  if ( $nd_options_import_demo->image == 'bakery' ) {

                    //check theme name
                    $nd_options_theme_name = '';
                    $nd_options_theme_name = wp_get_theme();
                    $nd_options_theme_name = $nd_options_theme_name->get('TextDomain');


                    //start if is love travel for wp bakery alert
                    if ( $nd_options_theme_name == 'lovetravell' ) {

                    }else{


                      $nd_options_step_demo_2_bakery = '

                      <div class="nd_options_box_sizing_border_box nd_options_padding_20 nd_options_float_left nd_options_width_100_percentage">
                        <div class="notice notice-error nd_options_padding_20 nd_options_margin_top_30 nd_options_margin_0">
                          <p><strong>'.esc_html__('WP BAKERY DEMOS','nd-shortcodes').' : </strong>
                            '.esc_html__('If you are looking for demos using ','nd-shortcodes').' 
                            <u>'.esc_html__('WP Bakery Page Builder','nd-shortcodes').'</u>
                            '.esc_html__(' plugin check','nd-shortcodes').' 
                            <a target="_blank" href="'.$nd_options_import_demo->url.'">'.esc_html__('this link','nd-shortcodes').'</a>
                          </p>
                        </div>
                      </div>

                      ';


                    }
                    //end love travel check


                  }else{

                    $nd_options_demos_list .= '

                    <div class="nd_options_width_33_percentage nd_options_box_sizing_border_box nd_options_padding_20 nd_options_float_left">

                      <img class="nd_options_section nd_options_box_shadow_0_0_30_000_10 nd_options_margin_bottom_20" src="'.$nd_options_import_demo->image.'">

                      <div class="nd_options_section">

                        <form class="nd_options_float_left" action="'.esc_url(admin_url('themes.php')).'" method="get">
                          <input type="hidden" name="page" value="nicdark-welcome-theme-page">
                          <input type="hidden" name="demo" value="'.$nd_options_i_d.'">
                          <input type="hidden" name="elementorpro" value="'.$nd_options_elementorpro.'">
                          <input class="button button-primary" type="submit" value="'.esc_html__('Choose This Demo','nd-shortcodes').'">
                        </form>

                        <a target="blank" class="button nd_options_float_right" href="'.$nd_options_import_demo->url.'">'.esc_html__('View Demo','nd-shortcodes').'</a>

                      </div>

                    </div>
                    ';


                  }




                

                $nd_options_i_d = $nd_options_i_d + 1;

                }
                //end foreach

              $nd_options_step_demo_2 .= ' '.$nd_options_step_demo_2_bakery.' '.$nd_options_demos_list.'
              </div>';

            } else {

                $nd_options_step_demo_2 .= '
                <div class="nd_options_box_sizing_border_box nd_options_float_left nd_options_width_100_percentage">
                  <div class="notice notice-error nd_options_padding_20 nd_options_margin_top_20 nd_options_margin_bottom_10">
                  <p><strong>'.esc_html__('Access denied to demos.xml file !','nd-shortcodes').'</strong></p>
                  <p>'.esc_html__('Your server permissions denies access to the demos.xml file, contact your hosting provider to solve it','nd-shortcodes').'</p>
                  <a target="blank" href="https://documentations.nicdark.com/themes/your-server-permission-denied-access-to-demo-xml/">'.esc_html__('Check the article','nd-shortcodes').'</p></a>
                  </div>
                </div>';

            }
            //END IF



        $nd_options_step_demo_2 .= '
        </div>
        <!--END 2-->';



        $nd_options_allowed_html_shortcodes = [
          'div' => [ 
            'class' => [],
          ],

          'h1' => [  
            'class' => [],
          ],

          'p' => [  
            'class' => [],
          ],

          'u' => [  
            'class' => [],
          ],

          'strong' => [  
            'class' => [],
          ],

          'img' => [ 
            'class' => [],
            'src' => [],
          ],

          'form' => [ 
            'class' => [],
            'action' => [],
            'method' => [],
          ],

          'input' => [ 
            'type' => [],
            'name' => [],
            'value' => [],
            'class' => [],
          ],

          'a' => [  
            'target' => [],
            'class' => [],
            'href' => [],
          ],
        ];

        echo wp_kses( $nd_options_step_demo_2, $nd_options_allowed_html_shortcodes );
        //END 2 STEP









        //START STEP 3
        $nd_options_demo = '';
        if ( isset( $_GET['demo'] ) ) { $nd_options_demo = sanitize_text_field($_GET['demo']); }
        
        if ( $nd_options_demo != '' ) {

          $nd_options_step_demo_3 = '';
         
          

          //START import demo options
          $nd_options_options_file = esc_url( get_template_directory().'/import/'.$nd_options_demo.'/options.xml');

          
          if ( file_exists($nd_options_options_file) ) {

            $nd_options_demoss = simplexml_load_file($nd_options_options_file);

            $nd_options_i = 0;
            foreach( $nd_options_demoss->option as $nd_options_demoo ) { 


            	$nd_options_demo_option_name = esc_attr($nd_options_demoo->name);
            	$nd_options_demo_option_value = esc_attr($nd_options_demoo->value);

            	//START update option only it contains the plugin suffix
      				if ( strpos($nd_options_demo_option_name, 'nd_options_') !== false OR strpos($nd_options_demo_option_name, 'nd_travel_') !== false OR strpos($nd_options_demo_option_name, 'elementor_') !== false ) {
      					update_option($nd_options_demo_option_name,$nd_options_demo_option_value);
      				} 
      				//END update option only it contains the plugin suffix
            	 
              $nd_options_i = $nd_options_i + 1;

            }

          }
          //END import demo options



          $nd_options_demo_img = esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/'.$nd_options_demo.'.jpg'); 


          
          $nd_options_step_demo_3 .= '
          <!--START 3-->
          <div class="nicdark_import_demo_3_step nd_options_box_sizing_border_box nd_options_width_100_percentage nd_options_padding_20">


            <div class="nd_options_section">
              <div class="nd_options_width_40_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                <h1 class="nd_options_section nd_options_margin_0">'.esc_html__('Import Content & Style','nd-shortcodes').'</h1>
                <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_20">'.esc_html__('Follow the two simple steps below to import content and style','nd-shortcodes').'.</p>
              </div>
            </div>

            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>';






            /*START IF ELEMENTOR PRO IS INSTALLED AND ACTIVATED*/
            $nd_options_elementorpro = '';
            $nd_options_elementorpro = sanitize_text_field($_GET['elementorpro']);
            if ( $nd_options_elementorpro == "" ) { $nd_options_elementorpro = 0; }

            if ( $nd_options_elementorpro == 1 ) {

              $nicdark_elementorpro_path = 'elementor-pro/elementor-pro.php';
              $nicdark_proelements_path = 'pro-elements/pro-elements.php';
              if ( is_plugin_active($nicdark_elementorpro_path) or is_plugin_active($nicdark_proelements_path) ) { 
                  
              }else{

                $nd_options_step_demo_3 .= '
                <!--plugins notice-->
                <div class="nd_options_box_sizing_border_box nd_options_padding_20 nd_options_float_left nd_options_width_100_percentage">
                  <div class="notice notice-error nd_options_padding_20 nd_options_margin_top_30 nd_options_margin_0 nd_options_background_color_d6363917">
                    
                    <p>
                      <strong>'.esc_html__('ONE PLUGIN IS MISSING !','nd-shortcodes').'</strong>
                      <br/><br/>
                      '.esc_html__('Install one of','nd-shortcodes').'
                      <a target="blank" href="https://www.nicdarkthemes.com/plugin-required/">'.esc_html__('these plugins','nd-shortcodes').'</a>
                      '.esc_html__('for import this demo.','nd-shortcodes').'
                    </p>

                    <br/>
                    
                    <a class="button nd_options_color_ffffff_important nd_options_background_color_d63638_important nd_options_border_width_0_important" target="blank" href="https://www.nicdarkthemes.com/plugin-required/">
                      '.esc_html__('CHECK HERE','nd-shortcodes').'
                    </a>

                  </div>
                </div>
                <!--plugins notice-->';

              }

            }
            /*END IF ELEMENTOR PRO IS INSTALLED AND ACTIVATED*/





            //START check if one setting is ok
            function nd_options_is_server_setting_ok($nd_options_server_setting_opt,$nd_options_server_setting){

              $nd_options_my_server_setting = ini_get($nd_options_server_setting);

              $nd_options_setting_with_m = strpos($nd_options_server_setting_opt,'M');
              if ($nd_options_setting_with_m !== false) {
              	$nd_options_my_server_setting = wp_convert_hr_to_bytes($nd_options_my_server_setting);
              	$nd_options_server_setting_opt = wp_convert_hr_to_bytes($nd_options_server_setting_opt);	
              }

              if ( $nd_options_my_server_setting < $nd_options_server_setting_opt ) {
                return 0;
              }else{
                return 1;
              }


            }
            //END check if one setting is ok


            //START check if all settings are ok
            function nd_options_are_server_settings_ok() {


              //array
              $nd_options_all_server_settings = array(
                  "memory_limit","256M",
                  "post_max_size","256M",
                  "upload_max_filesize","256M",
                  "max_execution_time","300",
                  "max_input_vars","5000",
              );


              //start for
              $nd_options_i_2 = 1;
              for ($nd_options_i = 0; $nd_options_i <= count($nd_options_all_server_settings); $nd_options_i++) {


                  if ( $nd_options_i_2 <= count($nd_options_all_server_settings)/2 ) {

                    if($nd_options_i % 2 == 0){
                      
                      if ( nd_options_is_server_setting_ok($nd_options_all_server_settings[$nd_options_i+1],$nd_options_all_server_settings[$nd_options_i]) == 0 ) { return 0; }

                      $nd_options_i_2 = $nd_options_i_2 + 1;

                    }

                  }


              }
              //end for


              return 1;

            
            }
            //END check if all settings are ok



            if ( nd_options_are_server_settings_ok() == 0 ) {

              $nd_options_step_demo_3 .= '
              <!--plugins notice-->
              <div class="nd_options_box_sizing_border_box nd_options_padding_20 nd_options_float_left nd_options_width_100_percentage">
                <div class="notice notice-error nd_options_padding_20 nd_options_margin_top_30 nd_options_margin_0">
                  <p><strong>'.esc_html__('SERVER SETTINGS','nd-shortcodes').' :</strong> '.esc_html__('Some of your server settings are not optimized and you may have some problems while importing','nd-shortcodes').' <u>'.esc_html__('check at the end of this page your server settings','nd-shortcodes').'</u></p>
                </div>
              </div>
              <!--plugins notice-->';

            }
            //END CHECK SERVER SETTINGS
            




            /*START DISPLAY ONLY IF ELEMENTOR PRO IS ACTIVATED*/
            if ( $nd_options_elementorpro == 1 ) {

              $nicdark_elementorpro_path = 'elementor-pro/elementor-pro.php';
              $nicdark_proelements_path = 'pro-elements/pro-elements.php';
              
              if ( is_plugin_active($nicdark_elementorpro_path) or is_plugin_active($nicdark_proelements_path) ) { 

                  //check theme name
                  $nd_options_theme_name = '';
                  $nd_options_theme_name = wp_get_theme();
                  $nd_options_theme_name = $nd_options_theme_name->get('TextDomain');
                
                  update_option( 'nicdark_type_demo', 1 );

                  //start if is love travel for new import
                  if ( $nd_options_theme_name == 'lovetravell' ) {



                    $nd_options_step_demo_3 .= '
                    
                    <div class="nd_options_section">
                      <div class="nd_options_width_100_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                        <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('1 - Import the Content','nd-shortcodes').'</h2>
                        <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10 nd_options_margin_bottom_15">'.esc_html__('Download the Content.zip by clicking on the button below, save the file on your desktop and then upload the file Content.zip on Elementor Import Tool','nd-shortcodes').' <a target="_blank" href="'.admin_url().'admin.php?page=elementor-app#/import">'.esc_html__('here','nd-shortcodes').'</a></p>


                        <a class="button" href="'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/content.zip').'" download >'.esc_html__('Donwload Content.zip','nd-shortcodes').'</a>
                        <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">* '.esc_html__('If your server blocks the download of the file, get it from this path','nd-shortcodes').' : <u>'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/content.zip').'</u></p>

                      </div>
                    </div>


                    <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>


                    <div class="nd_options_section">
                      <div class="nd_options_width_40_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                        
                        <form action="'.esc_url(admin_url('themes.php')).'" method="get">
                          <input type="hidden" name="page" value="nicdark-welcome-theme-page">
                          <input type="hidden" name="step" value="4">
                          <input class="button button-primary" type="submit" value="'.esc_html__('Go to the last Step ','nd-shortcodes').'*">
                        </form>

                        <p>* '.esc_html__('Mandatory step to import some important options of the chosen demo','nd-shortcodes').'</p>

                      </div>
                    </div>'
                    ;




                  }else{

                    $nd_options_step_demo_3 .= '
                    <div class="nd_options_section">
                      <div class="nd_options_width_100_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                        <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('1 - Import the Content','nd-shortcodes').'</h2>
                        <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10 nd_options_margin_bottom_15">'.esc_html__('Download the Content.xml by clicking on the button below, save the file on your desktop and then upload the file Content.xml on WordPress Importer','nd-shortcodes').' <a target="_blank" href="'.admin_url().'admin.php?import=wordpress">'.esc_html__('here','nd-shortcodes').'</a></p>
                        <a class="button" href="'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/content.xml').'" download>'.esc_html__('Donwload Content.xml','nd-shortcodes').'</a>
                        <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">* '.esc_html__('If your server blocks the download of the file, get it from this path','nd-shortcodes').' : <u>'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/content.xml').'</u></p>
                      </div>
                    </div>


                    <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>


                    <div class="nd_options_section">
                      <div class="nd_options_width_100_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                        <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('2 - Import the Style','nd-shortcodes').'</h2>
                        <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10 nd_options_margin_bottom_15">'.esc_html__('Download the Style.zip by clicking on the button below, save the file on your desktop and then upload the file Style.zip on Elementor Import Tool','nd-shortcodes').' <a target="_blank" href="'.admin_url().'admin.php?page=elementor-app#/import">'.esc_html__('here','nd-shortcodes').'</a></p>


                        <a class="button" href="'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/style.zip').'" download >'.esc_html__('Donwload Style.zip','nd-shortcodes').'</a>
                        <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">* '.esc_html__('If your server blocks the download of the file, get it from this path','nd-shortcodes').' : <u>'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/style.zip').'</u></p>

                      </div>
                    </div>


                    <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>


                    <div class="nd_options_section">
                      <div class="nd_options_width_40_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                        
                        <form action="'.esc_url(admin_url('themes.php')).'" method="get">
                          <input type="hidden" name="page" value="nicdark-welcome-theme-page">
                          <input type="hidden" name="step" value="4">
                          <input class="button button-primary" type="submit" value="'.esc_html__('Go to the last Step ','nd-shortcodes').'*">
                        </form>

                        <p>* '.esc_html__('Mandatory step to import some important options of the chosen demo','nd-shortcodes').'</p>

                      </div>
                    </div>'
                    ;
                  
                  }

              }else{

                //plugins are not installed
                update_option( 'nicdark_type_demo', 2 );

              }

            }
            /*END DISPLAY ONLY IF ELEMENTOR PRO IS ACTIVATED*/





            /*START ONLY IF THE DEMO SELECTED DON'T NEED ELEMENTOR PRO AND HAS 2 AS VALUE*/
            if ( $nd_options_elementorpro == 2 ) {
            
              $nd_options_step_demo_3 .= '
              <div class="nd_options_section">
                <div class="nd_options_width_100_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                  <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('1 - Import the Content','nd-shortcodes').'</h2>
                  <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10 nd_options_margin_bottom_15">'.esc_html__('Download the Content.xml by clicking on the button below, save the file on your desktop and then upload the file Content.xml on WordPress Importer','nd-shortcodes').' <a target="_blank" href="'.admin_url().'admin.php?import=wordpress">'.esc_html__('here','nd-shortcodes').'</a></p>
                  <a class="button" href="'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/content.xml').'" download>'.esc_html__('Donwload Content.xml','nd-shortcodes').'</a>
                  <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">* '.esc_html__('If your server blocks the download of the file, get it from this path','nd-shortcodes').' : <u>'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/content.xml').'</u></p>
                </div>
              </div>


              <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>


              <div class="nd_options_section">
                <div class="nd_options_width_100_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                  <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('2 - Import the Style','nd-shortcodes').'</h2>
                  <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10 nd_options_margin_bottom_15">'.esc_html__('Download the Style.zip by clicking on the button below, save the file on your desktop and then upload the file Style.zip on Elementor Import Tool','nd-shortcodes').' <a target="_blank" href="'.admin_url().'admin.php?page=elementor-app#/import">'.esc_html__('here','nd-shortcodes').'</a></p>


                  <a class="button" href="'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/style.zip').'" download >'.esc_html__('Donwload Style.zip','nd-shortcodes').'</a>
                  <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">* '.esc_html__('If your server blocks the download of the file, get it from this path','nd-shortcodes').' : <u>'.esc_url( get_template_directory_uri().'/import/'.$nd_options_demo.'/style.zip').'</u></p>

                </div>
              </div>


              <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>


              <div class="nd_options_section">
                <div class="nd_options_width_40_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                  
                  <form action="'.esc_url(admin_url('themes.php')).'" method="get">
                    <input type="hidden" name="page" value="nicdark-welcome-theme-page">
                    <input type="hidden" name="step" value="4">
                    <input class="button button-primary" type="submit" value="'.esc_html__('Go to the last Step ','nd-shortcodes').'*">
                  </form>

                  <p>* '.esc_html__('Mandatory step to import some important options of the chosen demo','nd-shortcodes').'</p>

                </div>
              </div>'
              ;
  

            }
            /*END ONLY IF THE DEMO SELECTED DON'T NEED ELEMENTOR PRO AND HAS 2 AS VALUE*/
            



            $nd_options_step_demo_3 .= '
            <div class="nd_options_section">
              <div class="nd_options_width_100_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                <img class="nd_options_section nd_options_box_shadow_0_0_30_000_10" src="'.$nd_options_demo_img.'">
              </div>
            </div>';


            $nd_options_memory_limit_content = ''; 
            $nd_options_post_max_size_content = '';
            $nd_options_upload_max_filesize_content = '';
            $nd_options_max_execution_time_content = '';
            $nd_options_max_input_vars_content = '';
            

            //START show server settings only if there are some incorrect settings
            if ( nd_options_are_server_settings_ok() == 0 ) {

             

	            //memory_limit
	            $nd_options_memory_limit_class = '';
	            $nd_options_memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
	            $nd_options_memory_limit_opt = wp_convert_hr_to_bytes('256M');
      				if ( $nd_options_memory_limit < $nd_options_memory_limit_opt ) { 

      					$nd_options_memory_limit_class = 'nd_options_color_d63638';
                $nd_options_memory_limit_content = '';
      					$nd_options_memory_limit_content .= '<p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('Your current value is insufficient. Please adjust the value to','nd-shortcodes').' <span class="nd_options_color_00a32a">'.size_format($nd_options_memory_limit_opt).'</span> '.esc_html__('in order to meet WordPress requirements.','nd-shortcodes').'</p>';

      				}


      				//post_max_size
      				$nd_options_post_max_size_class = "";
      				$nd_options_post_max_size = wp_convert_hr_to_bytes(ini_get('post_max_size'));
      	            $nd_options_post_max_size_opt = wp_convert_hr_to_bytes('256M');
      				if ( $nd_options_post_max_size < $nd_options_post_max_size_opt ) { 

      					$nd_options_post_max_size_class = "nd_options_color_d63638";
                $nd_options_post_max_size_content = '';
      					$nd_options_post_max_size_content .= '<p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('Your current value is insufficient. Please adjust the value to','nd-shortcodes').' <span class="nd_options_color_00a32a">'.size_format($nd_options_post_max_size_opt).'</span> '.esc_html__('in order to meet WordPress requirements.','nd-shortcodes').'</p>';

      				}


      				//upload_max_filesize
      				$nd_options_upload_max_filesize_class = "";
      				$nd_options_upload_max_filesize = wp_convert_hr_to_bytes(ini_get('upload_max_filesize'));
      	            $nd_options_upload_max_filesize_opt = wp_convert_hr_to_bytes('256M');
      				if ( $nd_options_upload_max_filesize < $nd_options_upload_max_filesize_opt ) { 

      					$nd_options_upload_max_filesize_class = "nd_options_color_d63638";
                $nd_options_upload_max_filesize_content = '';
      					$nd_options_upload_max_filesize_content .= '<p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('Your current value is insufficient. Please adjust the value to','nd-shortcodes').' <span class="nd_options_color_00a32a">'.size_format($nd_options_upload_max_filesize_opt).'</span> '.esc_html__('in order to meet WordPress requirements.','nd-shortcodes').'</p>';

      				}


      				//max_execution_time
      				$nd_options_max_execution_time_class = "";
      				if ( ini_get('max_execution_time') < 300 ) {

      					$nd_options_max_execution_time_class = "nd_options_color_d63638";
                $nd_options_max_execution_time_content = '';
      					$nd_options_max_execution_time_content .= '<p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('Your current value is insufficient. Please adjust the value to','nd-shortcodes').' <span class="nd_options_color_00a32a">300</span> '.esc_html__('in order to meet WordPress requirements.','nd-shortcodes').'</p>';

      				}



      				//max_input_vars
      				$nd_options_max_input_vars_class = "";
      				if ( ini_get('max_input_vars') < 5000 ) {

      					$nd_options_max_input_vars_class = "nd_options_color_d63638";
                $nd_options_max_input_vars_content = '';
      					$nd_options_max_input_vars_content .= '<p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('Your current value is insufficient. Please adjust the value to','nd-shortcodes').' <span class="nd_options_color_00a32a">5000</span> '.esc_html__('in order to meet WordPress requirements.','nd-shortcodes').'</p>';

      				}



	            $nd_options_step_demo_3 .= '
	            <div id="nd_options_server_requirements" class="nd_options_section nd_options_margin_top_25">
	              <div class="nd_options_width_40_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
	                <h1 class="nd_options_section nd_options_margin_0">'.esc_html__('Server Requirements','nd-shortcodes').'</h1>
	                <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_20">'.esc_html__('Below you can see some settings of your server, your PHP version is','nd-shortcodes').' '.phpversion().'</p>
	              </div>
	            </div>

	            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>


	            <div class="nd_options_section">
	              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
	                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('PHP Memory Limit','nd-shortcodes').' : <span class="'.$nd_options_memory_limit_class.'">'.size_format($nd_options_memory_limit).'</span></h2>
	                '.$nd_options_memory_limit_content.'
	              </div>
	            </div>

	            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>

	            <div class="nd_options_section">
	              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
	                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('PHP Post Max Size','nd-shortcodes').' : <span class="'.$nd_options_post_max_size_class.'">'.size_format($nd_options_post_max_size).'</span></h2>
	                '.$nd_options_post_max_size_content.'
	              </div>
	            </div>

	            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>

	            <div class="nd_options_section">
	              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
	                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('PHP Max Upload Size','nd-shortcodes').' : <span class="'.$nd_options_upload_max_filesize_class.'">'.size_format($nd_options_upload_max_filesize).'</span></h2>
	                '.$nd_options_upload_max_filesize_content.'
	              </div>
	            </div>

	            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>

	            <div class="nd_options_section">
	              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
	                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('PHP Time Limit','nd-shortcodes').' : <span class="'.$nd_options_max_execution_time_class.'">'.ini_get('max_execution_time').'</span></h2>
	                '.$nd_options_max_execution_time_content.'
	              </div>
	            </div>

	            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>

	            <div class="nd_options_section">
	              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
	                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('PHP Max Input Vars','nd-shortcodes').' : <span class="'.$nd_options_max_input_vars_class.'">'.ini_get('max_input_vars').'</span></h2>
	                '.$nd_options_max_input_vars_content.'
	              </div>
	            </div>

	            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>

	            <div class="nd_options_section nd_options_margin_bottom_20">
	              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
	                <a target="blank" class="button" href="https://documentations.nicdark.com/themes/server-settings-requirmentes/">'.esc_html__('Check the Article','nd-shortcodes').'</a>
	              </div>
	            </div>';


        	}
        	//END show server settings only if there are some incorrect settings


          $nd_options_step_demo_3 .= '
          </div>
          <!--END 3-->';


		$nd_options_allowed_html_shortcodes = [
			'div' => [ 
			  'class' => [],
			  'id' => [],
			],

			'h1' => [ 
			  'class' => [],
			],

			'br' => [ 
			  'class' => [],
			],

			'p' => [ 
			  'class' => [],
			  'u' => [],
			  'br' => [],
			],

			'ul' => [ 
			  'class' => [],
			],

			'li' => [ 
			  'class' => [],
			],

			'u' => [
			  'class' => [],
			],

			'strong' => [
			  'class' => [],
			],

			'span' => [ 
			  'class' => [],
			],

			'h2' => [ 
			  'class' => [],
			],

			'a' => [ 
			  'target' => [],
			  'href' => [],
			  'class' => [],
			  'download' => [],
			],
			                  
			'form' => [
			 'action' => [],
			  'method' => [],
			],

			'input' => [ 
			  'type' => [],
			  'name' => [],
			  'value' => [],
			  'class' => [],
			],

			'img' => [ 
			  'class' => [],
			  'src' => [],
			],  
		];

        echo wp_kses( $nd_options_step_demo_3, $nd_options_allowed_html_shortcodes );


        }
        //END STEP 3






        //START STEP 4
        $nd_options_step = '';
        if ( isset( $_GET['step'] ) ) { $nd_options_step = sanitize_text_field($_GET['step']); }


        if ( $nd_options_step == 4 ) {

          $nd_options_step_demo_4 = '';
          $nd_options_step_demo_4 .= '
          <!--START 4-->
          <div class="nicdark_import_demo_4_step nd_options_width_100_percentage nd_options_padding_20 nd_options_box_sizing_border_box">

            <div class="nd_options_section">
              <div class="nd_options_width_40_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                <h1 class="nd_options_section nd_options_margin_0">'.esc_html__('Enjoy your Theme','nd-shortcodes').'</h1>
                <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_20">'.esc_html__('The import process can cause some problems since each server can have very limited restrictions for this reason below we list the most frequent problems that you can solve in simple steps','nd-shortcodes').'</p>
              </div>
            </div>

            <div class="nd_options_section nd_options_height_1 nd_options_background_color_E7E7E7 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>


            <div class="nd_options_section">
              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('Disable blue fonts colors','nd-shortcodes').'</h2>
                <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('Sometimes, after installing the theme, the site fonts may appear blue and a different font from the demo.','nd-shortcodes').'</p>
              </div>
              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                
                <div class="nd_options_section nd_options_padding_left_20 nd_options_padding_right_20 nd_options_box_sizing_border_box">
                  
                    <a class="button" target="blank" href="https://documentations.nicdark.com/themes/disable-blue-fonts-colors/">'.esc_html__('Check the Article','nd-shortcodes').'</a>
                  
                </div>

              </div>
            </div>


            <div class="nd_options_section">
              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('Problem on Header and Footer','nd-shortcodes').'</h2>
                <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('If you don’t see the header and footer correctly after importing, there’s nothing to worry about, since the header and footer are created with the page builder, check the article for understand how to fix it.','nd-shortcodes').'</p>
              </div>
              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                
                <div class="nd_options_section nd_options_padding_left_20 nd_options_padding_right_20 nd_options_box_sizing_border_box">
                  
                    <a class="button" target="blank" href="https://documentations.nicdark.com/themes/i-dont-see-header-and-footer-after-import-demo/">'.esc_html__('Check the Article','nd-shortcodes').'</a>
                  
                </div>

              </div>
            </div>


            <div class="nd_options_section">
              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                <h2 class="nd_options_section nd_options_margin_0">'.esc_html__('Set your home page as front page','nd-shortcodes').'</h2>
                <p class="nd_options_color_666666 nd_options_section nd_options_margin_0 nd_options_margin_top_10">'.esc_html__('Set your home page as front page it’s very simple, this is a WordPress feature and through our article you can understand how to set it.','nd-shortcodes').'</p>
              </div>
              <div class="nd_options_width_50_percentage nd_options_padding_20 nd_options_box_sizing_border_box nd_options_float_left">
                
                <div class="nd_options_section nd_options_padding_left_20 nd_options_padding_right_20 nd_options_box_sizing_border_box">
                  
                    <a class="button" target="blank" href="https://documentations.nicdark.com/themes/set-your-home-page-as-front-page/">'.esc_html__('Check the Article','nd-shortcodes').'</a>
                  
                </div>

              </div>
            </div>


            <div class="nd_options_section nd_options_height_1 nd_options_margin_top_10 nd_options_margin_bottom_10"></div>';

            


          


          $nd_options_step_demo_4 .= '  
          </div>
          <!--END 4-->';
 

		$nd_options_allowed_html_shortcodes = [

			'div' => [ 
			  'class' => [],
			],

			'h1' => [ 
			  'class' => [],
			],

			'p' => [  
			  'class' => [],
			],

			'span' => [  
			  'class' => [],
			],

      'u' => [  
        'class' => [],
      ],
			              
			'h2' => [  
			  'class' => [],
			],

			'a' => [  
			  'class' => [],
			  'target' => [],
			  'href' => [],
			],

		];

        echo wp_kses( $nd_options_step_demo_4, $nd_options_allowed_html_shortcodes );

        }
        //END STEP 4


    
}