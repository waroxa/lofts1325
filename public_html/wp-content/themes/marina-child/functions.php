<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'ms_theme_editor_parent_css' ) ):
    function ms_theme_editor_parent_css() {
        $parent_theme = wp_get_theme()->parent();

        if ( ! $parent_theme instanceof WP_Theme ) {
            $parent_theme = wp_get_theme();
        }

        wp_enqueue_style(
            'chld_thm_cfg_parent',
            trailingslashit( get_template_directory_uri() ) . 'style.css',
            array(),
            $parent_theme->get( 'Version' )
        );
    }
endif;
add_action( 'wp_enqueue_scripts', 'ms_theme_editor_parent_css', 10 );

/**
 * Enqueue child theme styles.
 */
function marina_child_enqueue_custom_assets() {
    $dependencies = array( 'chld_thm_cfg_parent' );

    if ( wp_style_is( 'nd_booking_style', 'enqueued' ) || wp_style_is( 'nd_booking_style', 'registered' ) ) {
        $dependencies[] = 'nd_booking_style';
    }

    $header_fixes_path = get_stylesheet_directory() . '/css/header-fixes.css';
    $header_fixes_version = file_exists( $header_fixes_path ) ? (string) filemtime( $header_fixes_path ) : wp_get_theme()->get( 'Version' );

    wp_enqueue_style(
        'marina-child-header-fixes',
        get_stylesheet_directory_uri() . '/css/header-fixes.css',
        $dependencies,
        $header_fixes_version
    );

    if ( is_front_page() && wp_is_mobile() ) {
        $mobile_home_path = get_stylesheet_directory() . '/css/mobile-home.css';
        $home_translation_fix_path = get_stylesheet_directory() . '/js/home-translation-fix.js';

        if ( file_exists( $mobile_home_path ) && is_readable( $mobile_home_path ) ) {
            $mobile_home_version = (string) filemtime( $mobile_home_path );

            wp_enqueue_style(
                'marina-child-mobile-home',
                get_stylesheet_directory_uri() . '/css/mobile-home.css',
                array( 'marina-child-header-fixes' ),
                $mobile_home_version
            );
        }

        if ( file_exists( $home_translation_fix_path ) && is_readable( $home_translation_fix_path ) ) {
            $home_translation_fix_version = (string) filemtime( $home_translation_fix_path );

            wp_enqueue_script(
                'marina-child-home-translation-fix',
                get_stylesheet_directory_uri() . '/js/home-translation-fix.js',
                array(),
                $home_translation_fix_version,
                true
            );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'marina_child_enqueue_custom_assets', 20 );

/**
 * Load the elevated search experience styles when needed.
 */
function marina_child_enqueue_search_styles() {
    $should_enqueue = is_search();

    if ( ! $should_enqueue && is_page() ) {
        $page = get_post();

        if ( $page instanceof WP_Post ) {
            if ( has_shortcode( $page->post_content, 'nd_booking_search_results' ) ) {
                $should_enqueue = true;
            }

            if ( ! $should_enqueue && marina_child_post_contains_search_shortcode( $page ) ) {
                $should_enqueue = true;
            }
        }
    }

    if ( ! $should_enqueue ) {
        $nd_booking_query_params = array(
            'nd_booking_archive_form_date_range_from',
            'nd_booking_archive_form_date_range_to',
            'nd_booking_archive_form_guests',
            'nd_booking_archive_form_services',
            'nd_booking_archive_form_additional_services',
            'nd_booking_archive_form_branch_stars',
            'nd_booking_archive_form_branches',
            'nd_booking_archive_form_max_price_for_day',
        );

        foreach ( $nd_booking_query_params as $query_param ) {
            if ( isset( $_GET[ $query_param ] ) && '' !== $_GET[ $query_param ] ) {
                $should_enqueue = true;
                break;
            }
        }
    }

    if ( ! $should_enqueue ) {
        return;
    }

    $search_styles_path = get_stylesheet_directory() . '/css/search-results.css';
    $search_styles_uri  = get_stylesheet_directory_uri() . '/css/search-results.css';
    $search_styles_exists = file_exists( $search_styles_path ) && is_readable( $search_styles_path );
    $search_styles_version = $search_styles_exists ? (string) filemtime( $search_styles_path ) : wp_get_theme()->get( 'Version' );

    $nd_booking_handle          = 'nd_booking_style';
    $nd_booking_style_available = wp_style_is( $nd_booking_handle, 'enqueued' ) || wp_style_is( $nd_booking_handle, 'registered' );

    if ( $search_styles_exists && $nd_booking_style_available ) {
        $search_styles_css = file_get_contents( $search_styles_path );

        if ( false !== $search_styles_css && '' !== trim( $search_styles_css ) ) {
            if ( ! wp_style_is( $nd_booking_handle, 'enqueued' ) ) {
                wp_enqueue_style( $nd_booking_handle );
            }

            wp_add_inline_style( $nd_booking_handle, $search_styles_css );

            $GLOBALS['marina_child_search_styles_enqueued'] = 'inline';

            return;
        }
    }

    if ( ! $search_styles_exists ) {
        return;
    }

    wp_enqueue_style(
        'marina-child-search-results',
        $search_styles_uri,
        array( 'marina-child-header-fixes' ),
        $search_styles_version
    );

    $GLOBALS['marina_child_search_styles_enqueued'] = 'standalone';
}
add_action( 'wp_enqueue_scripts', 'marina_child_enqueue_search_styles', 25 );

/**
 * Determine whether the supplied post (or any Elementor template it references)
 * includes the ND Booking search results shortcode.
 *
 * @param WP_Post $post The post object under evaluation.
 *
 * @return bool
 */
function marina_child_post_contains_search_shortcode( WP_Post $post ) {
    return marina_child_post_contains_shortcode( $post, 'nd_booking_search_results' );
}

/**
 * Determine whether Elementor JSON data references the supplied shortcode.
 *
 * @param mixed  $elementor_data Elementor post meta value.
 * @param string $shortcode      Shortcode tag to search for.
 *
 * @return bool
 */
function marina_child_elementor_data_contains_shortcode( $elementor_data, $shortcode ) {
    $shortcode = trim( (string) $shortcode );

    if ( '' === $shortcode || empty( $elementor_data ) ) {
        return false;
    }

    if ( is_array( $elementor_data ) ) {
        $elementor_data = wp_json_encode( $elementor_data );
    }

    if ( ! is_string( $elementor_data ) ) {
        return false;
    }

    return false !== stripos( $elementor_data, $shortcode );
}

/**
 * Inspect Elementor JSON data for the ND Booking search results shortcode reference.
 *
 * @param mixed $elementor_data Elementor post meta value.
 *
 * @return bool
 */
function marina_child_elementor_data_contains_search_shortcode( $elementor_data ) {
    return marina_child_elementor_data_contains_shortcode( $elementor_data, 'nd_booking_search_results' );
}

/**
 * Determine whether the supplied post (or any referenced Elementor template) contains a target shortcode.
 *
 * @param WP_Post $post      The post object under evaluation.
 * @param string  $shortcode Shortcode tag to search for.
 * @param array   $visited   Internal recursion guard to avoid repeated scans.
 *
 * @return bool
 */
function marina_child_post_contains_shortcode( WP_Post $post, $shortcode, array &$visited = array() ) {
    $shortcode = trim( (string) $shortcode );

    if ( '' === $shortcode ) {
        return false;
    }

    if ( isset( $visited[ $post->ID ] ) ) {
        return false;
    }

    $visited[ $post->ID ] = true;

    $post_content = (string) $post->post_content;

    if ( has_shortcode( $post_content, $shortcode ) || false !== stripos( $post_content, '[' . $shortcode ) ) {
        return true;
    }

    if ( marina_child_elementor_data_contains_shortcode( get_post_meta( $post->ID, '_elementor_data', true ), $shortcode ) ) {
        return true;
    }

    if ( ! has_shortcode( $post_content, 'elementor-template' ) ) {
        return false;
    }

    preg_match_all( '/\[elementor-template[^\]]*id="?(\d+)"?[^\]]*\]/i', $post_content, $matches );

    if ( empty( $matches[1] ) ) {
        return false;
    }

    foreach ( $matches[1] as $template_id ) {
        $template_id = absint( $template_id );

        if ( ! $template_id || isset( $visited[ $template_id ] ) ) {
            continue;
        }

        $template_post = get_post( $template_id );

        if ( ! $template_post instanceof WP_Post ) {
            continue;
        }

        if ( marina_child_post_contains_shortcode( $template_post, $shortcode, $visited ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Output console diagnostics confirming which theme stylesheets load for administrators.
 */
function marina_child_output_stylesheet_debug_report() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $search_styles_state = isset( $GLOBALS['marina_child_search_styles_enqueued'] ) ? $GLOBALS['marina_child_search_styles_enqueued'] : '';

    $styles_to_check = array(
        'chld_thm_cfg_parent'       => __( 'Parent theme stylesheet', 'marina-child' ),
        'marina-child-header-fixes' => __( 'Header fixes stylesheet', 'marina-child' ),
    );

    if ( 'standalone' === $search_styles_state || wp_style_is( 'marina-child-search-results', 'enqueued' ) ) {
        $styles_to_check['marina-child-search-results'] = __( 'Search results stylesheet', 'marina-child' );
    }

    $styles_report = array();

    foreach ( $styles_to_check as $handle => $label ) {
        $styles_report[] = array(
            'handle'   => $handle,
            'label'    => $label,
            'enqueued' => wp_style_is( $handle, 'enqueued' ),
        );
    }

    if ( 'inline' === $search_styles_state ) {
        $styles_report[] = array(
            'handle'   => 'nd_booking_style',
            'label'    => __( 'ND Booking stylesheet (with inline search results styles)', 'marina-child' ),
            'enqueued' => wp_style_is( 'nd_booking_style', 'enqueued' ),
            'inline'   => true,
        );
    }

    if ( empty( $styles_report ) ) {
        return;
    }

    ?>
    <script>
        (function () {
            var styles = <?php echo wp_json_encode( $styles_report ); ?>;

            styles.forEach(function (style) {
                var elementId = style.handle + '-css';
                var stylesheetEl = document.getElementById(elementId);

                if (stylesheetEl && stylesheetEl.href) {
                    if (style.inline) {
                        console.info('[Marina Child] "%s" [%s] loaded from: %s (inline search results styles applied)', style.label, style.handle, stylesheetEl.href);
                        return;
                    }

                    console.info('[Marina Child] "%s" [%s] loaded from: %s', style.label, style.handle, stylesheetEl.href);
                    return;
                }

                if (style.enqueued) {
                    if (style.inline) {
                        console.warn('[Marina Child] "%s" [%s] was enqueued for inline styles, but the link element (#%s) is missing.', style.label, style.handle, elementId);
                        return;
                    }

                    console.warn('[Marina Child] "%s" [%s] was enqueued, but the link element (#%s) is missing.', style.label, style.handle, elementId);
                    return;
                }

                if (style.inline) {
                    console.warn('[Marina Child] "%s" [%s] could not apply inline search results styles because the stylesheet is not enqueued.', style.label, style.handle);
                    return;
                }

                console.warn('[Marina Child] "%s" [%s] is not enqueued on this page.', style.label, style.handle);
            });
        })();
    </script>
    <?php
}
add_action( 'wp_footer', 'marina_child_output_stylesheet_debug_report', 100 );

// END ENQUEUE PARENT ACTION


/**
 * Output global Loft 1325 keyword meta tags.
 */
function loft1325_output_global_meta_keywords() {
    // Skip if All in One SEO already renders meta keywords for this request.
    if ( function_exists( 'aioseo' ) ) {
        $aioseo_instance = aioseo();

        if ( is_object( $aioseo_instance ) && isset( $aioseo_instance->meta ) ) {
            $meta = $aioseo_instance->meta;

            if ( is_object( $meta ) && isset( $meta->metaData ) && is_object( $meta->metaData ) ) {
                $meta_data = $meta->metaData;

                if (
                    ( property_exists( $meta_data, 'keywords' ) && ! empty( $meta_data->keywords ) ) ||
                    ( method_exists( $meta_data, 'keywords' ) && ! empty( $meta_data->keywords() ) )
                ) {
                    return;
                }
            }
        }
    }

    $english_keywords = array(
        'Loft 1325',
        'Lofts 1325',
        'Le 1325',
        'Loft1325',
        'Lofts1325',
        'Loft 1325 Val-d’Or',
        'Lofts 1325 Val-d’Or',
        'Loft 1325 Québec',
        'Lofts 1325 Québec',
        'Loft 1325 Abitibi',
        'Loft 1325 Val-d\'Or Quebec Canada',
        'Loft 1325 hotel Val-d’Or',
        'Loft 1325 apartments Val-d’Or',
        'Loft 1325 rentals Val-d’Or',
        'Loft 1325 Airbnb Val-d’Or',
        'Loft 1325 corporate rentals Val-d’Or',
        'Loft 1325 tourist home Val-d’Or',
        'Loft 1325 short-term rentals Val-d’Or',
        'Loft 1325 furnished apartments Val-d’Or',
        'Loft 1325 long-term stay Val-d’Or',
        'Loft 1325 furnished rentals Val-d’Or',
        'Loft 1325 vacation rentals Val-d’Or',
    );

    $french_keywords = array(
        'Loft 1325 hôtel Val-d’Or',
        'Loft 1325 appartements meublés Val-d’Or',
        'Loft 1325 location court terme Val-d’Or',
        'Loft 1325 hébergement touristique Val-d’Or',
        'Loft 1325 location longue durée Val-d’Or',
    );

    $all_keywords = array_unique( array_map( 'trim', array_merge( $english_keywords, $french_keywords ) ) );

    if ( empty( $all_keywords ) ) {
        return;
    }

    printf(
        "<meta name=\"keywords\" content=\"%s\" />\n",
        esc_attr( implode( ', ', $all_keywords ) )
    );
}
add_action( 'wp_head', 'loft1325_output_global_meta_keywords', 1 );


//function BUTTERFLYMX
function encolar_scripts_listar_tenants() {
    if ( ! is_singular() ) {
        return;
    }

    $post = get_post();

    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    if ( ! marina_child_post_contains_shortcode( $post, 'boton_listar_tenants' ) ) {
        return;
    }

    $script_handle  = 'listar-tenants-js';
    $script_path    = get_stylesheet_directory() . '/js/listar-tenants.js';
    $script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );

    wp_enqueue_script(
        $script_handle,
        get_stylesheet_directory_uri() . '/js/listar-tenants.js',
        array( 'jquery' ),
        $script_version,
        true
    );

    $localization = array(
        'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
        'defaultBuildingId'   => apply_filters( 'marina_child_default_building_id', 60892 ),
        'i18n'                => array(
            'loading' => __( 'Loading tenants…', 'marina-child' ),
            'error'   => __( 'We could not load the tenant list. Please try again.', 'marina-child' ),
            'empty'   => __( 'No tenants were returned for this building.', 'marina-child' ),
            'retry'   => __( 'Retry', 'marina-child' ),
            'columns' => array(
                'full_name'   => __( 'Name', 'marina-child' ),
                'email'       => __( 'Email', 'marina-child' ),
                'phone'       => __( 'Phone', 'marina-child' ),
                'unit_label'  => __( 'Unit', 'marina-child' ),
                'lease_start' => __( 'Lease start', 'marina-child' ),
                'lease_end'   => __( 'Lease end', 'marina-child' ),
            ),
        ),
    );

    wp_localize_script( $script_handle, 'listarTenantsSettings', $localization );

    $style_path = get_stylesheet_directory() . '/css/admin-hub.css';

    if ( file_exists( $style_path ) ) {
        $style_version = (string) filemtime( $style_path );

        wp_enqueue_style(
            'marina-child-admin-hub',
            get_stylesheet_directory_uri() . '/css/admin-hub.css',
            array( 'marina-child-header-fixes' ),
            $style_version
        );
    }
}
add_action( 'wp_enqueue_scripts', 'encolar_scripts_listar_tenants' );

function boton_listar_tenants( $atts = array(), $content = '' ) {
    $atts = shortcode_atts(
        array(
            'building_id' => apply_filters( 'marina_child_default_building_id', 60892 ),
            'autoload'    => 'true',
        ),
        $atts,
        'boton_listar_tenants'
    );

    $building_id      = is_numeric( $atts['building_id'] ) ? (int) $atts['building_id'] : '';
    $should_autoload  = filter_var( $atts['autoload'], FILTER_VALIDATE_BOOLEAN );
    $unique_id        = wp_unique_id( 'listar-tenants-' );
    $button_id        = $unique_id . '-button';
    $results_id       = $unique_id . '-results';
    $building_attr    = '' !== $building_id ? sprintf( ' data-building-id="%s"', esc_attr( $building_id ) ) : '';
    $autoload_attr    = $should_autoload ? 'true' : 'false';

    $output  = '<div class="listar-tenants"' . $building_attr . ' data-autoload="' . esc_attr( $autoload_attr ) . '">';
    $output .= sprintf(
        '<button type="button" id="%1$s" class="listar-tenants__button button button-primary" data-building-id="%2$s" data-autoload="%3$s">%4$s</button>',
        esc_attr( $button_id ),
        esc_attr( $building_id ),
        esc_attr( $autoload_attr ),
        esc_html__( 'List tenants', 'marina-child' )
    );
    $output .= sprintf(
        '<div id="%1$s" class="listar-tenants__results" role="region" aria-live="polite"></div>',
        esc_attr( $results_id )
    );
    $output .= '</div>';

    return $output;
}
add_shortcode( 'boton_listar_tenants', 'boton_listar_tenants' );

function listar_tenants_building() {
    $plugin_instance = new IntegracionButterflyMX();
    $building_id = isset($_GET['building_id']) ? intval($_GET['building_id']) : 60892; // Default to 60892
    $response = $plugin_instance->get_tenants_by_building($building_id);

    if (is_wp_error($response)) {
        error_log('Error al listar tenants: ' . $response->get_error_message());
        wp_send_json_error('Error al listar tenants: ' . $response->get_error_message());
    } else {
        error_log('Tenants obtenidos correctamente.');
        wp_send_json_success($plugin_instance->format_tenants($response));
    }
}
add_action('wp_ajax_listar_tenants_building', 'listar_tenants_building');
add_action('wp_ajax_nopriv_listar_tenants_building', 'listar_tenants_building');

update_option('loft_booking_cleaning_calendar_id', 'e964e301b54d0e795b44a76ebfb9d2cfbd2f6517a822429c5af62bc2cb94de20@group.calendar.google.com');
update_option('loft_booking_calendar_id', 'a752f27cffee8c22988adb29fdc933c93184e3a5814c79dcee4f62115d69fbfd@group.calendar.google.com');

// add_action('nd_booking_after_booking_completed', 'handle_successful_booking', 10, 1);



// function handle_successful_booking($booking_id) {
//     global $wpdb;

//     // Fetch booking from custom booking table
//     $booking = $wpdb->get_row(
//         $wpdb->prepare("SELECT * FROM {$wpdb->prefix}nd_booking_booking WHERE id = %d", $booking_id)
//     );

//     if (!$booking) {
//         error_log("❌ Booking ID {$booking_id} not found in nd_booking_booking table.");
//         return;
//     }

//     // Extract info
//     $room_id      = $booking->id_post;
//     $room_type    = strtoupper($booking->title_post); // OCCUPATION SIMPLE, DOUBLE, PENTHOUSE
//     $first_name   = $booking->user_first_name;
//     $last_name    = $booking->user_last_name;
//     $email        = $booking->paypal_email;
//     $checkin      = $booking->date_from;
//     $checkout     = $booking->date_to;

//     // Normalize room type to match loft label syntax
//     if (stripos($room_type, 'SIMPLE') !== false) $room_type = 'SIMPLE';
//     if (stripos($room_type, 'DOUBLE') !== false) $room_type = 'DOUBLE';
//     if (stripos($room_type, 'PENTHOUSE') !== false) $room_type = 'PENTHOUSE';

//     // Step 1: Find matching available loft
//     $loft = find_first_available_loft_unit($room_type);

//     if (!$loft) {
//         error_log("❌ No available loft unit found for type: $room_type");
//         return;
//     }

//     // Step 2: Create tenant in ButterflyMX
//     $tenant_id = create_butterflymx_tenant($loft->id, $email, $first_name, $last_name);

//     if (!$tenant_id) {
//         error_log("❌ Failed to create ButterflyMX tenant for {$email}");
//         return;
//     }

//     // Step 3: Create virtual key / visitor pass
//     $created = create_butterflymx_visitor_pass($loft->id, $email, $checkin, $checkout);

//     if (!$created) {
//         error_log("❌ Failed to create visitor pass for {$email}");
//         return;
//     }

//     // Step 4: Google Calendar entry
//     add_booking_to_google_calendar("Booking for $first_name $last_name", $checkin, $checkout);

//     // // Step 5: Cleaning task (1 hour after checkout)
//     // $cleaning_time = date('Y-m-d H:i:s', strtotime($checkout . ' +1 hour'));
//     // schedule_cleaning_task("Cleaning: {$loft->unit_name}", $cleaning_time);

//     error_log("✅ Booking automation completed for $email");
// }

add_action( 'wp_head', function() {
    $child_style = get_stylesheet_directory_uri() . '/style.css';

    echo '<link rel="stylesheet" id="marina-child-style" href="' . esc_url( $child_style ) . '?v=' . filemtime( get_stylesheet_directory() . '/style.css' ) . '" type="text/css" media="all" />';
}, 999 );

/**
 * Strip broken TranslatePress placeholders from rendered HTML.
 *
 * @param string $html Output buffer contents.
 *
 * @return string
 */
function marina_child_strip_trp_placeholders( $html ) {
    if ( false === strpos( $html, '#!trp' ) && false === strpos( $html, '#TRP' ) ) {
        return $html;
    }

    $patterns = array(
        '/#!trpst#trp-gettext[^#]*#!trpen#(.*?)#!trpst#\\/trp-gettext#!trpen#/si',
        '/#!trpst#trp-ltr-start#!trpen#(.*?)#!trpst#trp-ltr-end#!trpen#/si',
    );

    foreach ( $patterns as $pattern ) {
        $html = preg_replace( $pattern, '$1', $html );
    }

    $html = str_replace( array( '#!trpst#', '#!trpen#' ), '', $html );
    $html = preg_replace( '/#TRP[^#<]*#/i', '', $html );

    return $html;
}

/**
 * Start output buffering early to clean TRP markers.
 */
function marina_child_start_trp_cleanup_buffer() {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    ob_start( 'marina_child_strip_trp_placeholders' );
}
add_action( 'template_redirect', 'marina_child_start_trp_cleanup_buffer', 0 );

/**
 * Localize ND Booking availability alerts based on the active language.
 */
function marina_child_translate_booking_alerts( $translation, $text, $domain ) {
    if ( 'nd-booking' !== $domain ) {
        return $translation;
    }

    $language = '';

    if ( function_exists( 'trp_get_current_language' ) ) {
        $language = trp_get_current_language();
    } elseif ( function_exists( 'determine_locale' ) ) {
        $language = determine_locale();
    } else {
        $language = get_locale();
    }

    $language = strtolower( substr( (string) $language, 0, 2 ) );

    $strings = array(
        'THIS IS THE LAST ROOM AT THIS PRICE' => array(
            'fr' => 'DERNIÈRE CHAMBRE DISPONIBLE À CE PRIX',
            'en' => 'THIS IS THE LAST ROOM AT THIS PRICE',
        ),
        'ONLY' => array(
            'fr' => 'PLUS QUE',
            'en' => 'ONLY',
        ),
        'ROOM LEFT AT THIS PRICE' => array(
            'fr' => 'CHAMBRES À CE PRIX',
            'en' => 'ROOM LEFT AT THIS PRICE',
        ),
        'LAST ROOMS AVAILABLE !' => array(
            'fr' => 'DERNIÈRES CHAMBRES DISPONIBLES !',
            'en' => 'LAST ROOMS AVAILABLE !',
        ),
    );

    if ( isset( $strings[ $text ] ) ) {
        if ( isset( $strings[ $text ][ $language ] ) ) {
            return $strings[ $text ][ $language ];
        }

        if ( isset( $strings[ $text ]['en'] ) ) {
            return $strings[ $text ]['en'];
        }
    }

    return $translation;
}
add_filter( 'gettext', 'marina_child_translate_booking_alerts', 10, 3 );

/**
 * Prevent search submissions from resolving as 404s so results can render.
 */
function marina_child_prevent_search_404() {
    if ( is_admin() ) {
        return;
    }

    if ( is_search() && is_404() ) {
        status_header( 200 );
        $GLOBALS['wp_query']->is_404 = false;
    }
}
add_action( 'template_redirect', 'marina_child_prevent_search_404', 9 );
