<?php
/*
Plugin Name: MotoPress Hotel Booking Custom Filters
Description: Añade un filtro por categoría en el calendario de reservas.
Version: 1.0
Author: Tu Nombre
*/

// Agrega un filtro de categoría en el formulario del calendario.
add_action( 'mphb_bookings_calendar_filters', 'añadir_filtro_categoria_calendario', 10 );

function añadir_filtro_categoria_calendario() {
    // Usa 'mphb_room_type_category' como el slug de la taxonomía de categorías de alojamiento.
    $categorias = get_terms( array(
        'taxonomy'   => 'mphb_room_type_category', // Confirma que este es el slug correcto de la taxonomía.
        'hide_empty' => false,
    ) );

    if ( ! empty( $categorias ) && ! is_wp_error( $categorias ) ) {
        ?>
        <label for="filtro_categoria_alojamiento" style="margin-left: 10px;"><?php esc_html_e( 'Category', 'motopress-hotel-booking' ); ?></label>
        <select name="categoria_alojamiento" id="filtro_categoria_alojamiento">
            <option value=""><?php esc_html_e( 'All Categories', 'motopress-hotel-booking' ); ?></option>
            <?php foreach ( $categorias as $categoria ) : ?>
                <option value="<?php echo esc_attr( $categoria->slug ); ?>" <?php selected( isset( $_GET['categoria_alojamiento'] ) ? $_GET['categoria_alojamiento'] : '', $categoria->slug ); ?>>
                    <?php echo esc_html( $categoria->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}

// Modifica los argumentos de la consulta del calendario para filtrar por la categoría seleccionada.
add_filter( 'mphb_bookings_calendar_query_args', 'filtrar_calendario_por_categoria', 10, 1 );

function filtrar_calendario_por_categoria( $query_args ) {
    if ( isset( $_GET['categoria_alojamiento'] ) && ! empty( $_GET['categoria_alojamiento'] ) ) {
        $categoria_slug = sanitize_text_field( wp_unslash( $_GET['categoria_alojamiento'] ) );

        // Obtener los IDs de los tipos de alojamiento que pertenecen a la categoría seleccionada.
        $alojamientos = get_posts( array(
            'post_type'      => 'mphb_room_type',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'mphb_room_type_category', // Usa 'mphb_room_type_category' como el slug.
                    'field'    => 'slug',
                    'terms'    => $categoria_slug,
                ),
            ),
        ) );

        if ( ! empty( $alojamientos ) ) {
            $query_args['post__in'] = $alojamientos;
        } else {
            // Si no hay alojamientos en la categoría, asegurarse de que no se muestren resultados.
            $query_args['post__in'] = array( 0 );
        }
    }

    return $query_args;
}
