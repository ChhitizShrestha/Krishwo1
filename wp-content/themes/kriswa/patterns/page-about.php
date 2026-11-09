<?php
/**
 * Title: About page content
 * Slug: kriswa/page-about
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:columns {"className":"kr-split"} -->
<div class="wp-block-columns kr-split"><!-- wp:column -->
<div class="wp-block-column">
<!-- wp:paragraph {"className":"kr-eyebrow"} --><p class="kr-eyebrow"><?php esc_html_e( 'Our story', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'A Kathmandu company that builds with honesty', 'kriswa' ); ?></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php esc_html_e( 'Kriswa Consultant and Construction Pvt. Ltd. is a Kathmandu-based team of engineers, architects and builders. We build and renovate homes, and we value land and buildings, so families and businesses can decide with confidence.', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p><?php esc_html_e( 'We believe construction should be transparent. You will always know what is being built, what it costs and when it will be ready. Replace this text with your own company history, founders and milestones.', 'kriswa' ); ?></p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"className":"kr-split__media"} -->
<div class="wp-block-column kr-split__media"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.png' ) ); ?>" alt="<?php esc_attr_e( 'Kriswa Consultant and Construction seal', 'kriswa' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php
$vals = array(
	array( 'ruler', __( 'Our mission', 'kriswa' ), __( 'To deliver safe, well-designed and affordable buildings through honest work and clear communication.', 'kriswa' ) ),
	array( 'building', __( 'Our vision', 'kriswa' ), __( 'To be the most trusted name in residential construction and interiors in Nepal.', 'kriswa' ) ),
	array( 'shield', __( 'Our values', 'kriswa' ), __( 'Integrity, safety, craftsmanship and respect for every client and every worker on our sites.', 'kriswa' ) ),
);
echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:html -->
<div class="kr-features kr-features--3">
<?php foreach ( $vals as $it ) : ?>
	<div class="kr-feature"><span class="kr-feature__icon"><?php echo kriswa_i( $it[0], 28 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php echo esc_html( $it[1] ); ?></h3><p><?php echo esc_html( $it[2] ); ?></p></div>
<?php endforeach; ?>
</div>
<!-- /wp:html -->
<?php echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

<!-- wp:pattern {"slug":"kriswa/home-stats"} /-->
<!-- wp:pattern {"slug":"kriswa/home-trust"} /-->
<!-- wp:pattern {"slug":"kriswa/home-process"} /-->
