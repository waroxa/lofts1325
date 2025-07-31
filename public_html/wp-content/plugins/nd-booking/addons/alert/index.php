<?php

/////////////////////////////////////////////////// REGISTER POST TYPE ///////////////////////////////////////////////////////////////

$nd_booking_alert_msg_enable = get_option('nd_booking_alert_msg_enable'); 

if ( $nd_booking_alert_msg_enable == 1 and get_option('nicdark_theme_author') == 1  ) {

    function nd_booking_create_post_type_alert_msg() {

        register_post_type('nd_booking_cpt_alert',
            array(
                'labels' => array(
                    'name' => __('Alerts', 'nd-booking'),
                    'singular_name' => __('Alerts', 'nd-booking')
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_nav_menus' => false,
                'menu_icon'   => 'dashicons-warning',
                'has_archive' => false,
                'exclude_from_search' => true,
                'rewrite' => array('slug' => 'alerts'),
                'supports' => array('title')
            )
        );
    }
    add_action('init', 'nd_booking_create_post_type_alert_msg');

}




/////////////////////////////////////////////////// CREATE METABOX ///////////////////////////////////////////////////////////////

add_action( 'add_meta_boxes', 'nd_booking_box_add_cpt_alert' );
function nd_booking_box_add_cpt_alert() {
    add_meta_box( 'nd_booking_metabox_cpt_alert', __('Metabox','nd-booking'), 'nd_booking_meta_box_cpt_alert', 'nd_booking_cpt_alert', 'normal', 'high' );
}



function nd_booking_meta_box_cpt_alert()
{

    //jquery-ui-tabs
    wp_enqueue_script('jquery-ui-tabs');

    //iris color picker
    wp_enqueue_script('iris');

    // $post is already set, and contains an object: the WordPress post
    global $post;
    $nd_booking_values = get_post_custom( $post->ID );

    //main settings
    $nd_booking_meta_box_alert_text = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_text', true ); 
    $nd_booking_meta_box_alert_color = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_color', true ); 
    $nd_booking_meta_box_alert_icon = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_icon', true ); 
    $nd_booking_meta_box_alert_time = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_time', true );
    $nd_booking_meta_box_alert_pages = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_pages', true ); 

    ?>



    <div id="nd_booking_id_metabox_cpt">
        
        <ul>
            <li><a href="#nd_booking_tab_main"><span class="dashicons-before dashicons-admin-settings nd_booking_line_height_20 nd_booking_margin_right_10 nd_booking_color_444444"></span><?php _e('Main Settings','nd-booking'); ?></a></li>
        </ul>
        
        <div class="nd_booking_id_metabox_cpt_content">
            <div id="nd_booking_tab_main">
                
                <div class="nd_booking_section nd_booking_border_bottom_1_solid_eee nd_booking_padding_10 nd_booking_box_sizing_border_box">
                    <p><strong><?php _e('Text Alert Message','nd-booking'); ?></strong></p>
                    <p><input class="nd_booking_width_100_percentage" type="text" name="nd_booking_meta_box_alert_text" id="nd_booking_meta_box_alert_text" value="<?php echo esc_attr($nd_booking_meta_box_alert_text); ?>" /></p>
                    <p><?php _e('Insert the text of you alert message','nd-booking'); ?></p>
                </div>

                <div class="nd_booking_section nd_booking_border_bottom_1_solid_eee nd_booking_padding_10 nd_booking_box_sizing_border_box">
                    <p><strong><?php _e('Bg Color','nd-booking'); ?></strong></p>
                    <p><input class="nd_booking_width_100_percentage" id="nd_booking_colorpicker" type="text" name="nd_booking_meta_box_alert_color" value="<?php echo esc_attr($nd_booking_meta_box_alert_color); ?>" /></p>
                    <p><?php _e('Set alert bg color','nd-booking'); ?></p>
                </div>
                <script type="text/javascript">
                  //<![CDATA[
                  
                  jQuery(document).ready(function($){
                      $('#nd_booking_colorpicker').iris();
                  });

                  //]]>
                </script>


                <div class="nd_booking_section nd_booking_border_bottom_1_solid_eee nd_booking_padding_10 nd_booking_box_sizing_border_box">
                    <p><strong><?php _e('Alert Icon','nd-booking'); ?></strong></p>
                    <p><input class="nd_booking_width_100_percentage" type="text" name="nd_booking_meta_box_alert_icon" id="nd_booking_meta_box_alert_icon" value="<?php echo esc_attr($nd_booking_meta_box_alert_icon); ?>" /></p>
                    <input class="button nd_booking_meta_box_alert_icon_button" type="button" name="nd_booking_meta_box_alert_icon_button" id="nd_booking_meta_box_alert_icon_button" value="<?php _e('Upload','nd-booking'); ?>" />
                    <p><?php _e('Insert the icon url','nd-booking'); ?></p>

                    <script type="text/javascript">
                      //<![CDATA[
                          
                      jQuery(document).ready(function() {

                        jQuery( function ( $ ) {

                          var file_frame = [],
                          $button = $( '.nd_booking_meta_box_alert_icon_button' );


                          $('#nd_booking_meta_box_alert_icon_button').click( function () {


                            var $this = $( this ),
                              id = $this.attr( 'id' );

                            // If the media frame already exists, reopen it.
                            if ( file_frame[ id ] ) {
                              file_frame[ id ].open();

                              return;
                            }

                            // Create the media frame.
                            file_frame[ id ] = wp.media.frames.file_frame = wp.media( {
                              title    : $this.data( 'uploader_title' ),
                              button   : {
                                text : $this.data( 'uploader_button_text' )
                              },
                              multiple : false  // Set to true to allow multiple files to be selected
                            } );

                            // When an image is selected, run a callback.
                            file_frame[ id ].on( 'select', function() {

                              // We set multiple to false so only get one image from the uploader
                              var attachment = file_frame[ id ].state().get( 'selection' ).first().toJSON();

                              $('#nd_booking_meta_box_alert_icon').val(attachment.url);

                            } );

                            // Finally, open the modal
                            file_frame[ id ].open();


                          } );

                        });

                      });

                        //]]>
                      </script>

                </div>

                <div class="nd_booking_section nd_booking_border_bottom_1_solid_eee nd_booking_padding_10 nd_booking_box_sizing_border_box">
                    <p><strong><?php _e('Alert Time','nd-booking'); ?></strong></p>
                    <p><input class="nd_booking_width_100_percentage" type="text" name="nd_booking_meta_box_alert_time" id="nd_booking_meta_box_alert_time" value="<?php echo esc_attr($nd_booking_meta_box_alert_time); ?>" /></p>
                    <p><?php _e('Enter the milliseconds after how long you want the warning to appear, EG : 5000 ( 5 seconds )','nd-booking'); ?></p>
                </div>



                <div class="nd_booking_section nd_booking_padding_10 nd_booking_box_sizing_border_box">
                    <p><strong><?php _e('Pages','nd-booking'); ?></strong></p>
                    <p><input class="nd_booking_width_100_percentage" type="text" name="nd_booking_meta_box_alert_pages" id="nd_booking_meta_box_alert_pages" value="<?php echo esc_attr($nd_booking_meta_box_alert_pages); ?>" /></p>
                    <p><?php _e('This is an intuitive field, enter the pages where you would like to add the alert ( separated by comma )','nd-booking'); ?></p>
                </div>



                <script type="text/javascript">
                  //<![CDATA[

                  jQuery(document).ready(function($){
                    var nd_booking_available_posts = [ 

                      //start all documents list
                      <?php 

                        $nd_booking_posts_args = array( 
                        	'posts_per_page' => -1, 
                        	'post_type' => array( 'post', 'page', 'nd_booking_cpt_1' )
                        );
                        $nd_booking_posts = get_posts($nd_booking_posts_args); 

                        foreach ($nd_booking_posts as $nd_booking_post) : ?>"<?php echo esc_attr($nd_booking_post->post_name); ?>",<?php endforeach;
                        
                      ?>
                      //end all documents list

                    ];
                    function split( val ) {
                      return val.split( /,\s*/ );
                    }
                    function extractLast( term ) {
                      return split( term ).pop();
                    }

                    $( "#nd_booking_meta_box_alert_pages" )
                      // don't navigate away from the field on tab when selecting an item
                      .on( "keydown", function( event ) {
                        if ( event.keyCode === $.ui.keyCode.TAB &&
                            $( this ).autocomplete( "instance" ).menu.active ) {
                          event.preventDefault();
                        }
                      })
                      .autocomplete({
                        minLength: 0,
                        source: function( request, response ) {
                          // delegate back to autocomplete, but extract the last term
                          response( $.ui.autocomplete.filter(
                            nd_booking_available_posts, extractLast( request.term ) ) );
                        },
                        focus: function() {
                          // prevent value inserted on focus
                          return false;
                        },
                        select: function( event, ui ) {
                          var terms = split( this.value );
                          // remove the current input
                          terms.pop();
                          // add the selected item
                          terms.push( ui.item.value );
                          // add placeholder to get the comma-and-space at the end
                          terms.push( "" );
                          this.value = terms.join( "," );
                          return false;
                        }
                      });
                  } );

                  //]]>
                  </script>


            </div>
             
        </div>

    </div>


    <?php   

}



add_action( 'save_post', 'nd_booking_meta_box_save_cpt_alert' );
function nd_booking_meta_box_save_cpt_alert( $post_id )
{

    //main settings : sanitize and validate
    $nd_booking_meta_box_alert_text = sanitize_text_field( $_POST['nd_booking_meta_box_alert_text'] );
    if ( isset( $nd_booking_meta_box_alert_text ) ) { 
        if ( $nd_booking_meta_box_alert_text != '' ) {
            update_post_meta( $post_id, 'nd_booking_meta_box_alert_text' , $nd_booking_meta_box_alert_text );               
        }else{
            delete_post_meta( $post_id, 'nd_booking_meta_box_alert_text' );
        }    
    }

    $nd_booking_meta_box_alert_color = sanitize_hex_color( $_POST['nd_booking_meta_box_alert_color'] );
    if ( isset( $nd_booking_meta_box_alert_color ) ) { 
        if ( $nd_booking_meta_box_alert_color != '' ) {
            update_post_meta( $post_id, 'nd_booking_meta_box_alert_color' , $nd_booking_meta_box_alert_color );      
        }else{
            delete_post_meta( $post_id, 'nd_booking_meta_box_alert_color' );
        }   
    }

    $nd_booking_meta_box_alert_icon = sanitize_url( $_POST['nd_booking_meta_box_alert_icon'] );
    if ( isset( $nd_booking_meta_box_alert_icon ) ) { 
        if ( $nd_booking_meta_box_alert_icon != '' ) {
            update_post_meta( $post_id, 'nd_booking_meta_box_alert_icon' , $nd_booking_meta_box_alert_icon );       
        }else{
            delete_post_meta( $post_id, 'nd_booking_meta_box_alert_icon' );
        }  
    }

    $nd_booking_meta_box_alert_time = sanitize_text_field( $_POST['nd_booking_meta_box_alert_time'] );
    if ( isset( $nd_booking_meta_box_alert_time ) ) { 
        if ( $nd_booking_meta_box_alert_time != '' ) {
            update_post_meta( $post_id, 'nd_booking_meta_box_alert_time' , $nd_booking_meta_box_alert_time );               
        }else{
            delete_post_meta( $post_id, 'nd_booking_meta_box_alert_time' );
        }    
    }


    $nd_booking_meta_box_alert_pages = sanitize_text_field( $_POST['nd_booking_meta_box_alert_pages'] );
    if ( isset( $nd_booking_meta_box_alert_pages ) ) { 
        if ( $nd_booking_meta_box_alert_pages != '' ) {
            update_post_meta( $post_id, 'nd_booking_meta_box_alert_pages' , $nd_booking_meta_box_alert_pages );       
        }else{
            delete_post_meta( $post_id, 'nd_booking_meta_box_alert_pages' );
        }   
    }


}


/////////////////////////////////////////////////// FUNCTIONS ///////////////////////////////////////////////////////////////

if ( $nd_booking_alert_msg_enable == 1 and get_option('nicdark_theme_author') == 1  ) {

  add_action('nicdark_footer_nd','nd_booking_get_alert');
  function nd_booking_get_alert() { 

  	global $post;
  	$nd_booking_id = $post->ID;
  	$nd_booking_slug = $post->post_name;


  	//START wp query
  	$args = array(
          'post_type' => 'nd_booking_cpt_alert',
          'posts_per_page' => 1,
          'meta_query' => array(
              array(
                  'key' => 'nd_booking_meta_box_alert_pages',
                  'value'   => $nd_booking_slug,
                  'compare' => 'LIKE',
                  'type' => 'CHAR',
              ),  
          )
      );

  	$the_query = new WP_Query( $args );

  	while ( $the_query->have_posts() ) : $the_query->the_post();

  		//text
  		$nd_booking_meta_box_alert_text = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_text', true );
  		if ( $nd_booking_meta_box_alert_text == '' ) { $nd_booking_meta_box_alert_text = __('ADD SOME TEXT','nd-booking'); }

  		//color
  		$nd_booking_meta_box_alert_color = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_color', true );
  		if ( $nd_booking_meta_box_alert_color == '' ) { $nd_booking_meta_box_alert_color = '#000'; }

  		//time
  		$nd_booking_meta_box_alert_time = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_time', true );
  		if ( $nd_booking_meta_box_alert_time == '' ) { $nd_booking_meta_box_alert_time = 1000; }

  		//icon
  		$nd_booking_meta_box_alert_icon = get_post_meta( get_the_ID(), 'nd_booking_meta_box_alert_icon', true );
  		if ( $nd_booking_meta_box_alert_icon == '' ) { $nd_booking_meta_box_alert_icon = esc_url(plugins_url('icon-warning-white.svg', __FILE__ )); }


  		$nd_booking_result = '

      <style>
      .nd_booking_alert_msg{
        position:fixed;
        bottom:20px;
        right:20px;
        background-color:'.$nd_booking_meta_box_alert_color.';
        padding:15px 25px;
        z-index:9;
        display:none;
        cursor:pointer;
      }
      .nd_booking_alert_msg p{
        color:#fff;
        float: left;
        line-height: 12px;
        padding-left: 10px;
        font-size: 12px;
        letter-spacing: 2px;
      }
      </style>

      <script type="text/javascript">
      jQuery(document).ready(function() {

        jQuery( function ( $ ) {

          $( ".nd_booking_alert_msg" ).delay( '.$nd_booking_meta_box_alert_time.' ).fadeIn( 1000 );
          $( ".nd_booking_alert_msg" ).click(function() { $(this).fadeOut( "slow" ); });

        });

      });
      </script>

      <div class="nd_booking_alert_msg">
        <img class="nd_booking_float_left" width="12px" src="'.$nd_booking_meta_box_alert_icon.'">
        <p>'.$nd_booking_meta_box_alert_text.'</p>
      </div>

  		';

      $nd_booking_allowed_html = [
        'div'      => [ 
          'class' => [],
          'id' => [],
          'style' => [],
        ],
        'img'      => [ 
          'width' => [],
          'src' => [],
          'class' => [],
          'id' => [],
          'style' => [],
        ],
        'p'      => [ 
          'class' => [],
          'id' => [],
          'style' => [],
        ],
        'script'      => [ 
          'type' => [],
        ],
        'style' => [],  
      ];

      echo wp_kses( $nd_booking_result, $nd_booking_allowed_html );


  	endwhile;
  	//END wp query


  }


}