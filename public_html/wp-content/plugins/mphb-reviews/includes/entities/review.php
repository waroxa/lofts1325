<?php

namespace MPHBR\Entities;

/**
 * Class Review
 * @package MPHBR\Entities
 */
class Review {
	/**
	 * @var int
	 */
	private $commentId = 0;
	/**
	 * @var array %ratingTypeId% => %ratingValue%
	 */
	private $ratings = [];
	/**
	 * @var float
	 */
	private $avgRating = 0.0;

	/***
	 * Review constructor.
	 *
	 * @param array $args      [
	 *
	 * @type int    $commentId (optional)
	 * @type array  $ratings   (optional) %ratingTypeId% => %ratingValue%
	 * @type float  $avgRating (optional)
	 * ]
	 *
	 */
	public function __construct( $args ) {

		if ( isset( $args['commentId'] ) ) {
			$this->commentId = $args['commentId'];
		}

		$this->ratings = isset( $args['ratings'] ) ? $args['ratings'] : [];

		if ( isset( $args['avgRating'] ) ) {
			$this->avgRating = $args['avgRating'];
		} else {
			$this->updateAverageRating();
		}

	}

	/**
	 * @return int
	 */
	public function getCommentId() {
		return $this->commentId;
	}

	/**
	 * @return array|null|\WP_Comment
	 */
	public function getComment() {
		return get_comment( $this->commentId );
	}

	/**
	 * @return array
	 */
	public function getRatings() {
		return $this->ratings;
	}

	/**
	 * @return int
	 */
    public function getRatingsCount() {
        if ( $this->hasRatings() ) {
            return count( $this->ratings );
        } else {
            return 0;
        }
    }

	/**
	 * @return bool
	 */
	public function hasRatings() {
		return ! empty( $this->ratings );
	}

	/**
	 * @return float
	 */
	public function getAvgRating() {
		return $this->avgRating;
	}

	/**
	 * @param array $ratings
	 */
	public function setRatings( $ratings ) {
		$this->ratings = $ratings;
		$this->updateAverageRating();
	}

	private function updateAverageRating(){
		$this->avgRating = ! empty( $this->ratings ) ? array_sum( $this->ratings ) / count( $this->ratings ) : 0.0;
	}

}