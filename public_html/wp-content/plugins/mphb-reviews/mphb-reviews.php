<?php

/*
 * Plugin Name: Hotel Booking Reviews
 * Plugin URI: https://motopress.com/products/hotel-booking-reviews/
 * Description: User-submitted reviews for your Hotel Booking plugin accommodations: display ratings, easily manage reviews via native WordPress comments.
 * Version: 1.2.7
 * Author: MotoPress
 * Author URI: https://motopress.com/
 * License: GPLv2 or later
 * Text Domain: mphb-reviews
 * Domain Path: /languages
 */

require_once dirname( __FILE__ ) . '/includes/autoloader.php';
require_once dirname( __FILE__ ) . '/functions.php';

if (!class_exists('\MPHBR\Plugin')) {
    define('MPHBR\PLUGIN_FILE', __FILE__);
}

new \MPHBR\Autoloader( 'MPHBR\\', trailingslashit( dirname( __FILE__ ) . '/includes' ) );

\MPHBR\Plugin::setBaseFilepath( __FILE__ );

// Init
\MPHBR\Plugin::getInstance();
