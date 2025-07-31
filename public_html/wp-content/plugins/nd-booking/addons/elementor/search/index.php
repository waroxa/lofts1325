<?php


//START ELEMENT POST GRID
class nd_booking_search_element extends \Elementor\Widget_Base {

  public function get_name() { return 'ndsearch'; }
  public function get_title() { return __( 'search', 'nd-booking' ); }
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
      'search_layout',
      [
        'label' => __( 'Layout', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'layout-1',
        'options' => [
          'layout-1'  => __( 'Layout 1', 'nd-booking' ),
          'layout-2'  => __( 'Layout 2', 'nd-booking' ),
          'layout-3'  => __( 'Layout 3', 'nd-booking' ),
        ],
      ]
    );
    
    $this->end_controls_section();


    $this->start_controls_section(
      'style_section_2',
      [
        'label' => __( 'Button Style', 'nd-booking' ),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name' => 'button_typography',
        'label' => __( 'Typography', 'nd-booking' ),
        'condition' => [
          'search_layout[value]' => 'layout-1',
        ],
        'selector' => '{{WRAPPER}} .nd_booking_search_elem_component_l1 input[type="submit"]',
      ]
    );

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name' => 'button_typography_2',
        'label' => __( 'Typography', 'nd-booking' ),
        'condition' => [
          'search_layout[value]' => 'layout-2',
        ],
        'selector' => '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]',
      ]
    );

    $this->add_responsive_control(
      'button_height',
      [
        'label' => __( 'Height', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => [ 'px','%' ],
        'range' => [
          'px' => [
            'min' => 0,
            'max' => 1200,
            'step' => 1,
          ],
          '%' => [
            'min' => 0,
            'max' => 100,
            'step' => 1,
          ],
        ],
        'devices' => [ 'desktop', 'tablet', 'mobile' ],
        'desktop_default' => [
          'size' => 100,
          'unit' => '%',
        ],
        'tablet_default' => [
          'size' => 100,
          'unit' => '%',
        ],
        'mobile_default' => [
          'size' => 100,
          'unit' => '%',
        ],
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l1 input[type="submit"]' => 'height: {{SIZE}}{{UNIT}};',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]' => 'height: {{SIZE}}{{UNIT}};',
          '{{WRAPPER}} .nd_booking_search_elem_component_l3 input[type="submit"]' => 'height: {{SIZE}}{{UNIT}};',
        ],
      ]
    );


    $this->add_responsive_control(
      'button_margin_top',
      [
        'label' => __( 'Margin Top', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => [ 'px','%' ],
        'range' => [
          'px' => [
            'min' => 0,
            'max' => 1200,
            'step' => 1,
          ],
          '%' => [
            'min' => 0,
            'max' => 100,
            'step' => 1,
          ],
        ],
        'devices' => [ 'desktop', 'tablet', 'mobile' ],
        'desktop_default' => [
          'size' => 100,
          'unit' => '%',
        ],
        'tablet_default' => [
          'size' => 100,
          'unit' => '%',
        ],
        'mobile_default' => [
          'size' => 100,
          'unit' => '%',
        ],
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l1 input[type="submit"]' => 'margin-top: {{SIZE}}{{UNIT}};',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]' => 'margin-top: {{SIZE}}{{UNIT}};',
          '{{WRAPPER}} .nd_booking_search_elem_component_l3 input[type="submit"]' => 'margin-top: {{SIZE}}{{UNIT}};',
        ],
      ]
    );


    $this->add_control(
      'button_color',
      [
        'label' => __( 'Text Color', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#ffffff',
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l1 input[type="submit"]' => 'color: {{VALUE}} !important',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]' => 'color: {{VALUE}} !important',
          '{{WRAPPER}} .nd_booking_search_elem_component_l3 input[type="submit"]' => 'color: {{VALUE}} !important',
        ],
      ]
    );

    $this->add_control(
      'button_bgcolor',
      [
        'label' => __( 'Background Color', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#000000',
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l1 input[type="submit"]' => 'background-color: {{VALUE}}',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]' => 'background-color: {{VALUE}}',
          '{{WRAPPER}} .nd_booking_search_elem_component_l3 input[type="submit"]' => 'background-color: {{VALUE}}',
        ],
      ]
    );

    $this->add_control(
      'hr_button_border',
      [
        'type' => \Elementor\Controls_Manager::DIVIDER,
        'condition' => [
          'search_layout[value]' => 'layout-2',
        ],
      ]
    );


    $this->add_control(
      'button_border_width',
      [
        'label' => __( 'Border Width', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => [ 'px' ],
        'default' => [
          'top' => 1,
          'right' => 1,
          'bottom' => 1,
          'left' => 1,
        ],
        'condition' => [
          'search_layout[value]' => 'layout-2',
        ],
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test input[type="submit"]' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
      ]
    );

    $this->add_control(
      'button_border_color',
      [
        'label' => __( 'Border Color', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#f1f1f1',
        'condition' => [
          'search_layout[value]' => 'layout-2',
        ],
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]' => 'border-color: {{VALUE}}',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test input[type="submit"]' => 'border-color: {{VALUE}}',
        ],
      ]
    );

    $this->add_control(
      'button_border_radius',
      [
        'label' => __( 'Border Radius', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => [ 'px' ],
        'range' => [
          'px' => [
            'min' => 0,
            'max' => 100,
            'step' => 1,
          ],
        ],
        'default' => [
          'unit' => 'px',
          'size' => 0,
        ],
        'condition' => [
          'search_layout[value]' => 'layout-2',
        ],
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 input[type="submit"]' => 'border-radius: {{SIZE}}px;',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test input[type="submit"]' => 'border-radius: {{SIZE}}px;',
        ],
      ]
    );

    $this->end_controls_section();


    $this->start_controls_section(
      'style_section_3',
      [
        'label' => __( 'Label Style', 'nd-booking' ),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        'condition' => [
          'search_layout[value]' => 'layout-2',
        ],
      ]
    );

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name' => 'label_typography',
        'label' => __( 'Typography', 'nd-booking' ),
        'selector' => '{{WRAPPER}} .nd_booking_search_elem_component_l2 h6.nd_booking_label_search',
      ]
    );

    $this->add_control(
      'label_color',
      [
        'label' => __( 'Text Color', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#000000',
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 h6.nd_booking_label_search' => 'color: {{VALUE}} !important',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test .nd_booking_label_search' => 'color: {{VALUE}} !important',
        ],
      ]
    );

    $this->end_controls_section();



    $this->start_controls_section(
      'style_section_4',
      [
        'label' => __( 'Fields Style', 'nd-booking' ),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        'condition' => [
          'search_layout[value]' => 'layout-2',
        ],
      ]
    );

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name' => 'fields_typography',
        'label' => __( 'Typography', 'nd-booking' ),
        'selector' => '{{WRAPPER}} .nd_booking_search_elem_component_l2 p.nd_booking_field_search',
      ]
    );

    $this->add_control(
      'fields_color',
      [
        'label' => __( 'Text Color', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#000000',
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 p.nd_booking_field_search' => 'color: {{VALUE}} !important',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test p.nd_booking_field_search' => 'color: {{VALUE}} !important',
        ],
      ]
    );

    $this->add_control(
      'fields_bgcolor',
      [
        'label' => __( 'Background Color', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#ffffff',
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .nd_booking_section_box_search_field' => 'background-color: {{VALUE}} !important',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test .nd_booking_section_box_search_field' => 'background-color: {{VALUE}} !important',
        ],
      ]
    );

    $this->add_control(
      'fields_arrow_icon',
      [
        'label' => __( 'Arrow Icon', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::MEDIA,
        'default' => [
          'url' => \Elementor\Utils::get_placeholder_image_src(),
        ],
      ]
    );

    $this->add_control(
      'hr_fields_border',
      [
        'type' => \Elementor\Controls_Manager::DIVIDER,
      ]
    );


    $this->add_control(
      'fields_border_width',
      [
        'label' => __( 'Border Width', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => [ 'px' ],
        'default' => [
          'top' => 1,
          'right' => 1,
          'bottom' => 1,
          'left' => 1,
        ],
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .nd_booking_section_box_search_field' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test .nd_booking_section_box_search_field' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
      ]
    );

    $this->add_control(
      'fields_border_color',
      [
        'label' => __( 'Border Color', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#f1f1f1',
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .nd_booking_section_box_search_field' => 'border-color: {{VALUE}}',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test .nd_booking_section_box_search_field' => 'border-color: {{VALUE}}',
        ],
      ]
    );

    $this->add_control(
      'fields_border_radius',
      [
        'label' => __( 'Border Radius', 'nd-booking' ),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'size_units' => [ 'px' ],
        'range' => [
          'px' => [
            'min' => 0,
            'max' => 100,
            'step' => 1,
          ],
        ],
        'default' => [
          'unit' => 'px',
          'size' => 0,
        ],
        'selectors' => [
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .nd_booking_section_box_search_field' => 'border-radius: {{SIZE}}px;',
          '{{WRAPPER}} .nd_booking_search_elem_component_l2 .test .nd_booking_section_box_search_field' => 'border-radius: {{SIZE}}px;',
        ],
      ]
    );

    $this->end_controls_section();


  }
  //END CONTROLS


 
  /*START RENDER*/
  protected function render() {

    $nd_booking_result = '';

    //script for calendar
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('nd_booking_search_guests', esc_url(plugins_url('js/search_guests.js', __FILE__ )) );
    wp_enqueue_style('jquery-ui-datepicker-css', esc_url(plugins_url('css/jquery-ui-datepicker.css', __FILE__ )) );

    //get datas
    $nd_booking_settings = $this->get_settings_for_display();
    $search_layout = $nd_booking_settings['search_layout'];
    $nd_booking_fields_arrow_icon = $nd_booking_settings['fields_arrow_icon']['url'];

    //get variables
    $nd_booking_class = '';
    $nd_booking_action = ''; if ( $nd_booking_action == '' ) { $nd_booking_action = nd_booking_search_page(); }else{ $nd_booking_action = get_the_permalink($nd_booking_action); }
    $nd_booking_submit_padding = '';
    $nd_booking_submit_bg = '';;
    $nd_booking_archive_form_guests = '';

    //date options
    $nd_booking_date_number_from_front = date('d');
    $nd_booking_date_month_from_front = date('M');
    $nd_booking_date_month_from_front = date_i18n('M');

    $nd_booking_date_tomorrow = new DateTime('tomorrow');
    $nd_booking_date_number_to_front = $nd_booking_date_tomorrow->format('d');
    $nd_booking_date_month_to_front = $nd_booking_date_tomorrow->format('M');
    $nd_booking_todayy = date('Y/m/d');
    $nd_booking_tomorroww = date('Y/m/d', strtotime($nd_booking_todayy.' + 1 days'));
    $nd_booking_date_month_to_front = date_i18n('M',strtotime($nd_booking_tomorroww));

    //default values
    if ($search_layout == '') { $search_layout = "layout-1"; }

    //get the layout selected    
    $nd_booking_layout_selected = dirname( __FILE__ ).'/layout/'.$search_layout.'.php';
    include realpath($nd_booking_layout_selected);

    $nd_booking_allowed_html = [
      'div'      => [ 
        'class' => [],
        'id' => [],
      ],
      'form'      => [ 
        'action' => [],
        'method' => [],
      ],
      'h6'      => [ 
        'class' => [],
        'id' => [],
      ],
      'h1'      => [ 
        'id' => [],
        'class' => [],
      ],
      'img'      => [ 
        'alt' => [],
        'width' => [],
        'src' => [],
        'class' => [],
        'style' => [],
      ],
      'input'      => [ 
        'type' => [],
        'id' => [],
        'class' => [],
        'placeholder' => [],
        'name' => [],
        'value' => [],
        'min' => [],
        'style' => [],
      ],          
      'script'      => [ 
        'type' => [],
      ],          
      'label'      => [ 
        'class' => [],
        'for' => [],
      ],
      'p'      => [ 
        'id' => [],
        'class' => [],
      ],
    ];

    echo wp_kses( $nd_booking_result, $nd_booking_allowed_html );

  }
  //END RENDER


}
//END ELEMENT POST GRID
