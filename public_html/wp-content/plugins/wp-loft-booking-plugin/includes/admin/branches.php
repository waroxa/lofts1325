<?php
defined('ABSPATH') || exit;

// Managing branches
function wp_loft_booking_branches_page() {
    global $wpdb;

    // Handle deletion of a branch
    if (isset($_GET['delete'])) {
        $delete_id = intval($_GET['delete']);
        $deleted = $wpdb->delete($wpdb->prefix . 'loft_branches', array('id' => $delete_id), array('%d'));
        
        if ($deleted !== false) {
            echo '<div class="notice notice-success"><p>Branch deleted successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error deleting branch.</p></div>';
        }
    }

    // Handle branch submission (add or edit)
    if (isset($_POST['submit_branch'])) {
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $name = sanitize_text_field($_POST['branch_name']);
        $location = sanitize_text_field(wp_unslash($_POST['branch_location']));
        $building_id = sanitize_text_field($_POST['building_id']);
        $search_description = sanitize_text_field($_POST['search_description']); // Add search description field

        $data = array(
            'name' => $name,
            'location' => $location,
            'building_id' => $building_id,
            'search_description' => $search_description
        );

        if ($branch_id) {
            // Update existing branch
            $updated = $wpdb->update($wpdb->prefix . 'loft_branches', $data, array('id' => $branch_id), array('%s', '%s', '%s', '%s'), array('%d'));
            if ($updated !== false) {
                echo '<div class="notice notice-success"><p>Branch updated successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error updating branch.</p></div>';
            }
        } else {
            // Insert new branch
            $inserted = $wpdb->insert($wpdb->prefix . 'loft_branches', $data, array('%s', '%s', '%s', '%s'));
            if ($inserted !== false) {
                echo '<div class="notice notice-success"><p>Branch added successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error adding branch.</p></div>';
            }
        }
    }

    // Fetch branch to edit if edit ID is set
    $editing_branch = null;
    if (isset($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        $editing_branch = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "loft_branches WHERE id = $edit_id");
    }

    // Display the form
    echo '<div class="wrap">';
    echo '<h1>' . ($editing_branch ? 'Edit Branch' : 'Add New Branch') . '</h1>';
    echo '<form method="post">';
    echo '<input type="hidden" name="branch_id" value="' . esc_attr($editing_branch->id ?? '') . '">';
    echo '<table class="form-table">';
    echo '<tr><th><label for="branch_name">Branch Name</label></th>';
    echo '<td><input type="text" name="branch_name" id="branch_name" value="' . esc_attr($editing_branch->name ?? '') . '" required></td></tr>';
    echo '<tr><th><label for="branch_location">Location</label></th>';
    echo '<td><input type="text" name="branch_location" id="branch_location" value="' . esc_attr($editing_branch->location ?? '') . '"></td></tr>';
    echo '<tr><th><label for="building_id">Building ID (ButterflyMX)</label></th>';
    echo '<td><input type="text" name="building_id" id="building_id" value="' . esc_attr($editing_branch->building_id ?? '') . '" required></td></tr>';
    echo '<tr><th><label for="search_description">Search Description</label></th>';
    echo '<td><input type="text" name="search_description" id="search_description" value="' . esc_attr($editing_branch->search_description ?? '') . '" placeholder="e.g., 1201, 3 Avenue Est (L\'entrée de la ville)"></td></tr>';
    echo '</table>';
    echo '<p><input type="submit" name="submit_branch" class="button-primary" value="' . ($editing_branch ? 'Update Branch' : 'Add Branch') . '"></p>';
    echo '</form>';

    // Display existing branches
    echo '<h2>Existing Branches</h2>';
    $branches = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "loft_branches");
    if ($branches) {
        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Location</th><th>Building ID</th><th>Search Description</th><th>Actions</th></tr></thead><tbody>';
        foreach ($branches as $branch) {
            echo '<tr>';
            echo '<td>' . esc_html($branch->id) . '</td>';
            echo '<td>' . esc_html($branch->name) . '</td>';
            echo '<td>' . esc_html($branch->location) . '</td>';
            echo '<td>' . esc_html($branch->building_id) . '</td>';
            echo '<td>' . esc_html($branch->search_description) . '</td>';
            echo '<td>';
            echo '<a href="?page=wp_loft_booking_branches&edit=' . esc_attr($branch->id) . '" class="button">Edit</a> ';
            echo '<a href="?page=wp_loft_booking_branches&delete=' . esc_attr($branch->id) . '" class="button" onclick="return confirm(\'Are you sure you want to delete this branch?\');">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No branches found.</p>';
    }

    echo '</div>';
}

