<?php
/**
 * Title: 404 content
 * Slug: kriswa/not-found
 * Inserter: no
 *
 * @package Kriswa
 */

echo kriswa_section_open( '', 'kr-center' ); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Page not found', 'kriswa' ); ?></h1><!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><?php esc_html_e( 'Sorry, we could not find that page. Try a search, or go back to the home page.', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<!-- wp:search {"label":"<?php echo esc_attr( __( 'Search', 'kriswa' ) ); ?>","showLabel":false,"buttonText":"<?php echo esc_attr( __( 'Search', 'kriswa' ) ); ?>","align":"center"} /-->
<?php
echo kriswa_buttons( kriswa_btn( __( 'Back to home', 'kriswa' ), home_url( '/' ) ), 'kr-center-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
