<?php

$nicdark_themename = "marina";

//TGMPA required plugin
require_once get_template_directory() . '/class-tgm-plugin-activation.php';
add_action( 'tgmpa_register', 'nicdark_register_required_plugins' );
function nicdark_register_required_plugins() {

    $nicdark_plugins = array(


        //cf7
        array(
            'name'      => esc_html__( 'Contact Form 7', 'marina' ),
            'slug'      => 'contact-form-7',
            'required'  => true,
        ),

        //wp import
        array(
            'name'      => esc_html__( 'Wordpress Importer', 'marina' ),
            'slug'      => 'wordpress-importer',
            'required'  => true,
        ),

        //woocommerce
        array(
            'name'      => esc_html__( 'Woo Commerce', 'marina' ),
            'slug'      => 'woocommerce',
            'required'  => true,
        ),

        //elementor
        array(
            'name'      => esc_html__( 'Elementor', 'marina' ),
            'slug'      => 'elementor',
            'required'  => true,
        ),
        
        //nd shortcodes
        array(
            'name'      => esc_html__( 'ND Shortcodes', 'marina' ),
            'slug'      => 'nd-shortcodes',
            'required'  => true,
        ),

        //nd elements
        array(
            'name'      => esc_html__( 'ND Elements', 'marina' ),
            'slug'      => 'nd-elements',
            'required'  => true,
        ),

        //nd projects
        array(
            'name'      => esc_html__( 'ND Projects', 'marina' ),
            'slug'      => 'nd-projects',
            'required'  => true,
        ),

        //nd booking
        array(
            'name'      => esc_html__( 'ND Booking', 'marina' ),
            'slug'      => 'nd-booking',
            'required'  => true,
        ),

        //nd restaurant
        array(
            'name'      => esc_html__( 'ND Restaurant', 'marina' ),
            'slug'      => 'nd-restaurant-reservations',
            'required'  => true,
        ),

        //revslider
        array(
            'name'               => esc_html__( 'Revolution Slider', 'marina' ),
            'slug'               => 'revslider', // The plugin slug (typically the folder name).
            'source'             => get_template_directory().'/plugins/revslider.zip', // The plugin source.
            'required'           => true, // If false, the plugin is only 'recommended' instead of required.
        ),


    );


    $nicdark_config = array(
        'id'           => 'marina',                 // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => false,                   // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table. 
    );

    tgmpa( $nicdark_plugins, $nicdark_config );
}
//END tgmpa


//translation
load_theme_textdomain( 'marina', get_template_directory().'/languages' );


//register my menus
function nicdark_register_my_menus() {
  register_nav_menu( 'main-menu', esc_html__( 'Main Menu', 'marina' ) );  
}
add_action( 'init', 'nicdark_register_my_menus' );


//Content_width
if (!isset($content_width )) $content_width  = 1180;


//automatic-feed-links
add_theme_support( 'automatic-feed-links' );

//post-thumbnails
add_theme_support( "post-thumbnails" );

//post-formats
add_theme_support( 'post-formats', array( 'quote', 'image', 'link', 'video', 'gallery', 'audio' ) );

//title tag
add_theme_support( 'title-tag' );

// Sidebar
function nicdark_add_sidebars() {

    // Sidebar Main
    register_sidebar(array(
        'name' =>  esc_html__('Sidebar','marina'),
        'id' => 'nicdark_sidebar',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ));

}
add_action( 'widgets_init', 'nicdark_add_sidebars' );

//add css and js
function nicdark_enqueue_scripts()
{
    
    //css
    wp_enqueue_style( 'nicdark-style', get_stylesheet_uri() );
    wp_enqueue_style( 'nicdark-fonts', nicdark_google_fonts_url(), array(), '1.0.0' );

    //comment-reply
    if ( is_singular() ) wp_enqueue_script( 'comment-reply' );

    //navigation
    wp_enqueue_script('nicdark-navigation', get_template_directory_uri() . '/js/nicdark-navigation.js', array('jquery'), false, true );

}
add_action("wp_enqueue_scripts", "nicdark_enqueue_scripts");
//end js


