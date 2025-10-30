<?php
defined('ABSPATH') || exit;

function wp_loft_booking_search_form() {
    global $wpdb;

    $branches = $wpdb->get_results("SELECT id, name, building_id, search_description, settings FROM {$wpdb->prefix}loft_branches");

    $nd_date_from = isset($_GET['nd_booking_archive_form_date_range_from'])
        ? sanitize_text_field(wp_unslash($_GET['nd_booking_archive_form_date_range_from']))
        : '';
    $nd_date_to = isset($_GET['nd_booking_archive_form_date_range_to'])
        ? sanitize_text_field(wp_unslash($_GET['nd_booking_archive_form_date_range_to']))
        : '';
    $selected_branch_building_id = isset($_GET['branch'])
        ? sanitize_text_field(wp_unslash($_GET['branch']))
        : '';
    $selected_nd_branch_id = isset($_GET['nd_booking_archive_form_branches'])
        ? sanitize_text_field(wp_unslash($_GET['nd_booking_archive_form_branches']))
        : '';
    $adults = isset($_GET['adults']) ? max(1, intval($_GET['adults'])) : 1;
    $children = isset($_GET['children']) ? max(0, intval($_GET['children'])) : 0;
    $total_guests = max(1, $adults + $children);

    $normalized_checkin = '';
    if (!empty($nd_date_from)) {
        $timestamp = strtotime($nd_date_from);
        if ($timestamp) {
            $normalized_checkin = gmdate('Y-m-d', $timestamp);
        }
    }

    $normalized_checkout = '';
    if (!empty($nd_date_to)) {
        $timestamp = strtotime($nd_date_to);
        if ($timestamp) {
            $normalized_checkout = gmdate('Y-m-d', $timestamp);
        }
    }

    $prepared_branches = [];
    $selected_branch_internal_id = '';

    foreach ($branches as $branch) {
        $settings = [];
        if (!empty($branch->settings)) {
            $decoded = json_decode($branch->settings, true);
            if (is_array($decoded)) {
                $settings = $decoded;
            }
        }

        $nd_branch_id = isset($settings['nd_booking_branch_id']) ? (string) intval($settings['nd_booking_branch_id']) : '';
        $branch_building_id = (string) $branch->building_id;

        if ($selected_branch_building_id === '' && $selected_nd_branch_id !== '' && $selected_nd_branch_id === $nd_branch_id) {
            $selected_branch_building_id = $branch_building_id;
        }

        if ($selected_nd_branch_id === '' && $selected_branch_building_id !== '' && $selected_branch_building_id === $branch_building_id) {
            $selected_nd_branch_id = $nd_branch_id;
        }

        if ($selected_branch_building_id !== '' && $selected_branch_building_id === $branch_building_id) {
            $selected_branch_internal_id = (string) $branch->id;
        }

        $prepared_branches[] = (object) [
            'id' => $branch->id,
            'name' => $branch->name,
            'building_id' => $branch_building_id,
            'search_description' => $branch->search_description,
            'nd_branch_id' => $nd_branch_id,
        ];
    }

    $selected_nd_branch_id = $selected_nd_branch_id !== '' ? $selected_nd_branch_id : '0';

    $action = function_exists('nd_booking_search_page') ? nd_booking_search_page() : home_url('/lofts-page/');

    ob_start();
    ?>
    <form id="loft-booking-search-form" class="loft-booking-search" method="get" action="<?php echo esc_url($action); ?>">
        <label for="branch" class="loft-booking-search__label"><?php esc_html_e('Location:', 'wp-loft-booking'); ?></label>
        <select name="branch" id="branch" class="loft-booking-search__select" required>
            <option value=""><?php esc_html_e('Sélectionnez la location', 'wp-loft-booking'); ?></option>
            <?php foreach ($prepared_branches as $branch) :
                $is_selected = $selected_branch_building_id !== '' && $selected_branch_building_id === (string) $branch->building_id;
            ?>
                <option value="<?php echo esc_attr($branch->building_id); ?>"
                        data-nd-branch="<?php echo esc_attr($branch->nd_branch_id); ?>"
                        data-internal-branch="<?php echo esc_attr($branch->id); ?>"
                    <?php selected($is_selected, true); ?>>
                    <?php echo esc_html($branch->search_description ?: $branch->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="loft-booking-search__row">
            <div class="loft-booking-search__column">
                <label for="adults" class="loft-booking-search__label"><?php esc_html_e('Adultes :', 'wp-loft-booking'); ?></label>
                <input type="number" id="adults" name="adults" min="1" max="10" value="<?php echo esc_attr($adults); ?>" required class="loft-booking-search__input">
            </div>
            <div class="loft-booking-search__column">
                <label for="children" class="loft-booking-search__label"><?php esc_html_e('Enfants :', 'wp-loft-booking'); ?></label>
                <input type="number" id="children" name="children" min="0" max="10" value="<?php echo esc_attr($children); ?>" class="loft-booking-search__input">
            </div>
        </div>

        <label for="checkin_date" class="loft-booking-search__label"><?php esc_html_e("Date d'arrivée :", 'wp-loft-booking'); ?></label>
        <input type="date" name="checkin_date" id="checkin_date" value="<?php echo esc_attr($normalized_checkin); ?>" required class="loft-booking-search__input">

        <label for="checkout_date" class="loft-booking-search__label"><?php esc_html_e('Date de départ :', 'wp-loft-booking'); ?></label>
        <input type="date" name="checkout_date" id="checkout_date" value="<?php echo esc_attr($normalized_checkout); ?>" required class="loft-booking-search__input">

        <input type="hidden" id="nd_booking_archive_form_date_range_from" name="nd_booking_archive_form_date_range_from" value="<?php echo esc_attr($nd_date_from); ?>">
        <input type="hidden" id="nd_booking_archive_form_date_range_to" name="nd_booking_archive_form_date_range_to" value="<?php echo esc_attr($nd_date_to); ?>">
        <input type="hidden" id="nd_booking_archive_form_guests" name="nd_booking_archive_form_guests" value="<?php echo esc_attr($total_guests); ?>">
        <input type="hidden" id="nd_booking_archive_form_branches" name="nd_booking_archive_form_branches" value="<?php echo esc_attr($selected_nd_branch_id); ?>">
        <input type="hidden" id="loft_branch_internal" name="branch_id" value="<?php echo esc_attr($selected_branch_internal_id); ?>">
        <input type="hidden" id="nd_booking_archive_form_max_price_for_day" name="nd_booking_archive_form_max_price_for_day" value="<?php echo esc_attr(isset($_GET['nd_booking_archive_form_max_price_for_day']) ? sanitize_text_field(wp_unslash($_GET['nd_booking_archive_form_max_price_for_day'])) : ''); ?>">
        <input type="hidden" id="nd_booking_archive_form_services" name="nd_booking_archive_form_services" value="<?php echo esc_attr(isset($_GET['nd_booking_archive_form_services']) ? sanitize_text_field(wp_unslash($_GET['nd_booking_archive_form_services'])) : ''); ?>">
        <input type="hidden" id="nd_booking_archive_form_additional_services" name="nd_booking_archive_form_additional_services" value="<?php echo esc_attr(isset($_GET['nd_booking_archive_form_additional_services']) ? sanitize_text_field(wp_unslash($_GET['nd_booking_archive_form_additional_services'])) : ''); ?>">
        <input type="hidden" id="nd_booking_archive_form_branch_stars" name="nd_booking_archive_form_branch_stars" value="<?php echo esc_attr(isset($_GET['nd_booking_archive_form_branch_stars']) ? sanitize_text_field(wp_unslash($_GET['nd_booking_archive_form_branch_stars'])) : ''); ?>">

        <button type="submit" class="loft-booking-search__submit"><?php esc_html_e('Rechercher', 'wp-loft-booking'); ?></button>
    </form>

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('loft-booking-search-form');
        if (!form) {
            return;
        }

        var branchSelect = document.getElementById('branch');
        var ndBranchInput = document.getElementById('nd_booking_archive_form_branches');
        var branchInternalInput = document.getElementById('loft_branch_internal');
        var checkinInput = document.getElementById('checkin_date');
        var checkoutInput = document.getElementById('checkout_date');
        var hiddenCheckin = document.getElementById('nd_booking_archive_form_date_range_from');
        var hiddenCheckout = document.getElementById('nd_booking_archive_form_date_range_to');
        var adultsInput = document.getElementById('adults');
        var childrenInput = document.getElementById('children');
        var guestsInput = document.getElementById('nd_booking_archive_form_guests');

        var initialNdBranch = ndBranchInput ? ndBranchInput.value : '0';

        function getSelectedOption() {
            if (!branchSelect || branchSelect.selectedIndex < 0) {
                return null;
            }
            return branchSelect.options[branchSelect.selectedIndex];
        }

        function syncBranchFields() {
            var option = getSelectedOption();
            if (!option) {
                if (ndBranchInput && !ndBranchInput.value) {
                    ndBranchInput.value = initialNdBranch || '0';
                }
                return;
            }

            var ndBranch = option.getAttribute('data-nd-branch') || '';
            var internalBranch = option.getAttribute('data-internal-branch') || '';

            if (ndBranchInput) {
                ndBranchInput.value = ndBranch || initialNdBranch || '0';
            }

            if (branchInternalInput) {
                branchInternalInput.value = internalBranch || '';
            }
        }

        function formatForNdBooking(value) {
            if (!value) {
                return '';
            }
            var parts = value.split('-');
            if (parts.length !== 3) {
                return value;
            }
            return parts[1] + '/' + parts[2] + '/' + parts[0];
        }

        function syncGuests() {
            if (!guestsInput) {
                return;
            }
            var adults = parseInt(adultsInput && adultsInput.value ? adultsInput.value : '0', 10) || 0;
            var children = parseInt(childrenInput && childrenInput.value ? childrenInput.value : '0', 10) || 0;
            var total = adults + children;
            guestsInput.value = total > 0 ? total : 1;
        }

        if (branchSelect) {
            branchSelect.addEventListener('change', syncBranchFields);
            syncBranchFields();
        }

        if (adultsInput) {
            adultsInput.addEventListener('change', syncGuests);
            adultsInput.addEventListener('input', syncGuests);
        }

        if (childrenInput) {
            childrenInput.addEventListener('change', syncGuests);
            childrenInput.addEventListener('input', syncGuests);
        }

        syncGuests();

        form.addEventListener('submit', function() {
            if (hiddenCheckin && checkinInput) {
                hiddenCheckin.value = formatForNdBooking(checkinInput.value);
            }

            if (hiddenCheckout && checkoutInput) {
                hiddenCheckout.value = formatForNdBooking(checkoutInput.value);
            }

            syncGuests();
            syncBranchFields();
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('loft_booking_search', 'wp_loft_booking_search_form');