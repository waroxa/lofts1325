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

// END ENQUEUE PARENT ACTION


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








