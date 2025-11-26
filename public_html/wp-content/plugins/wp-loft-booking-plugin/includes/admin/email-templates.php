<?php
/**
 * Admin UI for bilingual email templates with versioning and previews.
 */

defined('ABSPATH') || exit;

/**
 * Get the table name for email templates.
 */
function wp_loft_booking_email_templates_table() {
    global $wpdb;

    return $wpdb->prefix . 'loft_email_templates';
}

/**
 * Get the table name for email template versions.
 */
function wp_loft_booking_email_template_versions_table() {
    global $wpdb;

    return $wpdb->prefix . 'loft_email_template_versions';
}

/**
 * Ensure at least one template exists to work with.
 */
function wp_loft_booking_seed_default_email_template() {
    global $wpdb;

    $templates_table = wp_loft_booking_email_templates_table();
    $existing        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$templates_table}" );

    if ( 0 !== $existing ) {
        return;
    }

    $wpdb->insert(
        $templates_table,
        array(
            'name'        => 'Booking confirmation',
            'slug'        => 'booking-confirmation',
            'description' => 'Guest confirmation email with bilingual content.',
            'subject'     => 'Lofts 1325 booking',
            'body'        => '<p>Merci pour votre réservation | Thank you for your booking</p>',
            'status'      => 'active',
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s' )
    );
}

/**
 * Allowed placeholders for variable validation.
 */
function wp_loft_booking_email_template_tokens() {
    return array(
        'guest_name'             => __('Guest full name', 'wp-loft-booking'),
        'room_name'              => __('Loft name or number', 'wp-loft-booking'),
        'checkin_fr'             => __('Check-in date (FR)', 'wp-loft-booking'),
        'checkout_fr'            => __('Checkout date (FR)', 'wp-loft-booking'),
        'checkin_en'             => __('Check-in date (EN)', 'wp-loft-booking'),
        'checkout_en'            => __('Checkout date (EN)', 'wp-loft-booking'),
        'guest_count_fr'         => __('Guest count (FR)', 'wp-loft-booking'),
        'guest_count_en'         => __('Guest count (EN)', 'wp-loft-booking'),
        'total_fr'               => __('Total amount (FR)', 'wp-loft-booking'),
        'total_en'               => __('Total amount (EN)', 'wp-loft-booking'),
        'virtual_key_message_fr' => __('Virtual key status (FR)', 'wp-loft-booking'),
        'virtual_key_message_en' => __('Virtual key status (EN)', 'wp-loft-booking'),
        'property_address'       => __('Property address', 'wp-loft-booking'),
        'support_email'          => __('Support email', 'wp-loft-booking'),
        'booking_reference'      => __('Internal booking reference', 'wp-loft-booking'),
        'parking_code'           => __('Parking code', 'wp-loft-booking'),
        'wifi_name'              => __('Wi-Fi network name', 'wp-loft-booking'),
        'wifi_password'          => __('Wi-Fi password', 'wp-loft-booking'),
    );
}

/**
 * Provide deterministic sample booking data for previews.
 */
function wp_loft_booking_sample_booking_data() {
    return array(
        'guest_name'             => 'Alex Tremblay',
        'room_name'              => 'Loft 307 · Signature King',
        'checkin_fr'             => '12 mars 2025',
        'checkout_fr'            => '14 mars 2025',
        'checkin_en'             => 'March 12, 2025',
        'checkout_en'            => 'March 14, 2025',
        'guest_count_fr'         => '2 invités',
        'guest_count_en'         => '2 guests',
        'total_fr'               => '842,00 $ CAD',
        'total_en'               => '$842.00 CAD',
        'virtual_key_message_fr' => __('Votre clé numérique sera envoyée avant l’arrivée.', 'wp-loft-booking'),
        'virtual_key_message_en' => __('Your smart key will arrive shortly before check-in.', 'wp-loft-booking'),
        'property_address'       => '1325 3e Avenue, Val-d’Or, QC, Canada',
        'support_email'          => 'concierge@loft1325.com',
        'booking_reference'      => '#L1325-2048',
        'parking_code'           => 'C-74219',
        'wifi_name'              => 'Loft1325-Guest',
        'wifi_password'          => 'valdor-2024',
    );
}

/**
 * Replace placeholder tokens with values.
 */
function wp_loft_booking_render_template_content( $content, $data ) {
    return preg_replace_callback(
        '/{{\s*([a-zA-Z0-9_\.]+)\s*}}/',
        static function( $matches ) use ( $data ) {
            $key = $matches[1];

            return array_key_exists( $key, $data ) ? $data[ $key ] : $matches[0];
        },
        $content
    );
}

