<?php
/**
 * Launch switches: hide whole sections (Projects, Testimonials, Social, Blog, FAQ, Credentials page) until the
 * content is ready. A hidden section disappears from the menu and footer, the home page, related lists and the search
 * engine sitemap; its address redirects visitors to the home page. Administrators can still open hidden pages to
 * prepare them, and see a note in the admin bar. Untick the switch and everything reappears.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

function kriswa_sections() {
	return array(
		'projects'     => array( 'Projects', 'The Projects page and each project, the menu and footer links, the home page "Recent projects" section and "related projects" lists.' ),
		'testimonials' => array( 'Testimonials', 'Client testimonials on the home page and on the Credentials page.' ),
		'social'       => array( 'Social (Instagram and TikTok)', 'The Social page, its menu and footer links, and the feed on the home page.' ),
		'blog'         => array( 'Blog', 'The Blog page and all articles, with its menu and footer links.' ),
		'faq'          => array( 'FAQ', 'The FAQ page and its footer link.' ),
		'credentials'  => array( 'Credentials and Team page', 'The full credentials, expertise and team page. (The registration badges on the home page stay.)' ),
	);
}

function kriswa_section_hidden( $slug ) {
	return '1' === (string) kriswa_opt( 'hide_' . $slug, false );
}

function kriswa_hidden_sections() {
	return array_values( array_filter( array_keys( kriswa_sections() ), 'kriswa_section_hidden' ) );
}

/** Which section the page being displayed belongs to ('' when none). Needs the main query to be resolved. */
function kriswa_current_section() {
	if ( is_post_type_archive( 'kriswa_project' ) || is_singular( 'kriswa_project' ) || is_tax( array( 'kriswa_project_type', 'kriswa_location' ) ) ) {
		return 'projects';
	}
	if ( ( is_home() && ! is_front_page() ) || is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author() ) {
		return 'blog';
	}
	if ( is_page() ) {
		$slug = kriswa_seo_slug( get_queried_object_id() );
		if ( in_array( $slug, array( 'social', 'faq', 'credentials' ), true ) ) {
			return $slug;
		}
	}
	return '';
}

/* Visitors of a hidden section go to the home page (a temporary redirect, never a 404 or a dead end). */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() || wp_doing_ajax() || is_feed() ) {
			return;
		}
		$hidden = kriswa_hidden_sections();
		$sec    = $hidden ? kriswa_current_section() : '';
		if ( ! $sec || ! in_array( $sec, $hidden, true ) || current_user_can( 'manage_options' ) ) {
			return;
		}
		nocache_headers();
		wp_safe_redirect( function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ), 302 );
		exit;
	},
	1
);

/* Lists on other pages (home sections, related projects, widgets, search) show nothing from a hidden section. */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() ) {
			return;
		}
		$map = array( 'kriswa_project' => 'projects', 'kriswa_testimonial' => 'testimonials', 'post' => 'blog' );
		$pt  = $q->get( 'post_type' );
		$pts = is_array( $pt ) ? $pt : array( $pt ? $pt : ( ( $q->is_home() || $q->is_archive() || $q->is_search() || $q->is_feed() ) ? 'post' : '' ) );
		foreach ( $map as $type => $sec ) {
			if ( in_array( $type, $pts, true ) && kriswa_section_hidden( $sec ) ) {
				// The hidden section's own pages are left alone here: visitors are redirected from them (see above)
				// and administrators can preview them. Everything else (home sections, widgets, search) gets nothing.
				if ( $q->is_main_query() && ( $q->is_post_type_archive( $type ) || $q->is_single() || ( 'kriswa_project' === $type && $q->is_tax( array( 'kriswa_project_type', 'kriswa_location' ) ) ) || ( 'post' === $type && ( $q->is_home() || $q->is_category() || $q->is_tag() || $q->is_date() || $q->is_author() ) ) ) ) {
					continue;
				}
				// Lists shown on the hidden page itself (for example the project grid) are visible to the administrator previewing it.
				if ( ! $q->is_main_query() && current_user_can( 'manage_options' ) && kriswa_current_section() === $sec ) {
					continue;
				}
				$q->set( 'post__in', array( 0 ) );
			}
		}
	},
	20
);

