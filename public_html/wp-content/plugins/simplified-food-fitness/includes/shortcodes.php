<?php
/**
 * Shortcodes for Simplified Food Fitness plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Display the logged-in client's profile information.
 *
 * @return string HTML output for the client profile.
 */
function sff_client_profile_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $args  = array(
        'post_type'      => 'clients',
        'posts_per_page' => 1,
        'meta_key'       => 'linked_user_id',
        'meta_value'     => get_current_user_id(),
        'post_status'    => 'publish',
        'fields'         => 'ids',
    );

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '';
    }

    $client_id = $query->posts[0];

    $fields = array(
        'first_name' => __( 'First Name', 'sff' ),
        'last_name'  => __( 'Last Name', 'sff' ),
        'email'      => __( 'Email', 'sff' ),
        'phone'      => __( 'Phone', 'sff' ),
    );

    $output = '<div class="sff-profile-card">';

    foreach ( $fields as $meta_key => $label ) {
        $value = get_post_meta( $client_id, $meta_key, true );
        if ( $value ) {
            $output .= '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
        }
    }

    $output .= '</div>';

    wp_reset_postdata();

    return $output;
}
add_shortcode( 'sff_client_profile', 'sff_client_profile_shortcode' );

/**
 * Render the frontend dashboard.
 *
 * @return string HTML output for the dashboard.
 */
function sff_frontend_dashboard_pretty() {
    $output  = '<div class="sff-dashboard">';
    $output .= do_shortcode( '[sff_client_profile]' );
    $output .= '<!-- Dashboard content will be added here -->';
    $output .= '</div>';

    return $output;
}
