<?php


$nd_options_breadcrumbs_enable = get_option('nd_options_breadcrumbs_enable');

//START enable
if ( $nd_options_breadcrumbs_enable == 1 ) {


  //nd learning plugin compatibility
  add_action('nd_learning_end_header_img_single_course_hook','nd_options_create_breadcrumbs');
  add_action('nd_learning_end_header_img_archive_courses_hook','nd_options_create_breadcrumbs');
  add_action('nd_learning_end_header_img_single_teacher_hook','nd_options_create_breadcrumbs');
  add_action('nd_learning_end_header_img_archive_teachers_hook','nd_options_create_breadcrumbs');


  add_action('nd_options_end_header_img_page_hook','nd_options_create_breadcrumbs');
  add_action('nd_options_end_header_img_post_hook','nd_options_create_breadcrumbs');
  add_action('nd_options_end_header_img_search_hook','nd_options_create_breadcrumbs');
  add_action('nd_options_end_header_img_archive_hook','nd_options_create_breadcrumbs');
  function nd_options_create_breadcrumbs() {
    
    $nd_options_allowed_html = [
      'div' => [ 
        'id' => [],
        'class' => [],
      ],
      'a' => [ 
        'class' => [],
        'href' => [],
      ],
      'img' => [ 
        'alt' => [],
        'class' => [],
        'width' => [], 
        'height' => [],
        'src' => [],
      ],
      'span' => [ 
        'class' => [],
      ],
      'p' => [ 
        'class' => [],
      ],
    ];

    //recover page layout customizer
    $nd_options_customizer_page_layout = get_option( 'nd_options_customizer_page_layout' );
    $nd_options_customizer_post_layout = get_option( 'nd_options_customizer_post_layout' );
    $nd_options_customizer_archives_archive_layout = get_option( 'nd_options_customizer_archives_archive_layout' );
    $nd_options_customizer_archives_search_layout = get_option( 'nd_options_customizer_archives_search_layout' );
    
    //understand in which page we are..
    if ( is_page() ) {
      $nd_options_customizer_breadcrumbs_layout = $nd_options_customizer_page_layout;
    }elseif ( is_search() ){
      $nd_options_customizer_breadcrumbs_layout = $nd_options_customizer_archives_search_layout; 
    }elseif ( is_archive() ) {
      $nd_options_customizer_breadcrumbs_layout = $nd_options_customizer_archives_archive_layout; 
    }elseif ( is_single() ) {
      $nd_options_customizer_breadcrumbs_layout = $nd_options_customizer_post_layout;  
    }else{
      $nd_options_customizer_breadcrumbs_layout = 'layout-1';  
    }

    //set classes for different breadcrumbs layout 
    if ( $nd_options_customizer_breadcrumbs_layout == 'layout-3' ) { 

      $nd_options_breadcrumbs_img_color = 'white';
      $nd_options_breadcrumbs_container_classes = 'nd_options_text_align_center';
      $nd_options_breadcrumbs_link_classes = 'nd_options_color_white nd_options_color_white_first_a nd_options_letter_spacing_3 nd_options_font_weight_lighter nd_options_font_size_13 nd_options_text_transform_uppercase';

    }else{

      $nd_options_breadcrumbs_img_color = 'grey';
      $nd_options_breadcrumbs_container_classes = 'nd_options_bg_grey nd_options_border_bottom_1_solid_grey';
      $nd_options_breadcrumbs_link_classes = '';

    }

    //img color
    $nd_options_img_color_path = 'img/icon-next-'.$nd_options_breadcrumbs_img_color.'.svg';

    //variables
    $nd_options_delimiter = '<img alt="" class="nd_options_margin_left_10 nd_options_margin_right_10" width="10" height="10" src="'.esc_url(plugins_url($nd_options_img_color_path, __FILE__ )).'">';
    $nd_options_home = __('Home', 'nd-shortcodes');
    $nd_options_before = '<p class=" nd_options_display_inline_block nd_options_current_breadcrumb '.$nd_options_breadcrumbs_link_classes.' ">';
    $nd_options_after = '</p>';
    


    if ( !is_home() && !is_front_page() || is_paged() ) {
      
      global $post;


      //START
      $nd_options_output_bread_1 = '
      <div id="nd_options_breadcrumbs" class="nd_options_section '.$nd_options_breadcrumbs_container_classes.' ">

          <div class="nd_options_container nd_options_clearfix">

              <div class="nd_options_section nd_options_padding_15 nd_options_box_sizing_border_box">';

      echo wp_kses( $nd_options_output_bread_1, $nd_options_allowed_html );
    
      

      //Home
      $nd_options_home_link = home_url();
      $nd_options_output_bread_2 = '<a class="'.$nd_options_breadcrumbs_link_classes.'" href="' . $nd_options_home_link . '">' . $nd_options_home . '</a> ' . $nd_options_delimiter . ' ';
      echo wp_kses( $nd_options_output_bread_2, $nd_options_allowed_html );
      
      //Category
      if ( is_category() ) {
        global $wp_query;
        $cat_obj = $wp_query->get_queried_object();
        $thisCat = $cat_obj->term_id;
        $thisCat = get_category($thisCat);
        $parentCat = get_category($thisCat->parent);
        if ($thisCat->parent != 0) $nd_options_output_bread_3 = (get_category_parents($parentCat, TRUE, ' ' . $nd_options_delimiter . ' ')); echo wp_kses( $nd_options_output_bread_3, $nd_options_allowed_html );
        $nd_options_output_bread_4 = $nd_options_before . single_cat_title('', false) . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_4, $nd_options_allowed_html );
    
      } 

      //Day
      elseif ( is_day() ) {
        $nd_options_output_bread_5 = '<a class="'.$nd_options_breadcrumbs_link_classes.'" href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a> ' . $nd_options_delimiter . ' ';
        echo wp_kses( $nd_options_output_bread_5, $nd_options_allowed_html );
        $nd_options_output_bread_6 = '<a class="'.$nd_options_breadcrumbs_link_classes.'" href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a> ' . $nd_options_delimiter . ' ';
        echo wp_kses( $nd_options_output_bread_6, $nd_options_allowed_html );
        $nd_options_output_bread_7 = $nd_options_before . get_the_time('d') . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_7, $nd_options_allowed_html );

      } 


      //Month
      elseif ( is_month() ) {
        $nd_options_output_bread_8 = '<a class="'.$nd_options_breadcrumbs_link_classes.'" href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a> ' . $nd_options_delimiter . ' ';
        echo wp_kses( $nd_options_output_bread_8, $nd_options_allowed_html );
        $nd_options_output_bread_9 = $nd_options_before . get_the_time('F') . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_9, $nd_options_allowed_html );
      } 


      //Year
      elseif ( is_year() ) {
        $nd_options_output_bread_10 = $nd_options_before . get_the_time('Y') . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_10, $nd_options_allowed_html );
      } 


      //Post
      elseif ( is_single() && !is_attachment() ) {
        if ( get_post_type() != 'post' ) {
          $post_type = get_post_type_object(get_post_type());
          $slug = $post_type->rewrite;
          $nd_options_output_bread_11 = '<a class="'.$nd_options_breadcrumbs_link_classes.'" href="' . $nd_options_home_link . '/' . $slug['slug'] . '/">' . $post_type->labels->singular_name . '</a> ' . $nd_options_delimiter . ' ';
          echo wp_kses( $nd_options_output_bread_11, $nd_options_allowed_html );
          $nd_options_output_bread_12 = $nd_options_before . get_the_title() . $nd_options_after;
          echo wp_kses( $nd_options_output_bread_12, $nd_options_allowed_html );
        } else {
          $cat = get_the_category(); $cat = $cat[0];
          $nd_options_output_bread_13 = '<span class="'.$nd_options_breadcrumbs_link_classes.'">'.get_category_parents($cat, TRUE, ' ' . $nd_options_delimiter . '</span>');
          echo wp_kses( $nd_options_output_bread_13, $nd_options_allowed_html );
          $nd_options_output_bread_14 = $nd_options_before . get_the_title() . $nd_options_after;
          echo wp_kses( $nd_options_output_bread_14, $nd_options_allowed_html );
        }
    
      } 


      //post type
      elseif ( !is_single() && !is_page() && get_post_type() != 'post' && !is_404() ) {
        
        if ( get_post_type() != '' ) { 

          $post_type = get_post_type_object(get_post_type());
          $nd_options_output_bread_15 = $nd_options_before . $post_type->labels->singular_name . $nd_options_after;
          echo wp_kses( $nd_options_output_bread_15, $nd_options_allowed_html );

        }

      } 


      //Media
      elseif ( is_attachment() ) {
        $parent = get_post($post->post_parent);
        $cat = get_the_category($parent->ID); $cat = $cat[0];
        $nd_options_output_bread_16 = get_category_parents($cat, TRUE, ' ' . $nd_options_delimiter . ' ');
        echo wp_kses( $nd_options_output_bread_16, $nd_options_allowed_html );
        $nd_options_output_bread_17 = '<a class="'.$nd_options_breadcrumbs_link_classes.'" href="' . get_permalink($parent) . '">' . $parent->post_title . '</a> ' . $nd_options_delimiter . ' ';
        echo wp_kses( $nd_options_output_bread_17, $nd_options_allowed_html );
        $nd_options_output_bread_18 = $nd_options_before . get_the_title() . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_18, $nd_options_allowed_html );
    
      } 


      //
      elseif ( is_page() && !$post->post_parent ) {
        $nd_options_output_bread_19 = $nd_options_before . get_the_title() . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_19, $nd_options_allowed_html );
      } 


      //Page
      elseif ( is_page() && $post->post_parent ) {
        $parent_id  = $post->post_parent;
        $breadcrumbs = array();
        while ($parent_id) {
          $page = get_page($parent_id);
          $breadcrumbs[] = '<a class="'.$nd_options_breadcrumbs_link_classes.'" href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a>';
          $parent_id  = $page->post_parent;
        }
        $breadcrumbs = array_reverse($breadcrumbs);
        foreach ($breadcrumbs as $crumb) $nd_options_output_bread_20 = $crumb . ' ' . $nd_options_delimiter . ' '; echo wp_kses( $nd_options_output_bread_20, $nd_options_allowed_html );
        $nd_options_output_bread_21 = $nd_options_before . get_the_title() . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_21, $nd_options_allowed_html );
    
      } 


      //Search
      elseif ( is_search() ) {
        $nd_options_output_bread_22 = $nd_options_before . get_search_query() . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_22, $nd_options_allowed_html );
      } 


      //Tag
      elseif ( is_tag() ) {
        $nd_options_output_bread_23 = $nd_options_before . single_tag_title('', false) . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_23, $nd_options_allowed_html );
      } 


      //author
      elseif ( is_author() ) {
         global $author;
        $userdata = get_userdata($author);
        $nd_options_output_bread_24 = $nd_options_before . $userdata->display_name . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_24, $nd_options_allowed_html );
      } 


      //404
      elseif ( is_404() ) {
        $nd_options_output_bread_25 = $nd_options_before . 'Error 404' . $nd_options_after;
        echo wp_kses( $nd_options_output_bread_25, $nd_options_allowed_html );
      }
    
      
      //Pagination
      if ( get_query_var('paged') ) {
        if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) $nd_options_output_bread_26 = '<p class=" nd_options_display_inline_block '.$nd_options_breadcrumbs_link_classes.'"> - </p>'; echo wp_kses( $nd_options_output_bread_26, $nd_options_allowed_html );
        $nd_options_output_bread_27 = '<p class=" nd_options_display_inline_block '.$nd_options_breadcrumbs_link_classes.'" >'.esc_html__('Page', 'nd-shortcodes') . ' ' . get_query_var('paged').'</p>'; echo wp_kses( $nd_options_output_bread_27, $nd_options_allowed_html );
        if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) $nd_options_output_bread_28 = ' '; echo wp_kses( $nd_options_output_bread_28, $nd_options_allowed_html );
      }


    
      $nd_options_output_bread_29 = '
        </div>
      
      </div>

    </div>';
    echo wp_kses( $nd_options_output_bread_29, $nd_options_allowed_html );
    //END

    
    }
  }


}
//END enable