function nicdark_admin_enqueue_scripts() {
  
  wp_enqueue_style( 'marina-admin-style', get_template_directory_uri() . '/admin-style.css', array(), false, false );
  
}
add_action( 'admin_enqueue_scripts', 'nicdark_admin_enqueue_scripts' );


//logo settings
add_action('customize_register','nicdark_customizer_logo');
function nicdark_customizer_logo( $wp_customize ) {
  

    //Logo
    $wp_customize->add_setting( 'nicdark_customizer_logo_img', array(
      'type' => 'option', // or 'option'
      'capability' => 'edit_theme_options',
      'theme_supports' => '', // Rarely needed.
      'default' => '',
      'transport' => 'refresh', // or postMessage
      'sanitize_callback' => 'nicdark_sanitize_callback_logo_img',
      //'sanitize_js_callback' => '', // Basically to_json.
    ) );
    $wp_customize->add_control( 
        new WP_Customize_Media_Control( 
            $wp_customize, 
            'nicdark_customizer_logo_img', 
            array(
              'label' => esc_html__( 'Logo', 'marina' ),
              'section' => 'title_tagline',
              'mime_type' => 'image',
            ) 
        ) 
    );

    //sanitize_callback
    function nicdark_sanitize_callback_logo_img($nicdark_logo_img_value) {
        return absint($nicdark_logo_img_value);
    }


}
//end logo settings


//woocommerce support
add_theme_support( 'woocommerce' );


//define nicdark theme option
function nicdark_theme_setup(){
    add_option( 'nicdark_theme_author', 1, '', 'yes' );
    update_option( 'nicdark_theme_author', 1 );
}
add_action( 'after_setup_theme', 'nicdark_theme_setup' );


//START add google fonts
function nicdark_google_fonts_url() {
    
    $nicdark_font_url = '';
    
    if ( 'off' !== _x( 'on', 'Google font: on or off', 'marina' ) ) {
        $nicdark_font_url = add_query_arg( 'family', urlencode( 'Lato:400,500,700' ), "//fonts.googleapis.com/css" );
    }

    return $nicdark_font_url;

}
//END add google fonts


//START create welcome page on activation

//create transient
add_action( 'after_switch_theme','nicdark_welcome_set_trans');
function nicdark_welcome_set_trans(){ if ( ! is_network_admin() ) { set_transient( 'nicdark_welcome_page_redirect', 1, 30 ); } }

//create page
add_action('admin_menu', 'nicdark_create_welcome_page');
function nicdark_create_welcome_page() {
    add_theme_page( esc_html__( 'About', 'marina' ), esc_html__( 'About', 'marina' ),current_user_can( 'edit_theme_options' ),'nicdark-welcome-theme-page', 'nicdark_welcome_page_content' );
    remove_submenu_page( 'themes.php', 'nicdark-welcome-theme-page' );
}

//set redirect
add_action( 'admin_init', 'nicdark_welcome_theme_page_redirect' );
function nicdark_welcome_theme_page_redirect() {

    if ( ! get_transient( 'nicdark_welcome_page_redirect' ) ) { return; }
    delete_transient( 'nicdark_welcome_page_redirect' );
    if ( is_network_admin() ) { return; }
    wp_safe_redirect( add_query_arg( array( 'page' => 'nicdark-welcome-theme-page' ), esc_url( admin_url( 'themes.php' ) ) ) );
    exit;

}

