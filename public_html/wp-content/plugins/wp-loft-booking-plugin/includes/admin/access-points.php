<?php
defined('ABSPATH') || exit;

/**
 * Render the ButterflyMX access points admin page.
 */
function wp_loft_booking_access_points_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to access this page.', 'wp-loft-booking'));
    }

    $environment = function_exists('wp_loft_booking_get_butterflymx_environment')
        ? wp_loft_booking_get_butterflymx_environment()
        : 'production';

    $buildings       = [];
    $buildings_error = '';

    if (function_exists('wp_loft_booking_get_buildings')) {
        $buildings_raw = wp_loft_booking_get_buildings();

        if (is_array($buildings_raw)) {
            $buildings = $buildings_raw;
        } elseif ($buildings_raw instanceof WP_Error) {
            $buildings_error = $buildings_raw->get_error_message();
        } elseif (is_string($buildings_raw) && '' !== trim($buildings_raw)) {
            $buildings_error = $buildings_raw;
        }
    }

    $selected_building_id = isset($_GET['building_id']) ? (int) $_GET['building_id'] : 0;

    if ($selected_building_id <= 0) {
        $default_building_id = (int) get_option('butterflymx_building_id');
        $selected_building_id = $default_building_id > 0 ? $default_building_id : 0;
    }

    $access_points = [];
    $access_error  = '';

    if ($selected_building_id > 0 && function_exists('wp_loft_booking_fetch_building_access_points')) {
        $result = wp_loft_booking_fetch_building_access_points($selected_building_id, $environment, true);

        if ($result instanceof WP_Error) {
            $access_error = $result->get_error_message();
        } elseif (is_array($result)) {
            $access_points = $result;
        }
    }

    if (!empty($access_points)) {
        uasort($access_points, static function ($a, $b) {
            $a_name = strtolower($a['name'] ?? '');
            $b_name = strtolower($b['name'] ?? '');

            return strcmp($a_name, $b_name);
        });
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('ButterflyMX Access Points', 'wp-loft-booking') . '</h1>';

    if ('' !== $buildings_error) {
        echo '<div class="notice notice-error"><p>' . esc_html($buildings_error) . '</p></div>';
    }

    echo '<form method="get" class="tablenav top" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="page" value="' . esc_attr('wp_loft_booking_access_points') . '" />';

    echo '<label for="wplb-access-point-building" class="screen-reader-text">' . esc_html__('Select building', 'wp-loft-booking') . '</label>';
    echo '<select id="wplb-access-point-building" name="building_id" style="min-width:240px;">';

    if (empty($buildings)) {
        $label = $selected_building_id > 0
            ? sprintf(__('Building #%d', 'wp-loft-booking'), $selected_building_id)
            : __('Select a building…', 'wp-loft-booking');

        echo '<option value="' . esc_attr($selected_building_id) . '">' . esc_html($label) . '</option>';
    } else {
        foreach ($buildings as $building) {
            if (!isset($building['id'])) {
                continue;
            }

            $id   = (int) $building['id'];
            $name = isset($building['name']) ? (string) $building['name'] : sprintf(__('Building #%d', 'wp-loft-booking'), $id);

            echo '<option value="' . esc_attr($id) . '"' . selected($selected_building_id, $id, false) . '>' . esc_html($name) . '</option>';
        }
    }

    echo '</select> ';
    submit_button(__('Refresh list', 'wp-loft-booking'), 'secondary', '', false);
    echo '</form>';

    if ($selected_building_id <= 0) {
        echo '<p>' . esc_html__('Select a building to view its access points.', 'wp-loft-booking') . '</p>';
        echo '</div>';
        return;
    }

    echo '<p>' . sprintf(
        /* translators: %1$s environment, %2$d building id */
        esc_html__('Environment: %1$s — Building ID: %2$d', 'wp-loft-booking'),
        esc_html(ucfirst($environment)),
        (int) $selected_building_id
    ) . '</p>';

    if ('' !== $access_error) {
        echo '<div class="notice notice-error"><p>' . esc_html($access_error) . '</p></div>';
        echo '</div>';
        return;
    }

    if (empty($access_points)) {
        echo '<div class="notice notice-info"><p>' . esc_html__('No access points were returned for this building.', 'wp-loft-booking') . '</p></div>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th scope="col">' . esc_html__('ID', 'wp-loft-booking') . '</th>';
    echo '<th scope="col">' . esc_html__('Name', 'wp-loft-booking') . '</th>';
    echo '<th scope="col">' . esc_html__('Details', 'wp-loft-booking') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($access_points as $access_point) {
        $id         = isset($access_point['id']) ? (int) $access_point['id'] : 0;
        $name       = isset($access_point['name']) ? (string) $access_point['name'] : '';
        $attributes = isset($access_point['attributes']) && is_array($access_point['attributes'])
            ? $access_point['attributes']
            : [];
        $door       = isset($access_point['door']) && is_array($access_point['door'])
            ? $access_point['door']
            : [];
        $door_name  = isset($door['name']) ? (string) $door['name'] : '';
        $door_attr  = isset($door['attributes']) && is_array($door['attributes'])
            ? $door['attributes']
            : [];
        $devices    = isset($access_point['devices']) && is_array($access_point['devices'])
            ? $access_point['devices']
            : [];

        if ('' === $name && '' !== $door_name) {
            $name = $door_name;
        }

        if ('' === $name && $id > 0) {
            $name = sprintf(__('Access Point #%d', 'wp-loft-booking'), $id);
        }

        $type = '';

        if (!empty($attributes['kind'])) {
            $type = (string) $attributes['kind'];
        } elseif (!empty($attributes['type'])) {
            $type = (string) $attributes['type'];
        }

        $direction = !empty($attributes['direction']) ? (string) $attributes['direction'] : '';

        $details = [];

        if ('' !== $type) {
            $details[] = sprintf(__('Type: %s', 'wp-loft-booking'), $type);
        }

        if ('' !== $direction) {
            $details[] = sprintf(__('Direction: %s', 'wp-loft-booking'), $direction);
        }

        if (!empty($attributes['description'])) {
            $details[] = sprintf(__('Description: %s', 'wp-loft-booking'), $attributes['description']);
        }

        $normalize_for_compare = static function ($value) {
            if (!is_string($value) || '' === $value) {
                return '';
            }

            return function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
        };

        if ('' !== $door_name && $normalize_for_compare($door_name) !== $normalize_for_compare($name)) {
            $details[] = sprintf(__('Door: %s', 'wp-loft-booking'), $door_name);
        }

        $door_detail_map = [
            'floor'             => __('Floor: %s', 'wp-loft-booking'),
            'device_status'     => __('Device status: %s', 'wp-loft-booking'),
            'device_state'      => __('Device state: %s', 'wp-loft-booking'),
            'device_name'       => __('Device name: %s', 'wp-loft-booking'),
            'device_identifier' => __('Device identifier: %s', 'wp-loft-booking'),
            'location'          => __('Location: %s', 'wp-loft-booking'),
        ];

        $extract_scalar = static function ($source, $keys) {
            if (!is_array($source)) {
                return '';
            }

            foreach ((array) $keys as $key) {
                if (!array_key_exists($key, $source)) {
                    continue;
                }

                $value = $source[$key];

                if (!is_scalar($value)) {
                    continue;
                }

                $value = (string) $value;

                if ('' === trim($value)) {
                    continue;
                }

                return $value;
            }

            return '';
        };

        $device_meta = [];

        foreach ([
            'device_status'     => ['device_status', 'status'],
            'device_state'      => ['device_state', 'state'],
            'device_identifier' => ['device_identifier', 'identifier'],
            'location'          => ['location'],
        ] as $meta_key => $keys) {
            $value = $extract_scalar($attributes, $keys);

            if ('' !== $value) {
                $device_meta[$meta_key] = $value;
            }
        }

        $device_name_value = $extract_scalar($attributes, ['device_name', 'name']);

        if ('' !== $device_name_value) {
            $device_meta['device_name'] = $device_name_value;
        }

        foreach ($devices as $device) {
            if (!is_array($device)) {
                continue;
            }

            if (empty($device_meta['device_name'])) {
                $device_name_candidate = '';

                if (isset($device['name']) && is_string($device['name'])) {
                    $device_name_candidate = trim($device['name']);
                }

                if ('' === $device_name_candidate) {
                    $device_name_candidate = $extract_scalar($device['attributes'] ?? [], ['name', 'display_name', 'label', 'description']);
                }

                if ('' !== $device_name_candidate) {
                    $device_meta['device_name'] = $device_name_candidate;
                }
            }

            $device_attributes = isset($device['attributes']) && is_array($device['attributes'])
                ? $device['attributes']
                : [];

            foreach ([
                'device_status'     => ['device_status', 'status'],
                'device_state'      => ['device_state', 'state'],
                'device_identifier' => ['device_identifier', 'identifier'],
                'location'          => ['location', 'location_description', 'location_name'],
            ] as $meta_key => $keys) {
                if (!empty($device_meta[$meta_key])) {
                    continue;
                }

                $value = $extract_scalar($device_attributes, $keys);

                if ('' !== $value) {
                    $device_meta[$meta_key] = $value;
                }
            }
        }

        if (!empty($device_meta)) {
            foreach ($door_detail_map as $field => $format) {
                if (empty($device_meta[$field])) {
                    continue;
                }

                $details[] = sprintf($format, $device_meta[$field]);
            }
        }

        if (!empty($door_attr)) {
            foreach ($door_detail_map as $field => $format) {
                if (isset($device_meta[$field]) && '' !== $device_meta[$field]) {
                    continue;
                }

                if (!isset($door_attr[$field])) {
                    continue;
                }

                $value = $door_attr[$field];

                if (!is_scalar($value)) {
                    continue;
                }

                $value = (string) $value;

                if ('' === trim($value)) {
                    continue;
                }

                $details[] = sprintf($format, $value);
            }
        }

        if (empty($details)) {
            $details[] = __('No additional details provided.', 'wp-loft-booking');
        }

        echo '<tr>';
        echo '<td>' . esc_html($id) . '</td>';
        echo '<td>' . esc_html($name) . '</td>';
        echo '<td>';
        echo '<ul style="margin:0;">';
        foreach ($details as $detail) {
            echo '<li>' . esc_html($detail) . '</li>';
        }
        echo '</ul>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
