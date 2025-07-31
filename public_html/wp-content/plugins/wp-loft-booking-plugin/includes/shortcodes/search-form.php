<?php
defined('ABSPATH') || exit;

function wp_loft_booking_search_form() {
    ob_start();
    global $wpdb;
    $branches = $wpdb->get_results("SELECT id, name, building_id, search_description FROM {$wpdb->prefix}loft_branches");
    ?>
    <form method="POST" action="https://loft1325.com/lofts-page/">
        <label for="branch">Location:</label>
        <select name="branch" id="branch" required>
            <option value="">Sélectionnez la location</option>
            <?php foreach ($branches as $branch): ?>
                <option value="<?php echo esc_attr($branch->building_id); ?>">
                    <?php echo esc_html($branch->search_description ?: $branch->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div style="display: flex; gap: 10px; align-items: center;">
            <div style="flex: 1;">
                <label for="adults">Adultes :</label>
                <input type="number" id="adults" name="adults" min="1" max="10" value="1" required style="width: 100%;">
            </div>
            <div style="flex: 1;">
                <label for="children">Enfants :</label>
                <input type="number" id="children" name="children" min="0" max="10" value="0" style="width: 100%;">
            </div>
        </div>
        <label for="checkin_date">Date d'arrivée :</label>
        <input type="date" name="checkin_date" id="checkin_date" required>
        <label for="checkout_date">Date de départ :</label>
        <input type="date" name="checkout_date" id="checkout_date" required>
        <button type="submit" name="search_bookings">Rechercher</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('loft_booking_search', 'wp_loft_booking_search_form');