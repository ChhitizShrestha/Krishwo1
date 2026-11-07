<?php
/**
 * Title: Featured projects
 * Slug: kriswa/home-projects
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Our work', 'kriswa' ), __( 'Recent projects', 'kriswa' ), __( 'A selection of homes, interiors and renovations we have delivered across Kathmandu Valley and beyond.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:shortcode -->
[kriswa_projects count="6"]
<!-- /wp:shortcode -->
<?php
echo kriswa_buttons( kriswa_btn( __( 'View all projects', 'kriswa' ), kriswa_projects_url() ), 'kr-center-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
