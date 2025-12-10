<?php
defined('ABSPATH') || exit;

// Add Payment Settings Page as a submenu under the main Loft Booking menu
add_action('admin_menu', 'loft_booking_payment_settings_page');
add_action('admin_post_loft_booking_save_payment_settings', 'loft_booking_handle_payment_settings_save');

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
 * Fetch all Stripe settings with sensible defaults.
 */
function wp_loft_booking_get_stripe_settings()
{
    $settings = (array) get_option('wp_loft_booking_stripe_settings', []);

    // Backward compatibility: fall back to legacy option names if the new array is empty.
    $legacy_defaults = [
        'live_publishable' => get_option('stripe_publishable_key', ''),
        'live_secret'      => get_option('stripe_secret_key', ''),
        'test_publishable' => get_option('stripe_test_publishable_key', ''),
        'test_secret'      => get_option('stripe_test_secret_key', ''),
        'test_mode'        => (bool) get_option('stripe_test_mode', false),
        'checkout_message' => get_option('stripe_checkout_message', 'Simple and safe. Make payments with any type of credit card.'),
        'currency'         => get_option('stripe_currency', 'CAD'),
    ];

    return wp_parse_args($settings, $legacy_defaults);
}

/**
 * Return the currently active Stripe keys based on the chosen environment.
 */
function wp_loft_booking_get_active_stripe_keys()
{
    $settings = wp_loft_booking_get_stripe_settings();

    if (!empty($settings['test_mode'])) {
        return [
            'publishable' => $settings['test_publishable'],
            'secret'      => $settings['test_secret'],
            'mode'        => 'test',
        ];
    }

    return [
        'publishable' => $settings['live_publishable'],
        'secret'      => $settings['live_secret'],
        'mode'        => 'live',
    ];
}

/**
 * Handle form submissions for the payment settings screen.
 */
function loft_booking_handle_payment_settings_save()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to update payment settings.', 'wp-loft-booking'));
    }

    check_admin_referer('loft_booking_payment_settings');

    $settings = wp_loft_booking_get_stripe_settings();

    $settings['live_publishable'] = sanitize_text_field(wp_unslash($_POST['stripe_publishable_key'] ?? ''));
    $settings['live_secret']      = sanitize_text_field(wp_unslash($_POST['stripe_secret_key'] ?? ''));
    $settings['test_publishable'] = sanitize_text_field(wp_unslash($_POST['stripe_test_publishable_key'] ?? ''));
    $settings['test_secret']      = sanitize_text_field(wp_unslash($_POST['stripe_test_secret_key'] ?? ''));
    $settings['test_mode']        = !empty($_POST['stripe_test_mode']);
    $settings['checkout_message'] = sanitize_textarea_field(wp_unslash($_POST['stripe_checkout_message'] ?? ''));
    $settings['currency']         = sanitize_text_field(wp_unslash($_POST['stripe_currency'] ?? 'CAD'));

    update_option('wp_loft_booking_stripe_settings', $settings, false);

    // Keep legacy option names in sync for any existing integrations.
    update_option('stripe_publishable_key', $settings['live_publishable']);
    update_option('stripe_secret_key', $settings['live_secret']);
    update_option('stripe_test_publishable_key', $settings['test_publishable']);
    update_option('stripe_test_secret_key', $settings['test_secret']);
    update_option('stripe_test_mode', (bool) $settings['test_mode']);
    update_option('stripe_checkout_message', $settings['checkout_message']);
    update_option('stripe_currency', $settings['currency']);

    wp_safe_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=loft-payment-settings')));
    exit;
}

function loft_booking_payment_settings()
{
    // Fetch Existing Settings
    $stripe_settings           = wp_loft_booking_get_stripe_settings();
    $stripe_publishable_key    = $stripe_settings['live_publishable'];
    $stripe_secret_key         = $stripe_settings['live_secret'];
    $stripe_test_publishable   = $stripe_settings['test_publishable'];
    $stripe_test_secret        = $stripe_settings['test_secret'];
    $stripe_test_mode          = (bool) $stripe_settings['test_mode'];
    $active_keys               = wp_loft_booking_get_active_stripe_keys();
    $stripe_checkout_message   = $stripe_settings['checkout_message'];
    $stripe_currency           = $stripe_settings['currency'];

    // Render the Form
    ?>
    <div class="wrap">
        <h1>Payment Settings</h1>
        <p class="description" style="font-weight:600;">Hello World</p>
        <p class="description">Choose which Stripe environment to use and store separate keys for live and test plans.</p>

        <div class="notice notice-info" style="padding:15px;margin:15px 0;">
            <p style="margin:0 0 10px 0;"><strong>Sandbox testing:</strong> Enable test mode, enter your Stripe test keys, and run a booking with a Stripe test card (e.g., 4242 4242 4242 4242) to validate the full flow. Switch back to live mode after confirming emails and records.</p>
        </div>

        <div class="card" style="padding:16px;max-width:820px;">
            <?php if (!empty($_GET['settings-updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Payment settings saved successfully.</p></div>
            <?php endif; ?>

            <h2 style="margin-top:0;">Stripe environment</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Active mode</th>
                    <td>
                        <span style="display:inline-block;padding:3px 8px;border-radius:999px;color:#fff;background:<?php echo $active_keys['mode'] === 'test' ? '#d63638' : '#2271b1'; ?>;font-weight:600;letter-spacing:0.02em;">
                            <?php echo esc_html(strtoupper($active_keys['mode'])); ?>
                        </span>
                        <span style="margin-left:8px;">
                            <?php echo $active_keys['mode'] === 'test' ? 'Sandbox keys are active for new checkouts.' : 'Live keys are active for new checkouts.'; ?>
                        </span>
                    </td>
                </tr>
            </table>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="loft_booking_save_payment_settings">
                <?php wp_nonce_field('loft_booking_payment_settings'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="stripe_test_mode">Enable Stripe test mode</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="stripe_test_mode" name="stripe_test_mode" value="1" <?php checked($stripe_test_mode); ?>>
                                Use sandbox/test keys without replacing live keys.
                            </label>
                            <p class="description" style="margin-top:6px;">When enabled, the test publishable/secret keys below are used instead of the live keys so you can run purchase flows in Stripe’s test data view.</p>
                        </td>
                    </tr>
                </table>

                <h2>Live keys</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="stripe_publishable_key">Live publishable key</label></th>
                        <td><input type="text" id="stripe_publishable_key" name="stripe_publishable_key" class="regular-text" value="<?php echo esc_attr($stripe_publishable_key); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_secret_key">Live secret key</label></th>
                        <td><input type="text" id="stripe_secret_key" name="stripe_secret_key" class="regular-text" value="<?php echo esc_attr($stripe_secret_key); ?>" /></td>
                    </tr>
                </table>

                <h2>Sandbox / test keys</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="stripe_test_publishable_key">Test publishable key</label></th>
                        <td><input type="text" id="stripe_test_publishable_key" name="stripe_test_publishable_key" class="regular-text" value="<?php echo esc_attr($stripe_test_publishable_key); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_test_secret_key">Test secret key</label></th>
                        <td><input type="text" id="stripe_test_secret_key" name="stripe_test_secret_key" class="regular-text" value="<?php echo esc_attr($stripe_test_secret_key); ?>" /></td>
                    </tr>
                </table>

                <h2>Checkout display</h2>
                <table class="form-table" role="presentation">
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
    </div>
    <?php
}