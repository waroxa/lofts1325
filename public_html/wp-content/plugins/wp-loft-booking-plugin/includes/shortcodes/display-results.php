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
    $adults      = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children    = isset($_POST['children']) ? intval($_POST['children']) : 0;
    $requested_type = isset($_POST['room_type']) ? strtolower(sanitize_text_field(wp_unslash($_POST['room_type']))) : '';

    $allowed_types = array('simple', 'double', 'penthouse');
    if (!in_array($requested_type, $allowed_types, true)) {
        $requested_type = '';
    }

    $branch_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}loft_branches WHERE building_id = %d", $building_id));
    if (!$branch_id) return '<p class="custom-nd-booking-no-results">Invalid branch selection. Please try again.</p>';

    // Fetch units that match the search criteria. Use COALESCE to treat
    // NULL capacities as a large number so units with unspecified limits
    // are still returned in results.
    $where   = array("u.status = 'Available'", 'u.branch_id = %d', 'COALESCE(u.max_adults, 999) >= %d', 'COALESCE(u.max_children, 999) >= %d');
    $params  = array($branch_id, $adults, $children);

    if ($requested_type) {
        $where[]  = 'LOWER(u.unit_name) LIKE %s';
        $params[] = '%' . $wpdb->esc_like($requested_type) . '%';
    }

    $query   = "SELECT u.id, u.unit_name, u.max_adults, u.max_children, u.status, u.price_per_night FROM {$wpdb->prefix}loft_units AS u WHERE " . implode(' AND ', $where);
    $results = $wpdb->get_results($wpdb->prepare($query, ...$params));

    // Count available lofts grouped by type directly from the loft_units table (filtered by branch when provided)
    $count_where  = array("LOWER(status) = 'available'", 'branch_id = %d');
    $count_params = array($branch_id);

    $count_query = "SELECT \
            CASE \
                WHEN LOWER(unit_name) LIKE '%(simple)%' THEN 'simple' \
                WHEN LOWER(unit_name) LIKE '%(double)%' THEN 'double' \
                WHEN LOWER(unit_name) LIKE '%penthouse%' THEN 'penthouse' \
            END AS type, \
            COUNT(*) AS cnt \
        FROM {$wpdb->prefix}loft_units \
        WHERE " . implode(' AND ', $count_where) . ' GROUP BY type';

    $count_results = $wpdb->get_results($wpdb->prepare($count_query, ...$count_params), OBJECT_K);
    $counts        = array(
        'simple'    => intval($count_results['simple']->cnt ?? 0),
        'double'    => intval($count_results['double']->cnt ?? 0),
        'penthouse' => intval($count_results['penthouse']->cnt ?? 0),
    );

    // Keep loft_types table in sync so admin views reflect current availability
    $loft_types_table = $wpdb->prefix . 'loft_types';
    $wpdb->query($wpdb->prepare("UPDATE $loft_types_table SET quantity = %d WHERE LOWER(name) = %s", $counts['simple'], 'simple'));
    $wpdb->query($wpdb->prepare("UPDATE $loft_types_table SET quantity = %d WHERE LOWER(name) = %s", $counts['double'], 'double'));
    $wpdb->query($wpdb->prepare("UPDATE $loft_types_table SET quantity = %d WHERE LOWER(name) = %s", $counts['penthouse'], 'penthouse'));

    $simple_count         = $counts['simple'];
    $double_count         = $counts['double'];
    $penthouse_count      = $counts['penthouse'];
    $available_for_request = $requested_type ? ($counts[$requested_type] ?? 0) : ($simple_count + $double_count + $penthouse_count);

    $cta_url = function_exists('nd_booking_search_page') ? nd_booking_search_page() : home_url('/');

    $output = '<div class="custom-nd-booking-results">';
    $output .= '<p class="available-summary">'
            . 'Simple: ' . ($simple_count ? $simple_count : '<span class="not-available">non disponible</span>')
            . ', Double: ' . ($double_count ? $double_count : '<span class="not-available">non disponible</span>')
            . ', Penthouse: ' . ($penthouse_count ? $penthouse_count : '<span class="not-available">non disponible</span>')
            . '</p>';
    if (!empty($results)) {
        foreach ($results as $result) {
            $output .= '<div class="custom-nd-booking-item"><div class="custom-nd-booking-thumbnail"><img src="https://via.placeholder.com/150" alt="Loft Image"></div><div class="custom-nd-booking-info">';
            $output .= '<h3>' . esc_html($result->unit_name) . '</h3><p><strong>Guests:</strong> ' . esc_html($result->max_adults + $result->max_children) . '</p>';
            $output .= '<p><strong>Price:</strong> $' . esc_html(number_format($result->price_per_night, 2)) . ' CAD</p><a href="#" class="custom-nd-booking-btn">Book Now</a></div></div>';
        }
    } else {
        if ($available_for_request > 0) {
            $output .= '<div class="custom-nd-booking-no-results friendly">'
                . '<h3>' . esc_html__('We found space for you!', 'wp-loft-booking') . '</h3>'
                . '<p>' . esc_html__('A loft in this category is available. Continue to finalize your reservation with ND Booking.', 'wp-loft-booking') . '</p>'
                . '<a class="custom-nd-booking-btn" href="' . esc_url($cta_url) . '">' . esc_html__('Continue to booking', 'wp-loft-booking') . '</a>'
                . '</div>';
        } else {
            $output .= '<div class="custom-nd-booking-no-results friendly friendly--empty">'
                . '<h3>' . esc_html__('We are fully booked right now', 'wp-loft-booking') . '</h3>'
                . '<p>' . esc_html__('All lofts in this category are reserved for your dates. Please check back soon—we would love to host you!', 'wp-loft-booking') . '</p>'
                . '<a class="custom-nd-booking-btn custom-nd-booking-btn--ghost" href="' . esc_url($cta_url) . '">' . esc_html__('See other dates and lofts', 'wp-loft-booking') . '</a>'
                . '</div>';
        }
    }
    $output .= '</div>';
    $output .= '<style>.custom-nd-booking-results { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));gap: 20px; margin: 20px 0; } .custom-nd-booking-item { border: 1px solid #eaeaea; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease; } .custom-nd-booking-item:hover { transform: translateY(-5px);} .custom-nd-booking-thumbnail img { width: 100%; height: auto; display: block; } .custom-nd-booking-info { padding: 15px; text-align: center; } .custom-nd-booking-info h3 { font-size: 1.2rem; margin-bottom: 10px; } .custom-nd-booking-info p { margin: 5px 0; font-size: 1rem; color: #555; } .custom-nd-booking-btn { display: inline-block; margin-top: 10px; padding: 10px 20px; background-color: #76B1C4; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background-color 0.3s ease; } .custom-nd-booking-btn:hover { background-color: #5990A3; } .custom-nd-booking-btn--ghost { background: transparent; color: #5990A3; border: 1px solid #76B1C4; } .custom-nd-booking-no-results { text-align: center; color: #0f172a; background: linear-gradient(135deg, #f3f7fb 0%, #fdfefe 100%); border: 1px solid #dfe9f3; border-radius: 12px; padding: 24px; box-shadow: 0 12px 28px rgba(15,27,45,0.08); } .custom-nd-booking-no-results h3 { margin-top: 0; margin-bottom: 8px; font-size: 1.2rem; font-weight: 700; } .custom-nd-booking-no-results p { margin: 0 0 12px; color: #4b5563; line-height: 1.6; } .custom-nd-booking-no-results.friendly--empty { border-color: #f6d5d8; } .available-summary { grid-column: 1 / -1; font-weight: bold; margin-bottom: 15px; } .not-available { background:#d9534f; color:#fff; padding:2px 6px; border-radius:4px; }</style>';
    return $output;
}
add_shortcode('custom_nd_booking_results', 'custom_booking_search_results');
