<?php
/**
 * Title: Testimonials
 * Slug: kriswa/home-testimonials
 * Categories: kriswa
 *
 * @package Kriswa
 */

if ( ! kriswa_has( 'kriswa_testimonial' ) ) {
	return; // Nothing to show yet: skip the whole section.
}

echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Testimonials', 'kriswa' ), __( 'What our clients say', 'kriswa' ), __( 'Our best advertisement is a happy family living in a home we built.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_testimonials' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
