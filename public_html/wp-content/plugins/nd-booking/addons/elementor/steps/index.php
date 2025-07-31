<?php


//START ELEMENT POST GRID
class nd_booking_steps_element extends \Elementor\Widget_Base {

  public function get_name() { return 'steps'; }
  public function get_title() { return __( 'steps', 'nd-booking' ); }
  public function get_icon() { return 'fa fa-hands-helping'; }
  public function get_categories() { return [ 'nd-booking' ]; }

  /*START CONTROLS*/
  protected function _register_controls() {

    /*Create Tab*/
    $this->start_controls_section(
      'content_section',
      [
        'label' => __( 'Main Options', 'nd-booking' ),
        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'steps_layout',
      [
        'label' => __( 'Layout', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'layout-1',
        'options' => [
          'layout-1'  => __( 'Layout 1', 'nd-booking' ),
        ],
      ]
    );

    $this->end_controls_section();

  }
  //END CONTROLS


 
  /*START RENDER*/
  protected function render() {

    $nd_booking_result = '';

    //get datas
    $nd_booking_settings = $this->get_settings_for_display();
    $nd_booking_steps_layout = $nd_booking_settings['steps_layout'];

    //default values
    if ($nd_booking_steps_layout == '') { $nd_booking_steps_layout = "layout-1"; }

    //START decide class
    $nd_booking_search_class = 'nd_booking_border_1_solid_white';
    $nd_booking_booking_class = 'nd_booking_border_1_solid_white';
    $nd_booking_checkout_class = 'nd_booking_border_1_solid_white';
    $nd_booking_thankyou_class = 'nd_booking_border_1_solid_white';
    $nd_booking_id = get_the_ID();
    $nd_booking_permalink = get_permalink($nd_booking_id);
    if ( $nd_booking_permalink == nd_booking_search_page() ) {
      $nd_booking_search_class .= ' nd_booking_bg_greydark nd_booking_bg_custom_color nd_booking_border_1_solid_greydark_important';
    }elseif ( $nd_booking_permalink == nd_booking_booking_page() ){
      $nd_booking_booking_class .= ' nd_booking_bg_greydark nd_booking_bg_custom_color nd_booking_border_1_solid_greydark_important';
    }elseif ( $nd_booking_permalink == nd_booking_checkout_page() ){

      if( isset( $_POST['nd_booking_form_booking_arrive'] ) ) {  $nd_booking_form_booking_arrive = sanitize_text_field($_POST['nd_booking_form_booking_arrive']); }else{ $nd_booking_form_booking_arrive = '';} 
      if( isset( $_POST['nd_booking_form_checkout_arrive'] ) ) {  $nd_booking_form_checkout_arrive = sanitize_text_field($_POST['nd_booking_form_checkout_arrive']); }else{ $nd_booking_form_checkout_arrive = '';} 

      if ( $nd_booking_form_booking_arrive == 1 ) {
        $nd_booking_checkout_class .= ' nd_booking_bg_greydark nd_booking_bg_custom_color nd_booking_border_1_solid_greydark_important';
      }elseif ( $nd_booking_form_checkout_arrive == 1 OR isset($_GET['tx']) OR $nd_booking_form_checkout_arrive == 2 ) {
        $nd_booking_thankyou_class .= ' nd_booking_bg_greydark nd_booking_bg_custom_color nd_booking_border_1_solid_greydark_important';
      }else{
        $nd_booking_checkout_class .= ' nd_booking_bg_greydark nd_booking_bg_custom_color nd_booking_border_1_solid_greydark_important';
      }
    }
    //END decide class

    //START LAYOUT
    $nd_booking_result .= '
    <div class="nd_booking_section">';

      //get the layout selected
      $nd_booking_layout_selected = dirname( __FILE__ ).'/layout/'.$nd_booking_steps_layout.'.php';
      include realpath($nd_booking_layout_selected);

    $nd_booking_result .= '
    </div>';
    //END LAYOUT


    $nd_booking_allowed_html = [
      'div'      => [  
          'class' => [],
      ],
      'ul'      => [  
          'class' => [],
      ],       
      'li'      => [  
          'id' => [],
          'class' => [],
      ],
      'h1'      => [  
          'class' => [],
      ],
      'a'      => [  
          'class' => [],
          'href' => [],
      ],
    ];

    echo wp_kses( $nd_booking_result, $nd_booking_allowed_html );

  }
  //END RENDER


}
//END ELEMENT POST GRID
