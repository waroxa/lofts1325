<?php
/**
 * Custom search results template for Marina child theme.
 */

global $wp_query;

get_header();

$results_total = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
$search_query  = get_search_query();

$check_in  = filter_input( INPUT_GET, 'nd_booking_archive_form_date_range_from', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$check_out = filter_input( INPUT_GET, 'nd_booking_archive_form_date_range_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$guests    = filter_input( INPUT_GET, 'nd_booking_archive_form_guests', FILTER_SANITIZE_NUMBER_INT );

$check_in_time  = $check_in ? strtotime( $check_in ) : false;
$check_out_time = $check_out ? strtotime( $check_out ) : false;

$check_in_display  = $check_in_time ? date_i18n( get_option( 'date_format' ), $check_in_time ) : esc_html__( 'Add date', 'marina' );
$check_out_display = $check_out_time ? date_i18n( get_option( 'date_format' ), $check_out_time ) : esc_html__( 'Add date', 'marina' );
$guests_display    = $guests ? number_format_i18n( max( 1, (int) $guests ) ) : esc_html__( 'Add guests', 'marina' );

$results_copy = _n(
    /* translators: %s: number of search results. */
    'We located %s stay tailored to your search.',
    'We located %s stays tailored to your search.',
    $results_total,
    'marina'
);

$query_copy = $search_query
    ? sprintf(
        /* translators: %s: search query term. */
        esc_html__( 'Results for "%s"', 'marina' ),
        esc_html( $search_query )
    )
    : esc_html__( 'Available lofts', 'marina' );
?>

<section class="loft-search-page" aria-labelledby="loft-search-heading">
    <aside class="loft-search-sidebar" aria-label="<?php esc_attr_e( 'Search summary', 'marina' ); ?>">
        <div class="loft-search-sidebar__intro">
            <p class="loft-search-sidebar__eyebrow"><?php esc_html_e( 'Rooms & Rates', 'marina' ); ?></p>
            <h1 id="loft-search-heading" class="loft-search-sidebar__title"><?php echo $query_copy; ?></h1>
            <p class="loft-search-sidebar__meta">
                <?php
                printf(
                    esc_html( $results_copy ),
                    esc_html( number_format_i18n( $results_total ) )
                );
                ?>
            </p>
        </div>

        <div class="loft-search-stay-card" role="region" aria-label="<?php esc_attr_e( 'Stay overview', 'marina' ); ?>">
            <h2 class="loft-search-stay-card__title"><?php esc_html_e( 'Stay overview', 'marina' ); ?></h2>
            <ul class="loft-search-stay-card__details">
                <li>
                    <span><?php esc_html_e( 'Arrival', 'marina' ); ?></span>
                    <strong><?php echo esc_html( $check_in_display ); ?></strong>
                </li>
                <li>
                    <span><?php esc_html_e( 'Departure', 'marina' ); ?></span>
                    <strong><?php echo esc_html( $check_out_display ); ?></strong>
                </li>
                <li>
                    <span><?php esc_html_e( 'Guests', 'marina' ); ?></span>
                    <strong><?php echo esc_html( $guests_display ); ?></strong>
                </li>
            </ul>

            <div class="loft-search-sidebar__form">
                <?php get_search_form(); ?>
            </div>
            <p class="loft-search-sidebar__fine-print"><?php esc_html_e( 'Modify your dates or headcount to refresh the available lofts instantly.', 'marina' ); ?></p>
        </div>

        <div class="loft-search-sidebar__perks" role="list">
            <div class="loft-search-perk" role="listitem">
                <span aria-hidden="true">★</span>
                <p><?php esc_html_e( 'Independent travel network serving over 100,000 stays worldwide.', 'marina' ); ?></p>
            </div>
            <div class="loft-search-perk" role="listitem">
                <span aria-hidden="true">☕</span>
                <p><?php esc_html_e( 'Complimentary hot breakfast & artisan coffee at arrival.', 'marina' ); ?></p>
            </div>
            <div class="loft-search-perk" role="listitem">
                <span aria-hidden="true">🅿️</span>
                <p><?php esc_html_e( 'Secure covered parking included for every reservation.', 'marina' ); ?></p>
            </div>
        </div>
    </aside>

    <main class="loft-search-results" id="primary">
        <?php if ( have_posts() ) : ?>
            <div class="loft-room-list" role="list">
                <?php
                $loop_index = 0;

                while ( have_posts() ) :
                    the_post();
                    $loop_index++;

                    $raw_rate      = get_post_meta( get_the_ID(), 'nightly_rate', true );
                    $raw_currency  = get_post_meta( get_the_ID(), 'nightly_rate_currency', true );
                    $rate_display  = '';
                    $currency_code = $raw_currency ? strtoupper( sanitize_text_field( (string) $raw_currency ) ) : 'CAD';

                    if ( '' !== $raw_rate ) {
                        if ( is_numeric( $raw_rate ) ) {
                            $rate_display = sprintf(
                                '%s %s',
                                esc_html( $currency_code ),
                                esc_html( number_format_i18n( (float) $raw_rate ) )
                            );
                        } else {
                            $rate_display = esc_html( (string) $raw_rate );
                        }
                    }

                    $rate_display = $rate_display ? $rate_display : esc_html__( 'Contact us for rates', 'marina' );

                    $categories = get_the_terms( get_the_ID(), 'category' );
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'loft-room-card' ); ?> role="listitem">
                        <div class="loft-room-card__content">
                            <?php if ( 1 === $loop_index ) : ?>
                                <span class="loft-room-card__flag"><?php esc_html_e( 'Best value!', 'marina' ); ?></span>
                            <?php endif; ?>

                            <h2 class="loft-room-card__title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <div class="loft-room-card__description">
                                <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 32, '&hellip;' ) ); ?>
                            </div>

                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="loft-room-card__media">
                                    <?php the_post_thumbnail( 'medium_large' ); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                                <ul class="loft-room-card__perks" aria-label="<?php esc_attr_e( 'Highlights', 'marina' ); ?>">
                                    <?php foreach ( $categories as $category ) : ?>
                                        <li><?php echo esc_html( $category->name ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="loft-room-card__rate">
                            <div class="loft-room-card__rate-header">
                                <span class="loft-room-card__rate-title"><?php esc_html_e( 'Today\'s low rate', 'marina' ); ?></span>
                                <div class="loft-room-card__rate-amount">
                                    <strong><?php echo $rate_display; ?></strong>
                                    <small><?php esc_html_e( 'per night', 'marina' ); ?></small>
                                </div>
                            </div>
                            <ul class="loft-room-card__inclusions">
                                <li><?php esc_html_e( 'Free internet', 'marina' ); ?></li>
                                <li><?php esc_html_e( 'Hot breakfast', 'marina' ); ?></li>
                                <li><?php esc_html_e( 'Free parking', 'marina' ); ?></li>
                            </ul>
                            <a class="loft-room-card__cta" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Book now', 'marina' ); ?></a>
                        </div>
                    </article>
                    <?php
                endwhile;
                ?>
            </div>

            <nav class="loft-pagination" aria-label="<?php esc_attr_e( 'Search results pagination', 'marina' ); ?>">
                <?php
                the_posts_pagination(
                    array(
                        'prev_text' => esc_html__( 'Previous', 'marina' ),
                        'next_text' => esc_html__( 'Next', 'marina' ),
                    )
                );
                ?>
            </nav>
        <?php else : ?>
            <section class="loft-search-no-results" role="region" aria-label="<?php esc_attr_e( 'No search results', 'marina' ); ?>">
                <h2><?php esc_html_e( 'We could not find a match just yet', 'marina' ); ?></h2>
                <p><?php esc_html_e( 'Try adjusting your dates or exploring a different experience—we curate new lofts frequently.', 'marina' ); ?></p>
                <div class="loft-search-sidebar__form">
                    <?php get_search_form(); ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</section>

<?php
get_footer();
