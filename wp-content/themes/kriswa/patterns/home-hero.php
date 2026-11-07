<?php
/**
 * Title: Home hero
 * Slug: kriswa/home-hero
 * Categories: kriswa
 *
 * @package Kriswa
 */
?>
<!-- wp:group {"align":"full","className":"kr-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-hero"><!-- wp:group {"align":"wide","className":"kr-hero__in","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide kr-hero__in">
<!-- wp:paragraph {"className":"kr-hero__eyebrow"} --><p class="kr-hero__eyebrow"><?php esc_html_e( 'Kathmandu, Nepal  |  Residential, Interior and Architecture', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"kr-hero__title"} --><h1 class="wp-block-heading kr-hero__title"><?php esc_html_e( 'Homes built with care, designed to last for generations', 'kriswa' ); ?></h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"kr-hero__text"} --><p class="kr-hero__text"><?php esc_html_e( 'From first sketch to final handover, our engineers, architects and site teams deliver your house, renovation or interior with honest pricing and on-time results.', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<?php
echo kriswa_buttons( // phpcs:ignore WordPress.Security.EscapeOutput
	kriswa_btn( __( 'Get a Free Quote', 'kriswa' ), kriswa_quote_url(), '', 'kr-btn-light' ) .
	kriswa_btn( __( 'See our projects', 'kriswa' ), kriswa_projects_url(), 'outline', 'kr-btn-ghost-light' ),
	'kr-hero__btns'
);
?>
<!-- wp:html -->
<ul class="kr-hero__checks">
	<li><?php echo kriswa_i( 'check', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Registered and licensed', 'kriswa' ); ?></li>
	<li><?php echo kriswa_i( 'check', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Transparent, itemised pricing', 'kriswa' ); ?></li>
	<li><?php echo kriswa_i( 'check', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Quality materials, on-time handover', 'kriswa' ); ?></li>
</ul>
<!-- /wp:html -->
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
