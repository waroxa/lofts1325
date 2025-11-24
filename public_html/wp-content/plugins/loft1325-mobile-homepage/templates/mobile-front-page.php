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
$language           = $plugin->get_current_language();

$arrival_label      = $plugin->localize_label( 'Arrivée', 'Arrival' );
$departure_label    = $plugin->localize_label( 'Départ', 'Departure' );
$date_placeholder   = $plugin->localize_label( 'Sélectionner les dates', 'Select dates' );
$guest_singular     = $plugin->localize_label( 'invité', 'guest' );
$guest_plural       = $plugin->localize_label( 'invités', 'guests' );
$night_singular     = $plugin->localize_label( 'nuit', 'night' );
$night_plural       = $plugin->localize_label( 'nuits', 'nights' );
$search_helper_text = $plugin->localize_label( 'Choisissez vos dates et le nombre d’invités pour trouver un loft disponible.', 'Choose your dates and number of guests to find an available loft.' );
$features_heading   = $plugin->localize_label( 'Avantages principaux', 'Key benefits' );
$empty_rooms_text   = $plugin->localize_label( 'Aucun loft n’est actuellement disponible. Ajoutez vos chambres ND Booking pour alimenter cette section.', 'No lofts are currently available. Add your ND Booking rooms to populate this section.' );
$price_label        = ( 'en' === $language ) ? 'From %1$s%2$s' : 'À partir de %1$s%2$s';
$rating_label       = $plugin->localize_label( 'Note %s sur 5', 'Rating %s out of 5' );
$per_night_label    = $plugin->localize_label( 'par nuit', 'per night' );

if ( ! $rooms_archive ) {
    $rooms_archive = home_url( '/' );
}

get_header();
?>

