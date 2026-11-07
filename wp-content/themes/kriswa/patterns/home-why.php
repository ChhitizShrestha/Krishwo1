<?php
/**
 * Title: Why choose us
 * Slug: kriswa/home-why
 * Categories: kriswa
 *
 * @package Kriswa
 */

$items = array(
	array( 'shield', __( 'Built to last', 'kriswa' ), __( 'Quality materials, earthquake-resilient design practices and careful site supervision on every project.', 'kriswa' ) ),
	array( 'clipboard', __( 'Clear, itemised pricing', 'kriswa' ), __( 'You get a detailed estimate before work starts, with no hidden costs or surprise additions.', 'kriswa' ) ),
	array( 'clock', __( 'On-time handover', 'kriswa' ), __( 'Realistic schedules, regular progress updates and a project manager you can reach any day.', 'kriswa' ) ),
	array( 'ruler', __( 'Design and build under one roof', 'kriswa' ), __( 'Architects, engineers and builders working together, so your plans and your site always match.', 'kriswa' ) ),
);

echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Why choose us', 'kriswa' ), __( 'A construction partner you can trust', 'kriswa' ), __( 'Building a home is one of the biggest decisions you will make. We make it clear, calm and dependable.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:html -->
<div class="kr-features">
<?php foreach ( $items as $it ) : ?>
	<div class="kr-feature">
		<span class="kr-feature__icon"><?php echo kriswa_i( $it[0], 28 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<h3><?php echo esc_html( $it[1] ); ?></h3>
		<p><?php echo esc_html( $it[2] ); ?></p>
	</div>
<?php endforeach; ?>
</div>
<!-- /wp:html -->
<?php echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
