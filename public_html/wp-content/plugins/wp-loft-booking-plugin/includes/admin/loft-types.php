<?php
defined('ABSPATH') || exit;

function wp_loft_types_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'loft_types';
    $loft_types = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1>All Loft Types</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Image</th>
                    <th>Max Guests</th>
                    <th>Room Size</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loft_types as $type): ?>
                    <tr>
                        <td><?php echo esc_html($type->name); ?></td>
                        <td><img src="<?php echo esc_url($type->image_url); ?>" style="width: 100px;"></td>
                        <td><?php echo esc_html($type->guests); ?></td>
                        <td><?php echo esc_html($type->room_size); ?> sq ft</td>
                        <td>$<?php echo esc_html(number_format($type->price, 2)); ?> CAD</td>
                        <td><?php echo esc_html($type->quantity); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=add-edit-loft-type&edit_loft_id=' . $type->id); ?>" class="button">Edit</a>
                            <a href="<?php echo admin_url('admin.php?page=loft-types&delete_loft_id=' . $type->id); ?>" class="button button-danger" onclick="return confirm('Are you sure you want to delete this loft type?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function wp_add_edit_loft_type_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'loft_types';

    $edit_mode = false;
    $loft_type = null;

    if (isset($_GET['edit_loft_id'])) {
        $edit_id = intval($_GET['edit_loft_id']);
        $loft_type = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $edit_id");
        if ($loft_type) {
            // Remove slashes when editing existing data
            $loft_type = stripslashes_deep($loft_type);
            $edit_mode = true;
        }
    }

    // Handle form submissions
    if (isset($_POST['save_loft_type'])) {
        if (!isset($_POST['loft_type_nonce']) || !wp_verify_nonce($_POST['loft_type_nonce'], 'save_loft_type')) {
            echo '<div class="notice notice-error is-dismissible"><p>Security check failed. Please try again.</p></div>';
            return;
        }

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'revolution_slider_shortcode' => sanitize_text_field($_POST['revolution_slider_shortcode']),
            'room_size' => absint($_POST['room_size']),
            'room_color' => sanitize_hex_color($_POST['room_color']),
            'quantity' => absint($_POST['quantity']),
            'guests' => absint($_POST['guests']),
            'price' => floatval($_POST['price']),
            'description' => sanitize_textarea_field(stripslashes($_POST['description'])),
            'mini_description' => sanitize_textarea_field(stripslashes($_POST['mini_description'])),
            'room_plan_url' => esc_url_raw($_POST['room_plan_url']),
            'inclusions' => sanitize_textarea_field($_POST['inclusions']),
            'nearby_attractions' => sanitize_textarea_field($_POST['nearby_attractions']),
            'reviews' => floatval($_POST['reviews']),
            'header_image_url' => esc_url_raw($_POST['header_image_url']),
            'page_layout' => sanitize_text_field($_POST['page_layout']),
            'featured_image_size' => sanitize_text_field($_POST['featured_image_size']),
            'services' => sanitize_text_field($_POST['services']),
            'additional_services' => sanitize_text_field($_POST['additional_services']),
        ];

        // Handle seasonal price variations (dropdown format)
        $seasonal_prices = [];
        if (!empty($_POST['season_prices'])) {
            foreach ($_POST['season_prices'] as $season => $price) {
                if (!empty($price)) {
                    $season = sanitize_text_field($season);
                    $price = floatval($price);
                    $seasonal_prices[] = ['season' => $season, 'price' => $price];
                }
            }
            $data['price_variations'] = wp_json_encode($seasonal_prices);
        }

        // Handle blocked reservation dates (via date picker)
        $blocked_dates = [];
        if (!empty($_POST['blocked_dates'])) {
            $blocked_dates = array_map('sanitize_text_field', $_POST['blocked_dates']);
            $data['block_reservations'] = wp_json_encode($blocked_dates);
        }

        if ($edit_mode) {
            $wpdb->update($table_name, $data, ['id' => $edit_id]);
            $message = 'Loft Type Updated Successfully!';
        } else {
            $wpdb->insert($table_name, $data);
            $message = 'Loft Type Saved Successfully!';
        }

        if ($wpdb->last_error) {
            echo '<div class="notice notice-error is-dismissible"><p>Error: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h1><?php echo $edit_mode ? 'Edit Loft Type' : 'Add New Loft Type'; ?></h1>
        <form method="POST">
            <?php wp_nonce_field('save_loft_type', 'loft_type_nonce'); ?>

            <h2>Main Settings</h2>
            <table class="form-table">
                <tr>
                    <th><label for="name">Loft Type Name</label></th>
                    <td><input type="text" name="name" class="regular-text" value="<?php echo esc_attr($loft_type->name ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="image_url">Image URL</label></th>
                    <td><input type="url" name="image_url" class="regular-text" value="<?php echo esc_url($loft_type->image_url ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="revolution_slider_shortcode">Revolution Slider Shortcode</label></th>
                    <td><input type="text" name="revolution_slider_shortcode" class="regular-text" value="<?php echo esc_attr($loft_type->revolution_slider_shortcode ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="room_size">Room Size (sq ft)</label></th>
                    <td><input type="number" name="room_size" class="small-text" min="1" value="<?php echo esc_attr($loft_type->room_size ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="room_color">Room Color (HEX)</label></th>
                    <td><input type="text" name="room_color" class="regular-text" placeholder="#FFFFFF" value="<?php echo esc_attr($loft_type->room_color ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="quantity">Quantity</label></th>
                    <td><input type="number" name="quantity" min="1" class="small-text" value="<?php echo esc_attr($loft_type->quantity ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="guests">Max Guests</label></th>
                    <td><input type="number" name="guests" min="1" class="small-text" value="<?php echo esc_attr($loft_type->guests ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="price">Price (CAD)</label></th>
                    <td><input type="number" name="price" step="0.01" class="small-text" value="<?php echo esc_attr($loft_type->price ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="reviews">Reviews (Average Score)</label></th>
                    <td><input type="number" name="reviews" step="0.1" class="small-text" min="0" max="5" value="<?php echo esc_attr($loft_type->reviews ?? 5.0); ?>"></td>
                </tr>
                <tr>
                    <th><label for="mini_description">Mini Description (for results page)</label></th>
                    <td><textarea name="mini_description" rows="3"><?php echo esc_html($loft_type->mini_description ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="description">Detailed Description (for details page)</label></th>
                    <td><textarea name="description" rows="6"><?php echo esc_html($loft_type->description ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="room_plan_url">Room Plan (Blueprint URL)</label></th>
                    <td><input type="url" name="room_plan_url" class="regular-text" value="<?php echo esc_url($loft_type->room_plan_url ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="inclusions">Inclusions (Amenities List)</label></th>
                    <td><textarea name="inclusions" rows="4"><?php echo esc_textarea($loft_type->inclusions ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="nearby_attractions">Nearby Attractions</label></th>
                    <td><textarea name="nearby_attractions" rows="4"><?php echo esc_textarea($loft_type->nearby_attractions ?? ''); ?></textarea></td>
                </tr>
            </table>

            <h2>Price and Booking Settings</h2>
            <table class="form-table">
                <tr>
                    <th>Seasonal Prices</th>
                    <td>
                        <div id="seasonal-price-fields">
                            <select id="season-dropdown" name="season_prices[season]">
                                <option value="summer">Summer</option>
                                <option value="winter">Winter</option>
                                <option value="spring">Spring</option>
                                <option value="autumn">Autumn</option>
                            </select>
                            <input type="number" id="season-price-input" placeholder="Enter price">
                            <button type="button" id="add-season-price" class="button">Add</button>
                            <ul id="season-prices-list">
                                <?php
                                $seasonal_prices = json_decode($loft_type->price_variations ?? '[]', true);
                                if ($seasonal_prices):
                                    foreach ($seasonal_prices as $price):
                                ?>
                                <li><?php echo esc_html("{$price['season']}: {$price['price']}"); ?></li>
                                <?php endforeach; endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>Blocked Reservation Dates</th>
                    <td>
                        <div id="blocked-dates-wrapper">
                            <input type="text" id="date-picker" placeholder="Select date">
                            <button type="button" id="add-date-button" class="button">Add</button>
                            <ul id="blocked-dates-list">
                                <?php
                                $blocked_dates = json_decode($loft_type->block_reservations ?? '[]', true);
                                if ($blocked_dates):
                                    foreach ($blocked_dates as $date):
                                ?>
                                <li><?php echo esc_html($date); ?></li>
                                <?php endforeach; endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>

            <h2>Page Settings</h2>
            <table class="form-table">
                <tr>
                    <th><label for="header_image_url">Header Image URL</label></th>
                    <td><input type="url" name="header_image_url" class="regular-text" value="<?php echo esc_url($loft_type->header_image_url ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="page_layout">Page Layout</label></th>
                    <td>
                        <select name="page_layout">
                            <option value="left-sidebar" <?php selected($loft_type->page_layout ?? '', 'left-sidebar'); ?>>Left Sidebar</option>
                            <option value="right-sidebar" <?php selected($loft_type->page_layout ?? '', 'right-sidebar'); ?>>Right Sidebar</option>
                            <option value="full-width" <?php selected($loft_type->page_layout ?? '', 'full-width'); ?>>Full Width</option>
                        </select>
                    </td>
                </tr>
            </table>
            <button type="submit" name="save_loft_type" class="button button-primary"><?php echo $edit_mode ? 'Update Loft Type' : 'Save Loft Type'; ?></button>
        </form>
    </div>

    <?php
    
}

function get_room_type_by_post_id($room_id) {
    $title = get_the_title($room_id);
    if (stripos($title, 'simple') !== false) return 'SIMPLE';
    if (stripos($title, 'double') !== false) return 'DOUBLE';
    if (stripos($title, 'penthouse') !== false) return 'PENTHOUSE';
    return null;
}