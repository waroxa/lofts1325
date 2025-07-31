<?php
/**
 * This is the output for Local Business - Locations by Category on the frontend.
 *
 * @since 1.1.1
 * @version 1.1.1
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="aioseo-location-categories <?php echo esc_attr( $instance['class'] ); ?>">
	<?php foreach ( $categories as $category ) { ?>
		<li>
			<a href="<?php echo esc_attr( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
		</li>
	<?php } ?>
</ul>