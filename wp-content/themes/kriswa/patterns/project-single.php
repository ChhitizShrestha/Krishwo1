<?php
/**
 * Title: Project single layout
 * Slug: kriswa/project-single
 * Inserter: no
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:post-featured-image {"aspectRatio":"16/9","className":"kr-project-hero"} /-->
<!-- wp:columns {"className":"kr-split kr-split--top kr-split--wide"} -->
<div class="wp-block-columns kr-split kr-split--top kr-split--wide"><!-- wp:column {"width":"66%"} -->
<div class="wp-block-column" style="flex-basis:66%"><!-- wp:post-content {"layout":{"type":"default"}} /--></div>
<!-- /wp:column -->
<!-- wp:column {"width":"34%","className":"kr-sidecard"} -->
<div class="wp-block-column kr-sidecard" style="flex-basis:34%">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><?php esc_html_e( 'Project details', 'kriswa' ); ?></h3><!-- /wp:heading -->
<?php echo kriswa_sc( 'kriswa_project_facts' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><?php esc_html_e( 'Planning something similar?', 'kriswa' ); ?></h3><!-- /wp:heading -->
<?php echo kriswa_buttons( kriswa_btn( __( 'Get a free quote', 'kriswa' ), kriswa_quote_url() ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- wp:pattern {"slug":"kriswa/cta"} /-->
