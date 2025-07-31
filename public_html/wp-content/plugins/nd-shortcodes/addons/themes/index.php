<?php


add_action('admin_menu', 'nd_options_create_themes_menu');
function nd_options_create_themes_menu() {
  
  /*1*/
  add_menu_page( __('ND Library','nd-shortcodes'), __('ND Library','nd-shortcodes'), 'manage_options', 'nd-shortcodes-themes', 'nd_options_themes_menu_page', 'dashicons-superhero' );

}


//START add custom css
function nd_options_admin_style_for_theme_page() {
  
  wp_enqueue_style( 'nd_options_style_theme_page', esc_url( plugins_url( 'admin-style.css', __FILE__ ) ), array(), false, false );
  
}
add_action( 'admin_enqueue_scripts', 'nd_options_admin_style_for_theme_page' );
//END add custom css


/*1 - page*/
function nd_options_themes_menu_page() {
?>
  
  <style>
  .notice { display: none;}
  #wpfooter { display: none;}
  #wpcontent { padding-left: 0px;}
  #wpbody-content { padding-bottom: 0px; }
  </style>

  <iframe style="margin: 0px; padding: 0px; border-width: 0px; width: 100%; height: 100vh;" src="https://library.nicdark.com/" title="Nicdark Library"></iframe>

<?php } 
/*END 1*/