/* Search engines: hidden sections never appear in the sitemap. */
add_filter(
	'the_seo_framework_sitemap_supported_post_types',
	function ( $types ) {
		$drop = array();
		if ( kriswa_section_hidden( 'projects' ) ) {
			$drop[] = 'kriswa_project';
		}
		if ( kriswa_section_hidden( 'blog' ) ) {
			$drop[] = 'post';
		}
		return $drop ? array_values( array_diff( (array) $types, $drop ) ) : $types;
	}
);
add_filter(
	'the_seo_framework_sitemap_exclude_ids',
	function ( $ids ) {
		$ids = (array) $ids;
		foreach ( array( 'social', 'faq', 'credentials', 'blog' ) as $slug ) {
			if ( ! kriswa_section_hidden( $slug ) ) {
				continue;
			}
			$page = get_page_by_path( $slug );
			if ( $page ) {
				$ids[] = $page->ID;
				if ( function_exists( 'pll_get_post_translations' ) ) {
					$ids = array_merge( $ids, array_values( pll_get_post_translations( $page->ID ) ) );
				}
			}
		}
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}
);

/* Admin: say so on the page list, in the admin bar and on the main screens. */
add_filter(
	'display_post_states',
	function ( $states, $post ) {
		if ( 'page' === $post->post_type ) {
			$slug = kriswa_seo_slug( $post->ID );
			if ( in_array( $slug, array( 'social', 'faq', 'credentials', 'blog' ), true ) && kriswa_section_hidden( $slug ) ) {
				$states['kr_hidden'] = 'Hidden from visitors';
			}
		}
		return $states;
	},
	10,
	2
);

add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$sec = kriswa_current_section();
		if ( $sec && kriswa_section_hidden( $sec ) ) {
			$bar->add_node( array( 'id' => 'kriswa-hidden', 'title' => '&#9888; Hidden from visitors (only you can see this)', 'href' => admin_url( 'admin.php?page=kriswa-settings#tab-sections' ) ) );
		}
	},
	100
);

add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || ! current_user_can( 'manage_options' ) || ! in_array( $screen->id, array( 'dashboard', 'toplevel_page_kriswa-settings' ), true ) ) {
			return;
		}
		$names = array();
		foreach ( kriswa_hidden_sections() as $s ) {
			$names[] = kriswa_sections()[ $s ][0];
		}
		if ( $names ) {
			echo '<div class="notice notice-info"><p><strong>Hidden from visitors:</strong> ' . esc_html( implode( ', ', $names ) ) . '. They are removed from menus, the home page and the sitemap. <a href="' . esc_url( admin_url( 'admin.php?page=kriswa-settings#tab-sections' ) ) . '">Change this</a> when the content is ready.</p></div>';
		}
	}
);

/* TSF caches its sitemap: clear it whenever a section is hidden or shown so search engines never see stale URLs. */
function kriswa_clear_sitemap_cache() {
	if ( class_exists( '\The_SEO_Framework\Sitemap\Cache' ) && method_exists( '\The_SEO_Framework\Sitemap\Cache', 'clear_sitemap_caches' ) ) {
		\The_SEO_Framework\Sitemap\Cache::clear_sitemap_caches();
	}
}
function kriswa_sections_changed( $old, $new ) {
	$old = is_array( $old ) ? $old : array();
	$new = is_array( $new ) ? $new : array();
	foreach ( array_keys( kriswa_sections() ) as $k ) {
		$a = isset( $old[ 'hide_' . $k ] ) ? $old[ 'hide_' . $k ] : '0';
		$b = isset( $new[ 'hide_' . $k ] ) ? $new[ 'hide_' . $k ] : '0';
		if ( $a !== $b ) {
			kriswa_clear_sitemap_cache();
			return;
		}
	}
}
add_action( 'update_option_kriswa_options', 'kriswa_sections_changed', 10, 2 );
add_action( 'add_option_kriswa_options', function ( $o, $v ) { kriswa_sections_changed( array(), $v ); }, 10, 2 );
