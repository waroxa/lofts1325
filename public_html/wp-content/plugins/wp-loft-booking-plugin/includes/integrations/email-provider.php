<?php
/**
 * Mailgun email provider integration for Loft 1325.
 */

defined('ABSPATH') || exit;

/**
 * Retrieve configured Mailgun settings.
 *
 * @return array{api_key:string,domain:string,signing_key:string,endpoint:string,daily_quota:int}
 */
function wp_loft_email_provider_get_settings() {
    $endpoint = trim(get_option('loft_email_endpoint', 'https://api.mailgun.net'));

    return [
        'api_key'     => trim((string) get_option('loft_email_api_key', '')),
        'domain'      => trim((string) get_option('loft_email_domain', '')),
        'signing_key' => trim((string) get_option('loft_email_signing_key', '')),
        'endpoint'    => untrailingslashit($endpoint ?: 'https://api.mailgun.net'),
        'daily_quota' => (int) get_option('loft_email_daily_quota', 10000),
    ];
}

/**
 * Compute the default from address using the configured domain.
 *
 * @return string
 */
function wp_loft_email_provider_get_from_address() {
    $settings = wp_loft_email_provider_get_settings();

    if (!empty($settings['domain']) && is_email('booking@' . $settings['domain'])) {
        return sprintf('Loft 1325 <%s>', 'booking@' . $settings['domain']);
    }

    $admin_email = get_option('admin_email');

    return is_email($admin_email) ? sprintf('Loft 1325 <%s>', $admin_email) : 'Loft 1325 <no-reply@loft1325.com>';
}

/**
 * Prepare DNS records for SPF, DKIM, DMARC and tracking.
 *
 * @param string $domain
 *
 * @return array<int,array<string,string>>
 */
function wp_loft_email_provider_dns_records($domain) {
    $domain = trim((string) $domain);

    if ('' === $domain) {
        return [];
    }

    $records = [
        [
            'type'  => 'TXT',
            'name'  => $domain,
            'value' => 'v=spf1 include:mailgun.org ~all',
        ],
        [
            'type'  => 'TXT',
            'name'  => sprintf('_dmarc.%s', $domain),
            'value' => sprintf('v=DMARC1; p=quarantine; rua=mailto:dmarc@%s; ruf=mailto:dmarc@%s; fo=1', $domain, $domain),
        ],
        [
            'type'  => 'CNAME',
            'name'  => sprintf('email.%s', $domain),
            'value' => 'mailgun.org',
        ],
        [
            'type'  => 'TXT',
            'name'  => sprintf('k1._domainkey.%s', $domain),
            'value' => 'k=rsa; p=<mailgun-public-key>',
        ],
    ];

    $verification = wp_loft_email_provider_fetch_domain_verification();

    if (!is_wp_error($verification) && isset($verification['records']) && is_array($verification['records'])) {
        $records = $verification['records'];
    }

    return $records;
}

/**
 * Perform an authenticated Mailgun request.
 *
 * @param string $method HTTP method.
 * @param string $path   Path without base endpoint (e.g. /v3/domains/example.com).
 * @param array  $args   Optional body/query arguments.
 *
 * @return array|WP_Error
 */
function wp_loft_email_provider_request($method, $path, array $args = []) {
    $settings = wp_loft_email_provider_get_settings();

    if (empty($settings['api_key'])) {
        return new WP_Error('loft_email_missing_key', __('Mailgun API key is not configured.', 'wp-loft-booking'));
    }

    $url = trailingslashit($settings['endpoint']) . ltrim($path, '/');

    $request_args = [
        'method'  => strtoupper($method),
        'timeout' => 20,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode('api:' . $settings['api_key']),
        ],
    ];

    if (!empty($args)) {
        if ('GET' === $request_args['method']) {
            $url = add_query_arg($args, $url);
        } else {
            $request_args['body'] = $args;
        }
    }

    $response = wp_remote_request($url, $request_args);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code >= 400) {
        return new WP_Error(
            'loft_email_http_error',
            sprintf('Mailgun API responded with %d', $code),
            [
                'code' => $code,
                'body' => $body,
            ]
        );
    }

    return is_array($body) ? $body : [];
}

