<?php

namespace MPHBR\Widgets;

use MPHBR\Views\ReviewView;
use MPHB\Utils\ValidateUtils;
use MPHB\Widgets\BaseWidget;

class AccommodationReviewsWidget extends BaseWidget {

	public function __construct() {

		parent::__construct(
			'mphb_accommodation_reviews',
			esc_html__( 'Accommodation Reviews', 'mphb-reviews' ),
			array(
				'description' => esc_html__( 'Display Accommodation Reviews', 'mphb-reviews' ),
			)
		);
	}

	/**
	 * Backend widget form.
	 * @param array $values
	 * @see \WP_Widget::form()
	 */
	public function form( $values ) {

		$values = wp_parse_args(
			$values,
			array(
				'title'        => '',
				'id'           => '',
				'count'        => 3,
				'show_details' => 'yes',
				'class'        => '',
			)
		);

		extract( $values );

		if ( is_numeric( $id ) ) {
			$id = absint( $id );
		} else {
			$id = -1; // 0 = "Current Post"
		}

		if ( is_numeric( $count ) ) {
			$count = absint( $count );
		}

		$show_details = ValidateUtils::validateBool( $show_details );

		$accommodationTypes = MPHB()->getRoomTypeRepository()->getIdTitleList();

		?>
		<p>
			<label>
				<?php esc_html_e( 'Title', 'mphb-reviews' ); ?>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</label>
		</p>

		<p>
			<label>
				<?php esc_html_e( 'Accommodation Type', 'mphb-reviews' ); ?><br/>
				<select name="<?php echo esc_attr( $this->get_field_name( 'id' ) ); ?>">
					<option value="0" <?php selected( $id, 0 ); ?>>&ndash; <?php esc_html_e( 'Current Accommodation', 'mphb-reviews' ); ?> &ndash;</option>
					<?php foreach ( $accommodationTypes as $roomTypeId => $accommodationTitle ) { ?>
						<option value="<?php echo esc_attr( $roomTypeId ); ?>" <?php selected( $id, $roomTypeId ); ?>>
							<?php echo esc_html( $accommodationTitle ); ?>
						</option>
					<?php } ?>
				</select>
			</label>
		</p>

		<p>
			<label>
				<?php esc_html_e( 'Number of reviews to show', 'mphb-reviews' ); ?>
				<input class="small-text" type="number" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" value="<?php	echo esc_attr( $count ); ?>" min="0" step="1" />
			</label>
		</p>

		<p>
			<label>
				<?php esc_html_e( 'Show Rating Types', 'mphb-reviews' ); ?>
				<select name="<?php echo esc_attr( $this->get_field_name( 'show_details' ) ); ?>">
					<option value="yes" <?php selected( $show_details, true ); ?>><?php esc_html_e( 'Yes', 'mphb-reviews' ); ?></option>
					<option value="no" <?php selected( $show_details, false ); ?>><?php esc_html_e( 'No', 'mphb-reviews' ); ?></option>
				</select>
			</label>
		</p>

		<p>
			<label>
				<?php esc_html_e( 'CSS Class', 'mphb-reviews' ); ?>
				<input class="widefat" type="text" name="<?php echo esc_attr( $this->get_field_name( 'class' ) ); ?>" value="<?php echo esc_attr( $class ); ?>" />
			</label>
		</p>
		<?php
	}

	/**
	 * Frontend display of widget.
	 * @param array $args Widget arguments.
	 * @param array $values Saved values from database.
	 * @see \WP_Widget::widget()
	 */
	public function widget( $args, $values ) {

		// Validate values
		$roomTypeId  = (int) ValidateUtils::validateInt( $values['id'] ); // "" -> 0
		$count       = (int) ValidateUtils::validateInt( $values['count'] );
		$isAutoCount = $values['count'] === '';
		$showDetails = ValidateUtils::validateBool( $values['show_details'] );
		$class       = mphb_clean( $values['class'] );

		// Maybe get current post ID
		if ( $roomTypeId == 0 ) {
			if ( mphb_is_single_room_type_page() && ! is_null( MPHB()->getCurrentRoomType() ) ) {
				$roomTypeId = MPHB()->getCurrentRoomType()->getId();
			} else {
				return;
			}
		}

		// Query review comments
		$queryArgs = array( 'post_id' => $roomTypeId );

		if ( ! $isAutoCount ) {
			$queryArgs['count'] = $count;
		}

		$queryArgs = MPHBR()->getReviewRepository()->getCommentsQueryArgs( $queryArgs );
		$query     = new \WP_Comment_Query();
		$comments  = $query->query( $queryArgs );

		// Display the widget
		$widgetTitle = apply_filters( 'widget_title', $values['title'], $values, $this->id_base );

		$wrapperClass = apply_filters( 'mphb_widget_accommodation_reviews_class', 'mphb_widget_accommodation_reviews-wrapper comments-area mphb-reviews' );
		$wrapperClass = trim( $wrapperClass . ' ' . $class );

		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $widgetTitle ) ) {

			echo wp_kses_post( $args['before_title'] . esc_html( $widgetTitle ) . $args['after_title'] );
		}

		echo '<div class="' . esc_attr( $wrapperClass ) . '">';
			echo '<div class="mphbr-accommodation-rating">';
				ReviewView::displayAverageRating( $roomTypeId, false );

		if ( $showDetails ) {
			ReviewView::displayRatings( $roomTypeId );
		}
			echo '</div>';

		if ( $count > 0 || ( $isAutoCount && count( $comments ) > 0 ) ) {
			ReviewView::displayCommentsList( $comments );
		}
		echo '</div>';

		echo wp_kses_post( $args['after_widget'] );
	}

	public function update( $values, $oldValues ) {
        
		// Add parameters that does not exist
		foreach ( array( 'title', 'id', 'count', 'show_details', 'class' ) as $parameter ) {
			if ( ! isset( $values[ $parameter ] ) ) {
				$values[ $parameter ] = '';
			}
		}

		// Validate values
		$title        = mphb_clean( $values['title'] );
		$id           = is_numeric( $values['id'] ) ? $values['id'] : '';
		$count        = empty( $values['count'] ) || is_numeric( $values['count'] ) ? $values['count'] : '';
		$show_details = ValidateUtils::validateBool( $values['show_details'] ) ? 'yes' : 'no';
		$class        = mphb_clean( $values['class'] );

		return compact( 'title', 'id', 'count', 'show_details', 'class' );
	}
}
