<?php
// Additional services section
if ( get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_additional_services', true ) != '' ) {

    $nd_booking_additional_services  = '';
    $nd_booking_additional_services .= '<section class="loft-form-section loft-section-addons">';
    $nd_booking_additional_services .= '  <h2><span aria-hidden="true">✨</span> ' . esc_html__( 'Add extra services', 'nd-booking' ) . '</h2>';
    $nd_booking_additional_services .= '  <p class="loft-help-text">' . esc_html__( 'Select the extras you would like us to arrange for your stay.', 'nd-booking' ) . '</p>';
    $nd_booking_additional_services .= '  <div class="loft-addon-grid">';

    $nd_booking_meta_box_additional_services_array = explode( ',', get_post_meta( $nd_booking_form_booking_id, 'nd_booking_meta_box_additional_services', true ) );

    for ( $nd_booking_meta_box_additional_services_array_i = 0; $nd_booking_meta_box_additional_services_array_i < count( $nd_booking_meta_box_additional_services_array ) - 1; $nd_booking_meta_box_additional_services_array_i++ ) {

        $nd_booking_page_by_path = get_page_by_path( $nd_booking_meta_box_additional_services_array[ $nd_booking_meta_box_additional_services_array_i ], OBJECT, 'nd_booking_cpt_2' );
        if ( ! $nd_booking_page_by_path instanceof WP_Post ) {
            continue;
        }

        $nd_booking_service_id      = $nd_booking_page_by_path->ID;
        $nd_booking_service_name    = get_the_title( $nd_booking_service_id );
        $nd_booking_service_content = get_post_field( 'post_content', $nd_booking_service_id );

        $nd_booking_meta_box_cpt_2_service_type = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_service_type', true );
        if ( '' === $nd_booking_meta_box_cpt_2_service_type ) {
            $nd_booking_meta_box_cpt_2_service_type = 'nd_booking_normal_service';
        }

        $nd_booking_meta_box_cpt_2_price       = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_price', true );
        $nd_booking_meta_box_cpt_2_price_type_1 = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_price_type_1', true );
        if ( '' === $nd_booking_meta_box_cpt_2_price_type_1 ) {
            $nd_booking_meta_box_cpt_2_price_type_1 = 'nd_booking_price_type_person';
        }
        $nd_booking_meta_box_cpt_2_price_type_2 = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_price_type_2', true );
        if ( '' === $nd_booking_meta_box_cpt_2_price_type_2 ) {
            $nd_booking_meta_box_cpt_2_price_type_2 = 'nd_booking_price_type_day';
        }

        $nd_booking_meta_box_cpt_2_mandatory          = get_post_meta( $nd_booking_service_id, 'nd_booking_meta_box_cpt_2_mandatory', true );
        $nd_booking_meta_box_cpt_2_mandatory_result   = '';
        $nd_booking_mandatory_chip_markup             = '';
        $nd_booking_mandatory_chip_style              = '';
        $nd_booking_mandatory_chip_service_text_style = '';

        if ( 'nd_booking_price_type_mandatory_yes' === $nd_booking_meta_box_cpt_2_mandatory ) {
            $nd_booking_meta_box_cpt_2_mandatory_result = 'checked disabled';
            $nd_booking_customizer_color_3              = get_option( 'nd_booking_customizer_color_3', '#d34949' );
            $nd_booking_mandatory_chip_style            = 'background-color: ' . esc_attr( $nd_booking_customizer_color_3 ) . ';';
        } elseif ( 'nd_booking_price_type_mandatory_yes_edit' === $nd_booking_meta_box_cpt_2_mandatory ) {
            $nd_booking_meta_box_cpt_2_mandatory_result = 'checked';
        }

        if ( $nd_booking_meta_box_cpt_2_mandatory_result ) {
            $nd_booking_mandatory_chip_markup = '<span class="loft-addon-mandatory" style="' . $nd_booking_mandatory_chip_style . '">' . esc_html__( 'Required', 'nd-booking' ) . '</span>';
        }

        if ( 'nd_booking_price_type_person' === $nd_booking_meta_box_cpt_2_price_type_1 ) {
            $nd_booking_operator_1 = $nd_booking_form_booking_guests;
            $nd_booking_word_1     = __( 'Guest', 'nd-booking' );
        } else {
            $nd_booking_operator_1 = 1;
            $nd_booking_word_1     = __( 'Room', 'nd-booking' );
        }

        if ( 'nd_booking_price_type_day' === $nd_booking_meta_box_cpt_2_price_type_2 ) {
            $nd_booking_operator_2 = nd_booking_get_number_night( $nd_booking_date_from, $nd_booking_date_to );
            $nd_booking_word_2     = __( 'Night', 'nd-booking' );
        } else {
            $nd_booking_operator_2 = 1;
            $nd_booking_word_2     = __( 'Trip', 'nd-booking' );
        }

        $nd_booking_additional_service_total_price = floatval( $nd_booking_meta_box_cpt_2_price ) * $nd_booking_operator_1 * $nd_booking_operator_2;

        $nd_booking_additional_services .= '<label class="loft-addon-item">';
        $nd_booking_additional_services .= '  <span class="loft-addon-item__header">';
        $nd_booking_additional_services .= '      <input ' . $nd_booking_meta_box_cpt_2_mandatory_result . ' data-id="' . esc_attr( $nd_booking_service_id . ',' ) . '" class="nd_booking_booking_checkbox_service" type="checkbox" value="' . esc_attr( $nd_booking_additional_service_total_price . ',' ) . '">';
        $nd_booking_additional_services .= '      <span class="loft-addon-item__title" style="' . $nd_booking_mandatory_chip_service_text_style . '">' . esc_html( $nd_booking_service_name ) . '</span>';
        $nd_booking_additional_services .=        $nd_booking_mandatory_chip_markup;
        $nd_booking_additional_services .= '  </span>';

        if ( '' !== $nd_booking_service_content ) {
            $nd_booking_additional_services .= '  <span class="loft-addon-item__description">' . wp_kses_post( wp_trim_words( $nd_booking_service_content, 30 ) ) . '</span>';
        }

        $nd_booking_additional_services .= '  <span class="loft-addon-item__price">' . esc_html( $nd_booking_meta_box_cpt_2_price ) . ' ' . esc_html( nd_booking_get_currency() ) . ' · ' . esc_html( $nd_booking_word_1 ) . ' / ' . esc_html( $nd_booking_word_2 ) . '</span>';
        $nd_booking_additional_services .= '  <span class="loft-addon-item__total">' . esc_html( nd_booking_format_decimal( $nd_booking_additional_service_total_price ) ) . ' ' . esc_html( nd_booking_get_currency() ) . '</span>';
        $nd_booking_additional_services .= '</label>';

        if ( 'nd_booking_price_type_mandatory_yes' === $nd_booking_meta_box_cpt_2_mandatory || 'nd_booking_price_type_mandatory_yes_edit' === $nd_booking_meta_box_cpt_2_mandatory ) {
            $nd_booking_additional_services .= '<script type="text/javascript">jQuery(function($){var value="' . esc_js( $nd_booking_additional_service_total_price . ',' ) . '";var previous=$("#nd_booking_booking_checkbox_services").val();$("#nd_booking_booking_checkbox_services").val(value+previous);var idValue="' . esc_js( $nd_booking_service_id . ',' ) . '";var idPrevious=$("#nd_booking_booking_checkbox_services_id").val();$("#nd_booking_booking_checkbox_services_id").val(idValue+idPrevious);});</script>';
        }
    }

    $nd_booking_additional_services .= '  </div>';
    $nd_booking_additional_services .= '  <input type="hidden" id="nd_booking_booking_checkbox_services" name="nd_booking_booking_checkbox_services" readonly value="">';
    $nd_booking_additional_services .= '</section>';

    $nd_booking_additional_services .= '<script type="text/javascript">jQuery(function($){nd_booking_final_price();$(".nd_booking_booking_checkbox_service").on("change",function(){var value=$(this).val();var current=$("#nd_booking_booking_checkbox_services").val();var idValue=$(this).data("id");var currentIds=$("#nd_booking_booking_checkbox_services_id").val();if($(this).is(":checked")){ $("#nd_booking_booking_checkbox_services").val(value+current); $("#nd_booking_booking_checkbox_services_id").val(idValue+currentIds); }else{ $("#nd_booking_booking_checkbox_services").val(current.replace(value,"")); $("#nd_booking_booking_checkbox_services_id").val(currentIds.replace(idValue+"","")); } nd_booking_final_price();});});</script>';
}

