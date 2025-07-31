<?php
defined('ABSPATH') || exit;

function wp_loft_booking_activate() {
    error_log("wp_loft_booking_activate called");
    wp_loft_booking_create_tables();
    if (get_option('butterflymx_token_v3') === false) update_option('butterflymx_token_v3', '');
    if (get_option('butterflymx_token_v3_expires') === false) update_option('butterflymx_token_v3_expires', 0);
    if (get_option('butterflymx_token_v4') === false) update_option('butterflymx_token_v4', '');
    if (get_option('butterflymx_token_v4_expires') === false) update_option('butterflymx_token_v4_expires', 0);
}
register_activation_hook(dirname(__FILE__, 3) . '/wp-loft-booking-plugin.php', 'wp_loft_booking_activate');

function wp_loft_booking_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $branches_table = $wpdb->prefix . 'loft_branches';
    if ($wpdb->get_var("SHOW TABLES LIKE '$branches_table'") != $branches_table) {
        $sql = "CREATE TABLE $branches_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            location varchar(255),
            building_id varchar(255),
            settings text,
            address varchar(255),
            phone_number varchar(20),
            operational_hours JSON,
            search_description varchar(255),
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);
    }

    $lofts_table = $wpdb->prefix . 'loft_lofts';
    if ($wpdb->get_var("SHOW TABLES LIKE '$lofts_table'") != $lofts_table) {
        $sql = "CREATE TABLE $lofts_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            branch_id mediumint(9) NOT NULL,
            name varchar(255) NOT NULL,
            availability tinyint(1) DEFAULT 1,
            rate float NOT NULL,
            max_adults int NOT NULL DEFAULT 0,
            max_children int NOT NULL DEFAULT 0,
            price_per_night DECIMAL(10, 2) DEFAULT 0.00,
            features JSON NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'available',
            PRIMARY KEY (id),
            CONSTRAINT fk_branch FOREIGN KEY (branch_id) REFERENCES $branches_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);
    }

    $loft_types_table = $wpdb->prefix . 'loft_types';
    $sql = "CREATE TABLE $loft_types_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        image_id bigint(20) NOT NULL,
        image_url varchar(255) NOT NULL,
        revolution_slider_shortcode varchar(255),
        guests int NOT NULL,
        price decimal(10,2) NOT NULL,
        description text,
        mini_description text NULL,
        room_plan_url varchar(255) NULL,
        inclusions text NULL,
        nearby_attractions text NULL,
        reviews decimal(2,1) NULL DEFAULT 5.0,
        room_size int NULL,
        room_color varchar(7) NULL,
        quantity int NULL,
        min_booking_days int NULL,
        weekly_prices text NULL,
        min_price decimal(10,2) NULL,
        services text NULL,
        additional_services text NULL,
        price_variations text NULL,
        block_reservations text NULL,
        header_image_url varchar(255) NULL,
        page_layout varchar(50) NULL,
        featured_image_size varchar(50) NULL,
        generate_virtual_key tinyint(1) DEFAULT 0,
        cleaning_status varchar(50) NULL,
        send_reminder tinyint(1) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql);

    $bookings_table = $wpdb->prefix . 'loft_bookings';
    $sql = "CREATE TABLE $bookings_table (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        loft_id MEDIUMINT(9) NOT NULL,
        user_id BIGINT(20) UNSIGNED,
        loft_type_id MEDIUMINT(9) NOT NULL,
        loft_number INT NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        checkin_date DATETIME NOT NULL,
        checkout_date DATETIME NOT NULL,
        payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
        booking_status ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
        cleaning_status ENUM('pending', 'in_progress', 'ready', 'issue') DEFAULT 'pending',
        virtual_key VARCHAR(255) NULL,
        total FLOAT NOT NULL,
        unit_id MEDIUMINT(9) NOT NULL,
        total_amount FLOAT NOT NULL,
        id_verification_url_front VARCHAR(255) NULL,
        id_verification_url_back VARCHAR(255) NULL,
        id_verification_number VARCHAR(50) NULL,
        special_requests TEXT NULL,
        notes TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (loft_id) REFERENCES {$wpdb->prefix}loft_lofts(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta($sql);


    $keys_table = $wpdb->prefix . 'loft_virtual_keys';
    if ($wpdb->get_var("SHOW TABLES LIKE '$keys_table'") != $keys_table) {
        $sql = "CREATE TABLE $keys_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255),
            booking_id mediumint(9) NULL, -- 🔧 allow NULL
            virtual_key_id varchar(255),
            pin_code varchar(10),
            qr_code_url varchar(255),
            key_status varchar(255) DEFAULT 'inactive',
            PRIMARY KEY (id),
            CONSTRAINT fk_booking FOREIGN KEY (booking_id) REFERENCES $bookings_table(id) ON DELETE SET NULL
        ) $charset_collate;";

        dbDelta($sql);
    }

    $cleaning_table = $wpdb->prefix . 'loft_cleaning';
    if ($wpdb->get_var("SHOW TABLES LIKE '$cleaning_table'") != $cleaning_table) {
        $sql = "CREATE TABLE $cleaning_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            loft_id mediumint(9) NOT NULL,
            cleaning_date DATE NOT NULL,
            status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
            PRIMARY KEY (id),
            CONSTRAINT fk_cleaning_loft FOREIGN KEY (loft_id) REFERENCES {$wpdb->prefix}loft_lofts(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);
    }

    $pricing_table = $wpdb->prefix . 'loft_pricing';
    if ($wpdb->get_var("SHOW TABLES LIKE '$pricing_table'") != $pricing_table) {
        $sql = "CREATE TABLE $pricing_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            loft_id mediumint(9) NOT NULL,
            season_name varchar(100),
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            price_per_night DECIMAL(10, 2) NOT NULL,
            PRIMARY KEY (id),
            CONSTRAINT fk_pricing_loft FOREIGN KEY (loft_id) REFERENCES {$wpdb->prefix}loft_lofts(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);
    }

    $units_table = $wpdb->prefix . 'loft_units';
    $sql = "CREATE TABLE $units_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        branch_id mediumint(9) NOT NULL,
        unit_name varchar(255) NOT NULL,
        unit_id_api bigint(20) DEFAULT NULL,  -- 🆕 ButterflyMX Unit ID
        floor varchar(255),
        access_group varchar(255),
        tenants int DEFAULT 0,
        status varchar(50) DEFAULT 'Available',
        availability_until datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_unit_api (unit_id_api),  -- ✅ Add this
        FOREIGN KEY (branch_id) REFERENCES {$wpdb->prefix}loft_branches(id) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta($sql);



   $tenants_table = $wpdb->prefix . 'loft_tenants';
    $sql = "CREATE TABLE $tenants_table (
        id INT NOT NULL AUTO_INCREMENT,
        tenant_id INT NOT NULL,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        building_name VARCHAR(255),
        unit_label VARCHAR(255),
        floor VARCHAR(50),
        lease_start DATETIME DEFAULT NULL,
        lease_end DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY tenant_id (tenant_id)
    ) $charset_collate;";
    dbDelta($sql);



    

    // Keychains Table ✅
    // Keychains Table ✅
    $keychains_table = $wpdb->prefix . 'loft_keychains';

    $sql = "CREATE TABLE $keychains_table (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tenant_id INT UNSIGNED DEFAULT NULL,
        unit_id MEDIUMINT DEFAULT NULL,
        name VARCHAR(255),
        valid_from DATETIME NOT NULL,
        valid_until DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        CONSTRAINT fk_loft_keychains_tenant_id
            FOREIGN KEY (tenant_id) REFERENCES {$wpdb->prefix}loft_tenants(id)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_loft_keychains_unit_id
            FOREIGN KEY (unit_id) REFERENCES {$wpdb->prefix}loft_units(id)
            ON DELETE SET NULL ON UPDATE CASCADE
    ) $charset_collate;";

    dbDelta($sql);



    
    // Keychains Virtual Keys Table ✅
        $keychain_virtual_keys_table = $wpdb->prefix . 'loft_keychain_virtual_keys';
    if ($wpdb->get_var("SHOW TABLES LIKE '$keychain_virtual_keys_table'") != $keychain_virtual_keys_table) {
        $sql = "CREATE TABLE $keychain_virtual_keys_table (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            keychain_id INT UNSIGNED NOT NULL,
            key_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (keychain_id)
                REFERENCES {$wpdb->prefix}loft_keychains(id)
                ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);
    }

    $availability_table = $wpdb->prefix . 'loft_availability';
    if ($wpdb->get_var("SHOW TABLES LIKE '$availability_table'") != $availability_table) {
        $sql = "CREATE TABLE $availability_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            loft_id INT NOT NULL,
            is_available BOOLEAN DEFAULT FALSE,
            notes TEXT NULL,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (loft_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);
    }


}