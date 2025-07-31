<?php


add_action('customize_register','nd_options_customizer_archives');
function nd_options_customizer_archives( $wp_customize ) {
  

	//ADD panel
	$wp_customize->add_panel( 'nd_options_customizer_archives_panel', array(
	  'title' => __('Archives Template','nd-shortcodes'),
	  'capability' => 'edit_theme_options',
	  'theme_supports' => '',
	  'description' => __('Archives Settigns','nd-shortcodes'), // html tags such as <p>.
	  'priority' => 230, // Mixed with top-level-section hierarchy.
	) );

}




// all options
foreach ( glob ( plugin_dir_path( __FILE__ ) . "*/index.php" ) as $file ){
  include_once realpath($file);
}


