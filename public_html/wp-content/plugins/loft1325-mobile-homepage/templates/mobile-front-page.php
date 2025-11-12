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
        <section class="relative isolate overflow-hidden" <?php if ( $hero_background ) : ?>style="background-image: url('<?php echo esc_url( $hero_background ); ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
            <div class="absolute inset-0 bg-gradient-to-b from-[#0d3c47] via-[#0d3c47] to-[#041f26] opacity-95"></div>
            <div class="relative z-10 mx-auto flex max-w-md flex-col items-center px-6 pt-10 pb-16 text-white sm:pb-20">
                <a href="#loft1325-mobile-home-search" class="inline-flex items-center justify-center rounded-full border border-white/40 bg-white/10 px-6 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-white shadow-lg backdrop-blur-sm transition hover:bg-white/20">
                    <?php echo esc_html( $plugin->get_string( 'hero_tagline' ) ); ?>
                </a>
                <h1 class="mt-6 text-center text-3xl font-semibold leading-tight sm:text-4xl">
                    <?php echo esc_html( $plugin->get_string( 'hero_title' ) ); ?>
                </h1>
                <p class="mt-4 text-center text-base leading-relaxed text-white/80">
                    <?php echo esc_html( $plugin->get_string( 'hero_description' ) ); ?>
                </p>

                <div id="loft1325-mobile-home-search" class="mt-8 w-full">
                    <div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-900/15">
                        <h2 class="sr-only"><?php echo esc_html( $plugin->get_string( 'search_card_title' ) ); ?></h2>
                        <form action="<?php echo esc_url( $rooms_archive ); ?>" method="get" class="space-y-5">
                            <div class="space-y-2">
                                <label for="loft1325-mobile-location" class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                                    <?php echo esc_html( $plugin->get_string( 'search_location_label' ) ); ?>
                                </label>
                                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 shadow-sm shadow-slate-900/10">
                                    <span class="text-[#0d3c47]" aria-hidden="true">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 12.75a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5z" />
                                            <path d="M18.75 10.5c0 4.5-6.75 10.5-6.75 10.5S5.25 15 5.25 10.5a6.75 6.75 0 1 1 13.5 0z" />
                                        </svg>
                                    </span>
                                    <input id="loft1325-mobile-location" name="location" type="text" value="<?php echo esc_attr__( 'Loft1325, Québec', 'loft1325-mobile-home' ); ?>" readonly class="w-full border-none bg-transparent text-base font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label for="loft1325-mobile-dates" class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                                    <?php echo esc_html( $plugin->get_string( 'search_date_label' ) ); ?>
                                </label>
                                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 shadow-sm shadow-slate-900/10">
                                    <span class="text-[#0d3c47]" aria-hidden="true">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M7.5 3v3m9-3v3M4.5 9h15" />
                                            <rect x="4.5" y="5.25" width="15" height="15.75" rx="2.25" />
                                            <path d="M9 13.5h.008M12 13.5h.008M15 13.5h.008M9 16.5h.008M12 16.5h.008M15 16.5h.008" />
                                        </svg>
                                    </span>
                                    <input id="loft1325-mobile-dates" name="dates" type="text" value="<?php echo esc_attr__( 'Sélectionner les dates', 'loft1325-mobile-home' ); ?>" readonly class="w-full border-none bg-transparent text-base font-medium text-slate-500 focus:outline-none focus:ring-0" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label for="loft1325-mobile-guests" class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                                    <?php echo esc_html( $plugin->get_string( 'search_guests_label' ) ); ?>
                                </label>
                                <div class="flex items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 shadow-sm shadow-slate-900/10">
                                    <span class="text-[#0d3c47]" aria-hidden="true">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" />
                                            <path d="M4.5 20.25a7.5 7.5 0 1 1 15 0" />
                                        </svg>
                                    </span>
                                    <input id="loft1325-mobile-guests" name="guests" type="text" value="<?php echo esc_attr__( '1 invité', 'loft1325-mobile-home' ); ?>" readonly class="w-full border-none bg-transparent text-base font-medium text-slate-900 focus:outline-none focus:ring-0" />
                                </div>
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-[#002b5b] py-3 text-center text-base font-semibold uppercase tracking-wide text-white shadow-lg transition hover:bg-[#01366f] focus:outline-none focus:ring-2 focus:ring-[#0d3c47] focus:ring-offset-2 focus:ring-offset-white">
                                <?php echo esc_html( $plugin->get_string( 'search_submit_label' ) ); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-12 flex w-full flex-wrap items-center justify-center gap-8 text-center text-xs font-medium text-slate-200">
                    <div class="flex flex-col items-center gap-2">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white shadow-lg">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 2.25h6A1.5 1.5 0 0 1 16.5 3.75v16.5a1.5 1.5 0 0 1-1.5 1.5H9a1.5 1.5 0 0 1-1.5-1.5V3.75A1.5 1.5 0 0 1 9 2.25z" />
                                <path d="M9 18.75h6" />
                            </svg>
                        </span>
                        <span><?php esc_html_e( 'Gestion mobile', 'loft1325-mobile-home' ); ?></span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white shadow-lg">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 6v6l3.5 3.5" />
                                <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                            </svg>
                        </span>
                        <span><?php esc_html_e( 'Check-in 24/7', 'loft1325-mobile-home' ); ?></span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white shadow-lg">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4.5 9.75h15" />
                                <path d="M8.25 4.5v3" />
                                <path d="M15.75 4.5v3" />
                                <path d="M6.75 12.75h3" />
                                <path d="M14.25 12.75h3" />
                                <path d="M4.5 7.5h15a1.5 1.5 0 0 1 1.5 1.5v9.75a1.5 1.5 0 0 1-1.5 1.5h-15a1.5 1.5 0 0 1-1.5-1.5V9a1.5 1.5 0 0 1 1.5-1.5z" />
                            </svg>
                        </span>
                        <span><?php esc_html_e( 'Séjour flexible', 'loft1325-mobile-home' ); ?></span>
                    </div>
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