function wplb_branches_page() {
    global $wpdb;

    if (isset($_GET['delete'])) {
        $delete_id = intval($_GET['delete']);
        $deleted = $wpdb->delete($wpdb->prefix . 'loft_branches', ['id' => $delete_id], ['%d']);
        echo $deleted !== false ? '<div class="notice notice-success"><p>Branch deleted successfully!</p></div>' : '<div class="notice notice-error"><p>Error deleting branch.</p></div>';
    }

    if (isset($_POST['submit_branch'])) {
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $data = [
            'name' => sanitize_text_field($_POST['branch_name']),
            'location' => sanitize_text_field(wp_unslash($_POST['branch_location'])),
            'building_id' => sanitize_text_field($_POST['building_id']),
            'search_description' => sanitize_text_field($_POST['search_description'])
        ];

        if ($branch_id) {
            $updated = $wpdb->update($wpdb->prefix . 'loft_branches', $data, ['id' => $branch_id], ['%s', '%s', '%s', '%s'], ['%d']);
            echo $updated !== false ? '<div class="notice notice-success"><p>Branch updated successfully!</p></div>' : '<div class="notice notice-error"><p>Error updating branch.</p></div>';
        } else {
            $inserted = $wpdb->insert($wpdb->prefix . 'loft_branches', $data, ['%s', '%s', '%s', '%s']);
            echo $inserted !== false ? '<div class="notice notice-success"><p>Branch added successfully!</p></div>' : '<div class="notice notice-error"><p>Error adding branch.</p></div>';
        }
    }

    $editing_branch = isset($_GET['edit']) ? $wpdb->get_row("SELECT * FROM {$wpdb->prefix}loft_branches WHERE id = " . intval($_GET['edit'])) : null;

    echo '<div class="wrap">';
    echo '<h1>' . ($editing_branch ? 'Edit Branch' : 'Add New Branch') . '</h1>';
    echo '<form method="post">';
    echo '<input type="hidden" name="branch_id" value="' . esc_attr($editing_branch->id ?? '') . '">';
    echo '<table class="form-table">';
    echo '<tr><th><label for="branch_name">Branch Name</label></th><td><input type="text" name="branch_name" id="branch_name" value="' . esc_attr($editing_branch->name ?? '') . '" required></td></tr>';
    echo '<tr><th><label for="branch_location">Location</label></th><td><input type="text" name="branch_location" id="branch_location" value="' . esc_attr($editing_branch->location ?? '') . '"></td></tr>';
    echo '<tr><th><label for="building_id">Building ID (ButterflyMX)</label></th><td><input type="text" name="building_id" id="building_id" value="' . esc_attr($editing_branch->building_id ?? '') . '" required></td></tr>';
    echo '<tr><th><label for="search_description">Search Description</label></th><td><input type="text" name="search_description" id="search_description" value="' . esc_attr($editing_branch->search_description ?? '') . '" placeholder="e.g., 1201, 3 Avenue Est (L\'entrée de la ville)"></td></tr>';
    echo '</table>';
    echo '<p><input type="submit" name="submit_branch" class="button-primary" value="' . ($editing_branch ? 'Update Branch' : 'Add Branch') . '"></p>';
    echo '</form>';

    echo '<h2>Existing Branches</h2>';
    $branches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}loft_branches");
    if ($branches) {
        echo '<table class="widefat fixed" cellspacing="0"><thead><tr><th>ID</th><th>Name</th><th>Location</th><th>Building ID</th><th>Search Description</th><th>Actions</th></tr></thead><tbody>';
        foreach ($branches as $branch) {
            echo "<tr><td>{$branch->id}</td><td>{$branch->name}</td><td>{$branch->location}</td><td>{$branch->building_id}</td><td>{$branch->search_description}</td><td>";
            echo '<a href="?page=wp_loft_booking_branches&edit=' . $branch->id . '" class="button">Edit</a> ';
            echo '<a href="?page=wp_loft_booking_branches&delete=' . $branch->id . '" class="button" onclick="return confirm(\'Are you sure?\');">Delete</a>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No branches found.</p>';
    }
    echo '</div>';
}