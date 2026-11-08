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
	array( __( 'Social', 'kriswa' ), kriswa_u( 'social' ) ),
	array( __( 'Contact', 'kriswa' ), kriswa_u( 'contact' ) ),
);
?>
<!-- wp:group {"align":"full","className":"kr-header","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull kr-header">
<!-- wp:group {"align":"full","className":"kr-mainbar","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-mainbar"><!-- wp:group {"align":"wide","className":"kr-mainbar__in","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide kr-mainbar__in"><!-- wp:html -->
<a class="kr-brand" href="<?php echo esc_url( $home ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.png' ) ); ?>" width="42" height="42" alt=""><span class="kr-brand__name"><?php echo esc_html_x( 'Kriswa', 'brand short name', 'kriswa' ); ?><small><?php esc_html_e( 'Consultant and Construction Pvt. Ltd.', 'kriswa' ); ?></small></span><span class="screen-reader-text"><?php echo esc_html( kriswa_o( 'company_name', 'Kriswa Consultant and Construction Pvt. Ltd.' ) ); ?></span></a>
<!-- /wp:html -->

<!-- wp:navigation {"overlayMenu":"mobile","icon":"menu","className":"kr-nav","layout":{"type":"flex","setCascadingProperties":true,"justifyContent":"right"}} -->
<?php foreach ( $nav as $item ) { echo kriswa_navlink( $item[0], $item[1] ) . "\n"; } // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- /wp:navigation -->

<?php echo kriswa_sc( 'kriswa_lang_switcher' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php echo kriswa_buttons( kriswa_btn( __( 'Get a Free Quote', 'kriswa' ), kriswa_quote_url(), '', 'kr-header-cta' ), 'kr-header-btns' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
