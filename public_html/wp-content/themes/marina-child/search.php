<?php
/**
 * Custom search results template for Marina child theme.
 */

global $wp_query;

get_header();

$results_total = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
$search_query = get_search_query();
?>

<section class="loft-search-hero">
    <div class="loft-search-hero__inner">
        <p class="loft-search-hero__eyebrow"><?php esc_html_e( 'Curated suites & stays', 'marina' ); ?></p>
        <div class="loft-search-hero__rating">
            <span aria-hidden="true">★★★★★</span>
            <strong><?php esc_html_e( 'Five-star service promise', 'marina' ); ?></strong>
        </div>
        <h1 class="loft-search-hero__title"><?php esc_html_e( 'Find your perfect Loft 1325 escape', 'marina' ); ?></h1>
        <p class="loft-search-hero__description">
            <?php
            $description_template = _n(
                /* translators: %1$s: search results count, %2$s: search query */
                'Explore %1$s refined hideaway tailored to "%2$s". Adjust your dates or filters to reveal the suite that matches your itinerary.',
                'Explore %1$s refined hideaways tailored to "%2$s". Adjust your dates or filters to reveal the suite that matches your itinerary.',
                $results_total,
                'marina'
            );

            printf(
                esc_html( $description_template ),
                esc_html( number_format_i18n( $results_total ) ),
                esc_html( $search_query )
            );
            ?>
        </p>
        <div class="loft-search-hero__form">
            <?php get_search_form(); ?>
        </div>
    </div>
</section>

<div class="loft-search-toolbar">
    <div class="loft-search-toolbar__summary">
        <h2>
            <?php
            printf(
                /* translators: %1$s: search results count */
                esc_html__( 'Showing %1$s stay options', 'marina' ),
                number_format_i18n( $results_total )
            );
            ?>
        </h2>
        <span><?php esc_html_e( 'Handpicked suites with thoughtful amenities to elevate your arrival.', 'marina' ); ?></span>
    </div>
    <div class="loft-filter-pills" aria-label="<?php esc_attr_e( 'Popular refinements', 'marina' ); ?>">
        <button type="button" class="loft-filter-pill"><?php esc_html_e( 'Late checkout', 'marina' ); ?></button>
        <button type="button" class="loft-filter-pill"><?php esc_html_e( 'Private balconies', 'marina' ); ?></button>
        <button type="button" class="loft-filter-pill"><?php esc_html_e( 'Concierge access', 'marina' ); ?></button>
        <button type="button" class="loft-filter-pill"><?php esc_html_e( 'Gourmet kitchens', 'marina' ); ?></button>
    </div>
</div>

<main class="loft-search-results" id="primary">
    <?php if ( have_posts() ) : ?>
        <div class="loft-search-results-grid">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'loft-card' ); ?>>
                    <div class="loft-card__media">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large' ); ?>
                        <?php endif; ?>
                        <span class="loft-card__badge"><?php esc_html_e( 'Loft 1325', 'marina' ); ?></span>
                    </div>
                    <div class="loft-card__body">
                        <div class="loft-card__meta">
                            <span><?php echo esc_html( get_the_date() ); ?></span>
                            <span>&bull;</span>
                            <span>
                                <?php
                                printf(
                                    /* translators: %s: last modified date */
                                    esc_html__( 'Updated %s', 'marina' ),
                                    esc_html( get_the_modified_date() )
                                );
                                ?>
                            </span>
                        </div>

                        <h2 class="loft-card__title">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_title(); ?>
                            </a>
                        </h2>

                        <div class="loft-card__excerpt">
                            <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 32, '&hellip;' ) ); ?>
                        </div>

                        <?php
                        $categories = get_the_terms( get_the_ID(), 'category' );
                        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                            ?>
                            <div class="loft-card__amenities">
                                <?php
                                foreach ( $categories as $category ) :
                                    ?>
                                    <span><?php echo esc_html( $category->name ); ?></span>
                                    <?php
                                endforeach;
                                ?>
                            </div>
                        <?php endif; ?>

                        <a class="loft-card__cta" href="<?php the_permalink(); ?>">
                            <span>
                                <?php esc_html_e( 'View suite', 'marina' ); ?>
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z" />
                                </svg>
                            </span>
                        </a>
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
        <div class="loft-no-results">
            <h2><?php esc_html_e( 'We could not find a match just yet', 'marina' ); ?></h2>
            <p><?php esc_html_e( 'Try adjusting your dates or exploring a different experience—we curate new lofts frequently.', 'marina' ); ?></p>
            <div class="loft-search-hero__form">
                <?php get_search_form(); ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php
get_footer();
