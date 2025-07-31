<?php

namespace MPHBR;

class AdminReviews {

	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'addMetaBox' ) );
		add_filter( 'wp_update_comment_data', array( $this, 'saveMetaBoxes' ), 1, 3 );

		// Display customer's rating in comment
		add_filter( 'comment_text', array( $this, 'showRatingInComment' ), 10, 2 );
	}

	function addMetaBox() {

		add_meta_box(
			'mphb-reviews',
			esc_html__( 'Rating Types', 'mphb-reviews' ),
			array(
				$this,
				'showMetaBox',
			),
			'comment',
			'normal'
		);
	}

	/**
	 * @param array $data       The new, processed comment data.
	 * @param array $comment    The old, unslashed comment data.
	 * @param array $commentarr The new, raw comment data.
	 * @return array
	 */
	function saveMetaBoxes( $data, $comment, $commentarr ) {

		// Not allowed, return regular value without updating meta
		if ( ! isset( $_POST['mphbr_save_comment_meta_nonce'], $_POST['comment_ID'], $_POST['mphbr_rating'] ) ||
			 ! wp_verify_nonce( wp_unslash( $_POST['mphbr_save_comment_meta_nonce'] ), 'mphbr_save_comment_meta' ) ) {

			return $data;
		}

		$review = MPHBR()->getReviewRepository()->findById( $data['comment_ID'] );

		if ( is_array( $_POST['mphbr_rating'] ) ) {

			$ratings     = array();
			$ratingTypes = MPHBR()->getRatingTypeTaxonomy()->getAll();

			foreach ( $_POST['mphbr_rating'] as $ratingTypeId => $ratingValue ) {

				// Check is rating type exists
				if ( ! in_array( $ratingTypeId, $ratingTypes ) ) {
					continue;
				}

				if ( $ratingValue !== '' ) {

					$ratingValue = filter_var(
						$ratingValue,
						FILTER_VALIDATE_INT,
						array(
							'min_range' => MPHBR()->getSettings()->main()->getMinRating(),
							'max_range' => MPHBR()->getSettings()->main()->getMaxRating(),
						)
					);

					// Allow only integer in rating min/max diaposone or empty value
					if ( false === $ratingValue ) {
						continue;
					}
				}

				$ratings[ $ratingTypeId ] = $ratingValue;
			}
			$review->setRatings( $ratings );
		}

		// Update Review Meta
		MPHBR()->getReviewRepository()->save( $review );

		// Return regular value after updating
		return $data;
	}

	/**
	 * @param string      $commentText
	 * @param \WP_Comment $comment
	 * @return string
	 */
	public function showRatingInComment( $commentText, $comment ) {

		// Needs valid comment
		if ( is_null( $comment ) ) {
			return $commentText;
		}

		// bail if shouldn't show ratings for the current post
		if ( ! mphbr_is_reviews_enabled_for_post() ) {
			return $commentText;
		}

		$review = MPHBR()->getReviewRepository()->findById( $comment->comment_ID );

		if ( ! $review ) {
			return $commentText;
		}

		// Prepare Ratings HTML
		$ratingComment = '';

		if ( $review->hasRatings() ) {

			$ratingsCount = $review->getRatingsCount();

			$ratingComment .= mphbr_render_rating( $review->getAvgRating(), 0, esc_html__( 'Average Rating', 'mphb-reviews' ) );

			foreach ( $review->getRatings() as $ratingTermId => $rating ) {

				$ratingType     = get_term( $ratingTermId );
				$ratingComment .= mphbr_render_rating( $rating, 0, $ratingType->name );
			}

			$ratingComment = '<div class="mphbr-ratings-wrapper">' . $ratingComment . '</div>';
		}

		// Add ratings to comment text
		return $commentText . $ratingComment;
	}

	/**
	 * @param \WP_Comment $comment
	 */
	function showMetaBox( $comment ) {

		// Check is MPHB Review
		if ( ! is_a( $comment, '\WP_Comment' ) || $comment->comment_type !== 'mphbr_review' ) {
			return;
		}

		$review = MPHBR()->getReviewRepository()->findById( $comment->comment_ID );

		// Use nonce for verification
		wp_nonce_field( 'mphbr_save_comment_meta', 'mphbr_save_comment_meta_nonce' );

		$ratings = $review->getRatings();
		?>

		<fieldset>
			<table class="form-table ">
				<tbody>
				<tr>
					<td class="first">
						<label>
							<?php esc_html_e( 'Average Rating', 'mphb-reviews' ); ?>
						</label>
					</td>
					<td>
						<?php echo wp_kses_post( mphbr_render_rating( $review->getAvgRating() ) ); ?>
					</td>
				</tr>

				<?php
				$ratingTypes = MPHBR()->getRatingTypeTaxonomy()->getAll( array( 'fields' => 'all' ) );

				foreach ( $ratingTypes as $ratingType ) {
					$rating = isset( $ratings[ $ratingType->term_id ] ) ? $ratings[ $ratingType->term_id ] : '';
					?>
					<tr>
						<td class="first">
							<label for="<?php echo esc_attr( "mphbr-rating-parameter-{$ratingType->term_id}" ); ?>">
								<?php echo esc_html( $ratingType->name ); ?>
							</label>
						</td>
						<td>
							<?php
							$pickerId   = 'mphbr-rating-parameter-' . $ratingType->term_id;
							$pickerName = 'mphbr_rating[' . $ratingType->term_id . ']';
							mphbr_display_rating_picker( $rating, $pickerId, $pickerName );
							?>
						</td>
					</tr>
				<?php } ?>

				</tbody>
			</table>
		</fieldset>
		<?php
	}
}