//page content
function nicdark_welcome_page_content(){
    
    $nicdark_welcome_title = 'Marina';
    $nicdark_welcome_documentation_link = 'http://documentations.nicdark.com/resort';
    $nicdark_welcome_youtube_video = '#';
    $nicdark_welcome_color_1 = '#444';
    $nicdark_welcome_color_2 = '#444';
    $nicdark_welcome_theme = 'marina'; //copy and replace all

    echo '

    <style>
        #setting-error-tgmpa { display:none !important; }
    </style>

    <div style="position: relative; margin: 25px 40px 0 20px; max-width: 1050px; font-size: 15px; display: block;">
    
        <div style="float:left; width:100%; padding-right:200px; box-sizing:border-box;">
            <h1 style="margin:0px; margin: .2em 200px 0 0; padding: 0; color: #32373c; line-height: 1.2; font-size: 2.8em; font-weight: 400;">'.esc_html__( 'Welcome to', 'marina' ).' '.$nicdark_welcome_title.' '.esc_html__( 'Theme', 'marina' ).'</h1>    
            <p style="color:#555d66; font-weight: 400; line-height: 1.6; font-size: 19px;">'.esc_html__( 'Thank you for choosing our theme for the design of your website. In a few simple steps you can import the contents of our demo and start working on your new project.', 'marina' ).'</p>
        </div>

        <img style="position: absolute;right: 0px;width: 110px;top: 20px;" src="https://secure.gravatar.com/avatar/0229d779828e62328bbdbe168118a84a?s=200&d=mm&r=g">
        
        <div style="float:left; width:100%;">
            <h3 style="margin-top:30px; margin: 1.25em 0 .6em; font-size: 1.4em; line-height: 1.5;">'.esc_html__( 'Import demo and sample content :', 'marina' ).'</h3>
            <p style="line-height: 1.5; font-size: 16px;">'.esc_html__( 'Follow the video tutorial below to import the contents and the various options of the demo you prefer. Follow the steps carefully and start with your new project !', 'marina' ).'</p>
        </div>

        <div style="float:left; width:100%;">

            <div style="float:left; width:100%;">

                <div style="float:left; width:25%;">
                    <p style="line-height: 1.5; font-size: 16px;"><strong>1 : </strong> <a target="_blank" href="'.admin_url().'themes.php?page=tgmpa-install-plugins">'.esc_html__( 'Install Required Plugins', 'marina' ).'</a></p>
                </div>
                <div style="float:left; width:25%;">
                    <p style="line-height: 1.5; font-size: 16px;"><strong>2 : </strong> '.esc_html__( 'Import Demo Options', 'marina' ).'</p>
                </div>
                <div style="float:left; width:25%;">
                    <p style="line-height: 1.5; font-size: 16px;"><strong>3 : </strong> '.esc_html__( 'Import Content', 'marina' ).'</p>
                </div>
                <div style="float:left; width:25%;">
                    <p style="line-height: 1.5; font-size: 16px;"><strong>4 : </strong> '.esc_html__( 'Import Slides ( Rev. Slider )', 'marina' ).'</p>
                </div>
                
            </div>


            <div style="float:left; width:100%; margin-top:20px;">
                
                <div style="float:left; width:50%; padding-right:15px; box-sizing:border-box;">
                    
                    <div style="float:left; width:100%; position:relative; height:287px;">
                        
                        <div style="float: left;width: 100%;position: absolute;height: 100%;top: 0px; left: 0px; background-color:#76b1c4;">

                            <div style=" width: 100%;height: 100%;display: table;text-align: center;">
                                <div style="display: table-cell; vertical-align: middle;">
                                    <a style="text-decoration:none; margin:0px; padding:0px;" target="_blank" href="'.$nicdark_welcome_documentation_link.'/installation/"><h3 style="color:#fff; margin:0px; padding;0px; display: inline-block; border-bottom:2px solid #fff;">'.esc_html__( 'Installation Video', 'marina' ).'</h3></a>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div style="float:left; width:50%; padding-left:15px; box-sizing:border-box;">
                    
                    <div style="float:left; width:100%; position:relative; height:287px;">
                        
                        <div style="float: left;width: 100%;position: absolute;height: 100%;top: 0px; left: 0px; background: '.$nicdark_welcome_color_1.'; background: -moz-linear-gradient(45deg, '.$nicdark_welcome_color_1.' 0%, '.$nicdark_welcome_color_2.' 100%); background: -webkit-linear-gradient(45deg, '.$nicdark_welcome_color_1.' 0%,'.$nicdark_welcome_color_2.' 100%); background: linear-gradient(45deg, '.$nicdark_welcome_color_1.' 0%,'.$nicdark_welcome_color_2.' 100%);">

                            <div style=" width: 100%;height: 100%;display: table;text-align: center;">
                                <div style="display: table-cell; vertical-align: middle;">
                                    <a style="text-decoration:none; margin:0px; padding:0px;" target="_blank" href="'.$nicdark_welcome_documentation_link.'"><h3 style="color:#fff; margin:0px; padding;0px; display: inline-block; border-bottom:2px solid #fff;">'.esc_html__( 'Theme Documentation', 'marina' ).'</h3></a>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>

        <div style="float:left; width:100%;">  
            <p style="margin-top:60px; line-height: 1.5; font-size: 16px; color: #777;">'.esc_html__( 'Thank you for choosing', 'marina' ).' '.$nicdark_welcome_title.' Theme,<br>'.esc_html__( 'Nicdark Team', 'marina' ).'</p>
        </div>

    </div>';

}
//END create welcome page on activation

