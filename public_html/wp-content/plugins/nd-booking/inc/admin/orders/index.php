<?php

add_action('admin_menu','nd_booking_add_settings_menu_orders');
function nd_booking_add_settings_menu_orders(){

  add_submenu_page( 'nd-booking-settings','Orders', __('All Orders','nd-booking'), 'manage_options', 'nd-booking-settings-orders', 'nd_booking_settings_menu_orders' );

  //custom hook
  do_action("nd_booking_add_menu_page_after_order");

}


function nd_booking_settings_menu_orders() {

  //custom hook
  do_action("nd_booking_before_orders_list");

?>

  
  <div class="nd_booking_section nd_booking_padding_right_20 nd_booking_padding_left_2 nd_booking_box_sizing_border_box nd_booking_margin_top_25 ">

    <?php nd_booking_get_table_orders(); ?> 

  </div>

<?php } 




function nd_booking_get_table_orders(){

  //START if
  if ( isset($_POST['edit_order_id']) OR isset($_POST['nd_booking_order_id'])  ) {
     
    include realpath(dirname( __FILE__ ).'/include/edit.php'); 

  }elseif ( isset($_POST['delete_order_id']) OR isset($_POST['nd_booking_delete_order_id']) ){

    include realpath(dirname( __FILE__ ).'/include/delete.php'); 
  
  }else{

    include realpath(dirname( __FILE__ ).'/include/orders.php'); 

  }
  //END if

  $nd_booking_allowed_html = [
    'div' => [
      'class' => [],
      'style' => [],
    ],  
    'h1' => [ 
      'class' => [],
      'style' => [],
    ],
    'ul' => [ 
      'class' => [],
    ],
    'li' => [
      'class' => [],
    ],
    'a' => [ 
      'href' => [],
      'class' => [],
      'style' => [],
      'download' => [],
    ],
    'span' => [ 
      'class' => [],
      'style' => [],
      'aria-hidden' => [],
    ],
    'style' => [],
    'h2' => [],
    'table' => [ 
      'class' => [],
    ],
    'tbody' => [],
    'tr' => [ 
      'class' => [],
    ],
    'td' => [ 
      'width' => [],
      'style' => [],
      'class' => [],
    ],
    'img' => [ 
      'class' => [],
      'width' => [],
      'src' => [],
    ],
    'form' => [ 
      'class' => [],
      'method' => [],
    ],
    'input' => [ 
      'type' => [],
      'name' => [],
      'value' => [],
      'class' => [],
      'readonly' => [],
    ],
    'strong' => [],
    'u' => [],
    'h3' => [
      'class' => [],
    ],
    'textarea' => [ 
      'name' => [],
      'class' => [],
      'rows' => [],
    ],
    'p' => [ 
      'class' => [],
    ],
    'h4' => [ 
      'class' => [],
    ],
    'label' => [ 
      'class' => [],
      'style' => [],
    ],
    'select' => [  
      'name' => [],
      'class' => [],
      'id' => [],
    ],
    'option' => [  
      'value' => [],
      'selected' => [],
    ],
    'script' => [
      'type' => [],
    ], 
  ];

  echo wp_kses( $nd_booking_result, $nd_booking_allowed_html );

}

include realpath(dirname( __FILE__ ).'/include/add.php'); 