<style id="loft1325-mobile-home-inline-style">
body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__rooms,
body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta {
    background: linear-gradient(180deg, #f7f7f9 0%, #eef3f6 100%);
    padding: 2.75rem 1.5rem 3.25rem;
    color: #0b0b0b;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta {
    padding: 3rem 1.5rem 3.65rem;
    position: relative;
    overflow: hidden;
    isolation: isolate;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__section-heading,
body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-list,
body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta-inner,
body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-footer {
    max-width: 780px;
    margin: 0 auto;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__section-heading {
    display: grid;
    gap: 0.75rem;
    margin-bottom: 1.75rem;
    text-align: left;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__section-title {
    font-size: 1.35rem;
    margin: 0;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: 0.01em;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__section-description {
    font-size: 0.94rem;
    color: rgba(11, 11, 11, 0.7);
    margin: 0;
    line-height: 1.65;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-list {
    display: grid;
    gap: 1.5rem;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-card {
    background: #ffffff;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 32px rgba(15, 27, 45, 0.08);
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(118, 177, 196, 0.28);
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-media img {
    width: 100%;
    height: 210px;
    object-fit: cover;
    display: block;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-body {
    padding: 1.5rem;
    display: grid;
    gap: 1rem;
    color: #0b0b0b;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-badge {
    align-self: flex-start;
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
    background: rgba(118, 177, 196, 0.14);
    color: #0f172a;
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.02em;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-title {
    margin: 0;
    font-size: 1.12rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-title a {
    color: #0f172a;
    text-decoration: none;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-title a:hover,
body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-title a:focus {
    color: #0b1220;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-text {
    margin: 0;
    font-size: 0.92rem;
    line-height: 1.65;
    color: rgba(11, 11, 11, 0.7);
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-price {
    margin: 0;
    font-size: 1.08rem;
    font-weight: 700;
    color: #0f172a;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-price-unit {
    margin-left: 0.25rem;
    font-size: 0.82rem;
    color: rgba(11, 11, 11, 0.65);
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__room-footer {
    margin-top: 2.35rem;
    display: flex;
    justify-content: center;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta-inner {
    position: relative;
    z-index: 1;
    background-color: #ffffff;
    border-radius: 26px;
    padding: 2.4rem 1.65rem;
    display: grid;
    gap: 1.35rem;
    text-align: center;
    border: 1px solid rgba(118, 177, 196, 0.28);
    box-shadow: 0 22px 38px rgba(15, 27, 45, 0.08);
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta-title {
    margin: 0;
    font-size: clamp(1.45rem, 5.8vw, 1.85rem);
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: 0.01em;
    color: #0f172a;
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta-text {
    margin: 0;
    font-size: clamp(0.95rem, 4.2vw, 1.06rem);
    line-height: 1.7;
    color: rgba(11, 11, 11, 0.7);
}

body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta-actions {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    margin-top: 0.5rem;
    justify-content: center;
}

@media (max-width: 420px) {
    body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__search-card {
        padding: 1.65rem 1.45rem;
    }
}

@media (min-width: 600px) {
    body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta-actions {
        flex-direction: row;
        justify-content: center;
    }
}

@media (min-width: 768px) {
    body.loft1325-mobile-home-active #loft1325-mobile-homepage {
        max-width: 480px;
        margin: 0 auto;
    }

    body.loft1325-mobile-home-active .loft1325-mobile-home__wrapper {
        background: #f7f7f9;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__cta,
    body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__rooms,
    body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__features,
    body.loft1325-mobile-home-active #loft1325-mobile-homepage .loft1325-mobile-home__hero {
        max-width: 520px;
        width: 100%;
    }
}
</style>

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
                <div
                    class="loft1325-mobile-home__search-card"
                    id="loft1325-mobile-home-search"
                    data-date-label="<?php echo esc_attr( $plugin->get_string( 'search_date_label' ) ); ?>"
                    data-arrival-label="<?php echo esc_attr( $arrival_label ); ?>"
                    data-departure-label="<?php echo esc_attr( $departure_label ); ?>"
                    data-guests-label="<?php echo esc_attr( $plugin->get_string( 'search_guests_label' ) ); ?>"
                    data-submit-label="<?php echo esc_attr( $plugin->get_string( 'search_submit_label' ) ); ?>"
                    data-date-placeholder="<?php echo esc_attr( $date_placeholder ); ?>"
                    data-guests-singular="<?php echo esc_attr( $guest_singular ); ?>"
                    data-guests-plural="<?php echo esc_attr( $guest_plural ); ?>"
                    data-nights-singular="<?php echo esc_attr( $night_singular ); ?>"
                    data-nights-plural="<?php echo esc_attr( $night_plural ); ?>"
                >
                    <div class="loft-search-card loft-search-card--stacked">
                        <div class="loft-search-card__header">
                            <h2 class="loft1325-mobile-home__search-title"><?php echo esc_html( $plugin->get_string( 'search_card_title' ) ); ?></h2>
                            <p class="loft-search-card__subtext"><?php echo esc_html( $search_helper_text ); ?></p>
                        </div>
                        <div class="loft1325-mobile-home__search-form loft-search-card__body">
                            <?php echo $plugin->get_mobile_search_form_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                </div>

                <div class="loft1325-mobile-home__hero-content loft1325-mobile-home__hero-content--after-search">
                    <span class="loft1325-mobile-home__hero-pill"><?php echo esc_html( $plugin->get_string( 'hero_tagline' ) ); ?></span>
                    <h1 class="loft1325-mobile-home__hero-title"><?php echo esc_html( $plugin->get_string( 'hero_title' ) ); ?></h1>
                    <p class="loft1325-mobile-home__hero-text"><?php echo esc_html( $plugin->get_string( 'hero_description' ) ); ?></p>
                </div>

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
            <h2 id="loft1325-mobile-features-heading" class="screen-reader-text"><?php echo esc_html( $features_heading ); ?></h2>
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
                        <?php echo esc_html( $empty_rooms_text ); ?>
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
                                    <span class="loft1325-mobile-home__room-badge" aria-label="<?php echo esc_attr( sprintf( $rating_label, $room['rating'] ) ); ?>"><?php echo esc_html( $room['rating'] ); ?></span>
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
                                                $price_label,
                                                number_format_i18n( (float) $room['price'], 0 ),
                                                $currency_suffix
                                            )
                                        );
                                        ?>
                                        <span class="loft1325-mobile-home__room-price-unit"><?php echo esc_html( $per_night_label ); ?></span>
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

</div>

<?php
get_footer();
