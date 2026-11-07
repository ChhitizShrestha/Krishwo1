<?php
/**
 * Title: Call to action
 * Slug: kriswa/cta
 * Categories: kriswa
 *
 * @package Kriswa
 */

$wa = function_exists( 'kriswa_whatsapp_url' ) ? kriswa_whatsapp_url() : '#';
?>
<!-- wp:group {"align":"full","className":"kr-cta","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-cta"><!-- wp:group {"align":"wide","className":"kr-cta__in","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide kr-cta__in">
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'Ready to start your project?', 'kriswa' ); ?></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php esc_html_e( 'Tell us about your plot and plans. We will get back to you quickly with honest advice and a free estimate.', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<?php
echo kriswa_buttons( // phpcs:ignore WordPress.Security.EscapeOutput
	kriswa_btn( __( 'Request a free quote', 'kriswa' ), kriswa_quote_url(), '', 'kr-btn-light' ) .
	kriswa_btn( __( 'Chat on WhatsApp', 'kriswa' ), $wa, 'outline', 'kr-btn-ghost-light' ),
	'kr-cta__btns'
);
?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
