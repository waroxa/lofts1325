<?php
defined('ABSPATH') || exit;

function wp_loft_display_types() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'loft_types';
    $loft_types = $wpdb->get_results("SELECT * FROM $table_name");

    ob_start();
    echo '<div class="nd-booking-results-container" style="display: flex; gap: 20px; flex-wrap: wrap;">';
    foreach ($loft_types as $type) {
        ?>
        <div class="nd-booking-result-item" style="border: 1px solid #ddd; border-radius: 10px; margin-bottom: 20px; padding: 15px; width: 300px;">
            <div class="nd-booking-thumbnail" style="width: 100%; height: 200px; overflow: hidden; border-radius: 8px;">
                <img src="<?php echo esc_url($type->image_url ?: 'https://example.com/default-image.jpg'); ?>" alt="<?php echo esc_attr($type->name); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="nd-booking-info" style="padding: 15px;">
                <h3><?php echo esc_html($type->name); ?></h3>
                <p><strong>Nombre maximal d'invités :</strong> <?php echo esc_html($type->guests); ?></p>
                <p><strong>Prix :</strong> $<?php echo esc_html(number_format($type->price, 2)); ?> CAD</p>
                <p><?php echo nl2br(esc_html($type->mini_description)); ?></p>
                <a href="<?php echo home_url('/lofts-details/') . '?type=' . urlencode($type->name); ?>" class="nd-booking-button" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #76B1C4; color: white; text-decoration: none; border-radius: 4px;">Voir les détails</a>
            </div>
        </div>
        <?php
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('display_loft_types', 'wp_loft_display_types');

function wp_loft_detail_page() {
    if (!isset($_GET['type'])) return '<p>No loft type selected. Please choose from the main page.</p>';

    global $wpdb;
    $table_name = $wpdb->prefix . 'loft_types';
    $loft_type = sanitize_text_field($_GET['type']);
    $loft = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE name = %s", $loft_type));

    if (!$loft) return '<p>Loft type not found.</p>';

    ob_start();
    ?>
    <div class="loft-detail-page" style="padding: 20px;">
        <h1><?php echo esc_html($loft->name); ?></h1>
        <p><strong>Rating:</strong>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span style="color: <?php echo $i <= $loft->reviews ? '#FFD700' : '#ccc'; ?>">&#9733;</span>
            <?php endfor; ?>
            (<?php echo esc_html($loft->reviews); ?> / 5)
        </p><br />
        <?php if (!empty($loft->revolution_slider_shortcode)) echo do_shortcode(stripslashes($loft->revolution_slider_shortcode)); ?>
        <br /><p><?php echo nl2br(esc_html($loft->description)); ?></p>
        <?php if (!empty($loft->room_plan_url)): ?>
            <div style="margin-top: 20px; text-align: center;">
                <h3 style="margin-bottom: 10px;">Room Plan:</h3>
                <div style="max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background-color: #f9f9f9;">
                    <img src="<?php echo esc_url($loft->room_plan_url); ?>" alt="Room Plan" style="width: 100%; height: auto; max-height: 400px; border-radius: 8px; object-fit: contain;">
                </div>
            </div>
        <?php endif; ?>
        <a href="<?php echo home_url('/booking-page/?type=' . urlencode($loft->name)); ?>" style="background-color: #76B1C4; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Bientôt disponible</a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('loft_detail_page', 'wp_loft_detail_page');