<?php
/**
 * Email job audit UI with filters, saved views, and CSV export.
 */

defined('ABSPATH') || exit;

/**
 * Normalize filter inputs for the email job log.
 */
function wp_loft_booking_email_job_filters() {
    $filters = [
        'job_id'    => isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0,
        'start_date' => isset($_GET['start_date']) ? sanitize_text_field((string) $_GET['start_date']) : '',
        'end_date'   => isset($_GET['end_date']) ? sanitize_text_field((string) $_GET['end_date']) : '',
        'loft_id'    => isset($_GET['loft_id']) ? (int) $_GET['loft_id'] : 0,
        'template'   => isset($_GET['template']) ? sanitize_text_field((string) $_GET['template']) : '',
        'status'     => isset($_GET['status']) ? sanitize_text_field((string) $_GET['status']) : '',
        'source'     => isset($_GET['source']) ? sanitize_text_field((string) $_GET['source']) : '',
    ];

    foreach (['start_date', 'end_date'] as $key) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key])) {
            $filters[$key] = '';
        }
    }

    $filters['job_id'] = max(0, (int) $filters['job_id']);

    $valid_statuses = ['pending', 'processing', 'retrying', 'completed', 'failed', 'rendered'];
    if (!in_array($filters['status'], $valid_statuses, true)) {
        $filters['status'] = '';
    }

    $valid_sources = ['automatic', 'manual'];
    if (!in_array($filters['source'], $valid_sources, true)) {
        $filters['source'] = '';
    }

    return $filters;
}

/**
 * Retrieve saved views keyed by name.
 */
function wp_loft_booking_email_job_views() {
    $views = get_option('loft_email_job_views', []);

    return is_array($views) ? $views : [];
}

/**
 * Fetch email jobs with optional filters.
 *
 * @param array $filters
 * @param int   $limit
 *
 * @return array<int,array<string,mixed>>
 */
