<?php
/**
 * Title: Get a quote page content
 * Slug: kriswa/page-quote
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Free quote', 'kriswa' ), __( 'Tell us about your project', 'kriswa' ), __( 'It takes about two minutes. No obligation, and we never share your details.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_quote_form' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:pattern {"slug":"kriswa/home-trust"} /-->
