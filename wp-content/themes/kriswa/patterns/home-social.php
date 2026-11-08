<?php
/**
 * Title: Instagram and TikTok feed
 * Slug: kriswa/home-social
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Follow our work', 'kriswa' ), __( 'See our sites, in photos and video', 'kriswa' ), __( 'Fresh updates from our teams on Instagram and TikTok. Tap any post to watch it right here.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo '<!-- wp:shortcode -->' . "\n" . '[kriswa_social_feed ig="6" tt="4"]' . "\n" . '<!-- /wp:shortcode -->'; // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
