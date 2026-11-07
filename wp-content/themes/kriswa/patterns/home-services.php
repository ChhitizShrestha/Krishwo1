<?php
/**
 * Title: Services grid
 * Slug: kriswa/home-services
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'What we do', 'kriswa' ), __( 'Complete construction and design services', 'kriswa' ), __( 'One trusted team for planning, designing, building and finishing your project.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_services' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