// Enqueue the necessary scripts
function encolar_scripts_listar_tenants() {
    wp_enqueue_script('listar-tenants-js', get_stylesheet_directory_uri() . '/js/listar-tenants.js', array('jquery'), '1.0', true);
    wp_localize_script('listar-tenants-js', 'ajaxurl', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'encolar_scripts_listar_tenants');

// Shortcode for the button (if you need it)
function boton_listar_tenants() {
    return '<button id="listarTenantsBtn">Listar Tenants</button><div id="resultadoTenants"></div>';
}
add_shortcode('boton_listar_tenants', 'boton_listar_tenants');

// Function to list tenants and log to console
function listar_tenants_building() {
    error_log('Entrando en listar_tenants_building');

    // Assuming IntegracionButterflyMX class is correctly included and instantiated.
    $plugin_instance = new IntegracionButterflyMX();
    $building_id = isset($_GET['building_id']) ? intval($_GET['building_id']) : 60892; // Default to 60892
    error_log('Building ID: ' . $building_id);

    $response = $plugin_instance->get_tenants_by_building($building_id);

    if (is_wp_error($response)) {
        echo '<script>console.error("Error al listar tenants: ' . $response->get_error_message() . '");</script>';
    } else {
        $tenants = json_encode($response);
        echo '<script>console.log(' . $tenants . ');</script>';
    }
}

// Hook the function to the footer if the parameter is set
if (isset($_GET['listar_tenants']) && $_GET['listar_tenants'] == 1) {
    add_action('wp_footer', 'listar_tenants_building');
}

// Function to list access points and log to console
function listar_puntos_de_acceso() {
    error_log('Starting listar_puntos_de_acceso function');
    $plugin_instance = new IntegracionButterflyMX();
    $response = $plugin_instance->get_access_points();

    if (is_wp_error($response)) {
        error_log('Error in get_access_points: ' . $response->get_error_message());
        echo '<script>console.error("Error al listar puntos de acceso: ' . $response->get_error_message() . '");</script>';
    } else {
        error_log('Access points retrieved successfully');
        $access_points = json_encode($response);
        echo '<script>console.log(' . $access_points . ');</script>';
    }
}

// Hook the function to the footer if the parameter is set
if (isset($_GET['listar_puntos_de_acceso']) && $_GET['listar_puntos_de_acceso'] == 1) {
    add_action('wp_footer', 'listar_puntos_de_acceso');
}

//WORKS
// add_action('init', function() {
//     add_action('nd_booking_after_booking_completed', 'handle_successful_booking', 10, 1);

//     // Trigger the hook for test purposes
//     do_action('nd_booking_after_booking_completed', 8);
// });

// function handle_successful_booking($booking_id) {
//     // Instead of logging to file, we'll log to browser console
//     add_action('wp_footer', function() use ($booking_id) {
//         echo "<script>console.log('🔥 Booking completed for ID: {$booking_id}');</script>";
//     });

//     // Also add basic DB check output
//     global $wpdb;
//     $booking = $wpdb->get_row(
//     $wpdb->prepare("SELECT * FROM lum_nd_booking_booking WHERE id = %d", $booking_id)
// );

//     if ($booking) {
//         $name = esc_js($booking->user_first_name . ' ' . $booking->user_last_name);
//         echo "<script>console.log('✅ Booking loaded: {$name}');</script>";
//     } else {
//         echo "<script>console.log('❌ Booking ID {$booking_id} not found');</script>";
//     }
// }

?>
