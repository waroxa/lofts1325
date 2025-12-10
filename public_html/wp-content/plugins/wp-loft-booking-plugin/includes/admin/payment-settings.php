<?php
defined('ABSPATH') || exit;

// Add Payment Settings Page as a submenu under the main Loft Booking menu
add_action('admin_menu', 'loft_booking_payment_settings_page');
function loft_booking_payment_settings_page()
{
    add_submenu_page(
        'wp_loft_booking',                   // Parent Slug
        'Payment Settings',                  // Page Title
        '💳 Payment Settings',               // Menu Title
        'manage_options',                    // Capability
        'loft-payment-settings',             // Menu Slug
        'loft_booking_payment_settings'      // Callback Function
    );
}

/**
 * Return the currently active Stripe keys based on the chosen environment.
 */
function wp_loft_booking_get_active_stripe_keys()
{
    $test_mode = (bool) get_option('stripe_test_mode', false);

    $live_publishable = get_option('stripe_publishable_key', '');
    $live_secret      = get_option('stripe_secret_key', '');
    $test_publishable = get_option('stripe_test_publishable_key', '');
    $test_secret      = get_option('stripe_test_secret_key', '');

    if ($test_mode) {
        return [
            'publishable' => $test_publishable,
            'secret'      => $test_secret,
            'mode'        => 'test',
        ];
    }

    return [
        'publishable' => $live_publishable,
        'secret'      => $live_secret,
        'mode'        => 'live',
    ];
}

function loft_booking_payment_settings()
{
    // Save Settings if Form is Submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_settings'])) {
        check_admin_referer('loft_booking_payment_settings');

        $live_publishable = sanitize_text_field(wp_unslash($_POST['stripe_publishable_key'] ?? ''));
        $live_secret      = sanitize_text_field(wp_unslash($_POST['stripe_secret_key'] ?? ''));
        $test_publishable = sanitize_text_field(wp_unslash($_POST['stripe_test_publishable_key'] ?? ''));
        $test_secret      = sanitize_text_field(wp_unslash($_POST['stripe_test_secret_key'] ?? ''));
        $test_mode        = !empty($_POST['stripe_test_mode']);

        update_option('stripe_publishable_key', $live_publishable);
        update_option('stripe_secret_key', $live_secret);
        update_option('stripe_test_publishable_key', $test_publishable);
        update_option('stripe_test_secret_key', $test_secret);
        update_option('stripe_test_mode', $test_mode);

        update_option('stripe_checkout_message', sanitize_textarea_field(wp_unslash($_POST['stripe_checkout_message'] ?? '')));
        update_option('stripe_currency', sanitize_text_field(wp_unslash($_POST['stripe_currency'] ?? 'CAD')));

        echo '<div class="updated"><p>Payment settings saved successfully.</p></div>';
    }

    // Fetch Existing Settings
    $stripe_publishable_key    = get_option('stripe_publishable_key', '');
    $stripe_secret_key         = get_option('stripe_secret_key', '');
    $stripe_test_publishable   = get_option('stripe_test_publishable_key', '');
    $stripe_test_secret        = get_option('stripe_test_secret_key', '');
    $stripe_test_mode          = (bool) get_option('stripe_test_mode', false);
    $active_keys               = wp_loft_booking_get_active_stripe_keys();
    $stripe_checkout_message   = get_option('stripe_checkout_message', 'Simple and safe. Make payments with any type of credit card.');
    $stripe_currency           = get_option('stripe_currency', 'CAD');

    // Render the Form
    ?>
    <div class="wrap">
        <h1>Payment Settings</h1>
        <p class="description">Choose which Stripe environment to use and store separate keys for live and test plans.</p>
        <div class="notice notice-info" style="padding:15px;margin:15px 0;">
            <p style="margin:0 0 10px 0;"><strong>Sandbox testing:</strong> Enable test mode, use Stripe test keys, and run a full booking payment to confirm the checkout flow. See the checklist below before switching back to live keys.</p>
            <ul style="margin:0 0 0 18px;list-style:disc;">
                <li>Turn on <em>Enable Stripe test mode</em> and save your test keys.</li>
                <li>Place a booking using the public form and pay with a Stripe test card (e.g., 4242 4242 4242 4242).</li>
                <li>Verify the booking record, receipt email, and Stripe test dashboard payment all show the test amount.</li>
                <li>Return here to switch back to live mode once checks pass.</li>
            </ul>
        </div>
        <table class="form-table">
            <tr>
                <th scope="row">Active mode</th>
                <td><strong><?php echo esc_html(strtoupper($active_keys['mode'])); ?></strong> (<?php echo $active_keys['mode'] === 'test' ? 'Test keys are used for new checkouts.' : 'Live keys are used for new checkouts.'; ?>)</td>
            </tr>
        </table>
        <form method="post">
            <?php wp_nonce_field('loft_booking_payment_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="stripe_test_mode">Enable Stripe test mode</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="stripe_test_mode" name="stripe_test_mode" value="1" <?php checked($stripe_test_mode); ?>>
                            Use test keys without replacing live keys.
                        </label>
                        <p class="description">When enabled, the test publishable/secret keys below are used instead of the live keys.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2"><h2 style="margin:0;">Live keys</h2></th>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_publishable_key">Live publishable key</label></th>
                    <td><input type="text" id="stripe_publishable_key" name="stripe_publishable_key" class="regular-text" value="<?php echo esc_attr($stripe_publishable_key); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_secret_key">Live secret key</label></th>
                    <td><input type="text" id="stripe_secret_key" name="stripe_secret_key" class="regular-text" value="<?php echo esc_attr($stripe_secret_key); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row" colspan="2"><h2 style="margin:0;">Test keys</h2></th>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_test_publishable_key">Test publishable key</label></th>
                    <td><input type="text" id="stripe_test_publishable_key" name="stripe_test_publishable_key" class="regular-text" value="<?php echo esc_attr($stripe_test_publishable_key); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_test_secret_key">Test secret key</label></th>
                    <td><input type="text" id="stripe_test_secret_key" name="stripe_test_secret_key" class="regular-text" value="<?php echo esc_attr($stripe_test_secret_key); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_checkout_message">Stripe Checkout Message</label></th>
                    <td><textarea id="stripe_checkout_message" name="stripe_checkout_message" rows="4" class="large-text"><?php echo esc_textarea($stripe_checkout_message); ?></textarea></td>
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