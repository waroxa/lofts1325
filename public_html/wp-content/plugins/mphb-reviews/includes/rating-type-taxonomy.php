<?php

namespace MPHBR;

class RatingTypeTaxonomy {
	const TAX_NAME = 'mphbr_ratings';

	public function __construct() {
		add_action( 'init', [ $this, '_registerTaxonomy' ] );

		// Update Average Ratings after remove rating type term
		add_action( 'delete_term', [ $this, '_onDeleteTerm' ], 10, 5 );
	}

	public function _registerTaxonomy() {
		$roomTypePostType = MPHB()->postTypes()->roomType()->getPostType();

		register_taxonomy( self::TAX_NAME, $roomTypePostType, [
			'labels'            => array(
				'name'          => esc_html__( 'Rating Types', 'mphb-reviews' ),
				'singular_name' => esc_html__( 'Rating Type', 'mphb-reviews' ),
				'search_items'  => esc_html__( 'Search Rating Type', 'mphb-reviews' ),
				'all_items'     => esc_html__( 'All Rating Types', 'mphb-reviews' ),
				'edit_item'     => esc_html__( 'Edit Rating Type', 'mphb-reviews' ),
				'update_item'   => esc_html__( 'Update Rating Type', 'mphb-reviews' ),
				'add_new_item'  => esc_html__( 'Add New Rating Type', 'mphb-reviews' ),
				'new_item_name' => esc_html__( 'New Rating Type Name', 'mphb-reviews' ),
				'not_found'     => esc_html__( 'No rating types found.', 'mphb-reviews' ),
				'menu_name'     => esc_html__( 'Rating Types', 'mphb-reviews' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => MPHB()->menus()->getMainMenuSlug(),
			'meta_box_cb'       => false,
			'show_tagcloud'     => false,
			'show_admin_column' => true,
			'rewrite'           => false,
			'capabilities'		 => array(
				'manage_terms' => "manage_mphbr_ratings",
				'edit_terms' => "manage_mphbr_ratings",
				'delete_terms' => "manage_mphbr_ratings",
				'assign_terms' => "edit_mphbr_ratings"
			)
		] );

		register_taxonomy_for_object_type( self::TAX_NAME, $roomTypePostType );
	}

	/**
	 * @param int    $term          Term ID.
	 * @param int    $tt_id         Term taxonomy ID.
	 * @param string $taxonomy      Taxonomy slug.
	 * @param mixed  $deleted_term  Copy of the already-deleted term, in the form specified
	 *                              by the parent function. WP_Error otherwise.
	 * @param array  $object_ids    List of term object IDs.
	 */
	public function _onDeleteTerm( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {

		if ( $taxonomy !== RatingTypeTaxonomy::TAX_NAME ) {
			return;
		}

		$ratingMetaName      = sprintf( 'mphbr_rating_%d', $term );
		$countMetaName       = sprintf( 'mphbr_rating_%d_count', $term );
		$reviewRatingMetaKey = sprintf( 'mphbr_rating_%d', $term );

		$affectedRoomTypes = MPHB()->getRoomTypeRepository()->findAll( [
			'post_status' => [
				'any',
				'trash',
			],
			'meta_query' => [
				'relation' => 'OR',
				[
					'key'     => $ratingMetaName,
					'compare' => 'EXISTS',
				],
				[
					'key'     => $countMetaName,
					'compare' => 'EXISTS',
				],
			],
		] );

		$affectedReviews = get_comments( [
			'meta_query' => [
				[
					'key'     => $reviewRatingMetaKey,
					'compare' => 'EXISTS',
				]
			],
			'fields' => 'ids',
			'status' => ['all', 'trash']
		] );

		// Prevent update average ratings after each deleting
		add_filter( 'mphbr_update_average_ratings_on_save_review', '__return_false' );

		// Delete Rating Comment Meta for deleted rating type
		foreach ( $affectedReviews as $commentId ) {
			delete_comment_meta( $commentId, $reviewRatingMetaKey );
		}

		// Return to normal functionality
		remove_filter( 'mphbr_update_average_ratings_on_save_review', '__return_false' );

		// Update Global Rating
		foreach ( $affectedRoomTypes as $roomType ) {
			$roomTypeId = $roomType->getId();

			// Delete Average Ratings meta for deleted rating type
			delete_post_meta( $roomTypeId, $ratingMetaName );
			delete_post_meta( $roomTypeId, $countMetaName );

			MPHBR()->getRatingManager()->updateGlobalRating( $roomType->getId() );
		}

	}

	/**
	 * @param $args @see get_terms
	 *
	 * @return \WP_Term[]|int[]
	 */
	public function getAll( $args = [] ) {
		$terms = get_terms( array_merge( [
			'taxonomy'   => RatingTypeTaxonomy::TAX_NAME,
			'hide_empty' => false,
			'fields'     => 'ids',
		], $args ) );

		return ! is_wp_error( $terms ) ? $terms : [];
	}
}