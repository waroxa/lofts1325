<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'ms_theme_editor_parent_css' ) ):
    function ms_theme_editor_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array(  ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'ms_theme_editor_parent_css', 10 );

/**
 * Enqueue child theme styles.
 */
function marina_child_enqueue_custom_assets() {
    wp_enqueue_style(
        'marina-child-header-fixes',
        get_stylesheet_directory_uri() . '/css/header-fixes.css',
        array( 'chld_thm_cfg_parent' ),
        '20241009'
    );
}
add_action( 'wp_enqueue_scripts', 'marina_child_enqueue_custom_assets', 20 );

/**
 * Load the elevated search experience styles when needed.
 */
function marina_child_enqueue_search_styles() {
    $should_enqueue = is_search();

    if ( ! $should_enqueue && is_page() ) {
        $page = get_post();

        if ( $page instanceof WP_Post ) {
            if ( has_shortcode( $page->post_content, 'nd_booking_search_results' ) ) {
                $should_enqueue = true;
            }
        }
    }

    if ( ! $should_enqueue ) {
        $nd_booking_query_params = array(
            'nd_booking_archive_form_date_range_from',
            'nd_booking_archive_form_date_range_to',
            'nd_booking_archive_form_guests',
            'nd_booking_archive_form_services',
            'nd_booking_archive_form_additional_services',
            'nd_booking_archive_form_branch_stars',
            'nd_booking_archive_form_branches',
            'nd_booking_archive_form_max_price_for_day',
        );

        foreach ( $nd_booking_query_params as $query_param ) {
            if ( isset( $_GET[ $query_param ] ) && '' !== $_GET[ $query_param ] ) {
                $should_enqueue = true;
                break;
            }
        }
    }

    if ( ! $should_enqueue ) {
        return;
    }

    wp_enqueue_style(
        'marina-child-search-results',
        get_stylesheet_directory_uri() . '/css/search-results.css',
        array( 'marina-child-header-fixes' ),
        '20241010'
    );

    $GLOBALS['marina_child_search_styles_enqueued'] = true;
}
add_action( 'wp_enqueue_scripts', 'marina_child_enqueue_search_styles', 25 );

/**
 * Output console diagnostics confirming the search stylesheet loads for administrators.
 */
function marina_child_output_search_style_debug_marker() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! wp_style_is( 'marina-child-search-results', 'enqueued' ) ) {
        return;
    }

    ?>
    <script>
        (function () {
            var handle = 'marina-child-search-results-css';
            var stylesheet = document.getElementById(handle);

            if (stylesheet && stylesheet.href) {
                console.info('[Marina Child] Search stylesheet detected:', stylesheet.href);
            } else {
                console.warn('[Marina Child] Search stylesheet handle enqueued, but link element not found.');
            }
        })();
    </script>
    <?php
}
add_action( 'wp_footer', 'marina_child_output_search_style_debug_marker', 100 );

// END ENQUEUE PARENT ACTION


/**
 * Output global Loft 1325 keyword meta tags.
 */
function loft1325_output_global_meta_keywords() {
    // Skip if All in One SEO already renders meta keywords for this request.
    if ( function_exists( 'aioseo' ) ) {
        $aioseo_instance = aioseo();

        if ( is_object( $aioseo_instance ) && isset( $aioseo_instance->meta ) ) {
            $meta = $aioseo_instance->meta;

            if ( is_object( $meta ) && isset( $meta->metaData ) && is_object( $meta->metaData ) ) {
                $meta_data = $meta->metaData;

                if (
                    ( property_exists( $meta_data, 'keywords' ) && ! empty( $meta_data->keywords ) ) ||
                    ( method_exists( $meta_data, 'keywords' ) && ! empty( $meta_data->keywords() ) )
                ) {
                    return;
                }
            }
        }
    }

    $english_keywords = array(
        'Loft 1325',
        'Lofts 1325',
        'Le 1325',
        'Loft1325',
        'Lofts1325',
        'Loft 1325 Val-d’Or',
        'Lofts 1325 Val-d’Or',
        'Loft 1325 Québec',
        'Lofts 1325 Québec',
        'Loft 1325 Abitibi',
        'Loft 1325 Val-d\'Or Quebec Canada',
        'Loft 1325 hotel Val-d’Or',
        'Loft 1325 apartments Val-d’Or',
        'Loft 1325 rentals Val-d’Or',
        'Loft 1325 Airbnb Val-d’Or',
        'Loft 1325 corporate rentals Val-d’Or',
        'Loft 1325 tourist home Val-d’Or',
        'Loft 1325 short-term rentals Val-d’Or',
        'Loft 1325 furnished apartments Val-d’Or',
        'Loft 1325 long-term stay Val-d’Or',
        'Loft 1325 furnished rentals Val-d’Or',
        'Loft 1325 vacation rentals Val-d’Or',
    );

    $french_keywords = array(
        'Loft 1325 hôtel Val-d’Or',
        'Loft 1325 appartements meublés Val-d’Or',
        'Loft 1325 location court terme Val-d’Or',
        'Loft 1325 hébergement touristique Val-d’Or',
        'Loft 1325 location longue durée Val-d’Or',
    );

    $all_keywords = array_unique( array_map( 'trim', array_merge( $english_keywords, $french_keywords ) ) );

    if ( empty( $all_keywords ) ) {
        return;
    }

    printf(
        "<meta name=\"keywords\" content=\"%s\" />\n",
        esc_attr( implode( ', ', $all_keywords ) )
    );
}
add_action( 'wp_head', 'loft1325_output_global_meta_keywords', 1 );


