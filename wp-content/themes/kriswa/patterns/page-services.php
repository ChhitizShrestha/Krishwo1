<?php
/**
 * Title: Services page content
 * Slug: kriswa/page-services
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Our services', 'kriswa' ), __( 'Everything you need to build, renovate and furnish', 'kriswa' ), __( 'Choose a service to learn more, or request a free quote for a custom package.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo '<!-- wp:shortcode -->' . "\n" . '[kriswa_services count="12"]' . "\n" . '<!-- /wp:shortcode -->'; // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:pattern {"slug":"kriswa/home-why"} /-->
<!-- wp:pattern {"slug":"kriswa/home-process"} /-->