/**
 * Retrieve verification info for the configured domain.
 *
 * @return array{records:array<int,array<string,string>>,status:array<string,string>}|WP_Error
 */
function wp_loft_email_provider_fetch_domain_verification() {
    $settings = wp_loft_email_provider_get_settings();

    if (empty($settings['domain'])) {
        return new WP_Error('loft_email_missing_domain', __('Mailgun domain is not configured.', 'wp-loft-booking'));
    }

    $result = wp_loft_email_provider_request('GET', '/v3/domains/' . rawurlencode($settings['domain']) . '/verify');

    if (is_wp_error($result)) {
        return $result;
    }

    $records = [];
    $status  = [];

    if (isset($result['sending_dns_records']) && is_array($result['sending_dns_records'])) {
        foreach ($result['sending_dns_records'] as $record) {
            if (empty($record['name']) || empty($record['record_type']) || empty($record['value'])) {
                continue;
            }

            $records[] = [
                'name'  => $record['name'],
                'type'  => $record['record_type'],
                'value' => $record['value'],
            ];

            if (isset($record['record_type']) && isset($record['valid'])) {
                $status[strtolower($record['record_type'])] = true === $record['valid'] ? 'valid' : 'pending';
            }
        }
    }

    if (isset($result['receiving_dns_records']) && is_array($result['receiving_dns_records'])) {
        foreach ($result['receiving_dns_records'] as $record) {
            if (empty($record['name']) || empty($record['record_type']) || empty($record['value'])) {
                continue;
            }

            $records[] = [
                'name'  => $record['name'],
                'type'  => $record['record_type'],
                'value' => $record['value'],
            ];

            if (isset($record['record_type']) && isset($record['valid'])) {
                $status[strtolower($record['record_type'])] = true === $record['valid'] ? 'valid' : 'pending';
            }
        }
    }

    if (isset($result['tracking_dns_record']) && is_array($result['tracking_dns_record'])) {
        $tracking = $result['tracking_dns_record'];

        if (!empty($tracking['record_type']) && !empty($tracking['name']) && !empty($tracking['value'])) {
            $records[] = [
                'name'  => $tracking['name'],
                'type'  => $tracking['record_type'],
                'value' => $tracking['value'],
            ];

            $status['tracking'] = isset($tracking['valid']) && true === $tracking['valid'] ? 'valid' : 'pending';
        }
    }

    return [
        'records' => $records,
        'status'  => $status,
    ];
}

/**
 * Send an email via Mailgun.
 *
 * @param array $message {
 *   @type array  $to
 *   @type string $subject
 *   @type string $html
 *   @type string $text
 *   @type array  $bcc
 *   @type string $from
 * }
 *
 * @return true|WP_Error
 */
function wp_loft_email_provider_send(array $message) {
    $settings = wp_loft_email_provider_get_settings();

    if (empty($settings['api_key']) || empty($settings['domain'])) {
        return new WP_Error('loft_email_not_configured', __('Mailgun is not fully configured.', 'wp-loft-booking'));
    }

    $body = [
        'from'    => $message['from'] ?? wp_loft_email_provider_get_from_address(),
        'to'      => isset($message['to']) ? (array) $message['to'] : [],
        'subject' => $message['subject'] ?? '',
    ];

    if (!empty($message['html'])) {
        $body['html'] = $message['html'];
    }

    if (!empty($message['text'])) {
        $body['text'] = $message['text'];
    }

    if (!empty($message['bcc'])) {
        $body['bcc'] = implode(',', array_filter(array_map('sanitize_email', (array) $message['bcc'])));
    }

    $response = wp_loft_email_provider_request('POST', '/v3/' . rawurlencode($settings['domain']) . '/messages', $body);

    if (is_wp_error($response)) {
        return $response;
    }

    return [
        'id'      => $response['id'] ?? null,
        'message' => $response['message'] ?? __('Queued for delivery', 'wp-loft-booking'),
        'to'      => $body['to'],
    ];
}

/**
 * Helper to send via Mailgun with wp_mail fallback.
 *
 * @param string $recipient
 * @param string $subject
 * @param string $body
 * @param array  $headers
 * @param array  $bcc
 *
 * @return bool
 */
