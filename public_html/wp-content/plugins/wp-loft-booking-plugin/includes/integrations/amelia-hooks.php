// wp-butterfly-plugin/includes/amelia_hooks.php
function trigger_amelia_booking_webhook($data) {
    // Example Amelia internal hook
    do_action('amelia_api_booking_created', $data);
}
