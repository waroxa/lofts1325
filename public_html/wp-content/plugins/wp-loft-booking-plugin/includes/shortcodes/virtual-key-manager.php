<?php
defined('ABSPATH') || exit;

function wp_loft_booking_virtual_key_manager_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Vous devez être connecté pour gérer les clés virtuelles. / You must be logged in to manage virtual keys.', 'wp-loft-booking') . '</p>';
    }

    if (!current_user_can('manage_options')) {
        return '<p>' . esc_html__('Vous ne disposez pas des autorisations nécessaires pour générer des clés virtuelles. / You do not have permission to generate virtual keys.', 'wp-loft-booking') . '</p>';
    }

    if (!function_exists('wp_loft_booking_prepare_unit_rows')) {
        return '';
    }

    $rows = wp_loft_booking_prepare_unit_rows();

    ob_start();
    ?>
    <div class="wplb-key-manager">
        <?php wp_nonce_field('wplb_generate_virtual_key', 'wplb_generate_virtual_key_nonce'); ?>
        <div id="wplb-generate-key-feedback" class="wplb-key-feedback" style="margin:15px 0;"></div>
        <div class="wplb-key-table-wrapper" style="overflow-x:auto;">
            <table class="wplb-key-table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:8px; border-bottom:2px solid #e5e7eb;"><?php esc_html_e('Loft', 'wp-loft-booking'); ?></th>
                        <th style="text-align:left; padding:8px; border-bottom:2px solid #e5e7eb;"><?php esc_html_e('Statut', 'wp-loft-booking'); ?></th>
                        <th style="text-align:left; padding:8px; border-bottom:2px solid #e5e7eb;"><?php esc_html_e('Disponible jusqu\'au', 'wp-loft-booking'); ?></th>
                        <th style="text-align:left; padding:8px; border-bottom:2px solid #e5e7eb;"><?php esc_html_e('Action', 'wp-loft-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td style="padding:8px; border-bottom:1px solid #f3f4f6; font-weight:600;">
                                <?php echo esc_html($row['name']); ?>
                            </td>
                            <td style="padding:8px; border-bottom:1px solid #f3f4f6; color: <?php echo esc_attr($row['status_color']); ?>; font-weight:600;">
                                <?php echo esc_html($row['status_text']); ?>
                            </td>
                            <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                                <?php echo esc_html($row['availability_display']); ?>
                            </td>
                            <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                                <button type="button"
                                        class="button button-secondary wplb-generate-key"
                                        data-unit-id="<?php echo esc_attr($row['id']); ?>"
                                        data-unit-name="<?php echo esc_attr($row['name']); ?>"
                                        <?php disabled($row['button_disabled']); ?>>
                                    <?php esc_html_e('Générer une clé virtuelle', 'wp-loft-booking'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php

    wp_loft_booking_render_virtual_key_script();

    return ob_get_clean();
}
add_shortcode('loft_virtual_key_manager', 'wp_loft_booking_virtual_key_manager_shortcode');
