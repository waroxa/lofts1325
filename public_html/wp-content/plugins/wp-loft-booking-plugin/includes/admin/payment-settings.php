<?php
defined('ABSPATH') || exit;

// Add Payment Settings Page to the Admin Menu
add_action('admin_menu', 'loft_booking_payment_settings_page');
function loft_booking_payment_settings_page() {
    add_menu_page(
        'Payment Settings',                  // Page Title
        'Payment Settings',                  // Menu Title
        'manage_options',                    // Capability
        'loft-payment-settings',             // Menu Slug
        'loft_booking_payment_settings',     // Callback Function
        'dashicons-admin-generic',           // Icon
        25                                   // Position
    );
}

function loft_booking_payment_settings() {
    // Save Settings if Form is Submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_settings'])) {
        update_option('loft_city_tax', sanitize_text_field($_POST['city_tax']));
        update_option('loft_vat', sanitize_text_field($_POST['vat']));
        update_option('stripe_publishable_key', sanitize_text_field($_POST['stripe_publishable_key']));
        update_option('stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key']));
        update_option('stripe_checkout_message', sanitize_textarea_field($_POST['stripe_checkout_message']));
        update_option('stripe_currency', sanitize_text_field($_POST['stripe_currency']));
        echo '<div class="updated"><p>Payment settings saved successfully.</p></div>';
    }

    // Fetch Existing Settings
    $city_tax = get_option('loft_city_tax', '0');
    $vat = get_option('loft_vat', '14.975');
    $stripe_publishable_key = get_option('stripe_publishable_key', '');
    $stripe_secret_key = get_option('stripe_secret_key', '');
    $stripe_checkout_message = get_option('stripe_checkout_message', 'Simple and safe. Make payments with any type of credit card.');
    $stripe_currency = get_option('stripe_currency', 'CAD');

    // Render the Form
    ?>
    <div class="wrap">
        <h1>Payment Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="city_tax">City Tax (%)</label></th>
                    <td><input type="number" id="city_tax" name="city_tax" value="<?php echo esc_attr($city_tax); ?>" step="0.01" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="vat">VAT (%)</label></th>
                    <td><input type="number" id="vat" name="vat" value="<?php echo esc_attr($vat); ?>" step="0.01" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_publishable_key">Stripe Publishable Key</label></th>
                    <td><input type="text" id="stripe_publishable_key" name="stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable_key); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_secret_key">Stripe Secret Key</label></th>
                    <td><input type="text" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_checkout_message">Stripe Checkout Message</label></th>
                    <td><textarea id="stripe_checkout_message" name="stripe_checkout_message" rows="4"><?php echo esc_textarea($stripe_checkout_message); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_currency">Currency</label></th>
                    <td>
                        <select id="stripe_currency" name="stripe_currency">
                            <option value="CAD" <?php selected($stripe_currency, 'CAD'); ?>>CAD</option>
                            <option value="USD" <?php selected($stripe_currency, 'USD'); ?>>USD</option>
                            <option value="EUR" <?php selected($stripe_currency, 'EUR'); ?>>EUR</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="save_payment_settings" id="save_payment_settings" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}