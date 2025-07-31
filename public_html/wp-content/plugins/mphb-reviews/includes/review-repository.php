<?php

namespace MPHBR;

use MPHBR\Entities\Review;
use MPHB\Utils\ValidateUtils;

/**
 *
 * Class ReviewRepository
 * @package MPHBR
 */
class ReviewRepository {

	private $commentType = 'mphbr_review';

	/**
	 * @param array $args
	 *
	 * @return Review[]
	 */
	function findAll( $args = [] ) {

		$defaults = [
			'type'   => $this->commentType,
			'fields' => 'ids',
		];

		$comments = get_comments( array_merge( $defaults, $args ) );

		return array_map( [ $this, 'mapCommentToEntity' ], $comments );
	}

	/**
	 * @param \WP_Comment|int $comment
	 *
	 * @return Review
	 */
	function mapCommentToEntity( $comment ) {
		$commentId = is_a( $comment, '\WP_Comment' ) ? $comment->comment_ID : $comment;

		$ratings = [];
		foreach ( MPHBR()->getRatingTypeTaxonomy()->getAll() as $ratingTypeId ) {
			$ratingValue = (int) get_comment_meta( $commentId, sprintf( 'mphbr_rating_%d', $ratingTypeId ), true );
			if ( $ratingValue ) {
				$ratings[ $ratingTypeId ] = $ratingValue;
			}
		}

		$args = [
			'commentId' => (int) $commentId,
			'ratings'   => $ratings,
		];

		return new Review( $args );
	}

	/**
	 * @param $roomTypeId
	 *
	 * @return Review[]
	 */
	function findAllApprovedForRoomType( $roomTypeId ) {
		return $this->findAll( [
			'post_id' => $roomTypeId,
			'status'  => 'approve',
			'type'    => 'mphbr_review',
		] );
	}

	/**
	 * @param int $id
	 *
	 * @return Review|null
	 */
	function findById( $id ) {
		$comment = get_comment( $id );

		return ! is_null( $comment ) &&
		       $comment->comment_type === $this->commentType ? $this->mapCommentToEntity( $comment ) : null;
	}

	/**
	 * @param Review $review
	 */
	function save( $review ) {

		$ratings = $review->getRatings();

		foreach ( MPHBR()->getRatingTypeTaxonomy()->getAll() as $ratingId ) {
			if ( isset( $ratings[ $ratingId ] ) ) {
				update_comment_meta( $review->getCommentId(), sprintf( 'mphbr_rating_%d', $ratingId ), $ratings[ $ratingId ] );
			} else {
				delete_comment_meta( $review->getCommentId(), sprintf( 'mphbr_rating_%d', $ratingId ) );
			}
		}

		if ( apply_filters( 'mphbr_update_average_ratings_on_save_review', true ) ) {
			MPHBR()->getRatingManager()->updateRatings( $review->getComment()->comment_post_ID );
		}
	}

	/**
	 * @param int   $roomTypeId
	 * @param array $ratingTypes
	 *
	 * @return int
	 */
	function findRatingReviewsCount( $roomTypeId, $ratingTypes = [] ) {

		if ( empty( $ratingTypes ) ) {
			$ratingTypes = MPHBR()->getRatingTypeTaxonomy()->getAll();
		}

		if ( empty( $ratingTypes ) ) {
			return 0;
		}

		$ratingExistsMetaQuery = array_map( function ( $ratingTypeId ) {
			return [
				'key'     => sprintf( 'mphbr_rating_%d', $ratingTypeId ),
				'compare' => 'EXISTS',
			];
		}, $ratingTypes );

		return count( get_comments( [
//			'count'      => true, // Don't use count because wp comment query disable group by in this case
			'fields'     => 'ids',
			'type'       => $this->commentType,
			'status'     => 'approve',
			'post_id'    => $roomTypeId,
			'meta_query' => array_merge( [
				'relation' => 'OR',
			], $ratingExistsMetaQuery ),
		] ) );

	}

    /**
     * @param array  $args
     * @param int    $args['post_id']
     * @param int    $args['count']
     * @param int    $args['offset']
     * @param string $args['order']
     * @param bool $calculateAll Calculate the total amount of comments (to calc
     * the pages count).
     * @return array
     */
    public function getCommentsQueryArgs($args = [], $calculateAll = true)
    {
        // Display both reviews and default comments (type = "all") - admin can
        // leave a comment in the Dashboard
        $defaults = [
            'post_id'       => get_the_ID(),
            'type'          => 'mphbr_review',
            'status'        => 'approve',
            'no_found_rows' => !$calculateAll
        ];

        $queryArgs = array_merge($defaults, $args);

        if (isset($queryArgs['count'])) {
            if (!isset($queryArgs['number'])) {
                $queryArgs['number'] = $queryArgs['count'];
            }

            unset($queryArgs['count']);
        }

        if (!isset($queryArgs['number'])) {
            $usePages = ValidateUtils::validateBool(get_option('page_comments', ''));
            $queryArgs['number'] = $usePages ? absint(get_option('comments_per_page', 10)) : 10;
        }

        if (!isset($queryArgs['order'])) {
            $queryArgs['order'] = ValidateUtils::validateOrder(get_option('comment_order', 'desc'));
        }

        return $queryArgs;
    }

}