<?php
/**
 * Title: Statistics band
 * Slug: kriswa/home-stats
 * Categories: kriswa
 *
 * @package Kriswa
 */

$stats = array(
	array( kriswa_o( 'stat_years', '10' ), '+', __( 'Years of experience', 'kriswa' ) ),
	array( kriswa_o( 'stat_projects', '100' ), '+', __( 'Projects completed', 'kriswa' ) ),
	array( kriswa_o( 'stat_clients', '80' ), '+', __( 'Happy clients', 'kriswa' ) ),
	array( kriswa_o( 'stat_team', '25' ), '+', __( 'Engineers and staff', 'kriswa' ) ),
);
$ne = kriswa_is_nepali_theme();
?>
<!-- wp:group {"align":"full","className":"kr-stats","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-stats"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:html -->
<ul class="kr-stats__grid">
<?php foreach ( $stats as $s ) : ?>
	<li><strong data-kr-count="<?php echo esc_attr( (int) $s[0] ); ?>" data-suffix="<?php echo esc_attr( $s[1] ); ?>"><?php echo esc_html( ( $ne ? Kriswa_Nepali_Date::numerals( (int) $s[0] ) : (int) $s[0] ) . $s[1] ); ?></strong><span><?php echo esc_html( $s[2] ); ?></span></li>
<?php endforeach; ?>
</ul>
<!-- /wp:html --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