function wp_loft_email_provider_send_or_fallback($recipient, $subject, $body, array $headers, array $bcc = []) {
    $result = wp_loft_email_provider_send([
        'to'      => [$recipient],
        'subject' => $subject,
        'html'    => $body,
        'text'    => wp_strip_all_tags($body),
        'bcc'     => $bcc,
    ]);

    if (is_wp_error($result)) {
        error_log('⚠️ Mailgun send failed; falling back to wp_mail. ' . $result->get_error_message());

        return wp_mail($recipient, $subject, $body, $headers);
    }

    return true;
}

/**
 * Ensure email job/renders tables include the columns required for queueing.
 */
function wp_loft_email_provider_maybe_upgrade_tables() {
    global $wpdb;

    if (!function_exists('maybe_add_column')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    $jobs_table    = $wpdb->prefix . 'loft_email_jobs';
    $renders_table = $wpdb->prefix . 'loft_email_renders';

    maybe_add_column($jobs_table, 'idempotency_key', "ALTER TABLE {$jobs_table} ADD COLUMN idempotency_key VARCHAR(191) NULL");
    maybe_add_column($jobs_table, 'event', "ALTER TABLE {$jobs_table} ADD COLUMN event VARCHAR(100) DEFAULT 'booking-email'");
    maybe_add_column($jobs_table, 'template_key', "ALTER TABLE {$jobs_table} ADD COLUMN template_key VARCHAR(150) NULL");
    maybe_add_column($jobs_table, 'source', "ALTER TABLE {$jobs_table} ADD COLUMN source VARCHAR(50) DEFAULT 'automatic'");
    maybe_add_column($jobs_table, 'payload', "ALTER TABLE {$jobs_table} ADD COLUMN payload LONGTEXT NULL");
    maybe_add_column($jobs_table, 'attempts', "ALTER TABLE {$jobs_table} ADD COLUMN attempts SMALLINT DEFAULT 0");
    maybe_add_column($jobs_table, 'last_error', "ALTER TABLE {$jobs_table} ADD COLUMN last_error TEXT NULL");
    maybe_add_column($jobs_table, 'provider_response', "ALTER TABLE {$jobs_table} ADD COLUMN provider_response LONGTEXT NULL");
    maybe_add_column($jobs_table, 'provider_message_id', "ALTER TABLE {$jobs_table} ADD COLUMN provider_message_id VARCHAR(191) NULL");
    maybe_add_column($jobs_table, 'webhook_status', "ALTER TABLE {$jobs_table} ADD COLUMN webhook_status VARCHAR(50) NULL");

    maybe_add_column($renders_table, 'rendered_subject', "ALTER TABLE {$renders_table} ADD COLUMN rendered_subject VARCHAR(255) NULL");
    maybe_add_column($renders_table, 'rendered_text', "ALTER TABLE {$renders_table} ADD COLUMN rendered_text LONGTEXT NULL");
    maybe_add_column($renders_table, 'attachments', "ALTER TABLE {$renders_table} ADD COLUMN attachments LONGTEXT NULL");
    maybe_add_column($renders_table, 'variables', "ALTER TABLE {$renders_table} ADD COLUMN variables LONGTEXT NULL");
}
add_action('plugins_loaded', 'wp_loft_email_provider_maybe_upgrade_tables');

/**
 * Persist a rendered email snapshot for debugging or auditing.
 *
 * @param int   $job_id
 * @param array $booking
 * @param array $message
 * @param array $variables
 */
function wp_loft_email_provider_store_render($job_id, array $booking, array $message, array $variables = []) {
    global $wpdb;

    $renders_table = $wpdb->prefix . 'loft_email_renders';

    $wpdb->insert(
        $renders_table,
        [
            'job_id'           => (int) $job_id,
            'booking_id'       => isset($booking['booking_id']) ? (int) $booking['booking_id'] : null,
            'loft_id'          => isset($booking['room_id']) ? (int) $booking['room_id'] : null,
            'status'           => 'rendered',
            'rendered_subject' => $message['subject'] ?? '',
            'rendered_body'    => $message['html'] ?? '',
            'rendered_text'    => $message['text'] ?? '',
            'attachments'      => !empty($message['attachments']) ? wp_json_encode($message['attachments']) : null,
            'variables'        => !empty($variables) ? wp_json_encode($variables) : null,
        ],
        ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
    );
}

/**
 * Insert or reuse a queued email job for a booking event.
 *
 * @param array $message   {to, subject, html, text, bcc, attachments}
 * @param array $booking   Booking payload (expects booking_id/room_id/transaction_id).
 * @param array $context   {event, template}
 *
 * @return int|WP_Error Job ID or error.
 */
function wp_loft_email_provider_enqueue_job(array $message, array $booking, array $context = []) {
    global $wpdb;

    $jobs_table = $wpdb->prefix . 'loft_email_jobs';

    $booking_id = isset($booking['booking_id']) ? (int) $booking['booking_id'] : (int) ($booking['id'] ?? 0);
    $loft_id    = isset($booking['room_id']) ? (int) $booking['room_id'] : null;
    $event      = $context['event'] ?? 'booking-email';
    $template   = $context['template'] ?? ($message['subject'] ?? '');
    $source     = $context['source'] ?? 'automatic';
    $send_at    = isset($context['send_at']) ? $context['send_at'] : null;
    $dry_run    = !empty($context['dry_run']);
    $status     = $dry_run ? 'rendered' : 'pending';

    if ($send_at instanceof DateTimeInterface) {
        $send_at = $send_at->getTimestamp();
    }

    if (is_numeric($send_at)) {
        $send_at      = (int) $send_at;
        $scheduled_at = get_date_from_gmt(gmdate('Y-m-d H:i:s', $send_at), 'Y-m-d H:i:s');
    } elseif (is_string($send_at) && strtotime($send_at)) {
        $scheduled_at = wp_date('Y-m-d H:i:s', strtotime($send_at));
        $send_at      = strtotime($scheduled_at . ' UTC');
    } else {
        $scheduled_at = current_time('mysql');
        $send_at      = time();
    }
    $id_source  = implode('|', [
        $event,
        $booking_id ?: 'none',
        $loft_id ?: 'none',
        $template,
        $booking['transaction_id'] ?? '',
        $message['to'][0] ?? '',
    ]);

    $idempotency_key = hash('sha256', $id_source);

    $existing_job = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$jobs_table} WHERE idempotency_key = %s LIMIT 1",
            $idempotency_key
        )
    );

    if ($existing_job) {
        error_log(sprintf('ℹ️ Email job reused for key %s (job #%d).', $idempotency_key, $existing_job));

        return (int) $existing_job;
    }

    $inserted = $wpdb->insert(
        $jobs_table,
        [
            'booking_id'      => $booking_id ?: null,
            'loft_id'         => $loft_id ?: null,
            'template_id'     => 0,
            'event'           => $event,
            'template_key'    => $template,
            'source'          => $source,
            'status'          => $status,
            'scheduled_at'    => $scheduled_at,
            'idempotency_key' => $idempotency_key,
            'payload'         => wp_json_encode($message),
            'attempts'        => 0,
        ],
        ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d']
    );

    if (false === $inserted) {
        return new WP_Error('loft_email_job_insert_failed', __('Unable to enqueue email job.', 'wp-loft-booking'));
    }

    $job_id = (int) $wpdb->insert_id;

    wp_loft_email_provider_store_render($job_id, $booking, $message, $context['variables'] ?? []);

    if (!$dry_run) {
        $timestamp = max(time() + 1, (int) $send_at);

        if (!wp_next_scheduled('wp_loft_email_provider_process_job', [$job_id])) {
            wp_schedule_single_event($timestamp, 'wp_loft_email_provider_process_job', [$job_id]);
        }

        // Attempt an immediate run to reduce latency while still scheduling retries.
        wp_loft_email_provider_process_job($job_id);
    } else {
        $wpdb->update(
            $jobs_table,
            [
                'processed_at' => current_time('mysql'),
                'updated_at'   => current_time('mysql'),
            ],
            ['id' => $job_id],
            ['%s', '%s'],
            ['%d']
        );

        error_log(sprintf('🧪 Stored dry-run email render as job #%d for booking %s.', $job_id, $booking_id ?: 'n/a'));
    }

    error_log(sprintf('✉️ Enqueued email job #%d (%s) for booking %s.', $job_id, $event, $booking_id ?: 'n/a'));

    return $job_id;
}

