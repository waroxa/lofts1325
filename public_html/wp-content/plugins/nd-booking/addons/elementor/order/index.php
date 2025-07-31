<?php


//START ELEMENT POST GRID
class nd_booking_order_element extends \Elementor\Widget_Base {

  public function get_name() { return 'order'; }
  public function get_title() { return __( 'order', 'nd-booking' ); }
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
      'order_layout',
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
    $nd_booking_order_layout = $nd_booking_settings['order_layout'];

    //default values
    if ($nd_booking_order_layout == '') { $nd_booking_order_layout = "layout-1"; }

    //START LAYOUT
    $nd_booking_result .= '
    <div class="nd_booking_section">';

      //get the layout selected
      $nd_booking_layout_selected = dirname( __FILE__ ).'/layout/'.$nd_booking_order_layout.'.php';
      include realpath($nd_booking_layout_selected);

    $nd_booking_result .= '
    </div>';
    //END LAYOUT


    $nd_booking_allowed_html = [
      'div'      => [  
        'id' => [],
        'class' => [],
      ],
      'script'      => [  
        'type' => [],
      ],  
      'style'      => [],

      'ul'      => [  
        'id' => [],
        'class' => [],
      ],                
      'li'      => [  
        'id' => [],
        'class' => [],
      ],
      'p'      => [  
        'class' => [],
      ],
      'img'      => [  
        'alt' => [],
        'class' => [],
        'width' => [],
        'src' => [],
      ],
      'a'      => [  
        'data-meta-key' => [],
        'data-order' => [],
        'class' => [],
        'data-layout' => [],
      ],
      'span'      => [  
        'class' => [],
      ],  
    ];

    echo wp_kses( $nd_booking_result, $nd_booking_allowed_html );

  }
  //END RENDER


}
//END ELEMENT POST GRID
