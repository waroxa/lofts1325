<?php
/**
 * Email deliverability settings.
 */

defined('ABSPATH') || exit;

function wp_loft_booking_register_email_settings() {
    register_setting('wp_loft_booking_email_settings', 'loft_email_domain');
    register_setting('wp_loft_booking_email_settings', 'loft_email_api_key');
    register_setting('wp_loft_booking_email_settings', 'loft_email_signing_key');
    register_setting('wp_loft_booking_email_settings', 'loft_email_endpoint');
    register_setting('wp_loft_booking_email_settings', 'loft_email_daily_quota');
}
add_action('admin_init', 'wp_loft_booking_register_email_settings');

function wp_loft_booking_email_settings_page() {
    $domain      = trim((string) get_option('loft_email_domain', ''));
    $api_key     = trim((string) get_option('loft_email_api_key', ''));
    $signing_key = trim((string) get_option('loft_email_signing_key', ''));
    $endpoint    = trim((string) get_option('loft_email_endpoint', 'https://api.mailgun.net'));
    $daily_quota = (int) get_option('loft_email_daily_quota', 10000);

    $dns_records = function_exists('wp_loft_email_provider_dns_records')
        ? wp_loft_email_provider_dns_records($domain)
        : [];

    $health = function_exists('wp_loft_email_provider_run_health_ping')
        ? wp_loft_email_provider_run_health_ping()
        : ['ok' => false, 'api_status' => 'unknown', 'quota_remaining' => null, 'error' => 'Missing provider functions'];

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Email Deliverability', 'wp-loft-booking'); ?></h1>

        <p>Configure the dedicated sending domain, DNS records, and Mailgun credentials used for transactional emails.</p>
        <p class="description">If you leave Mailgun blank, booking emails will fall back to WordPress <code>wp_mail()</code>; any SMTP plugin (e.g., WP Mail SMTP) will be respected.</p>

        <form method="post" action="options.php">
            <?php
            settings_fields('wp_loft_booking_email_settings');
            do_settings_sections('wp_loft_booking_email_settings');
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="loft_email_domain"><?php esc_html_e('Sending domain', 'wp-loft-booking'); ?></label></th>
                    <td>
                        <input type="text" id="loft_email_domain" name="loft_email_domain" value="<?php echo esc_attr($domain); ?>" class="regular-text" placeholder="email.example.com">
                        <p class="description">Use a dedicated subdomain for transactional sends (e.g., email.loft1325.com).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="loft_email_api_key"><?php esc_html_e('Mailgun API key', 'wp-loft-booking'); ?></label></th>
                    <td>
                        <input type="password" id="loft_email_api_key" name="loft_email_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off">
                        <p class="description">Stored securely in WordPress options; required for sending and DNS verification.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="loft_email_signing_key"><?php esc_html_e('Webhook signing key', 'wp-loft-booking'); ?></label></th>
                    <td>
                        <input type="password" id="loft_email_signing_key" name="loft_email_signing_key" value="<?php echo esc_attr($signing_key); ?>" class="regular-text" autocomplete="off">
                        <p class="description">Used to validate Mailgun delivered/bounce/complaint webhooks.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="loft_email_endpoint"><?php esc_html_e('API endpoint', 'wp-loft-booking'); ?></label></th>
                    <td>
                        <input type="url" id="loft_email_endpoint" name="loft_email_endpoint" value="<?php echo esc_attr($endpoint); ?>" class="regular-text">
                        <p class="description">Defaults to https://api.mailgun.net; update for EU region or private relay.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="loft_email_daily_quota"><?php esc_html_e('Daily sending quota', 'wp-loft-booking'); ?></label></th>
                    <td>
                        <input type="number" id="loft_email_daily_quota" name="loft_email_daily_quota" value="<?php echo esc_attr($daily_quota); ?>" min="0" step="1">
                        <p class="description">Used to compute remaining sends in health checks.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save email settings', 'wp-loft-booking')); ?>
        </form>

        <h2><?php esc_html_e('DNS records', 'wp-loft-booking'); ?></h2>
        <?php if (!empty($dns_records)) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Type', 'wp-loft-booking'); ?></th>
                        <th><?php esc_html_e('Name', 'wp-loft-booking'); ?></th>
                        <th><?php esc_html_e('Value', 'wp-loft-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dns_records as $record) : ?>
                        <tr>
                            <td><?php echo esc_html($record['type'] ?? ''); ?></td>
                            <td><code><?php echo esc_html($record['name'] ?? ''); ?></code></td>
                            <td><code><?php echo esc_html($record['value'] ?? ''); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="description">Provide a domain and API key to pull SPF, DKIM, DMARC, and tracking records.</p>
        <?php endif; ?>

        <h2><?php esc_html_e('Deliverability webhooks', 'wp-loft-booking'); ?></h2>
        <p>Point Mailgun delivered, bounce, and complaint webhooks to:</p>
        <code><?php echo esc_url(rest_url('wp-loft-booking/v1/mailgun/webhook')); ?></code>

        <h2><?php esc_html_e('Health', 'wp-loft-booking'); ?></h2>
        <p>Status: <strong><?php echo esc_html($health['api_status'] ?? 'unknown'); ?></strong></p>
        <?php if (isset($health['quota_remaining'])) : ?>
            <p>Daily quota remaining: <strong><?php echo esc_html($health['quota_remaining']); ?></strong></p>
        <?php endif; ?>
        <?php if (!empty($health['error'])) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($health['error']); ?></p></div>
        <?php elseif (!empty($health['ok'])) : ?>
            <div class="notice notice-success"><p><?php esc_html_e('Mailgun API key validated successfully.', 'wp-loft-booking'); ?></p></div>
        <?php endif; ?>
    </div>
    <?php
}
