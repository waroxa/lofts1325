<?php
/**
 * Mobile-only front page template.
 *
 * @package Loft1325\MobileHomepage
 */

defined( 'ABSPATH' ) || exit;

$plugin = Loft1325_Mobile_Homepage::instance();

$hero_background_id = (int) get_theme_mod( 'loft1325_mobile_home_hero_background', 0 );
$hero_background    = $hero_background_id ? wp_get_attachment_image_url( $hero_background_id, 'full' ) : '';
$rooms_archive      = get_post_type_archive_link( 'nd_booking_cpt_1' );

if ( ! $rooms_archive ) {
    $rooms_archive = home_url( '/' );
}

get_header();
?>

<div class="loft1325-mobile-home__wrapper">
    <header class="loft1325-mobile-home__topbar">
        <div class="loft1325-mobile-home__logo">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span class="loft1325-mobile-home__site-title"><?php bloginfo( 'name' ); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <main id="loft1325-mobile-homepage" class="loft1325-mobile-home">
        <section class="loft1325-mobile-home__hero" style="<?php echo $hero_background ? 'background-image: url(' . esc_url( $hero_background ) . ');' : ''; ?>">
            <div class="loft1325-mobile-home__hero-overlay"></div>
            <div class="loft1325-mobile-home__hero-body">
                <div class="loft1325-mobile-home__hero-content">
                    <span class="loft1325-mobile-home__hero-pill"><?php echo esc_html( $plugin->get_string( 'hero_tagline' ) ); ?></span>
                    <h1 class="loft1325-mobile-home__hero-title"><?php echo esc_html( $plugin->get_string( 'hero_title' ) ); ?></h1>
                </div>

                <div
                    class="loft1325-mobile-home__search-card"
                    id="loft1325-mobile-home-search"
                    data-date-label="<?php echo esc_attr( $plugin->get_string( 'search_date_label' ) ); ?>"
                    data-arrival-label="<?php echo esc_attr( __( 'Arrivée', 'loft1325-mobile-home' ) ); ?>"
                    data-departure-label="<?php echo esc_attr( __( 'Départ', 'loft1325-mobile-home' ) ); ?>"
                    data-guests-label="<?php echo esc_attr( $plugin->get_string( 'search_guests_label' ) ); ?>"
                    data-submit-label="<?php echo esc_attr( $plugin->get_string( 'search_submit_label' ) ); ?>"
                    data-date-placeholder="<?php echo esc_attr( __( 'Sélectionner les dates', 'loft1325-mobile-home' ) ); ?>"
                    data-guests-singular="<?php echo esc_attr( __( 'invité', 'loft1325-mobile-home' ) ); ?>"
                    data-guests-plural="<?php echo esc_attr( __( 'invités', 'loft1325-mobile-home' ) ); ?>"
                    data-nights-singular="<?php echo esc_attr( __( 'nuit', 'loft1325-mobile-home' ) ); ?>"
                    data-nights-plural="<?php echo esc_attr( __( 'nuits', 'loft1325-mobile-home' ) ); ?>"
                >
                    <h2 class="loft1325-mobile-home__search-title"><?php echo esc_html( $plugin->get_string( 'search_card_title' ) ); ?></h2>
                    <div class="loft1325-mobile-home__search-location">
                        <span class="loft1325-mobile-home__search-location-label"><?php esc_html_e( 'Où', 'loft1325-mobile-home' ); ?></span>
                        <span class="loft1325-mobile-home__search-location-value">Loft1325, Québec</span>
                    </div>
                    <div class="loft1325-mobile-home__search-form">
                        <?php echo $plugin->get_mobile_search_form_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                </div>

                <p class="loft1325-mobile-home__hero-text"><?php echo esc_html( $plugin->get_string( 'hero_description' ) ); ?></p>

                <div class="loft1325-mobile-home__hero-actions">
                <?php if ( $plugin->get_string( 'hero_primary_label' ) ) : ?>
                    <a class="loft1325-mobile-home__btn loft1325-mobile-home__btn--primary" href="<?php echo esc_url( $plugin->get_string( 'hero_primary_url' ) ); ?>">
                        <?php echo esc_html( $plugin->get_string( 'hero_primary_label' ) ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( $plugin->get_string( 'hero_secondary_label' ) ) : ?>
                    <a class="loft1325-mobile-home__btn loft1325-mobile-home__btn--ghost" href="<?php echo esc_url( $plugin->get_string( 'hero_secondary_url' ) ); ?>">
                        <?php echo esc_html( $plugin->get_string( 'hero_secondary_label' ) ); ?>
                    </a>
                <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="loft1325-mobile-home__features" aria-labelledby="loft1325-mobile-features-heading">
            <h2 id="loft1325-mobile-features-heading" class="screen-reader-text"><?php esc_html_e( 'Avantages principaux', 'loft1325-mobile-home' ); ?></h2>
            <div class="loft1325-mobile-home__features-grid">
                <?php foreach ( $plugin->get_feature_cards() as $feature ) : ?>
                    <article class="loft1325-mobile-home__feature">
                        <div class="loft1325-mobile-home__feature-icon">
                            <?php if ( ! empty( $feature['image'] ) ) : ?>
                                <img src="<?php echo esc_url( $feature['image'] ); ?>" alt="" loading="lazy" />
                            <?php elseif ( ! empty( $feature['icon'] ) ) : ?>
                                <span class="dashicons <?php echo esc_attr( $feature['icon'] ); ?>" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                        <p class="loft1325-mobile-home__feature-title"><?php echo esc_html( $feature['title'] ); ?></p>
                        <?php if ( ! empty( $feature['description'] ) ) : ?>
                            <p class="loft1325-mobile-home__feature-description"><?php echo esc_html( $feature['description'] ); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="loft1325-mobile-home__rooms" aria-labelledby="loft1325-mobile-rooms-heading">
            <div class="loft1325-mobile-home__section-heading">
                <h2 id="loft1325-mobile-rooms-heading" class="loft1325-mobile-home__section-title"><?php echo esc_html( $plugin->get_string( 'rooms_heading' ) ); ?></h2>
                <p class="loft1325-mobile-home__section-description"><?php echo esc_html( $plugin->get_string( 'rooms_description' ) ); ?></p>
            </div>
            <div class="loft1325-mobile-home__room-list">
                <?php
                $rooms = $plugin->get_room_cards();
                if ( empty( $rooms ) ) :
                    ?>
                    <p class="loft1325-mobile-home__empty">
                        <?php esc_html_e( 'Aucun loft n’est actuellement disponible. Ajoutez vos chambres ND Booking pour alimenter cette section.', 'loft1325-mobile-home' ); ?>
                    </p>
                <?php else :
                    foreach ( $rooms as $room ) :
                        ?>
                        <article class="loft1325-mobile-home__room-card">
                            <?php if ( ! empty( $room['image'] ) ) : ?>
                                <a class="loft1325-mobile-home__room-media" href="<?php echo esc_url( $room['permalink'] ); ?>">
                                    <img src="<?php echo esc_url( $room['image'] ); ?>" alt="<?php echo esc_attr( $room['title'] ); ?>" loading="lazy" />
                                </a>
                            <?php endif; ?>
                            <div class="loft1325-mobile-home__room-body">
                                <?php if ( ! empty( $room['rating'] ) ) : ?>
                                    <span class="loft1325-mobile-home__room-badge" aria-label="<?php echo esc_attr( sprintf( __( 'Note %s sur 5', 'loft1325-mobile-home' ), $room['rating'] ) ); ?>"><?php echo esc_html( $room['rating'] ); ?></span>
                                <?php endif; ?>
                                <h3 class="loft1325-mobile-home__room-title">
                                    <a href="<?php echo esc_url( $room['permalink'] ); ?>"><?php echo esc_html( $room['title'] ); ?></a>
                                </h3>
                                <p class="loft1325-mobile-home__room-text"><?php echo esc_html( $room['excerpt'] ); ?></p>
                                <?php if ( $room['price'] ) : ?>
                                    <p class="loft1325-mobile-home__room-price">
                                        <?php
                                        $currency_suffix = $room['currency'] ? ' ' . $room['currency'] : '';
                                        echo esc_html(
                                            sprintf(
                                                /* translators: 1: price amount, 2: currency symbol */
                                                __( 'À partir de %1$s%2$s', 'loft1325-mobile-home' ),
                                                number_format_i18n( (float) $room['price'], 0 ),
                                                $currency_suffix
                                            )
                                        );
                                        ?>
                                        <span class="loft1325-mobile-home__room-price-unit"><?php esc_html_e( 'par nuit', 'loft1325-mobile-home' ); ?></span>
                                    </p>
                                <?php endif; ?>
                                <div class="loft1325-mobile-home__room-actions">
                                    <a class="loft1325-mobile-home__btn loft1325-mobile-home__btn--secondary" href="<?php echo esc_url( $room['permalink'] ); ?>">
                                        <?php echo esc_html( $plugin->get_string( 'rooms_button_label' ) ); ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php
                    endforeach;
                endif;
                ?>
            </div>
            <div class="loft1325-mobile-home__room-footer">
                <a class="loft1325-mobile-home__btn loft1325-mobile-home__btn--ghost" href="<?php echo esc_url( $rooms_archive ); ?>">
                    <?php echo esc_html( $plugin->get_string( 'rooms_view_all_label' ) ); ?>
                </a>
            </div>
        </section>

        <section class="loft1325-mobile-home__cta" aria-labelledby="loft1325-mobile-cta-heading">
            <div class="loft1325-mobile-home__cta-inner">
                <h2 id="loft1325-mobile-cta-heading" class="loft1325-mobile-home__cta-title"><?php echo esc_html( $plugin->get_string( 'cta_heading' ) ); ?></h2>
                <p class="loft1325-mobile-home__cta-text"><?php echo esc_html( $plugin->get_string( 'cta_description' ) ); ?></p>
                <div class="loft1325-mobile-home__cta-actions">
                    <?php if ( $plugin->get_string( 'cta_primary_label' ) ) : ?>
                        <a class="loft1325-mobile-home__btn loft1325-mobile-home__btn--primary" href="<?php echo esc_url( $plugin->get_string( 'cta_primary_url' ) ); ?>">
                            <?php echo esc_html( $plugin->get_string( 'cta_primary_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ( $plugin->get_string( 'cta_secondary_label' ) ) : ?>
                        <a class="loft1325-mobile-home__btn loft1325-mobile-home__btn--ghost" href="<?php echo esc_url( $plugin->get_string( 'cta_secondary_url' ) ); ?>">
                            <?php echo esc_html( $plugin->get_string( 'cta_secondary_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="loft1325-mobile-home__footer" aria-labelledby="loft1325-mobile-footer-heading">
        <h2 id="loft1325-mobile-footer-heading" class="screen-reader-text"><?php esc_html_e( 'Pied de page mobile Loft1325', 'loft1325-mobile-home' ); ?></h2>
        <div class="loft1325-mobile-home__footer-brand">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span class="loft1325-mobile-home__site-title"><?php bloginfo( 'name' ); ?></span>
            <?php endif; ?>
            <p class="loft1325-mobile-home__footer-tagline"><?php echo esc_html( $plugin->get_string( 'footer_legal' ) ); ?></p>
        </div>
        <div class="loft1325-mobile-home__footer-columns">
            <div class="loft1325-mobile-home__footer-column">
                <p class="loft1325-mobile-home__footer-heading"><?php echo esc_html( $plugin->get_string( 'footer_nav_heading' ) ); ?></p>
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'main-menu',
                        'container'      => false,
                        'menu_class'     => 'loft1325-mobile-home__footer-menu',
                        'fallback_cb'    => '__return_empty_string',
                    )
                );
                ?>
            </div>
            <div class="loft1325-mobile-home__footer-column">
                <p class="loft1325-mobile-home__footer-heading"><?php echo esc_html( $plugin->get_string( 'footer_support_heading' ) ); ?></p>
                <ul class="loft1325-mobile-home__footer-menu">
                    <?php foreach ( array( 'faq', 'reglements', 'contact' ) as $slug ) :
                        $page = get_page_by_path( $slug );
                        if ( $page instanceof WP_Post ) :
                            ?>
                            <li><a href="<?php echo esc_url( get_permalink( $page ) ); ?>"><?php echo esc_html( get_the_title( $page ) ); ?></a></li>
                        <?php endif;
                    endforeach; ?>
                </ul>
            </div>
            <?php $social_links = $plugin->get_social_links(); ?>
            <?php if ( ! empty( $social_links ) ) : ?>
                <div class="loft1325-mobile-home__footer-column">
                    <p class="loft1325-mobile-home__footer-heading"><?php echo esc_html( $plugin->get_string( 'footer_social_heading' ) ); ?></p>
                    <ul class="loft1325-mobile-home__footer-menu">
                        <?php foreach ( $social_links as $link ) : ?>
                            <li><a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link['label'] ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <p class="loft1325-mobile-home__footer-copy"><?php echo esc_html( $plugin->get_string( 'footer_copyright' ) ); ?></p>
    </footer>
</div>

<?php
get_footer();
