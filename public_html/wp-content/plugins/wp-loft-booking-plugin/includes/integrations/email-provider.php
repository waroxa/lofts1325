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

    return true;
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
