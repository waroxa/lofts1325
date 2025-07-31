<?php
defined('ABSPATH') || exit;

function wp_loft_booking_form_shortcode() {
    global $wpdb;
    $branches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}loft_branches");

    ob_start();
    ?>
    <form id="loft-booking-form" method="post" enctype="multipart/form-data">
        <h2>Book a Loft</h2>
        <label for="branch">Select Branch:</label>
        <select name="branch" id="branch" required>
            <option value="">Choose a Branch</option>
            <?php foreach ($branches as $branch): ?>
                <option value="<?php echo esc_attr($branch->id); ?>"><?php echo esc_html($branch->name); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="unit">Select Unit:</label>
        <select name="unit" id="unit" required>
            <option value="">Choose a Unit</option>
        </select>
        <label for="checkin_date">Check-In Date:</label>
        <input type="date" name="checkin_date" id="checkin_date" required>
        <label for="checkout_date">Check-Out Date:</label>
        <input type="date" name="checkout_date" id="checkout_date" required>
        <label for="id_verification">Upload ID:</label>
        <input type="file" name="id_verification" id="id_verification" accept=".jpg,.jpeg,.png,.pdf" required>
        <input type="submit" name="submit_booking" value="Book Now">
    </form>
    <div id="booking-message"></div>
    <script>
    document.getElementById('branch').addEventListener('change', function () {
        let branchId = this.value;
        let unitSelect = document.getElementById('unit');
        unitSelect.innerHTML = '<option value="">Loading...</option>';
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'wp_loft_booking_get_units',
            branch_id: branchId
        }, function(response) {
            unitSelect.innerHTML = '<option value="">Choose a Unit</option>';
            response.forEach(function(unit) {
                unitSelect.innerHTML += `<option value="${unit.id}">${unit.unit_name}</option>`;
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('wp_loft_booking_form', 'wp_loft_booking_form_shortcode');