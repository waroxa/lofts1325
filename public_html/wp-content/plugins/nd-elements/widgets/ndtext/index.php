<?php


//START ELEMENT POST GRID
class nd_elements_ndtext_element extends \Elementor\Widget_Base {

	public function get_name() { return 'ndtext'; }
	public function get_title() { return __( 'ND Text', 'nd-elements' ); }
	public function get_icon() { return 'eicon-t-letter'; }
	public function get_categories() { return [ 'nd-elements' ]; }

	
	/*START CONTROLS*/
	protected function _register_controls() {

	
		/*Create Tab*/
		$this->start_controls_section(
			'ndtext_content_section',
			[
				'label' => __( 'Main Options', 'nd-elements' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

	    $this->add_control(
			'ndtext_title',
			[
				'label' => __( 'Title', 'nd-elements' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Title', 'nd-elements' ),
				'placeholder' => __( 'Type your title', 'nd-elements' ),
			]
		);


		$this->add_control(
			'ndtext_tag',
			[
				'label' => esc_html__( 'HTML Tag', 'nd-elements' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'h1',
				'options' => [
					'h1' => esc_html__( 'H1', 'nd-elements' ),
					'h2' => esc_html__( 'H2', 'nd-elements' ),
					'h3' => esc_html__( 'H3', 'nd-elements' ),
					'h4' => esc_html__( 'H4', 'nd-elements' ),
					'h5' => esc_html__( 'H5', 'nd-elements' ),
					'h6' => esc_html__( 'H6', 'nd-elements' ),
				],
			]
		);


		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'ndtext_background',
				'types' => [ 'classic', 'gradient'],
				'selector' => '{{WRAPPER}} .nd_elements_ndtext_component .nd_elements_ndtext_component_imagebg',
			]
		);

		$this->end_controls_section();





		$this->start_controls_section(
			'ndtext_style_section',
			[
				'label' => __( 'Title', 'nd-elements' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);


		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'ndtext_title_typo',
				'label' => __( 'Typography', 'nd-elements' ),
				'selector' => '{{WRAPPER}} 
					.nd_elements_ndtext_component .nd_elements_ndtext_component_text',
			]
		);


		$this->add_control(
			'ndtext_align',
			[
				'label' => esc_html__( 'Alignment', 'nd-elements' ),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'nd-elements' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'nd-elements' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'nd-elements' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'default' => 'left',
				'toggle' => true,
				'selectors' => [
					'{{WRAPPER}} .nd_elements_ndtext_component .nd_elements_ndtext_component_text' => 'text-align: {{VALUE}};',
				],
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
		$ndtext_title = $nd_elements_settings['ndtext_title'];
		$ndtext_tag = $nd_elements_settings['ndtext_tag'];
		
		//START
		$nd_elements_result .= '
		<div class="nd_elements_section nd_elements_ndtext_component nd_elements_margin_0 nd_elements_padding_0">
			
			<div class="nd_elements_section nd_elements_ndtext_component_imagebg nd_elements_margin_0 nd_elements_padding_0">

				<div class="nd_elements_section nd_elements_ndtext_component_whitebg nd_elements_mix_blend_mode_screen nd_elements_background_color_fff nd_elements_margin_0 nd_elements_padding_0">
			
					<div class="nd_elements_section nd_elements_ndtext_component_blacktext nd_elements_margin_0 nd_elements_padding_0">
			
						<'.$ndtext_tag.' class="nd_elements_ndtext_component_text nd_elements_color_000 nd_elements_margin_0 nd_elements_padding_0">'.$ndtext_title.'</'.$ndtext_tag.'>
			
					</div>

				</div>

			</div>

		</div>';
		//END

	
  		$nd_elements_allowed_html = [
		    'div'      => [ 
				'class' => [],
			],
			'br'      => [ 
				'class' => [],
			],
			'h1'      => [
				'class' => [],
			],
			'h2'      => [
				'class' => [],
			],
			'h3'      => [
				'class' => [],
			],
			'h4'      => [
				'class' => [],
			],
			'h5'      => [
				'class' => [],
			],
			'h6'      => [
				'class' => [],
			],
		];

		echo wp_kses( $nd_elements_result, $nd_elements_allowed_html );

	}
	//END RENDER


}
//END ELEMENT POST GRID