add_action('wp_loft_email_provider_process_job', 'wp_loft_email_provider_process_job', 10, 1);

/**
 * Process a queued email job with retry backoff on transient failures.
 */
function wp_loft_email_provider_process_job($job_id) {
    global $wpdb;

    $jobs_table    = $wpdb->prefix . 'loft_email_jobs';
    $renders_table = $wpdb->prefix . 'loft_email_renders';

    $job = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$jobs_table} WHERE id = %d", (int) $job_id),
        ARRAY_A
    );

    if (empty($job) || in_array($job['status'], ['completed', 'failed', 'rendered'], true)) {
        return;
    }

    if (!empty($job['scheduled_at'])) {
        $scheduled_timestamp = strtotime(get_gmt_from_date($job['scheduled_at']));

        if ($scheduled_timestamp && $scheduled_timestamp > time()) {
            if (!wp_next_scheduled('wp_loft_email_provider_process_job', [$job_id])) {
                wp_schedule_single_event($scheduled_timestamp, 'wp_loft_email_provider_process_job', [$job_id]);
            }

            return;
        }
    }

    $payload = json_decode($job['payload'] ?? '', true);
    if (empty($payload) || empty($payload['to'])) {
        $wpdb->update(
            $jobs_table,
            ['status' => 'failed', 'last_error' => 'Missing recipients or payload', 'processed_at' => current_time('mysql')],
            ['id' => $job_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        return;
    }

    $attempts = (int) $job['attempts'] + 1;
    $wpdb->update(
        $jobs_table,
        ['status' => 'processing', 'attempts' => $attempts, 'updated_at' => current_time('mysql')],
        ['id' => $job_id],
        ['%s', '%d', '%s'],
        ['%d']
    );

    $send_result = wp_loft_email_provider_send($payload);

    if (is_wp_error($send_result)) {
        $error_message = $send_result->get_error_message();
        $status        = 'retrying';

        if ($attempts >= 5) {
            $status = 'failed';
        } else {
            $delay = min(3600, pow(2, $attempts) * 30); // exponential backoff with 30s base
            wp_schedule_single_event(time() + $delay, 'wp_loft_email_provider_process_job', [$job_id]);
        }

        $wpdb->update(
            $jobs_table,
            [
                'status'     => $status,
                'last_error' => $error_message,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $job_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        $wpdb->update(
            $renders_table,
            ['status' => $status],
            ['job_id' => $job_id],
            ['%s'],
            ['%d']
        );

        error_log(sprintf('⚠️ Email job #%d attempt %d failed: %s', $job_id, $attempts, $error_message));

        return;
    }

    $provider_message_id = is_array($send_result) && !empty($send_result['id']) ? $send_result['id'] : null;
    $response_payload    = is_array($send_result) ? wp_json_encode($send_result) : '';

    $wpdb->update(
        $jobs_table,
        [
            'status'              => 'completed',
            'processed_at'        => current_time('mysql'),
            'provider_response'   => $response_payload,
            'provider_message_id' => $provider_message_id,
            'updated_at'          => current_time('mysql'),
        ],
        ['id' => $job_id],
        ['%s', '%s', '%s', '%s', '%s'],
        ['%d']
    );

    $wpdb->update(
        $renders_table,
        ['status' => 'sent'],
        ['job_id' => $job_id],
        ['%s'],
        ['%d']
    );

    error_log(sprintf('✅ Email job #%d delivered (message ID: %s).', $job_id, $provider_message_id ?: 'n/a'));
}

/**
 * Run a health check for the Mailgun credentials and quota usage.
 *
 * @return array{ok:bool,api_status:string,quota_remaining:int|null,error:string|null}
 */
function wp_loft_email_provider_run_health_ping() {
    $settings = wp_loft_email_provider_get_settings();
    $status   = [
        'ok'               => false,
        'api_status'       => 'unverified',
        'quota_remaining'  => null,
        'error'            => null,
    ];

    $domain_check = wp_loft_email_provider_request('GET', '/v3/domains');

    if (is_wp_error($domain_check)) {
        $status['error'] = $domain_check->get_error_message();
        update_option('loft_email_health', $status);

        return $status;
    }

    $status['api_status'] = 'ok';

    if (!empty($settings['domain'])) {
        $stats = wp_loft_email_provider_request('GET', '/v3/' . rawurlencode($settings['domain']) . '/stats/total', [
            'event'    => 'accepted',
            'duration' => '1d',
        ]);

        if (!is_wp_error($stats) && isset($stats['total_count'])) {
            $total = (int) $stats['total_count'];
            $status['quota_remaining'] = max(0, $settings['daily_quota'] - $total);
        }
    }

    $status['ok'] = ('ok' === $status['api_status']);

    update_option('loft_email_health', $status);

    return $status;
}

/**
 * Schedule hourly health pings.
 */
function wp_loft_email_provider_schedule_health_ping() {
    if (!wp_next_scheduled('wp_loft_email_provider_health_ping')) {
        wp_schedule_event(time() + 300, 'hourly', 'wp_loft_email_provider_health_ping');
    }
}
add_action('init', 'wp_loft_email_provider_schedule_health_ping');

add_action('wp_loft_email_provider_health_ping', 'wp_loft_email_provider_run_health_ping');

/**
 * Register webhook endpoint for Mailgun events.
 */
function wp_loft_email_provider_register_webhook() {
    register_rest_route('wp-loft-booking/v1', '/mailgun/webhook', [
        'methods'             => 'POST',
        'callback'            => 'wp_loft_email_provider_handle_webhook',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'wp_loft_email_provider_register_webhook');

/**
 * Verify Mailgun webhook signature.
 *
 * @param array $signature
 *
 * @return bool
 */
function wp_loft_email_provider_verify_signature(array $signature) {
    $settings = wp_loft_email_provider_get_settings();

    if (empty($settings['signing_key'])) {
        return false;
    }

    if (empty($signature['timestamp']) || empty($signature['token']) || empty($signature['signature'])) {
        return false;
    }

    $expected = hash_hmac('sha256', $signature['timestamp'] . $signature['token'], $settings['signing_key']);

    return hash_equals($expected, $signature['signature']);
}

/**
 * Handle Mailgun webhook payloads.
 *
 * @param WP_REST_Request $request
 *
 * @return WP_REST_Response|WP_Error
 */
function wp_loft_email_provider_handle_webhook(WP_REST_Request $request) {
    $signature  = (array) $request->get_param('signature');
    $event_data = (array) $request->get_param('event-data');

    if (!wp_loft_email_provider_verify_signature($signature)) {
        return new WP_Error('loft_email_bad_signature', __('Invalid webhook signature.', 'wp-loft-booking'), ['status' => 403]);
    }

    $event = isset($event_data['event']) ? strtolower((string) $event_data['event']) : '';

    if (!in_array($event, ['delivered', 'complained', 'bounced'], true)) {
        return rest_ensure_response(['ignored' => true]);
    }

    wp_loft_email_provider_store_event([
        'type'      => $event,
        'recipient' => $event_data['recipient'] ?? '',
        'timestamp' => isset($event_data['timestamp']) ? (int) $event_data['timestamp'] : time(),
        'message'   => $event_data['message'] ?? [],
    ]);

    $message_id = '';
    if (isset($event_data['message']['headers']) && is_array($event_data['message']['headers'])) {
        $headers = array_change_key_case($event_data['message']['headers'], CASE_LOWER);
        $message_id = trim((string) ($headers['message-id'] ?? ''));
    }

    if (empty($message_id) && isset($event_data['message']['message-id'])) {
        $message_id = trim((string) $event_data['message']['message-id']);
    }

    if ($message_id) {
        global $wpdb;

        $jobs_table = $wpdb->prefix . 'loft_email_jobs';

        $updated = $wpdb->update(
            $jobs_table,
            [
                'webhook_status' => $event,
                'updated_at'     => current_time('mysql'),
            ],
            ['provider_message_id' => $message_id],
            ['%s', '%s'],
            ['%s']
        );

        error_log(sprintf('📫 Mailgun webhook "%s" captured for %s (jobs updated: %d).', $event, $message_id, (int) $updated));
    }

    return rest_ensure_response(['received' => true]);
}

/**
 * Persist recent provider events for debugging.
 *
 * @param array $event
 */
function wp_loft_email_provider_store_event(array $event) {
    $events = get_option('loft_email_events', []);

    array_unshift($events, $event);

    $events = array_slice($events, 0, 25);

    update_option('loft_email_events', $events);
}