function wp_loft_booking_fetch_email_jobs(array $filters, $limit = 200) {
    global $wpdb;

    $jobs_table      = $wpdb->prefix . 'loft_email_jobs';
    $lofts_table     = $wpdb->prefix . 'loft_lofts';
    $templates_table = $wpdb->prefix . 'loft_email_templates';
    $renders_table   = $wpdb->prefix . 'loft_email_renders';

    $where  = ['1=1'];
    $params = [];

    if (!empty($filters['start_date'])) {
        $where[]  = 'j.created_at >= %s';
        $params[] = $filters['start_date'] . ' 00:00:00';
    }

    if (!empty($filters['end_date'])) {
        $where[]  = 'j.created_at <= %s';
        $params[] = $filters['end_date'] . ' 23:59:59';
    }

    if (!empty($filters['job_id'])) {
        $where[]  = 'j.id = %d';
        $params[] = (int) $filters['job_id'];
    }

    if (!empty($filters['loft_id'])) {
        $where[]  = 'j.loft_id = %d';
        $params[] = (int) $filters['loft_id'];
    }

    if (!empty($filters['template'])) {
        $where[]  = '(j.template_key = %s OR t.slug = %s)';
        $params[] = $filters['template'];
        $params[] = $filters['template'];
    }

    if (!empty($filters['status'])) {
        $where[]  = 'j.status = %s';
        $params[] = $filters['status'];
    }

    if (!empty($filters['source'])) {
        $where[]  = 'j.source = %s';
        $params[] = $filters['source'];
    }

    $where_sql = implode(' AND ', $where);

    $query = $wpdb->prepare(
        "SELECT j.*, l.name AS loft_name, t.name AS template_name, t.slug AS template_slug,
            (SELECT rendered_subject FROM {$renders_table} r WHERE r.job_id = j.id ORDER BY r.id DESC LIMIT 1) AS rendered_subject,
            (SELECT rendered_body FROM {$renders_table} r WHERE r.job_id = j.id ORDER BY r.id DESC LIMIT 1) AS rendered_body,
            (SELECT rendered_text FROM {$renders_table} r WHERE r.job_id = j.id ORDER BY r.id DESC LIMIT 1) AS rendered_text,
            (SELECT variables FROM {$renders_table} r WHERE r.job_id = j.id ORDER BY r.id DESC LIMIT 1) AS render_variables
        FROM {$jobs_table} j
        LEFT JOIN {$lofts_table} l ON j.loft_id = l.id
        LEFT JOIN {$templates_table} t ON j.template_id = t.id
        WHERE {$where_sql}
        ORDER BY j.created_at DESC
        LIMIT %d",
        array_merge($params, [(int) $limit])
    );

    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * Find webhook events that match a Mailgun message ID.
 *
 * @param string $provider_message_id
 *
 * @return array<int,array<string,mixed>>
 */
function wp_loft_booking_filter_webhook_events($provider_message_id) {
    if (empty($provider_message_id)) {
        return [];
    }

    $events = get_option('loft_email_events', []);
    if (!is_array($events)) {
        return [];
    }

    $provider_message_id = strtolower(trim((string) $provider_message_id));

    return array_values(array_filter(
        $events,
        static function ($event) use ($provider_message_id) {
            if (empty($event['message']['headers'])) {
                return false;
            }

            $headers = array_change_key_case((array) $event['message']['headers'], CASE_LOWER);
            $message_id = isset($headers['message-id']) ? strtolower(trim((string) $headers['message-id'])) : '';

            return $message_id === $provider_message_id;
        }
    ));
}

/**
 * Render the Email Jobs admin page.
 */
function wp_loft_booking_email_jobs_page() {
    global $wpdb;

    if (!function_exists('maybe_add_column')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    $jobs_table = $wpdb->prefix . 'loft_email_jobs';

    maybe_add_column($jobs_table, 'event', "ALTER TABLE {$jobs_table} ADD COLUMN event VARCHAR(100) DEFAULT 'booking-email'");
    maybe_add_column($jobs_table, 'template_key', "ALTER TABLE {$jobs_table} ADD COLUMN template_key VARCHAR(150) NULL");
    maybe_add_column($jobs_table, 'source', "ALTER TABLE {$jobs_table} ADD COLUMN source VARCHAR(50) DEFAULT 'automatic'");

    $filters = wp_loft_booking_email_job_filters();
    $views   = wp_loft_booking_email_job_views();

    if (!empty($_GET['load_view'])) {
        $requested_view = sanitize_text_field((string) $_GET['load_view']);
        if (isset($views[$requested_view]) && is_array($views[$requested_view])) {
            $filters = array_merge($filters, $views[$requested_view]);
        }
    }

    if (isset($_POST['loft_email_jobs_save_view'])) {
        check_admin_referer('loft_email_jobs_save_view');

        $view_name = sanitize_text_field((string) ($_POST['view_name'] ?? ''));
        if ('' !== $view_name) {
            $views[$view_name] = $filters;
            update_option('loft_email_job_views', $views);

            echo '<div class="notice notice-success"><p>Saved view "' . esc_html($view_name) . '".</p></div>';
        }
    }

    if (isset($_GET['export']) && 'csv' === $_GET['export'] && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'loft_email_jobs_export')) {
        $export_jobs = wp_loft_booking_fetch_email_jobs($filters, 1000);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="loft-email-jobs.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Created', 'Loft', 'Template', 'Status', 'Source', 'Event', 'Message ID', 'Webhook', 'Last Error']);
        foreach ($export_jobs as $job) {
            fputcsv($out, [
                $job['id'],
                $job['created_at'],
                $job['loft_name'] ?? '',
                $job['template_key'] ?: ($job['template_slug'] ?? ''),
                $job['status'],
                $job['source'] ?: 'automatic',
                $job['event'],
                $job['provider_message_id'],
                $job['webhook_status'],
                $job['last_error'],
            ]);
        }
        fclose($out);
        exit;
    }

    $jobs = wp_loft_booking_fetch_email_jobs($filters);

    $loft_options = $wpdb->get_results(
        "SELECT id, name AS unit_name FROM {$wpdb->prefix}loft_lofts ORDER BY name",
        ARRAY_A
    );

    $template_rows = $wpdb->get_results(
        "SELECT slug, name FROM {$wpdb->prefix}loft_email_templates ORDER BY name",
        ARRAY_A
    );

    $template_keys = $wpdb->get_col(
        "SELECT DISTINCT template_key FROM {$jobs_table} WHERE template_key IS NOT NULL AND template_key <> '' ORDER BY template_key"
    );

    $template_options = [];
    foreach ($template_rows as $template_row) {
        $template_options[$template_row['slug']] = $template_row['name'];
    }

    foreach ($template_keys as $template_key) {
        if (!isset($template_options[$template_key])) {
            $template_options[$template_key] = $template_key;
        }
    }

    $export_url = add_query_arg(
        array_merge($filters, [
            'page'    => 'wp_loft_booking_email_jobs',
            'export'  => 'csv',
            '_wpnonce' => wp_create_nonce('loft_email_jobs_export'),
        ]),
        admin_url('admin.php')
    );

    ?>
    <div class="wrap">
        <h1>📨 Email Jobs</h1>
        <p>Inspect transactional email attempts with filters, saved views, and CSV export for audits.</p>

        <form method="get" style="margin-bottom:16px;">
            <input type="hidden" name="page" value="wp_loft_booking_email_jobs">
            <label style="margin-right:12px;">
                Job ID:
                <input type="number" name="job_id" value="<?php echo esc_attr($filters['job_id']); ?>" min="0" step="1" style="width:120px;">
            </label>
            <label>
                Start date:
                <input type="date" name="start_date" value="<?php echo esc_attr($filters['start_date']); ?>">
            </label>
            <label style="margin-left:12px;">
                End date:
                <input type="date" name="end_date" value="<?php echo esc_attr($filters['end_date']); ?>">
            </label>
            <label style="margin-left:12px;">
                Loft:
                <select name="loft_id">
                    <option value="0">All lofts</option>
                    <?php foreach ($loft_options as $loft) : ?>
                        <option value="<?php echo esc_attr($loft['id']); ?>" <?php selected((int) $filters['loft_id'], (int) $loft['id']); ?>><?php echo esc_html($loft['unit_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="margin-left:12px;">
                Template:
                <select name="template">
                    <option value="">All templates</option>
                    <?php foreach ($template_options as $slug => $name) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($filters['template'], $slug); ?>><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="margin-left:12px;">
                Status:
                <select name="status">
                    <option value="">Any status</option>
                    <?php foreach (['pending', 'processing', 'retrying', 'completed', 'failed'] as $status) : ?>
                        <option value="<?php echo esc_attr($status); ?>" <?php selected($filters['status'], $status); ?>><?php echo esc_html(ucfirst($status)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="margin-left:12px;">
                Source:
                <select name="source">
                    <option value="">Any</option>
                    <option value="automatic" <?php selected($filters['source'], 'automatic'); ?>>Automatic</option>
                    <option value="manual" <?php selected($filters['source'], 'manual'); ?>>Manual</option>
                </select>
            </label>
            <button class="button button-primary" type="submit" style="margin-left:12px;">Apply filters</button>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wp_loft_booking_email_jobs')); ?>">Reset</a>
            <a class="button" href="<?php echo esc_url($export_url); ?>" style="margin-left:6px;">Export CSV</a>
        </form>

        <form method="post" style="margin-bottom:16px;">
            <?php wp_nonce_field('loft_email_jobs_save_view'); ?>
            <label style="margin-right:8px;">Save current filters as view:</label>
            <input type="text" name="view_name" placeholder="e.g., Bounced this week" required>
            <button class="button" name="loft_email_jobs_save_view" value="1">Save view</button>
        </form>

        <?php if (!empty($views)) : ?>
            <form method="get" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="wp_loft_booking_email_jobs">
                <label>Saved views:</label>
                <select name="load_view">
                    <?php foreach ($views as $name => $view_filters) : ?>
                        <option value="<?php echo esc_attr($name); ?>" <?php selected(isset($_GET['load_view']) && $_GET['load_view'] === $name); ?>><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button">Load</button>
            </form>
        <?php endif; ?>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Created</th>
                    <th>Loft</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Webhook</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)) : ?>
                    <tr><td colspan="8">No email jobs found for the selected filters.</td></tr>
                <?php endif; ?>
                <?php foreach ($jobs as $job) :
                    $payload            = !empty($job['payload']) ? json_decode($job['payload'], true) : [];
                    $provider_response  = !empty($job['provider_response']) ? json_decode($job['provider_response'], true) : $job['provider_response'];
                    $render_variables   = !empty($job['render_variables']) ? json_decode($job['render_variables'], true) : [];
                    $webhook_events     = wp_loft_booking_filter_webhook_events($job['provider_message_id'] ?? '');
                    $template_label     = $job['template_key'] ?: ($job['template_slug'] ?? '');
                    ?>
                    <tr>
                        <td>#<?php echo esc_html($job['id']); ?></td>
                        <td><?php echo esc_html($job['created_at']); ?></td>
                        <td><?php echo esc_html($job['loft_name'] ?? '—'); ?></td>
                        <td><?php echo esc_html($template_label ?: 'N/A'); ?></td>
                        <td><?php echo esc_html(ucfirst($job['status'])); ?></td>
                        <td><?php echo esc_html($job['source'] ?: 'automatic'); ?></td>
                        <td><?php echo esc_html($job['webhook_status'] ?: '—'); ?></td>
                        <td>
                            <details>
                                <summary>Open</summary>
                                <div style="margin-top:8px;">
                                    <p><strong>Event:</strong> <?php echo esc_html($job['event'] ?: 'n/a'); ?> | <strong>Message ID:</strong> <?php echo esc_html($job['provider_message_id'] ?: 'n/a'); ?></p>
                                    <p><strong>Request payload</strong></p>
                                    <pre style="white-space:pre-wrap; background:#f6f7f7; padding:8px; border:1px solid #e5e7eb;"><?php echo esc_html($job['payload'] ? wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'n/a'); ?></pre>
                                    <p><strong>Rendered HTML preview</strong></p>
                                    <div style="border:1px solid #e5e7eb; padding:12px; background:#fff; max-height:320px; overflow:auto;">
                                        <?php echo !empty($job['rendered_body']) ? wp_kses_post($job['rendered_body']) : '<em>No render captured</em>'; ?>
                                    </div>
                                    <?php if (!empty($render_variables)) : ?>
                                        <p><strong>Template variables</strong></p>
                                        <pre style="white-space:pre-wrap; background:#f6f7f7; padding:8px; border:1px solid #e5e7eb;"><?php echo esc_html(wp_json_encode($render_variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                                    <?php endif; ?>
                                    <p><strong>Provider response</strong></p>
                                    <pre style="white-space:pre-wrap; background:#f6f7f7; padding:8px; border:1px solid #e5e7eb;"><?php echo esc_html(!empty($provider_response) ? wp_json_encode($provider_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'n/a'); ?></pre>
                                    <p><strong>Webhook events</strong></p>
                                    <?php if (!empty($webhook_events)) : ?>
                                        <pre style="white-space:pre-wrap; background:#f6f7f7; padding:8px; border:1px solid #e5e7eb;"><?php echo esc_html(wp_json_encode($webhook_events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                                    <?php else : ?>
                                        <p class="description">No matching webhook callbacks captured yet.</p>
                                    <?php endif; ?>
                                    <?php if (!empty($job['last_error'])) : ?>
                                        <p><strong>Last error:</strong> <code><?php echo esc_html($job['last_error']); ?></code></p>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
