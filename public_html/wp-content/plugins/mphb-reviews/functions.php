<?php

/**
 * @param string $version  version to compare with wp version
 * @param string $operator Optional. Possible operators are: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively. Default =.
 *                         This parameter is case-sensitive, values should be lowercase.
 * @return bool
 */
function mphbr_is_wp_version( $version, $operator = '=' ) {

	global $wp_version;

	return version_compare( $wp_version, $version, $operator );
}

/**
 * Check is plugin active.
 * @param string $pluginSubDirSlashFile
 *
 * @return bool
 */
function mphbr_is_plugin_active( $pluginSubDirSlashFile ) {

	if ( ! function_exists( 'is_plugin_active' ) ) {
		/**
		 * Detect plugin. For use on Front End only.
		 */
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( $pluginSubDirSlashFile );
}

function mphbr_match( $pattern, $subject, $default = '', $index = 0 ) {

	preg_match( $pattern, $subject, $matches );
	return isset( $matches[ $index ] ) ? $matches[ $index ] : $default;
}

/**
 * @return \MPHBR\Plugin
 */
function MPHBR() {

	return \MPHBR\Plugin::getInstance();
}

/**
 * @param int|null $postId
 *
 * @return bool
 */
function mphbr_is_reviews_enabled_for_post( $postId = null ) {

	if ( empty( $postId ) ) {
		$postId = get_the_ID();
	}

	$isEnabled = get_post_type( $postId ) === MPHB()->postTypes()->roomType()->getPostType();
	$isEnabled = apply_filters( 'mphbr_is_reviews_enabled_for_post', $isEnabled, $postId );

	return $isEnabled;
}

function mphbr_room_type_exists( $accommodationId ) {

	// Repository will cache the result and do one query to DB with multiple
	// calls of mphbr_room_type_exists()
	$roomType = MPHB()->getRoomTypeRepository()->findById( $accommodationId );

	return ! is_null( $roomType );
}

/**
 * @param int|null $roomTypeId Optional. Current post by default. Default value
 * is null.
 * @param bool     $echo Optional. Echo or return the result HTML. True by default.
 *
 * @return string An empty string if $echo is true, or there is no rating for
 * specified accommodation.
 */
function mphbr_show_accommodation_rating( $roomTypeId = null, $echo = true ) {

	$currentPostId = get_the_ID(); // int|false

	if ( empty( $roomTypeId ) ) {
		if ( mphbr_is_reviews_enabled_for_post() ) {
			$roomTypeId = $currentPostId;
		} else {
			// Bail if shouldn't show ratings for the current post
			return '';
		}
	}

	$ratings = MPHBR()->getRatingManager()->getRatings( $roomTypeId );

	$globalRating       = MPHBR()->getRatingManager()->getGlobalRating( $roomTypeId );
	$globalRatingsCount = MPHBR()->getRatingManager()->getGlobalRatingsCount( $roomTypeId );

	ob_start();
	?>
	<div class="mphbr-accommodation-rating">
		<div class="mphbr-accommodation-rating-wrapper">
			<h2 class="mphbr-accommodation-rating-title"><?php echo esc_html( sprintf( _n( '%d Review', '%d Reviews', $globalRatingsCount, 'mphb-reviews' ), $globalRatingsCount ) ); ?></h2>
			<div class="mphbr-accommodation-rating-value"><?php echo wp_kses_post( mphbr_render_rating( $globalRating, $globalRatingsCount ) ); ?></div>
			<?php if ( $roomTypeId === $currentPostId && comments_open( $roomTypeId ) ) { ?>
				<a class="button mphbr-add-review" href="#respond"><?php esc_html_e( 'Write a review', 'mphb-reviews' ); ?></a>
			<?php } ?>
		</div>

		<div class="mphbr-accommodation-rating-types">
		<?php
		foreach ( $ratings as $ratingTypeId => $ratingDetails ) {
			$ratingTypeTerm = get_term( $ratingTypeId );
			if ( is_null( $ratingTypeTerm ) || is_wp_error( $ratingTypeTerm ) ) {
				continue;
			}

			echo wp_kses_post( mphbr_render_rating( $ratingDetails['value'], $ratingDetails['count'], $ratingTypeTerm->name ) );
		}
		?>
		</div>
	</div>
	<?php
	$rating = ob_get_clean();

	if ( $echo ) {
		echo wp_kses_post( $rating );
		return '';
	} else {
		return $rating;
	}
}

/**
 * @param float  $rating
 * @param int    $count Optional. Reviews count. 0 by default.
 * @param string $title Optional. Rating title or name of the rating type.
 *                      Empty string by default.
 * @return string
 */
function mphbr_render_rating( $rating, $count = 0, $title = '' ) {

	$minRating = MPHBR()->getSettings()->main()->getMinRating();
	$maxRating = MPHBR()->getSettings()->main()->getMaxRating();

	$ratingPercentage = $rating / $maxRating * 100;
	$formattedRating  = floatval( number_format( $rating, 2, '.', '' ) );

	if ( $count > 0 ) {
		$ratingText = _n( 'Rated %1$s out of %2$s based on %3$s review.', 'Rated %1$s out of %2$s based on %3$s reviews.', $count, 'mphb-reviews' );
	} else {
		$ratingText = esc_html__( 'Rated %1$s out of %2$s.', 'mphb-reviews' );
	}

	ob_start();

	?>
	<div class="mphbr-rating-type" title="<?php echo esc_html( sprintf( $ratingText, $formattedRating, $maxRating, $count ) ); ?>">
		<span class="dashicons dashicons-star-filled mphbr-star-rating">
			<span style="<?php echo esc_attr( "width: {$ratingPercentage}%;" ); ?>">
				<?php
				printf(
					esc_html( $ratingText ),
					'<strong class="mphbr-rating">' . esc_html( $formattedRating ) . '</strong>',
					'<span class="mphbr-max-rating">' . esc_html( $maxRating ) . '</span>',
					'<span class="mphbr-rating-count">' . esc_html( $count ) . '</span>'
				);
				?>
			</span>
		</span>
		<?php if ( ! empty( $title ) ) { ?>
			<span class="mphbr-rating-type-title"><?php echo esc_html( $title ); ?></span>
		<?php } ?>
	</div>
	<?php

	$ratingHtml = ob_get_clean();

	/**
	 * @param string    $ratingHtml
	 * @param float|int $rating Rating value
	 * @param int       $count  Used by average ratings. Number of ratings based on. 0 for non-average ratings.
	 * @param string    $title  Name of rating type
	 */
	return apply_filters( 'mphbr_render_rating', $ratingHtml, $rating, $count, $title );
}

/**
 * @param string $value
 * @param        $id
 * @param        $name
 * @param string $title
 */
function mphbr_display_rating_picker( $value, $id, $name, $title = '' ) {

	$minRating = MPHBR()->getSettings()->main()->getMinRating();
	$maxRating = MPHBR()->getSettings()->main()->getMaxRating();
	?>
	<div class="mphbr-rating-parameter-wrapper">
		<select id="<?php echo esc_attr( $id ); ?>" class="mphb-rating-picker" name="<?php echo esc_attr( $name ); ?>" required="required">
			<option value=""><?php esc_html_e( 'Rate', 'mphb-reviews' ); ?></option>
			<?php foreach ( array_reverse( range( $minRating, $maxRating, 1 ) ) as $index ) { ?>
				<option <?php selected( $value, $index ); ?> value="<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $index ); ?></option>
			<?php } ?>
		</select>
		<?php if ( ! empty( $title ) ) { ?>
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></label>
		<?php } ?>
	</div>
	<?php
}

function mphbr_is_edit_page() {

	global $pagenow;
	return is_admin() && in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
}
