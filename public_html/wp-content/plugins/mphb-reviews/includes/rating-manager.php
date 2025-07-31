<?php

namespace MPHBR;

class RatingManager {

	public function __construct() {
		add_action( 'transition_comment_status', [ $this, 'onTransitionCommentStatus' ], 10, 3 );
	}

	/**
	 * @param string      $new_status
	 * @param string      $old_status
	 * @param \WP_Comment $comment
	 */
	function onTransitionCommentStatus( $new_status, $old_status, $comment ) {

		if ( $comment->comment_type !== 'mphbr_review' ) {
			return;
		}

		// Update rating when comment status change from approved or to approved
		if ( $new_status === 'approved' || $old_status === 'approved' ) {
			MPHBR()->getRatingManager()->updateRatings( $comment->comment_post_ID );
		}

	}

	/**
	 * @param int $roomTypeId
	 *
	 */
	public function updateRatings( $roomTypeId ) {

		$reviews = MPHBR()->getReviewRepository()->findAllApprovedForRoomType( $roomTypeId );

		$ratings = array_map( function ( $review ) {
			return $review->getRatings();
		}, $reviews );

		$counts         = [];
		$averageRatings = [];
		foreach ( MPHBR()->getRatingTypeTaxonomy()->getAll() as $ratingTypeId ) {

			$allRatingsByType = array_filter( array_column( $ratings, $ratingTypeId ) );

			// Collect only exists ratings
			if ( ! empty( $allRatingsByType ) ) {
				$ratingsCount                    = count( $allRatingsByType );
				$averageRatings[ $ratingTypeId ] = (float) array_sum( $allRatingsByType ) / $ratingsCount;
				$counts[ $ratingTypeId ]         = $ratingsCount;
			} else {
				$averageRatings[ $ratingTypeId ] = 0.0;
				$counts[ $ratingTypeId ]         = 0;
			}
		}

		foreach ( $averageRatings as $avgRatingTypeId => $avgRating ) {
			update_post_meta( $roomTypeId, sprintf( 'mphbr_rating_%d', $avgRatingTypeId ), $avgRating );
			update_post_meta( $roomTypeId, sprintf( 'mphbr_rating_%d_count', $avgRatingTypeId ), $counts[ $avgRatingTypeId ] );
		}

		$this->updateGlobalRating( $roomTypeId );
	}

	function updateGlobalRating( $roomTypeId ) {
		$ratings      = $this->getRatings( $roomTypeId );
		$globalRating = ! empty( $ratings ) ? array_sum( array_column( $ratings, 'value' ) ) / count( $ratings ) : 0.0;

		$globalCount = MPHBR()->getReviewRepository()->findRatingReviewsCount( $roomTypeId );

		update_post_meta( $roomTypeId, 'mphbr_avg_rating', $globalRating );
		update_post_meta( $roomTypeId, 'mphbr_avg_ratings_count', $globalCount );
	}

	/**
	 *
	 * @param int $roomTypeId
	 *
	 * @return array %ratingTypeId% => [ value => %ratingValue%, count => %ratingsCount% ]
	 *
	 */
	function getRatings( $roomTypeId ) {

		$ratings = [];
		foreach ( MPHBR()->getRatingTypeTaxonomy()->getAll() as $ratingTypeId ) {
			$ratingValue = (float) get_post_meta( $roomTypeId, sprintf( 'mphbr_rating_%d', $ratingTypeId ), true );
			$ratingCount = (int) get_post_meta( $roomTypeId, sprintf( 'mphbr_rating_%d_count', $ratingTypeId ), true );
			if ( $ratingValue && $ratingCount ) {
				$ratings[ $ratingTypeId ]['value'] = $ratingValue;
				$ratings[ $ratingTypeId ]['count'] = $ratingCount;
			}
		}

		return $ratings;
	}

	/**
	 * @param int $roomTypeId
	 *
	 * @return float
	 */
	function getGlobalRating( $roomTypeId ) {
		return (float) get_post_meta( $roomTypeId, 'mphbr_avg_rating', true );
	}

	/**
	 * @param $roomTypeId
	 *
	 * @return int
	 */
	function getGlobalRatingsCount( $roomTypeId ) {
		return (int) get_post_meta( $roomTypeId, 'mphbr_avg_ratings_count', true );
	}
}