/**
 * Return placeholders present in a string.
 */
function wp_loft_booking_collect_template_tokens( $content ) {
    preg_match_all( '/{{\s*([a-zA-Z0-9_\.]+)\s*}}/', (string) $content, $matches );

    return array_unique( $matches[1] ?? array() );
}

/**
 * Persist a new version for a template.
 */
function wp_loft_booking_create_template_version( $template_id, $payload, $status = 'draft' ) {
    global $wpdb;

    $versions_table = wp_loft_booking_email_template_versions_table();

    $current_max = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(version_number) FROM {$versions_table} WHERE template_id = %d", $template_id ) );
    $next        = $current_max + 1;

    $wpdb->insert(
        $versions_table,
        array(
            'template_id'     => $template_id,
            'version_number'  => $next,
            'status'          => $status,
            'subject_fr'      => sanitize_text_field( $payload['subject_fr'] ?? '' ),
            'subject_en'      => sanitize_text_field( $payload['subject_en'] ?? '' ),
            'body_html_fr'    => wp_kses_post( $payload['body_html_fr'] ?? '' ),
            'body_html_en'    => wp_kses_post( $payload['body_html_en'] ?? '' ),
            'body_text_fr'    => wp_kses_post( $payload['body_text_fr'] ?? '' ),
            'body_text_en'    => wp_kses_post( $payload['body_text_en'] ?? '' ),
            'notes'           => sanitize_text_field( $payload['notes'] ?? '' ),
            'created_by'      => get_current_user_id(),
            'published_at'    => 'published' === $status ? current_time( 'mysql' ) : null,
        ),
        array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
    );

    $insert_id = (int) $wpdb->insert_id;

    if ( $insert_id > 0 ) {
        $templates_table = wp_loft_booking_email_templates_table();
        $wpdb->update(
            $templates_table,
            array( 'latest_version_id' => $insert_id ),
            array( 'id' => $template_id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    if ( 'published' === $status && $insert_id > 0 ) {
        wp_loft_booking_publish_template_version( $template_id, $insert_id );
    }

    return $insert_id;
}

/**
 * Mark a version as published for its template.
 */
function wp_loft_booking_publish_template_version( $template_id, $version_id ) {
    global $wpdb;

    $versions_table  = wp_loft_booking_email_template_versions_table();
    $templates_table = wp_loft_booking_email_templates_table();

    $wpdb->query( $wpdb->prepare( "UPDATE {$versions_table} SET status = 'draft' WHERE template_id = %d", $template_id ) );
    $wpdb->query( $wpdb->prepare( "UPDATE {$versions_table} SET status = 'published', published_at = %s WHERE id = %d", current_time( 'mysql' ), $version_id ) );

    $wpdb->update(
        $templates_table,
        array( 'published_version_id' => $version_id ),
        array( 'id' => $template_id ),
        array( '%d' ),
        array( '%d' )
    );
}

/**
 * Handle create/publish actions from the admin UI.
 */
function wp_loft_booking_handle_email_template_actions() {
    if ( empty( $_POST['wp_loft_email_template_action'] ) ) {
        return;
    }

    check_admin_referer( 'wp_loft_booking_email_templates' );

    $action      = sanitize_text_field( wp_unslash( $_POST['wp_loft_email_template_action'] ) );
    $template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

    if ( $template_id <= 0 ) {
        return;
    }

    if ( 'publish_version' === $action ) {
        $version_id = isset( $_POST['version_id'] ) ? absint( $_POST['version_id'] ) : 0;

        if ( $version_id > 0 ) {
            wp_loft_booking_publish_template_version( $template_id, $version_id );
            add_settings_error( 'wp_loft_booking_email_templates', 'published', __( 'Template version published.', 'wp-loft-booking' ), 'updated' );
        }

        return;
    }

    $payload = array(
        'subject_fr'   => wp_unslash( $_POST['subject_fr'] ?? '' ),
        'subject_en'   => wp_unslash( $_POST['subject_en'] ?? '' ),
        'body_html_fr' => wp_unslash( $_POST['body_html_fr'] ?? '' ),
        'body_html_en' => wp_unslash( $_POST['body_html_en'] ?? '' ),
        'body_text_fr' => wp_unslash( $_POST['body_text_fr'] ?? '' ),
        'body_text_en' => wp_unslash( $_POST['body_text_en'] ?? '' ),
        'notes'        => wp_unslash( $_POST['notes'] ?? '' ),
    );

    $tokens            = wp_loft_booking_email_template_tokens();
    $unknown_tokens    = array();
    $collected_tokens  = array();
    $fields_to_inspect = array( 'subject_fr', 'subject_en', 'body_html_fr', 'body_html_en', 'body_text_fr', 'body_text_en' );

    foreach ( array( 'subject_fr', 'subject_en', 'body_html_fr', 'body_html_en' ) as $required_field ) {
        if ( '' === trim( $payload[ $required_field ] ) ) {
            add_settings_error(
                'wp_loft_booking_email_templates',
                'missing_field',
                __( 'Subject and body fields are required for both languages.', 'wp-loft-booking' ),
                'error'
            );

            return;
        }
    }

    foreach ( $fields_to_inspect as $field ) {
        $field_tokens      = wp_loft_booking_collect_template_tokens( $payload[ $field ] );
        $collected_tokens  = array_merge( $collected_tokens, $field_tokens );
        $unknown_tokens    = array_merge( $unknown_tokens, array_diff( $field_tokens, array_keys( $tokens ) ) );
    }

    $unknown_tokens = array_unique( $unknown_tokens );

    if ( ! empty( $unknown_tokens ) ) {
        add_settings_error(
            'wp_loft_booking_email_templates',
            'invalid_tokens',
            sprintf(
                /* translators: %s: list of invalid tokens */
                esc_html__( 'Unknown tokens found: %s', 'wp-loft-booking' ),
                esc_html( implode( ', ', $unknown_tokens ) )
            ),
            'error'
        );

        return;
    }

    $status = 'save_and_publish' === $action ? 'published' : 'draft';
    $id     = wp_loft_booking_create_template_version( $template_id, $payload, $status );

    if ( $id > 0 ) {
        $message = 'published' === $status
            ? __( 'Draft saved and published.', 'wp-loft-booking' )
            : __( 'Draft version saved.', 'wp-loft-booking' );

        add_settings_error( 'wp_loft_booking_email_templates', 'saved', $message, 'updated' );
    }
}
add_action( 'admin_init', 'wp_loft_booking_handle_email_template_actions' );

/**
 * Render the admin page.
 */
function wp_loft_booking_email_templates_page() {
    wp_loft_booking_seed_default_email_template();

    global $wpdb;

    $templates_table = wp_loft_booking_email_templates_table();
    $versions_table  = wp_loft_booking_email_template_versions_table();
    $templates       = $wpdb->get_results( "SELECT * FROM {$templates_table} ORDER BY name ASC" );
    $template_id     = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : ( $templates[0]->id ?? 0 );

    $versions = array();
    if ( $template_id ) {
        $versions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$versions_table} WHERE template_id = %d ORDER BY version_number DESC",
                $template_id
            )
        );
    }

    $preview_version_id = isset( $_GET['preview_version'] ) ? absint( $_GET['preview_version'] ) : 0;
    $preview_version    = null;

    if ( $preview_version_id > 0 ) {
        $preview_version = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$versions_table} WHERE id = %d AND template_id = %d",
                $preview_version_id,
                $template_id
            )
        );
    }

    $diff_from_id = isset( $_GET['diff_from'] ) ? absint( $_GET['diff_from'] ) : 0;
    $diff_to_id   = isset( $_GET['diff_to'] ) ? absint( $_GET['diff_to'] ) : 0;

    $diff_from = $diff_to = null;

    if ( $diff_from_id && $diff_to_id ) {
        $diff_from = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$versions_table} WHERE id = %d", $diff_from_id ) );
        $diff_to   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$versions_table} WHERE id = %d", $diff_to_id ) );
    }

    $tokens      = wp_loft_booking_email_template_tokens();
    $sample_data = wp_loft_booking_sample_booking_data();

    settings_errors( 'wp_loft_booking_email_templates' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Transactional Email Templates', 'wp-loft-booking' ); ?></h1>
        <p class="description"><?php esc_html_e( 'Edit bilingual content, validate placeholders, and manage version history for booking emails.', 'wp-loft-booking' ); ?></p>

        <form method="get" action="">
            <input type="hidden" name="page" value="wp_loft_booking_email_templates" />
            <label for="template_id" class="screen-reader-text"><?php esc_html_e( 'Template', 'wp-loft-booking' ); ?></label>
            <select id="template_id" name="template_id">
                <?php foreach ( $templates as $template ) : ?>
                    <option value="<?php echo esc_attr( $template->id ); ?>" <?php selected( $template_id, $template->id ); ?>><?php echo esc_html( $template->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit"><?php esc_html_e( 'Switch template', 'wp-loft-booking' ); ?></button>
        </form>

        <hr />

        <h2><?php esc_html_e( 'Create a new version', 'wp-loft-booking' ); ?></h2>
        <p><?php esc_html_e( 'Editors display FR/EN side-by-side with Visual and Text (code) modes built in.', 'wp-loft-booking' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'wp_loft_booking_email_templates' ); ?>
            <input type="hidden" name="template_id" value="<?php echo esc_attr( $template_id ); ?>" />

            <div class="loft-email-template-grid">
                <div>
                    <h3><?php esc_html_e( '🇫🇷 Sujet (FR)', 'wp-loft-booking' ); ?></h3>
                    <input type="text" name="subject_fr" class="large-text" required />

                    <h3><?php esc_html_e( '🇫🇷 Corps (éditeur visuel + code)', 'wp-loft-booking' ); ?></h3>
                    <?php
                    wp_editor(
                        '',
                        'body_html_fr',
                        array(
                            'textarea_name' => 'body_html_fr',
                            'textarea_rows' => 15,
                            'tinymce'       => true,
                            'quicktags'     => true,
                        )
                    );
                    ?>

                    <h4><?php esc_html_e( 'Version texte (optionnel)', 'wp-loft-booking' ); ?></h4>
                    <textarea name="body_text_fr" rows="6" class="large-text code" placeholder="<?php esc_attr_e( 'Plain text fallback content in French.', 'wp-loft-booking' ); ?>"></textarea>
                </div>

                <div>
                    <h3><?php esc_html_e( '🇬🇧 Subject (EN)', 'wp-loft-booking' ); ?></h3>
                    <input type="text" name="subject_en" class="large-text" required />

                    <h3><?php esc_html_e( '🇬🇧 Body (visual editor + code)', 'wp-loft-booking' ); ?></h3>
                    <?php
                    wp_editor(
                        '',
                        'body_html_en',
                        array(
                            'textarea_name' => 'body_html_en',
                            'textarea_rows' => 15,
                            'tinymce'       => true,
                            'quicktags'     => true,
                        )
                    );
                    ?>

                    <h4><?php esc_html_e( 'Plain text (optional)', 'wp-loft-booking' ); ?></h4>
                    <textarea name="body_text_en" rows="6" class="large-text code" placeholder="<?php esc_attr_e( 'Plain text fallback content in English.', 'wp-loft-booking' ); ?>"></textarea>
                </div>
            </div>

            <label for="notes" class="screen-reader-text"><?php esc_html_e( 'Version notes', 'wp-loft-booking' ); ?></label>
            <textarea id="notes" name="notes" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Why this change? What’s new in this version?', 'wp-loft-booking' ); ?>"></textarea>

            <p>
                <button type="submit" name="wp_loft_email_template_action" value="save_draft" class="button button-primary"><?php esc_html_e( 'Save draft version', 'wp-loft-booking' ); ?></button>
                <button type="submit" name="wp_loft_email_template_action" value="save_and_publish" class="button"><?php esc_html_e( 'Save & publish', 'wp-loft-booking' ); ?></button>
            </p>
        </form>

        <div class="loft-email-template-columns">
            <div class="loft-email-panel">
                <h2><?php esc_html_e( 'Allowed variables', 'wp-loft-booking' ); ?></h2>
                <ul class="loft-email-token-list">
                    <?php foreach ( $tokens as $token => $label ) : ?>
                        <li><code>{{<?php echo esc_html( $token ); ?>}}</code> — <?php echo esc_html( $label ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="loft-email-panel">
                <h2><?php esc_html_e( 'Sample preview data', 'wp-loft-booking' ); ?></h2>
                <dl class="loft-email-preview-data">
                    <?php foreach ( $sample_data as $key => $value ) : ?>
                        <div>
                            <dt><?php echo esc_html( $key ); ?></dt>
                            <dd><?php echo esc_html( $value ); ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>

        <hr />

        <h2><?php esc_html_e( 'Version history', 'wp-loft-booking' ); ?></h2>

        <?php if ( empty( $versions ) ) : ?>
            <p><?php esc_html_e( 'No versions yet. Save a draft to get started.', 'wp-loft-booking' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Version', 'wp-loft-booking' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-loft-booking' ); ?></th>
                        <th><?php esc_html_e( 'Notes', 'wp-loft-booking' ); ?></th>
                        <th><?php esc_html_e( 'Created', 'wp-loft-booking' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-loft-booking' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $versions as $version ) : ?>
                        <tr>
                            <td><?php echo esc_html( $version->version_number ); ?></td>
                            <td><?php echo esc_html( ucfirst( $version->status ) ); ?></td>
                            <td><?php echo esc_html( $version->notes ); ?></td>
                            <td><?php echo esc_html( $version->created_at ); ?></td>
                            <td>
                                <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'wp_loft_booking_email_templates', 'template_id' => $template_id, 'preview_version' => $version->id ) ) ); ?>"><?php esc_html_e( 'Preview', 'wp-loft-booking' ); ?></a>
                                <form method="post" action="" style="display:inline-block;">
                                    <?php wp_nonce_field( 'wp_loft_booking_email_templates' ); ?>
                                    <input type="hidden" name="template_id" value="<?php echo esc_attr( $template_id ); ?>" />
                                    <input type="hidden" name="version_id" value="<?php echo esc_attr( $version->id ); ?>" />
                                    <button type="submit" name="wp_loft_email_template_action" value="publish_version" class="button button-secondary"><?php esc_html_e( 'Publish', 'wp-loft-booking' ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ( $preview_version ) : ?>
            <hr />
            <h2><?php esc_html_e( 'Preview with sample booking data', 'wp-loft-booking' ); ?></h2>
            <div class="loft-email-preview-grid">
                <div>
                    <h3><?php esc_html_e( '🇫🇷 French', 'wp-loft-booking' ); ?> — <?php echo esc_html( $preview_version->subject_fr ); ?></h3>
                    <div class="loft-email-preview">
                        <?php echo wp_kses_post( wp_loft_booking_render_template_content( $preview_version->body_html_fr, $sample_data ) ); ?>
                    </div>
                </div>
                <div>
                    <h3><?php esc_html_e( '🇬🇧 English', 'wp-loft-booking' ); ?> — <?php echo esc_html( $preview_version->subject_en ); ?></h3>
                    <div class="loft-email-preview">
                        <?php echo wp_kses_post( wp_loft_booking_render_template_content( $preview_version->body_html_en, $sample_data ) ); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $diff_from && $diff_to ) : ?>
            <hr />
            <h2><?php esc_html_e( 'Diff between versions', 'wp-loft-booking' ); ?></h2>
            <div class="loft-email-preview-grid">
                <div>
                    <h3><?php esc_html_e( '🇫🇷 French body', 'wp-loft-booking' ); ?></h3>
                    <?php echo wp_kses_post( wp_text_diff( $diff_from->body_html_fr, $diff_to->body_html_fr ) ); ?>
                </div>
                <div>
                    <h3><?php esc_html_e( '🇬🇧 English body', 'wp-loft-booking' ); ?></h3>
                    <?php echo wp_kses_post( wp_text_diff( $diff_from->body_html_en, $diff_to->body_html_en ) ); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( count( $versions ) >= 2 ) : ?>
            <hr />
            <h2><?php esc_html_e( 'Compare versions', 'wp-loft-booking' ); ?></h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="wp_loft_booking_email_templates" />
                <input type="hidden" name="template_id" value="<?php echo esc_attr( $template_id ); ?>" />
                <label for="diff_from"><?php esc_html_e( 'From', 'wp-loft-booking' ); ?></label>
                <select id="diff_from" name="diff_from">
                    <?php foreach ( $versions as $version ) : ?>
                        <option value="<?php echo esc_attr( $version->id ); ?>"><?php echo esc_html( 'v' . $version->version_number ); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="diff_to"><?php esc_html_e( 'To', 'wp-loft-booking' ); ?></label>
                <select id="diff_to" name="diff_to">
                    <?php foreach ( $versions as $version ) : ?>
                        <option value="<?php echo esc_attr( $version->id ); ?>"><?php echo esc_html( 'v' . $version->version_number ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit"><?php esc_html_e( 'Show diff', 'wp-loft-booking' ); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <style>
        .loft-email-template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin: 16px 0;
        }

        .loft-email-template-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .loft-email-panel {
            background: #fff;
            border: 1px solid #dcdcdc;
            padding: 16px;
            border-radius: 8px;
        }

        .loft-email-token-list {
            columns: 2;
            margin: 0;
            padding-left: 18px;
        }

        .loft-email-token-list li {
            margin-bottom: 6px;
        }

        .loft-email-preview-data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px 12px;
        }

        .loft-email-preview-data dt {
            font-weight: 600;
        }

        .loft-email-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }

        .loft-email-preview {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            max-height: 600px;
            overflow: auto;
        }
    </style>
    <?php
}
