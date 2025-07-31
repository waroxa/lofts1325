<?php 

//get all datas
$nd_options_customizer_footer_5_content_page = get_option( 'nd_options_customizer_footer_5_content_page' );
if ( $nd_options_customizer_footer_5_content_page == '' ) { $nd_options_customizer_footer_5_content_page = '';  }

?>


<!--START footer-->
<div id="nd_options_footer_5" class="nd_options_section">

    <!--start nd_options_container-->
    <div class="nd_options_container nd_options_clearfix">

        <?php

        if ( $nd_options_customizer_footer_5_content_page != '') {

            $args = array(
                'post_type' => 'page',
                'p' => $nd_options_customizer_footer_5_content_page,
            );
            $the_query = new WP_Query( $args );

            while ( $the_query->have_posts() ) : $the_query->the_post();

                the_content();

            endwhile;

            wp_reset_postdata();

            $nd_options_post   = get_post($nd_options_customizer_footer_5_content_page);
            $nd_options_strings  = $nd_options_post->post_content;
            $nd_options_pieces = explode('css=".vc_custom_', $nd_options_strings);
            
            //get how many styles inserted
            $nd_options_qnt_styles = count($nd_options_pieces)-1;
            $nd_options_allowed_html_shortcodes = ['style' => [],];

            $nd_options_output_style_open = '<style>';
            $nd_options_output_style_close = '</style>';
            
            // style
            echo wp_kses( $nd_options_output_style_open, $nd_options_allowed_html_shortcodes );
            for ($nd_options_i = 1; $nd_options_i <= $nd_options_qnt_styles; $nd_options_i++) {
                $tests = explode(';}"][', $nd_options_pieces[$nd_options_i]);
                $nd_options_output_style_rule = '.vc_custom_'.$tests[0].';}';
                echo wp_kses( $nd_options_output_style_rule, $nd_options_allowed_html_shortcodes );
            }
            echo wp_kses( $nd_options_output_style_close, $nd_options_allowed_html_shortcodes );

        }

        ?>

    </div>
    <!--end container-->

</div>
<!--END footer-->