//function BUTTERFLYMX
function encolar_scripts_listar_tenants() {
    wp_enqueue_script('listar-tenants-js', get_stylesheet_directory_uri() . '/js/listar-tenants.js', array('jquery'), '1.0', true);
    wp_localize_script('listar-tenants-js', 'ajaxurl', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'encolar_scripts_listar_tenants');

function boton_listar_tenants() {
    return '<button id="listarTenantsBtn">Listar Tenants</button><div id="resultadoTenants"></div>';
}
add_shortcode('boton_listar_tenants', 'boton_listar_tenants');

function listar_tenants_building() {
    $plugin_instance = new IntegracionButterflyMX();
    $building_id = isset($_GET['building_id']) ? intval($_GET['building_id']) : 60892; // Default to 60892
    $response = $plugin_instance->get_tenants_by_building($building_id);

    if (is_wp_error($response)) {
        error_log('Error al listar tenants: ' . $response->get_error_message());
        wp_send_json_error('Error al listar tenants: ' . $response->get_error_message());
    } else {
        error_log('Tenants obtenidos correctamente.');
        wp_send_json_success($plugin_instance->format_tenants($response));
    }
}
add_action('wp_ajax_listar_tenants_building', 'listar_tenants_building');
add_action('wp_ajax_nopriv_listar_tenants_building', 'listar_tenants_building');

update_option('loft_booking_cleaning_calendar_id', 'e964e301b54d0e795b44a76ebfb9d2cfbd2f6517a822429c5af62bc2cb94de20@group.calendar.google.com');
update_option('loft_booking_calendar_id', 'a752f27cffee8c22988adb29fdc933c93184e3a5814c79dcee4f62115d69fbfd@group.calendar.google.com');

// add_action('nd_booking_after_booking_completed', 'handle_successful_booking', 10, 1);



// function handle_successful_booking($booking_id) {
//     global $wpdb;

//     // Fetch booking from custom booking table
//     $booking = $wpdb->get_row(
//         $wpdb->prepare("SELECT * FROM {$wpdb->prefix}nd_booking_booking WHERE id = %d", $booking_id)
//     );

//     if (!$booking) {
//         error_log("❌ Booking ID {$booking_id} not found in nd_booking_booking table.");
//         return;
//     }

//     // Extract info
//     $room_id      = $booking->id_post;
//     $room_type    = strtoupper($booking->title_post); // OCCUPATION SIMPLE, DOUBLE, PENTHOUSE
//     $first_name   = $booking->user_first_name;
//     $last_name    = $booking->user_last_name;
//     $email        = $booking->paypal_email;
//     $checkin      = $booking->date_from;
//     $checkout     = $booking->date_to;

//     // Normalize room type to match loft label syntax
//     if (stripos($room_type, 'SIMPLE') !== false) $room_type = 'SIMPLE';
//     if (stripos($room_type, 'DOUBLE') !== false) $room_type = 'DOUBLE';
//     if (stripos($room_type, 'PENTHOUSE') !== false) $room_type = 'PENTHOUSE';

//     // Step 1: Find matching available loft
//     $loft = find_first_available_loft_unit($room_type);

//     if (!$loft) {
//         error_log("❌ No available loft unit found for type: $room_type");
//         return;
//     }

//     // Step 2: Create tenant in ButterflyMX
//     $tenant_id = create_butterflymx_tenant($loft->id, $email, $first_name, $last_name);

//     if (!$tenant_id) {
//         error_log("❌ Failed to create ButterflyMX tenant for {$email}");
//         return;
//     }

//     // Step 3: Create virtual key / visitor pass
//     $created = create_butterflymx_visitor_pass($loft->id, $email, $checkin, $checkout);

//     if (!$created) {
//         error_log("❌ Failed to create visitor pass for {$email}");
//         return;
//     }

//     // Step 4: Google Calendar entry
//     add_booking_to_google_calendar("Booking for $first_name $last_name", $checkin, $checkout);

//     // // Step 5: Cleaning task (1 hour after checkout)
//     // $cleaning_time = date('Y-m-d H:i:s', strtotime($checkout . ' +1 hour'));
//     // schedule_cleaning_task("Cleaning: {$loft->unit_name}", $cleaning_time);

//     error_log("✅ Booking automation completed for $email");
// }








