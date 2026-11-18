<?php
/**
 * Title: Credentials and team page content
 * Slug: kriswa/page-credentials
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'Why trust us', 'kriswa' ), __( 'Registered, licensed and accountable', 'kriswa' ), __( 'Building and valuing property is a big responsibility. Here are our registrations, licences and the people behind the work.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_trust' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- wp:pattern {"slug":"kriswa/home-stats"} /-->
<?php
if ( kriswa_o( 'expertise', '' ) ) {
	echo kriswa_section_open( 'surface' ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo kriswa_head( __( 'Technical expertise', 'kriswa' ), __( 'What our engineers and architects do', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo kriswa_sc( 'kriswa_expertise' ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
}
if ( kriswa_has( 'kriswa_team' ) ) {
	echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
	echo kriswa_head( __( 'Our team', 'kriswa' ), __( 'Qualified people on every project', 'kriswa' ), __( 'Engineers, architects and valuers who plan, supervise and sign off our work.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo kriswa_sc( 'kriswa_team' ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
}
if ( kriswa_has( 'kriswa_testimonial' ) ) {
	echo '<!-- wp:pattern {"slug":"kriswa/home-testimonials"} /-->';
}
