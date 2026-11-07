<?php
/**
 * Title: Projects archive
 * Slug: kriswa/projects-archive
 * Inserter: no
 *
 * @package Kriswa
 */
?>
<!-- wp:group {"align":"full","className":"kr-banner","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-banner"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"level":1,"className":"kr-banner__title"} --><h1 class="wp-block-heading kr-banner__title"><?php esc_html_e( 'Our projects', 'kriswa' ); ?></h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"kr-banner__sub"} --><p class="kr-banner__sub"><?php esc_html_e( 'Homes, interiors and renovations delivered with care. Filter by type to explore.', 'kriswa' ); ?></p><!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<?php
echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
echo '<!-- wp:shortcode -->' . "\n" . '[kriswa_projects count="60" filter="1"]' . "\n" . '<!-- /wp:shortcode -->'; // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
