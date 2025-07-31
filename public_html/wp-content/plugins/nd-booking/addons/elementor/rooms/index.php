<?php


//START ELEMENT POST GRID
class nd_booking_rooms_element extends \Elementor\Widget_Base {

  public function get_name() { return 'rooms'; }
  public function get_title() { return __( 'rooms', 'nd-booking' ); }
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
      'rooms_layout',
      [
        'label' => __( 'Layout', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'layout-1',
        'options' => [
          'layout-1'  => __( 'Layout 1', 'nd-booking' ),
          'layout-2'  => __( 'Layout 2', 'nd-booking' ),
        ],
      ]
    );

    $this->add_control(
      'rooms_width',
      [
        'label' => __( 'Width', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'nd_booking_width_100_percentage',
        'options' => [
          'nd_booking_width_100_percentage'  => __( '1 Column', 'nd-booking' ),
          'nd_booking_width_50_percentage' => __( '2 Columns', 'nd-booking' ),
          'nd_booking_width_33_percentage'  => __( '3 Columns', 'nd-booking' ),
          'nd_booking_width_25_percentage' => __( '4 Columns', 'nd-booking' ),
        ],
      ]
    );

    $this->add_group_control(
      \Elementor\Group_Control_Image_Size::get_type(),
      [
        'name' => 'thumbnail', // // Usage: `{name}_size` and `{name}_custom_dimension`, in this case `thumbnail_size` and `thumbnail_custom_dimension`.
        'exclude' => [ 'custom' ],
        'include' => [],
        'default' => 'large',
      ]
    );

    $this->add_control(
      'rooms_order',
      [
        'label' => __( 'Order', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'DESC',
        'options' => [
          'DESC'  => __( 'DESC', 'nd-booking' ),
          'ASC' => __( 'ASC', 'nd-booking' ),
        ],
      ]
    );

    $this->add_control(
      'rooms_orderby',
      [
        'label' => __( 'Order By', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'date',
        'options' => [
          'ID'  => __( 'ID', 'nd-booking' ),
          'author' => __( 'Author', 'nd-booking' ),
          'title'  => __( 'Title', 'nd-booking' ),
          'name' => __( 'Name', 'nd-booking' ),
          'type'  => __( 'Type', 'nd-booking' ),
          'date' => __( 'Date', 'nd-booking' ),
          'modified'  => __( 'Modified', 'nd-booking' ),
          'rand' => __( 'Random', 'nd-booking' ),
          'comment_count'  => __( 'Comment Count', 'nd-booking' ),
        ],
      ]
    );

    $this->add_control(
      'rooms_qnt',
      [
        'label' => __( 'Posts Per Page', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'min' => -1,
        'max' => 20,
        'step' => 1,
        'default' => 3,
      ]
    );


    $this->add_control(
      'rooms_id',
      [
        'label' => __( 'ID', 'nd-elements' ),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'min' => 1,
        'max' => 9000,
        'step' => 1,
      ]
    );
    
    $this->end_controls_section();

  }
  //END CONTROLS


 
  /*START RENDER*/
  protected function render() {

    $nd_booking_result = '';

    //add script
    wp_enqueue_script('masonry');
    wp_enqueue_script('nd_booking_postgrid_js', esc_url( plugins_url('js/rooms.js', __FILE__ )) );

    //get datas
    $nd_booking_settings = $this->get_settings_for_display();
    $nd_booking_postgrid_order = $nd_booking_settings['rooms_order'];
    $nd_booking_postgrid_orderby = $nd_booking_settings['rooms_orderby'];
    $rooms_qnt = $nd_booking_settings['rooms_qnt'];
    $rooms_width = $nd_booking_settings['rooms_width'];
    $rooms_layout = $nd_booking_settings['rooms_layout'];
    $rooms_id = $nd_booking_settings['rooms_id'];
    $roomsgrid_image_size = $nd_booking_settings['thumbnail_size'];

    //default values
    if ($rooms_width == '') { $rooms_width = "nd_booking_width_100_percentage"; }
    if ($rooms_layout == '') { $rooms_layout = "layout-1"; }
    if ($rooms_qnt == '') { $rooms_qnt = 3; }
    if ($nd_booking_postgrid_order == '') { $nd_booking_postgrid_order = 'DESC'; }
    if ($nd_booking_postgrid_orderby == '') { $nd_booking_postgrid_orderby = 'date'; }
    if ($roomsgrid_image_size == '') { $roomsgrid_image_size = 'large'; }

    //args
    $args = array(
      'post_type' => 'nd_booking_cpt_1',
      'posts_per_page' => $rooms_qnt,
      'order' => $nd_booking_postgrid_order,
      'orderby' => $nd_booking_postgrid_orderby,
      'p' => $rooms_id,
    );
    $the_query = new WP_Query( $args );

    //START LAYOUT
    $nd_booking_result .= '
    <div class="nd_booking_section nd_booking_masonry_content">';

      while ( $the_query->have_posts() ) : $the_query->the_post();

        //info
        $nd_booking_id = get_the_ID(); 
        $nd_booking_title = get_the_title();
        $nd_booking_excerpt = get_the_excerpt();
        $nd_booking_permalink = get_permalink( $nd_booking_id );
        $nd_booking_meta_box_max_people = get_post_meta( get_the_ID(), 'nd_booking_meta_box_max_people', true );
        $nd_booking_meta_box_room_size = get_post_meta( get_the_ID(), 'nd_booking_meta_box_room_size', true );
        $nd_booking_meta_box_min_price = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_min_price', true );
        $nd_booking_meta_box_color = get_post_meta( $nd_booking_id, 'nd_booking_meta_box_color', true ); if ($nd_booking_meta_box_color == '') { $nd_booking_meta_box_color = '#000'; }

      //get the layout selected
      $nd_booking_layout_selected = dirname( __FILE__ ).'/layout/'.$rooms_layout.'.php';
      include realpath($nd_booking_layout_selected);

      endwhile;

    $nd_booking_result .= '
    </div>';
    //END LAYOUT

    wp_reset_postdata();

    $nd_booking_allowed_html = [
      'div'      => [  
        'id' => [],
        'class' => [],
        'style' => [],
      ],
      'a'      => [ 
        'class' => [],
        'href' => [],
        'style' => [],
      ],
      'img'      => [ 
        'alt' => [],
        'class' => [],
        'src' => [],
        'width' => [],
      ],
      'h3'      => [ 
        'class' => [],
      ],
      'h4'      => [ 
        'class' => [],
      ],
      'p'      => [
        'class' => [],
      ],
    ];

    echo wp_kses( $nd_booking_result, $nd_booking_allowed_html );

  }
  //END RENDER


}
//END ELEMENT POST GRID
