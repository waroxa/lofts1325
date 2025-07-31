wp-loft-booking-plugin/
├── assets/
│   ├── css/
│   │   └── custom-loft-style.css
│   └── js/
│       └── custom-loft-script.js
├── includes/
│   ├── admin/
│   │   ├── admin-menu.php        # Admin menu and page functions
│   │   ├── branches.php         # Branch management
│   │   ├── lofts.php            # Loft management
│   │   ├── bookings.php         # Booking management
│   │   ├── loft-types.php       # Loft types management
│   │   ├── butterflymx-settings.php # ButterflyMX settings
│   │   ├── payment-settings.php # Payment settings
│   │   └── tenants.php          # Tenants management
│   ├── database/
│   │   ├── db-setup.php         # Table creation and activation hooks
│   │   └── db-cleanup.php       # Deactivation and cleanup
│   ├── integrations/
│   │   └── butterflymx.php      # ButterflyMX API integration
│   ├── shortcodes/
│   │   ├── booking-form.php     # Booking form shortcode
│   │   ├── search-form.php      # Search form shortcode
│   │   ├── display-results.php  # Results display shortcodes
│   │   └── loft-types-display.php # Loft types display shortcodes
│   ├── ajax/
│   │   ├── ajax-handlers.php    # All AJAX handlers
│   └── cron/
│       └── cron-jobs.php        # Cron job scheduling and handling
├── vendor/                          # Third-party libraries (e.g., Stripe)
│   └── autoload.php
└── wp-loft-booking-plugin.php       # Main plugin file