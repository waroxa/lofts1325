<?php


//START ELEMENT EVENTS GRID
class nd_elements_eventsgrid_element extends \Elementor\Widget_Base {

	public function get_name() { return 'eventsgrid'; }
	public function get_title() { return __( 'Events Grid', 'nd-elements' ); }
	public function get_icon() { return 'fa fa-calendar'; }
	public function get_categories() { return [ 'nd-elements' ]; }

	
	/*START CONTROLS*/
	protected function _register_controls() {

	
		/*Create Tab*/
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Main Options', 'nd-elements' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
	      'eventsgrid_layout',
	      [
	        'label' => __( 'Layout', 'nd-elements' ),
	        'type' => \Elementor\Controls_Manager::SELECT,
	        'default' => 'layout-1',
	        'options' => [
	          'layout-1'  => __( 'Layout 1', 'nd-elements' ),
	          'layout-2' => __( 'Layout 2', 'nd-elements' ),
	        ],
	      ]
	    );

		$this->add_control(
			'eventsgrid_order',
			[
				'label' => __( 'Order', 'nd-elements' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'DESC'  => __( 'DESC', 'nd-elements' ),
					'ASC' => __( 'ASC', 'nd-elements' ),
				],
			]
		);

		$this->add_control(
			'eventsgrid_qnt',
			[
				'label' => __( 'Events Per Page', 'nd-elements' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => -1,
				'max' => 20,
				'step' => 1,
				'default' => 3,
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'style_section',
			[
				'label' => __( 'Style', 'nd-elements' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);


		$this->add_control(
			'eventsgrid_color_btn',
			[
				'label' => __( 'Button Color', 'nd-elements' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#000000',
			]
		);

		$this->add_control(
			'eventsgrid_color_time',
			[
				'label' => __( 'Time Color', 'nd-elements' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#000000',
			]
		);

		$this->end_controls_section();
		

	}
	//END CONTROLS


 
	/*START RENDER*/
	protected function render() {

		$nd_elements_result = '';

  		//get datas
  		$nd_elements_settings = $this->get_settings_for_display();
		$nd_elements_eventsgrid_order = $nd_elements_settings['eventsgrid_order'];
		$eventsgrid_qnt = $nd_elements_settings['eventsgrid_qnt'];
		$eventsgrid_color_btn = $nd_elements_settings['eventsgrid_color_btn'];
		$eventsgrid_color_time = $nd_elements_settings['eventsgrid_color_time'];
		$eventsgrid_layout = $nd_elements_settings['eventsgrid_layout'];

		//default values
		if ($eventsgrid_qnt == '') { $eventsgrid_qnt = 3; }
		if ($nd_elements_eventsgrid_order == '') { $nd_elements_eventsgrid_order = 'DESC'; }
		

		//args
		$args = array(
			'post_type'=>'tribe_events',
			'posts_per_page' => $eventsgrid_qnt,
			'order' => $nd_elements_eventsgrid_order,
			'orderby' => 'event_date',
		);
		$the_query = new WP_Query( $args );


		//default values
		if ($eventsgrid_layout == '') { $eventsgrid_layout = "layout-1"; }
		$nd_elements_number_layout_selected = str_replace('layout-','', $eventsgrid_layout);

		//check with realpath
		$eventsgrid_layout = sanitize_key($eventsgrid_layout);
  		$nd_elements_layout_selected = dirname( __FILE__ ).'/layout/'.$eventsgrid_layout.'.php';
  		$nd_elements_string_layout_selected = '/layout/layout-'.$nd_elements_number_layout_selected.'.php';

  		if ( $nd_elements_number_layout_selected != '' ) {

  			if ( str_contains($nd_elements_layout_selected, $nd_elements_string_layout_selected) ) {
	  			include realpath($nd_elements_layout_selected);
	  		}

  		}

  		wp_reset_postdata();

		$nd_elements_allowed_html = [
			'div'      => [ 
			    'class' => [],
			    'id' => [],
			    'style' => [],
			],
			'a'      => [
			    'style' => [],
			    'href' => [],
			    'class' => [],
			],
			'h2'      => [
			    'class' => [],
			    'style' => [],
			],
			'strong'      => [
			    'class' => [],
			    'style' => [],
			],
			'h4'      => [
			    'class' => [],
			    'style' => [],
			],
			'h3'      => [
			    'class' => [],
			    'style' => [],
			],
			'h6'      => [
			    'class' => [],
			    'style' => [],
			],
			'span'      => [
			    'style' => [],
			    'class' => [],
			],
			'p'      => [
			    'class' => [],
			    'style' => [],
			],
			'img'      => [
			    'width' => [],
			    'class' => [],
			    'alt' => [],
			    'src' => [],
			],
		];

		echo wp_kses( $nd_elements_result, $nd_elements_allowed_html );

	}
	//END RENDER


}
//END ELEMENT EVENTS GRID
