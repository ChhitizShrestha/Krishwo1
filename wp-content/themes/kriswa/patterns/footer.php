<?php
/**
 * Title: Site footer
 * Slug: kriswa/footer
 * Inserter: no
 *
 * @package Kriswa
 */

$home  = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
$links = array(
	array( __( 'About us', 'kriswa' ), kriswa_u( 'about' ) ),
	array( __( 'Our services', 'kriswa' ), kriswa_u( 'services' ) ),
	array( __( 'Projects', 'kriswa' ), kriswa_projects_url() ),
	array( __( 'Blog', 'kriswa' ), kriswa_u( 'blog' ) ),
	array( __( 'FAQ', 'kriswa' ), kriswa_u( 'faq' ) ),
	array( __( 'Contact', 'kriswa' ), kriswa_u( 'contact' ) ),
);
$svc   = array(
	array( __( 'New house construction', 'kriswa' ), kriswa_quote_url( 'new-house' ) ),
	array( __( 'Renovation and extension', 'kriswa' ), kriswa_quote_url( 'renovation' ) ),
	array( __( 'Interior design', 'kriswa' ), kriswa_quote_url( 'interior' ) ),
	array( __( 'Architectural design', 'kriswa' ), kriswa_quote_url( 'architecture' ) ),
	array( __( 'Consultancy and supervision', 'kriswa' ), kriswa_quote_url( 'consultancy' ) ),
);
?>
<!-- wp:group {"align":"full","className":"kr-footer","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-footer"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:html -->
<div class="kr-footer__grid">
	<div class="kr-footer__col kr-footer__about">
		<a class="kr-brand kr-brand--light" href="<?php echo esc_url( $home ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.png' ) ); ?>" width="64" height="64" alt="" loading="lazy"><span class="kr-brand__name"><?php echo esc_html_x( 'Kriswa', 'brand short name', 'kriswa' ); ?><small><?php esc_html_e( 'Consultant and Construction Pvt. Ltd.', 'kriswa' ); ?></small></span></a>
		<p><?php echo esc_html( kriswa_o( 'tagline', 'Building trusted homes and spaces across Nepal' ) ); ?></p>
		<?php echo do_shortcode( '[kriswa_social]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="kr-footer__col">
		<h2 class="kr-footer__title"><?php esc_html_e( 'Quick links', 'kriswa' ); ?></h2>
		<ul><?php foreach ( $links as $l ) : ?><li><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a></li><?php endforeach; ?></ul>
	</div>
	<div class="kr-footer__col">
		<h2 class="kr-footer__title"><?php esc_html_e( 'What we do', 'kriswa' ); ?></h2>
		<ul><?php foreach ( $svc as $l ) : ?><li><a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a></li><?php endforeach; ?></ul>
	</div>
	<div class="kr-footer__col">
		<h2 class="kr-footer__title"><?php esc_html_e( 'Get in touch', 'kriswa' ); ?></h2>
		<p><?php echo esc_html( kriswa_o( 'address', 'Kathmandu, Nepal' ) ); ?></p>
		<p><a href="tel:+<?php echo esc_attr( ltrim( preg_replace( '/\D+/', '', kriswa_o( 'phone', '' ) ), '+' ) ); ?>"><?php echo esc_html( kriswa_o( 'phone', '+977-1-0000000' ) ); ?></a><br><a href="mailto:<?php echo esc_attr( antispambot( kriswa_o( 'email', 'info@example.com' ) ) ); ?>"><?php echo esc_html( kriswa_o( 'email', 'info@example.com' ) ); ?></a></p>
		<h3 class="kr-footer__title kr-footer__title--sm"><?php esc_html_e( 'Get project tips by email', 'kriswa' ); ?></h3>
		<?php echo do_shortcode( '[kriswa_newsletter]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</div>
<!-- /wp:html -->

<!-- wp:html -->
<div class="kr-footer__bottom">
	<p>&copy; <?php echo esc_html( kriswa_is_nepali_theme() ? Kriswa_Nepali_Date::numerals( gmdate( 'Y' ) ) : gmdate( 'Y' ) ); ?> <?php echo esc_html( kriswa_o( 'company_name', 'Kriswa Consultant and Construction Pvt. Ltd.' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'kriswa' ); ?></p>
	<p><?php esc_html_e( 'Registered in Nepal', 'kriswa' ); ?> &middot; <?php esc_html_e( 'PAN/VAT', 'kriswa' ); ?>: <?php echo esc_html( kriswa_o( 'pan_vat', '000000000' ) ); ?></p>
</div>
<!-- /wp:html --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
