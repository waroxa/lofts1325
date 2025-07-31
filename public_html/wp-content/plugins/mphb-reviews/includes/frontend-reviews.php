<?php

namespace MPHBR;

class FrontendReviews {

	private $commentType = 'mphbr_review';

	/**
	 * @var int
	 */
	private $minRating;
	/**
	 * @var int
	 */
	private $maxRating;

	public function __construct() {

		$this->minRating = MPHBR()->getSettings()->main()->getMinRating();
		$this->maxRating = MPHBR()->getSettings()->main()->getMaxRating();

		$this->addActions();
	}

	private function addActions() {

		// Change labels, add rating inputs
		$this->customizeCommentForm();

		// Handle Save
		$this->addSaveActions();

		// Display customer's rating in comment
		add_filter( 'comment_text', array( $this, 'showRatingInComment' ), 10, 2 );

		// Support avatars for `mphbr_review` comment type.
		add_filter( 'get_avatar_comment_types', array( $this, 'addReviewToAllowedAvatarsList' ) );

		// Comment css-class
		add_filter( 'comment_class', array( $this, 'filterCommentClasses' ), 10, 4 );

		// Remove reply link
		add_filter( 'comment_reply_link', array( $this, 'filterReplyLink' ), 10, 4 );

		// Show rating after gallery ( template mode = developer )
		add_action( 'mphb_render_single_room_type_content', array( $this, 'showRoomTypeRating' ), 25 );

		// Show listing of reviews (template mode = developer)
		add_action( 'mphb_render_single_room_type_after_content', array( $this, 'showCommentListing' ), 20 );

		// Show rating after gallery ( template mode = theme )
		add_action( 'mphbr_reviews_content', array( $this, 'showRoomTypeRating' ), 60 );

		// Override comments_template
		add_filter( 'comments_template', array( $this, 'comments_template' ) );

		// Add aggregate rating microdata
		add_filter( 'mphb_single_room_type_microdata', array( $this, 'setupRoomTypeMicrodata' ), 10, 3 );
	}

	public function setupRoomTypeMicrodata( $microdata, $roomTypeId, $roomType ) {

		if ( ! is_null( $roomType ) ) {

			$rating       = MPHBR()->getRatingManager()->getGlobalRating( $roomTypeId );
			$reviewsCount = MPHBR()->getRatingManager()->getGlobalRatingsCount( $roomTypeId );

			if ( $reviewsCount > 0 ) {
				
				$microdata['aggregateRating'] = array(
					'@type'       => 'AggregateRating',
					'ratingValue' => (float) number_format( $rating, 2, '.', '' ),
					'reviewCount' => (int) $reviewsCount,
				);

				if ( $this->minRating != 1 ) {
					$microdata['aggregateRating']['worstRating'] = (int) $this->minRating;
				}

				if ( $this->maxRating != 5 ) {
					$microdata['aggregateRating']['bestRating'] = (int) $this->maxRating;
				}
			}
		}

		return $microdata;
	}

	private function customizeCommentForm() {

		// Review form
		if ( is_user_logged_in() ) {
			add_action( 'comment_form_logged_in_after', array( $this, 'outputRatingPicker' ) );
		} else {
			add_action( 'comment_form_top', array( $this, 'outputRatingPicker' ) );
		}

		// Comment type and redirect URL
		add_filter( 'comment_form_field_comment', array( $this, 'addHiddenInputsToCommentForm' ) );
	}

	private function addSaveActions() {

		// Save
		add_action( 'comment_post', array( $this, 'saveRating' ), 1, 3 );

		// Set comment type.
		add_filter( 'preprocess_comment', array( $this, 'updateCommentType' ), 1 );
	}

	public function forceEnable() {

		add_filter( 'mphbr_is_reviews_enabled_for_post', '__return_true' );
	}

	public function forceCancel() {

		remove_filter( 'mphbr_is_reviews_enabled_for_post', '__return_true' );
	}

	/**
	 * Display Rating pickers
	 */
	public function outputRatingPicker() {

		// Bail if shouldn't show ratings for the current post
		if ( ! mphbr_is_reviews_enabled_for_post() ) {
			return;
		}

		?>
		<div class="mphbr-rating-wrapper">
			<?php
			foreach ( MPHBR()->getRatingTypeTaxonomy()->getAll( array( 'fields' => 'all' ) ) as $ratingType ) {
				$pickerId   = 'mphbr-rating-parameter-' . $ratingType->term_id;
				$pickerName = 'mphbr_rating[' . $ratingType->term_id . ']';
				mphbr_display_rating_picker( '', $pickerId, $pickerName, $ratingType->name );
			}
			?>
		</div>
		<?php
	}

	/**
	 * @param $html
	 * @return string
	 */
	public function addHiddenInputsToCommentForm( $html ) {

		// Bail if shouldn't show ratings for the current post
		if ( ! mphbr_is_reviews_enabled_for_post() ) {
			return $html;
		}

		ob_start();

		?>
		<input type="hidden" name="mphbr_comment_type" value="mphbr_review"/>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>" />
		<?php

		return ob_get_clean() . $html;
	}

