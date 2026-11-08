<?php
/**
 * Title: Social page content
 * Slug: kriswa/page-social
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Instagram and TikTok', 'kriswa' ), __( 'Life on our construction sites', 'kriswa' ), __( 'Progress photos, finished homes and short videos from our teams. Tap any post to view it here.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo '<!-- wp:shortcode -->' . "\n" . '[kriswa_social_feed ig="24" tt="12"]' . "\n" . '<!-- /wp:shortcode -->'; // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
