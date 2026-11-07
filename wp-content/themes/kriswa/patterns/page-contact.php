<?php
/**
 * Title: Contact page content
 * Slug: kriswa/page-contact
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:columns {"className":"kr-split kr-split--top"} -->
<div class="wp-block-columns kr-split kr-split--top"><!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'Let us talk about your project', 'kriswa' ); ?></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php esc_html_e( 'Call, WhatsApp, email or visit us. We usually reply within one working day.', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<?php echo kriswa_sc( 'kriswa_contact' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><?php esc_html_e( 'Prefer to send details? Request a free quote', 'kriswa' ); ?></h3><!-- /wp:heading -->
<?php echo kriswa_sc( 'kriswa_quote_form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<?php echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php
echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Find us', 'kriswa' ), __( 'Our offices', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_offices' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo '<!-- wp:spacer {"height":"32px"} --><div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->';
echo kriswa_sc( 'kriswa_map' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