	public function showCommentListing() {

		comments_template();
	}

	/**
	 * Allow avatars for comments with the `mphbr_review` type.
	 * @param  array $commentTypes
	 * @return array
	 */
	public function addReviewToAllowedAvatarsList( $commentTypes ) {

		return array_merge( $commentTypes, array( $this->commentType ) );
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
		$ratingValue = '';
		if ( $review->hasRatings() ) {
			$ratingValue = '<div class="mphbr-review-rating">' . mphbr_render_rating( $review->getAvgRating() ) . '</div>';
		}

		// Add comment excerpt
		$commentExcerpt = '';

		$lengthLimit = MPHBR()->getSettings()->main()->getReviewLimit();

		if ( $lengthLimit > 0 ) {
			$nohtmlText = wp_strip_all_tags( $commentText );

			if ( mb_strlen( $nohtmlText ) > $lengthLimit ) {
				$commentExcerpt = mb_substr( $nohtmlText, 0, $lengthLimit );
				// Remove last broken (trimmed) special character
				$commentExcerpt = preg_replace( '/&#?[a-z0-9]*$/i', '', $commentExcerpt );

				/* translators: What to append if text needs to be trimmed. Do not translate. */
				$more  = __( '&hellip;', 'mphb-reviews' );
				$more .= ' <a href="#" class="mphbr-expand-comment-text">' . esc_html__( 'Read more', 'mphb-reviews' ) . '</a>';

				$commentExcerpt = '<div class="mphbr-comment-excerpt">' . wpautop( $commentExcerpt . $more ) . '</div>';
			}
		}

		$commentStyles = ! empty( $commentExcerpt ) ? ' style="display: none;"' : '';
		$commentText   = '<div class="mphbr-comment-full-text"' . $commentStyles . '>' . wpautop( $commentText ) . '</div>';

		// Build review
		$ratingWrapper = '<div class="mphbr-review-wrapper">' . $ratingValue . $commentExcerpt . $commentText . '</div>';

		return $ratingWrapper;
	}

	/**
	 * @param int        $commentId        The comment ID.
	 * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
	 * @param array      $commentdata      Comment data.
	 */
	public function saveRating( $commentId, $comment_approved, $commentdata ) {

		$postId = $commentdata['comment_post_ID'];

		if ( ! mphbr_is_reviews_enabled_for_post( $postId ) ) {
			return;
		}

		$ratings     = array();
		$ratingTypes = MPHBR()->getRatingTypeTaxonomy()->getAll();

		if ( isset( $_POST['mphbr_rating'] ) && is_array( $_POST['mphbr_rating'] ) ) {

			foreach ( $_POST['mphbr_rating'] as $ratingId => $ratingValue ) {

				if ( ! in_array( $ratingId, $ratingTypes ) ) {
					continue;
				}

				if ( $ratingValue !== '' ) {
					$ratingValue = filter_var(
						$ratingValue,
						FILTER_VALIDATE_INT,
						array(
							'min_range' => $this->minRating,
							'max_range' => $this->maxRating,
						)
					);

					// Allow only integer in rating min/max diaposone or empty value
					if ( false === $ratingValue ) {
						continue;
					}
				}

				$ratings[ $ratingId ] = $ratingValue;
			}
		}

		$review = MPHBR()->getReviewRepository()->findById( $commentId );

		if ( ! $review ) {
			return;
		}

		$review->setRatings( $ratings );

		MPHBR()->getReviewRepository()->save( $review );
	}

	/**
	 * Update comment type of reviews.
	 *
	 * @param array $commentData Comment data.
	 *
	 * @return array
	 */
	public static function updateCommentType( $commentData ) {

		if ( isset( $_POST['comment_post_ID'], $_POST['mphbr_comment_type'] )
			 && ( ! isset( $commentData['comment_type'] ) || $commentData['comment_type'] === '' || $commentData['comment_type'] === 'comment' )
			 && $_POST['mphbr_comment_type'] === 'mphbr_review'
			 && MPHB()->postTypes()->roomType()->getPostType() === get_post_type( absint( $_POST['comment_post_ID'] ) )
		) {
			$commentData['comment_type'] = 'mphbr_review';
		}

		return $commentData;
	}

	public function showRoomTypeRating() {

		mphbr_show_accommodation_rating();
	}

	public function filterCommentClasses( $classes, $class, $comment_ID, $comment ) {

		if ( ! empty( $comment->comment_type ) && $comment->comment_type == $this->commentType ) {
			$classes[] = 'comment';
		}
		return $classes;
	}

	public function filterReplyLink( $link, $args, $comment, $post ) {

		if ( function_exists( 'MPHB' ) && mphbr_is_reviews_enabled_for_post( $post ) ) {
			return '';
		}
		return $link;
	}

	public function comments_template( $template ) {

		if ( function_exists( 'MPHB' ) && mphbr_is_reviews_enabled_for_post() ) {

			return apply_filters(
				'mphbr_reviews_template',
				MPHBR()->getPluginData()->getPluginPath( 'templates/reviews.php' )
			);
		}
		return $template;
	}
}
