<?php
/**
 * Title: How we work
 * Slug: kriswa/home-process
 * Categories: kriswa
 *
 * @package Kriswa
 */

$steps = array(
	array( __( 'Free consultation', 'kriswa' ), __( 'We visit your plot, listen to your needs and discuss budget and timeline.', 'kriswa' ) ),
	array( __( 'Design and estimate', 'kriswa' ), __( 'Architectural drawings, 3D views and a detailed, itemised cost estimate.', 'kriswa' ) ),
	array( __( 'Construction', 'kriswa' ), __( 'Skilled crews, quality-checked materials and regular site updates to you.', 'kriswa' ) ),
	array( __( 'Handover and support', 'kriswa' ), __( 'Final inspection, a clean handover and after-sales support when you need it.', 'kriswa' ) ),
);
$ne    = kriswa_is_nepali_theme();

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'How we work', 'kriswa' ), __( 'A simple, transparent process', 'kriswa' ), __( 'Four clear steps from your first call to the day you move in.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:html -->
<ol class="kr-process">
<?php foreach ( $steps as $i => $st ) : ?>
	<li><span class="kr-process__n"><?php echo esc_html( $ne ? Kriswa_Nepali_Date::numerals( $i + 1 ) : $i + 1 ); ?></span><h3><?php echo esc_html( $st[0] ); ?></h3><p><?php echo esc_html( $st[1] ); ?></p></li>
<?php endforeach; ?>
</ol>
<!-- /wp:html -->
<?php echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
