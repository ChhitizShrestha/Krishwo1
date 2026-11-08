<?php
/**
 * Kriswa theme setup and block-markup helpers used by the patterns.
 *
 * @package Kriswa
 */

defined( 'ABSPATH' ) || exit;

define( 'KRISWA_THEME_VERSION', '1.0.0' );

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'kriswa', get_template_directory() . '/languages' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_image_size( 'kriswa-card', 800, 600, true );
	}
);

/* Styles load on the front end and inside the editor. */
add_action(
	'enqueue_block_assets',
	function () {
		wp_enqueue_style( 'kriswa-theme', get_theme_file_uri( 'assets/css/theme.css' ), array(), KRISWA_THEME_VERSION );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'kriswa-theme', get_theme_file_uri( 'assets/js/theme.js' ), array(), KRISWA_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
);

/* Performance: system fonts are used first (no download on Apple devices); drop the emoji script. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

add_action(
	'init',
	function () {
		register_block_pattern_category( 'kriswa', array( 'label' => __( 'Kriswa', 'kriswa' ) ) );
	}
);


/* ------------------------------------------------------------------------- *
 * Helpers for patterns. They fall back gracefully if the companion plugin is off.
 * ------------------------------------------------------------------------- */

/** Company setting (placeholder-friendly). */
function kriswa_o( $key, $fallback = '' ) {
	if ( function_exists( 'kriswa_opt' ) ) {
		$v = kriswa_opt( $key );
		return '' !== $v ? $v : $fallback;
	}
	return $fallback;
}

/** URL of a page by its English slug, language-aware. */
function kriswa_u( $slug ) {
	if ( function_exists( 'kriswa_page_url' ) ) {
		return kriswa_page_url( $slug );
	}
	return home_url( '/' . $slug . '/' );
}

function kriswa_projects_url() {
	$u = get_post_type_archive_link( 'kriswa_project' );
	return $u ? $u : home_url( '/projects/' );
}

function kriswa_quote_url( $service = '' ) {
	$u = kriswa_u( 'get-a-quote' );
	return $service ? add_query_arg( 'service', $service, $u ) : $u;
}

/** Render a shortcode as a core shortcode block. */
function kriswa_sc( $tag ) {
	return '<!-- wp:shortcode -->' . "\n" . '[' . $tag . ']' . "\n" . '<!-- /wp:shortcode -->';
}

/** Section header (eyebrow, h2, lead). All arguments are already-translated strings. */
function kriswa_head( $eyebrow, $title, $lead = '', $align = 'center' ) {
	$cls = 'kr-sechead' . ( 'center' === $align ? ' kr-center' : '' );
	$out = '<!-- wp:group {"className":"' . $cls . '","layout":{"type":"default"}} -->' . "\n" . '<div class="wp-block-group ' . $cls . '">';
	if ( $eyebrow ) {
		$out .= '<!-- wp:paragraph {"className":"kr-eyebrow"} --><p class="kr-eyebrow">' . esc_html( $eyebrow ) . '</p><!-- /wp:paragraph -->';
	}
	$out .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $title ) . '</h2><!-- /wp:heading -->';
	if ( $lead ) {
		$out .= '<!-- wp:paragraph {"className":"kr-lead"} --><p class="kr-lead">' . esc_html( $lead ) . '</p><!-- /wp:paragraph -->';
	}
	return $out . '</div>' . "\n" . '<!-- /wp:group -->';
}

/** Button block. $style: '' (solid), 'outline'. */
function kriswa_btn( $text, $url, $style = '', $extra_class = '' ) {
	$cls_attr = trim( ( 'outline' === $style ? 'is-style-outline ' : '' ) . $extra_class );
	$json     = $cls_attr ? ' {"className":"' . $cls_attr . '"}' : '';
	return '<!-- wp:button' . $json . ' --><div class="wp-block-button' . ( $cls_attr ? ' ' . $cls_attr : '' ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a></div><!-- /wp:button -->';
}

function kriswa_buttons( $buttons_html, $class = '' ) {
	$json = $class ? ' {"className":"' . $class . '"}' : '';
	return '<!-- wp:buttons' . $json . ' --><div class="wp-block-buttons' . ( $class ? ' ' . $class : '' ) . '">' . $buttons_html . '</div><!-- /wp:buttons -->';
}

/** Open and close a full-width section. $mod: '', 'surface', 'navy', 'tint'. */
function kriswa_section_open( $mod = '', $extra = '' ) {
	$cls = trim( 'kr-section' . ( $mod ? ' kr-section--' . $mod : '' ) . ( $extra ? ' ' . $extra : '' ) );
	return '<!-- wp:group {"align":"full","className":"' . $cls . '","layout":{"type":"constrained"}} -->' . "\n" . '<div class="wp-block-group alignfull ' . $cls . '"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->' . "\n" . '<div class="wp-block-group alignwide">';
}

function kriswa_section_close() {
	return '</div>' . "\n" . '<!-- /wp:group --></div>' . "\n" . '<!-- /wp:group -->';
}

/** Navigation link block with a safely JSON-encoded label and URL. */
function kriswa_navlink( $label, $url ) {
	$attrs = wp_json_encode(
		array(
			'label'          => $label,
			'type'           => 'custom',
			'url'            => esc_url_raw( $url ),
			'kind'           => 'custom',
			'isTopLevelLink' => true,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
	);
	return '<!-- wp:navigation-link ' . $attrs . ' /-->';
}

/** Inline brand icon from the companion plugin, or empty. */
function kriswa_i( $name, $size = 28 ) {
	return function_exists( 'kriswa_icon' ) ? kriswa_icon( $name, $size ) : '';
}

/** True when the Nepali version of the site is being shown. */
function kriswa_is_nepali_theme() {
	return function_exists( 'kriswa_is_nepali' ) && kriswa_is_nepali() && class_exists( 'Kriswa_Nepali_Date' );
}
