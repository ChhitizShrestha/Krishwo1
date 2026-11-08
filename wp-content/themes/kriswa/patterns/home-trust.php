<?php
/**
 * Title: Registration and trust badges
 * Slug: kriswa/home-trust
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open( '', 'kr-section--compact' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Registered and accountable', 'kriswa' ), __( 'Licensed, registered and fully accountable', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_trust' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
