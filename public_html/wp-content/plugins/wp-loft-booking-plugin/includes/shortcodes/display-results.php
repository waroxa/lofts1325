<?php
defined('ABSPATH') || exit;

function wp_loft_display_search_results() {
    global $wpdb;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return '<p>Please use the form to search for available lofts.</p>';

    $building_id = isset($_POST['branch']) ? intval($_POST['branch']) : 0;
    $adults = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
    $checkin_date = isset($_POST['checkin_date']) ? sanitize_text_field($_POST['checkin_date']) : '';
    $checkout_date = isset($_POST['checkout_date']) ? sanitize_text_field($_POST['checkout_date']) : '';

    if (!$checkin_date || !$checkout_date) return '<p style="color: red;">Please select both check-in and check-out dates.</p>';
    if (strtotime($checkin_date) >= strtotime($checkout_date)) return '<p style="color: red;">Check-out date must be after the check-in date.</p>';

    $branch_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}loft_branches WHERE building_id = %d", $building_id));
    if (!$branch_id) return '<p style="color: red;">Please select a valid branch.</p>';

    $query = "SELECT u.id, u.unit_name, u.max_adults, u.max_children, u.status, u.price_per_night FROM {$wpdb->prefix}loft_units AS u WHERE u.branch_id = %d AND u.max_adults >= %d AND u.max_children >= %d AND u.status = 'Available'";
    $units = $wpdb->get_results($wpdb->prepare($query, $branch_id, $adults, $children));

    if (!empty($units)) {
        $output = '<div class="nd-booking-results"><table class="nd-booking-table"><thead><tr><th>Unit Name</th><th>Status</th><th>Max Adults</th><th>Max Children</th><th>Price</th><th>Actions</th></tr></thead><tbody>';
        foreach ($units as $unit) {
            $output .= "<tr><td>{$unit->unit_name}</td><td>{$unit->status}</td><td>{$unit->max_adults}</td><td>{$unit->max_children}</td><td>" . number_format($unit->price_per_night, 2) . " $</td><td><a href='#' class='nd-booking-btn'>Book Now</a></td></tr>";
        }
        $output .= '</tbody></table></div>';
        return $output;
    }
    return '<p style="color: red;">No lofts available for the selected criteria.</p>';
}
add_shortcode('loft_search_results', 'wp_loft_display_search_results');

function custom_booking_search_results() {
    global $wpdb;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return '<p class="custom-nd-booking-no-results">Please use the search form to find available lofts.</p>';

    $building_id = isset($_POST['branch']) ? intval($_POST['branch']) : 0;
    $adults = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children = isset($_POST['children']) ? intval($_POST['children']) : 0;

    $branch_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}loft_branches WHERE building_id = %d", $building_id));
    if (!$branch_id) return '<p class="custom-nd-booking-no-results">Invalid branch selection. Please try again.</p>';

    // Fetch units that match the search criteria
    $query   = "SELECT u.id, u.unit_name, u.max_adults, u.max_children, u.status, u.price_per_night FROM {$wpdb->prefix}loft_units AS u WHERE u.status = 'Available' AND u.branch_id = %d AND u.max_adults >= %d AND u.max_children >= %d";
    $results = $wpdb->get_results($wpdb->prepare($query, $branch_id, $adults, $children));

    // Count available lofts grouped by type directly from the loft_units table
    $counts = get_transient('wp_loft_available_counts');
    if ($counts === false) {
        $count_query = "SELECT \
                CASE \
                    WHEN LOWER(unit_name) LIKE '%(simple)%' THEN 'simple' \
                    WHEN LOWER(unit_name) LIKE '%(double)%' THEN 'double' \
                    WHEN LOWER(unit_name) LIKE '%(penthouse)%' THEN 'penthouse' \
                END AS type, \
                COUNT(*) AS cnt \
            FROM {$wpdb->prefix}loft_units \
            WHERE LOWER(status) = 'available' \
            GROUP BY type";
        $count_results = $wpdb->get_results($count_query, OBJECT_K);
        $counts = [
            'simple'    => intval($count_results['simple']->cnt ?? 0),
            'double'    => intval($count_results['double']->cnt ?? 0),
            'penthouse' => intval($count_results['penthouse']->cnt ?? 0),
        ];

        // Cache the counts for a short period to avoid repeated heavy queries
        set_transient('wp_loft_available_counts', $counts, 5 * MINUTE_IN_SECONDS);

        // Keep loft_types table in sync so admin views reflect current availability
        $loft_types_table = $wpdb->prefix . 'loft_types';
        $wpdb->query($wpdb->prepare("UPDATE $loft_types_table SET quantity = %d WHERE LOWER(name) = %s", $counts['simple'], 'simple'));
        $wpdb->query($wpdb->prepare("UPDATE $loft_types_table SET quantity = %d WHERE LOWER(name) = %s", $counts['double'], 'double'));
        $wpdb->query($wpdb->prepare("UPDATE $loft_types_table SET quantity = %d WHERE LOWER(name) = %s", $counts['penthouse'], 'penthouse'));
    }

    $simple_count    = $counts['simple'];
    $double_count    = $counts['double'];
    $penthouse_count = $counts['penthouse'];

    $output = '<div class="custom-nd-booking-results">';
    $output .= '<p class="available-summary">Available Lofts - Simple: ' . esc_html($simple_count) . ', Double: ' . esc_html($double_count) . ', Penthouse: ' . esc_html($penthouse_count) . '</p>';
    if (!empty($results)) {
        foreach ($results as $result) {
            $output .= '<div class="custom-nd-booking-item"><div class="custom-nd-booking-thumbnail"><img src="https://via.placeholder.com/150" alt="Loft Image"></div><div class="custom-nd-booking-info">';
            $output .= '<h3>' . esc_html($result->unit_name) . '</h3><p><strong>Guests:</strong> ' . esc_html($result->max_adults + $result->max_children) . '</p>';
            $output .= '<p><strong>Price:</strong> $' . esc_html(number_format($result->price_per_night, 2)) . ' CAD</p><a href="#" class="custom-nd-booking-btn">Book Now</a></div></div>';
        }
    } else {
        $output .= '<p class="custom-nd-booking-no-results">No lofts available for the selected criteria.</p>';
    }
    $output .= '</div>';
    $output .= '<style>.custom-nd-booking-results { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; } .custom-nd-booking-item { border: 1px solid #eaeaea; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease; } .custom-nd-booking-item:hover { transform: translateY(-5px); } .custom-nd-booking-thumbnail img { width: 100%; height: auto; display: block; } .custom-nd-booking-info { padding: 15px; text-align: center; } .custom-nd-booking-info h3 { font-size: 1.2rem; margin-bottom: 10px; } .custom-nd-booking-info p { margin: 5px 0; font-size: 1rem; color: #555; } .custom-nd-booking-btn { display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #76B1C4; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background-color 0.3s ease; } .custom-nd-booking-btn:hover { background-color: #5990A3; } .custom-nd-booking-no-results { text-align: center; color: #d9534f; } .available-summary { grid-column: 1 / -1; font-weight: bold; margin-bottom: 15px; }</style>';
    return $output;
}
add_shortcode('custom_nd_booking_results', 'custom_booking_search_results');