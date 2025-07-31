<?php

namespace MPHBR\Views;

class ReviewView {

	public static function displayAverageRating( $roomTypeId, $showLeaveReviewButton = true ) {

		$averageRating = MPHBR()->getRatingManager()->getGlobalRating( $roomTypeId );
		$reviewsCount  = MPHBR()->getRatingManager()->getGlobalRatingsCount( $roomTypeId );

		?>
		<div class="mphbr-accommodation-rating-wrapper">
			<h3 class="mphbr-accommodation-rating-title">
				<?php echo esc_html( sprintf( _n( '%d Review', '%d Reviews', $reviewsCount, 'mphb-reviews' ), $reviewsCount ) ); ?>
			</h3>

			<div class="mphbr-accommodation-rating-value">
				<?php echo wp_kses_post( mphbr_render_rating( $averageRating, $reviewsCount ) ); ?>
			</div>

			<?php if ( $showLeaveReviewButton && mphbr_room_type_exists( $roomTypeId ) ) { ?>
				<button type="button" class="button mphbr-add-review"><?php esc_html_e( 'Write a review', 'mphb-reviews' ); ?></button>
			<?php } ?>
		</div>
		<?php
	}

	public static function displayRatings( $roomTypeId ) {

		$ratings = MPHBR()->getRatingManager()->getRatings( $roomTypeId );

		echo '<div class="mphbr-accommodation-rating-types">';

		foreach ( $ratings as $termId => $rating ) {
			$term = get_term( $termId );

			if ( is_null( $term ) || is_wp_error( $term ) ) {
				continue;
			}

			echo wp_kses_post( mphbr_render_rating( $rating['value'], $rating['count'], $term->name ) );
		}

		echo '</div>';
	}

	public static function displayForm( $roomTypeId ) {

		// Don't even try to show anything, if there is no such accommodation type
		if ( ! mphbr_room_type_exists( $roomTypeId ) ) {
			return;
		}

		MPHBR()->frontendReviews()->forceEnable();

		?>
		<div class="mphbr-new-review-box mphb-hide">
			<?php
			$commentsArgs = apply_filters(
				'mphbr_comment_form_args',
				array(
					'class_form'    => 'mphbr-review-form comment-form',
					'label_submit'  => esc_html__( 'Post review', 'mphb-reviews' ), // Change the title of send button
					'title_reply'   => sprintf( esc_html__( 'Review "%s"', 'mphb-reviews' ), get_the_title( $roomTypeId ) ), // Change the title of the reply section
					'comment_field' => '<p class="comment-form-comment"><label for="comment">' .
						esc_html__( 'Your review', 'mphb-reviews' ) .
						'</label><textarea name="comment" cols="45" rows="4" maxlength="65525" required="required"></textarea></p>',
				)
			);
			comment_form( $commentsArgs, $roomTypeId );
			?>
		</div>
		<?php

		MPHBR()->frontendReviews()->forceCancel();
	}

	public static function displayCommentsList( $comments, $columns = 1 ) {

		$columns       = max( 1, min( $columns, 6 ) ); // 1 <= $columns <= 6
		$columnClasses = $columns > 1 ? "mphbr-multicolumn-list mphbr-{$columns}-columns-list" : '';

		?>
		<ol class="comment-list mphbr-reviews-list <?php echo esc_attr( $columnClasses ); ?>">
			<?php static::displayComments( $comments ); ?>
		</ol><!-- .comment-list -->
		<?php
	}

	public static function displayMoreButton() {
		?>
		<p class="mphbr-load-more-wrapper">
			<button type="button" class="button mphbr-load-more"><?php esc_html_e( 'Load more reviews', 'mphb-reviews' ); ?></button>
			<span class="mphb-preloader mphb-hide"></span>
		</p>
		<?php
	}

	public static function displayComments( $comments ) {
        
		$commentsCount = count( $comments );

		$listArgs = apply_filters(
			'mphbr_list_comments_args',
			array(
				'style'             => 'ol',
				'short_ping'        => true,
				'avatar_size'       => 64,
				'per_page'          => $commentsCount,
				'reverse_top_level' => false,
			)
		);

		// Force 1 page for shortcodes and widgets - they don't support the
		// navigation
		$listArgs['per_page'] = $commentsCount;

		MPHBR()->frontendReviews()->forceEnable();

		wp_list_comments( $listArgs, $comments );

		MPHBR()->frontendReviews()->forceCancel();
	}
}
