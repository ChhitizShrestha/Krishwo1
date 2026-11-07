<?php
/**
 * Title: Site header
 * Slug: kriswa/header
 * Inserter: no
 *
 * @package Kriswa
 */

$home  = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
$nav   = array(
	array( __( 'Home', 'kriswa' ), $home ),
	array( __( 'About', 'kriswa' ), kriswa_u( 'about' ) ),
	array( __( 'Services', 'kriswa' ), kriswa_u( 'services' ) ),
	array( __( 'Projects', 'kriswa' ), kriswa_projects_url() ),
	array( __( 'Blog', 'kriswa' ), kriswa_u( 'blog' ) ),
	array( __( 'FAQ', 'kriswa' ), kriswa_u( 'faq' ) ),
	array( __( 'Contact', 'kriswa' ), kriswa_u( 'contact' ) ),
);
$phone = kriswa_o( 'phone', '+977-1-0000000' );
$mail  = kriswa_o( 'email', 'info@example.com' );
$hours = kriswa_o( 'hours', '' );
?>
<!-- wp:group {"align":"full","className":"kr-header","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull kr-header">
<!-- wp:group {"align":"full","className":"kr-topbar","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-topbar"><!-- wp:group {"align":"wide","className":"kr-topbar__in","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide kr-topbar__in"><!-- wp:html -->
<ul class="kr-topbar__list">
<li><?php echo kriswa_i( 'phone', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="tel:+<?php echo esc_attr( ltrim( preg_replace( '/\D+/', '', $phone ), '+' ) ); ?>"><?php echo esc_html( $phone ); ?></a></li>
<li class="kr-hide-sm"><?php echo kriswa_i( 'mail', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="mailto:<?php echo esc_attr( antispambot( $mail ) ); ?>"><?php echo esc_html( $mail ); ?></a></li>
<?php if ( $hours ) : ?><li class="kr-hide-md"><?php echo kriswa_i( 'clock', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $hours ); ?></span></li><?php endif; ?>
</ul>
<!-- /wp:html -->
<?php echo kriswa_sc( 'kriswa_lang_switcher' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"kr-mainbar","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-mainbar"><!-- wp:group {"align":"wide","className":"kr-mainbar__in","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide kr-mainbar__in"><!-- wp:html -->
<a class="kr-brand" href="<?php echo esc_url( $home ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.png' ) ); ?>" width="56" height="56" alt=""><span class="kr-brand__name"><?php echo esc_html_x( 'Kriswa', 'brand short name', 'kriswa' ); ?><small><?php esc_html_e( 'Consultant and Construction Pvt. Ltd.', 'kriswa' ); ?></small></span><span class="screen-reader-text"><?php echo esc_html( kriswa_o( 'company_name', 'Kriswa Consultant and Construction Pvt. Ltd.' ) ); ?></span></a>
<!-- /wp:html -->

<!-- wp:navigation {"overlayMenu":"mobile","icon":"menu","className":"kr-nav","layout":{"type":"flex","setCascadingProperties":true,"justifyContent":"right"}} -->
<?php foreach ( $nav as $item ) { echo kriswa_navlink( $item[0], $item[1] ) . "\n"; } // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- /wp:navigation -->

<?php echo kriswa_buttons( kriswa_btn( __( 'Get a Free Quote', 'kriswa' ), kriswa_quote_url(), '', 'kr-header-cta' ), 'kr-header-btns' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
