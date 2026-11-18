<?php
/**
 * Title: Registration and trust badges
 * Slug: kriswa/home-trust
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open( '', 'kr-section--compact' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Registered and accountable', 'kriswa' ), __( 'Licensed, registered and fully accountable', 'kriswa' ), __( 'Our registrations and licences, so you can verify who you are working with.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_trust' ); // phpcs:ignore WordPress.Security.EscapeOutput
if ( kriswa_sec( 'credentials' ) ) {
	echo kriswa_buttons( kriswa_btn( __( 'See all credentials and our team', 'kriswa' ), kriswa_u( 'credentials' ), 'outline' ), 'kr-center-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput
}
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
