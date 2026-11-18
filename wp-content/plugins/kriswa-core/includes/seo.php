<?php
/**
 * Search-engine optimisation, built to work WITH The SEO Framework (TSF).
 *
 * TSF owns the <title>, meta description, canonical, robots, Open Graph / Twitter tags, sitemap and the schema.org graph.
 * It generates descriptions from page content, but this site's pages are built from templates, so their content is empty.
 * This file therefore feeds TSF through its own public filters (never duplicating its output):
 *   - titles and descriptions for the template-built pages, the projects archive and each project
 *   - the business's details inside TSF's Organization schema (as a GeneralContractor, with real data only)
 *   - noindex for sample content so it never reaches Google or the sitemap
 * and applies sensible TSF settings once (only filling blanks, never overwriting what the owner chose).
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

function kriswa_tsf_active() {
	return defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

/* ========================================================================= *
 * Copy for the pages: [ SEO title (TSF adds the site name), meta description ]
 * Titles stay short so "title - site name" fits in about 60 characters.
 * ========================================================================= */
function kriswa_seo_copy() {
	return array(
		'about'       => array(
			__( 'About Us: Builders and Valuers', 'kriswa-core' ),
			__( 'Meet Kriswa, a Kathmandu team of engineers, architects and valuers who build and renovate homes and value land and buildings with honest pricing.', 'kriswa-core' ),
		),
		'services'    => array(
			__( 'Construction and Valuation Services', 'kriswa-core' ),
			__( 'House construction, renovation, interior design, architectural drawings and land and building valuation in Kathmandu, Nepal. Request a free quote.', 'kriswa-core' ),
		),
		'get-a-quote' => array(
			__( 'Free House Construction Quote', 'kriswa-core' ),
			__( 'Request a free quote for house construction, renovation or land and building valuation in Kathmandu. Tell us about your plot and we reply within a day.', 'kriswa-core' ),
		),
		'faq'         => array(
			__( 'House Construction and Valuation FAQ', 'kriswa-core' ),
			__( 'Answers about house construction cost, timelines, drawing approval, renovation and land and building valuation in Nepal from Kriswa engineers.', 'kriswa-core' ),
		),
		'social'      => array(
			__( 'Our Sites on Instagram and TikTok', 'kriswa-core' ),
			__( 'Photos and videos from our construction sites, renovations and finished homes in Kathmandu, on Instagram and TikTok.', 'kriswa-core' ),
		),
		'contact'     => array(
			__( 'Contact Us in Kathmandu', 'kriswa-core' ),
			__( 'Contact Kriswa Consultant and Construction in Kathmandu by phone, WhatsApp or email, or visit our office. Free quotes for homes and valuations.', 'kriswa-core' ),
		),
		'credentials' => array(
			__( 'Credentials and Qualified Team', 'kriswa-core' ),
			__( 'Company registration, PAN/VAT, Engineering Council credentials and the qualified engineers and architects behind Kriswa work in Nepal.', 'kriswa-core' ),
		),
		'blog'        => array(
			__( 'Building and Renovation Tips', 'kriswa-core' ),
			__( 'Practical advice on building, renovating and valuing homes and land in Nepal from the engineers at Kriswa Construction.', 'kriswa-core' ),
		),
	);
}

function kriswa_seo_archive_copy() {
	return array(
		__( 'Projects: Homes, Renovations, Valuations', 'kriswa-core' ),
		__( 'Residential buildings, renovations and land and building valuation projects completed by Kriswa in Kathmandu Valley. See photos and project details.', 'kriswa-core' ),
	);
}

/** English slug of a page (the Nepali copy of a page maps back to the English one). */
function kriswa_seo_slug( $post_id ) {
	if ( function_exists( 'pll_default_language' ) && function_exists( 'pll_get_post' ) ) {
		$en = pll_get_post( $post_id, pll_default_language() );
		if ( $en ) {
			$post_id = $en;
		}
	}
	return preg_replace( '/-ne$/', '', (string) get_post_field( 'post_name', $post_id ) );
}

/**
 * TSF passes null for "the page being displayed" and an array when it describes another page (sitemaps, admin).
 * Normalise both into [ 'id' => int, 'tax' => bool, 'pta' => string ].
 */
function kriswa_seo_context( $args ) {
	if ( is_array( $args ) ) {
		return array(
			'id'  => isset( $args['id'] ) ? (int) $args['id'] : 0,
			'tax' => ! empty( $args['tax'] ),
			'pta' => ! empty( $args['pta'] ) ? (string) $args['pta'] : '',
		);
	}
	if ( is_post_type_archive( 'kriswa_project' ) ) {
		return array( 'id' => 0, 'tax' => false, 'pta' => 'kriswa_project' );
	}
	if ( is_singular() ) {
		return array( 'id' => (int) get_queried_object_id(), 'tax' => false, 'pta' => '' );
	}
	if ( is_home() && ! is_front_page() && get_option( 'page_for_posts' ) ) {
		return array( 'id' => (int) get_option( 'page_for_posts' ), 'tax' => false, 'pta' => '' ); // The Blog page.
	}
	return array( 'id' => 0, 'tax' => is_tax() || is_category() || is_tag(), 'pta' => '' );
}

/** A concise, factual project description for results pages and link previews (max ~160 characters). */
function kriswa_seo_brand() {
	$b = kriswa_tsf_active() ? trim( (string) kriswa_tsf_get( 'site_title' ) ) : '';
	return $b ? $b : kriswa_opt( 'company_name' );
}

function kriswa_seo_project_description( $id ) {
	$types = get_the_terms( $id, 'kriswa_project_type' );
	$type  = $types && ! is_wp_error( $types ) ? $types[0]->name : '';
	$loc   = get_post_meta( $id, '_kr_location', true );
	$year  = get_post_meta( $id, '_kr_year', true );
	$area  = rtrim( (string) get_post_meta( $id, '_kr_area', true ), '. ' );
	$ne    = kriswa_is_nepali();
	$parts = array( trim( ( $type ? $type . ' ' : '' ) . ( $type ? __( 'project', 'kriswa-core' ) : __( 'Project', 'kriswa-core' ) ) . ( $loc ? ( $ne ? ', ' : ' in ' ) . $loc : '' ) ) );
	if ( $year ) {
		$parts[] = sprintf( /* translators: %s: year */ __( 'completed %s', 'kriswa-core' ), $ne ? Kriswa_Nepali_Date::numerals( $year ) : $year );
	}
	if ( $area ) {
		$parts[] = $area;
	}
	$sent   = array( implode( ', ', $parts ) . '.' );
	$sent[] = sprintf( /* translators: %s: company */ __( 'Built by %s.', 'kriswa-core' ), rtrim( kriswa_seo_brand(), '. ' ) );
	$svc    = function_exists( 'kriswa_project_services' ) ? kriswa_project_services( $id ) : array();
	if ( $svc ) {
		$sent[] = sprintf( /* translators: %s: service name */ __( 'Service: %s.', 'kriswa-core' ), get_the_title( $svc[0] ) );
	}
	$sent[] = __( 'See photos and project details.', 'kriswa-core' );
	// Search guidance: 120 to 160 characters. Add whole sentences while they fit; never cut one mid-way.
	$text = '';
	foreach ( $sent as $i => $sn ) {
		if ( $i && mb_strlen( $text . ' ' . $sn ) > 158 ) {
			continue;
		}
		$text .= ( $text ? ' ' : '' ) . $sn;
	}
	return mb_strimwidth( $text, 0, 158, '...' );
}

/* ------------------------------------------------------------------------- *
 * TSF filters
 * ------------------------------------------------------------------------- */
add_filter(
	'the_seo_framework_title_from_generation',
	function ( $title, $args ) {
		$ctx = kriswa_seo_context( $args );
		if ( 'kriswa_project' === $ctx['pta'] ) {
			$c = kriswa_seo_archive_copy();
			return $c[0];
		}
		$id = $ctx['id'];
		if ( $id && ! $ctx['tax'] && 'page' === get_post_type( $id ) && (int) get_option( 'page_on_front' ) !== $id ) {
			$copy = kriswa_seo_copy();
			$slug = kriswa_seo_slug( $id );
			if ( isset( $copy[ $slug ] ) ) {
				return $copy[ $slug ][0];
			}
		}
		return $title;
	},
	10,
	2
);

add_filter(
	'the_seo_framework_generated_description',
	function ( $desc, $args ) {
		$ctx = kriswa_seo_context( $args );
		if ( 'kriswa_project' === $ctx['pta'] ) {
			$c = kriswa_seo_archive_copy();
			return $c[1];
		}
		$id = $ctx['id'];
		if ( ! $id || $ctx['tax'] ) {
			return $desc;
		}
		$pt = get_post_type( $id );
		if ( 'kriswa_project' === $pt ) {
			return kriswa_seo_project_description( $id );
		}
		if ( 'page' === $pt ) {
			$copy = kriswa_seo_copy();
			$slug = kriswa_seo_slug( $id );
			if ( isset( $copy[ $slug ] ) && mb_strlen( trim( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ) ) ) < 120 ) {
				return $copy[ $slug ][1];
			}
		}
		if ( 'kriswa_service' === $pt && '' === trim( (string) $desc ) ) {
			return mb_strimwidth( wp_strip_all_tags( get_the_excerpt( $id ) ), 0, 158, '...' );
		}
		return $desc;
	},
	10,
	2
);

/* Business details inside TSF's Organization (so there is ONE business entity, not two competing ones). */
add_filter(
	'the_seo_framework_schema_graph_data',
	function ( $graph, $args ) {
		if ( ! is_array( $graph ) ) {
			return $graph;
		}
		$is_placeholder = function ( $v ) {
			return '' === trim( (string) $v ) || false !== strpos( (string) $v, '0000000' ) || false !== stripos( (string) $v, 'Your Street' ) || false !== strpos( (string) $v, 'example.com' );
		};
		$same = array();
		foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok', 'linkedin' ) as $n ) {
			$u = kriswa_opt( $n, false );
			// A bare "https://facebook.com/" is only a placeholder, not a profile.
			if ( $u && ! preg_match( '#^https?://(www\.)?[a-z]+\.com/?$#i', $u ) ) {
				$same[] = $u;
			}
		}
		foreach ( $graph as $i => $entity ) {
			if ( ! isset( $entity['@type'] ) || 'Organization' !== $entity['@type'] ) {
				continue;
			}
			$entity['@type']     = 'GeneralContractor';
			$entity['legalName'] = kriswa_opt( 'company_name', false );
			$tagline             = kriswa_opt( 'tagline', false );
			if ( $tagline ) {
				$entity['description'] = $tagline;
			}
			if ( ! $is_placeholder( kriswa_opt( 'phone', false ) ) ) {
				$entity['telephone'] = kriswa_opt( 'phone', false );
			}
			if ( ! $is_placeholder( kriswa_opt( 'email', false ) ) ) {
				$entity['email'] = kriswa_opt( 'email', false );
			}
			if ( ! $is_placeholder( kriswa_opt( 'address', false ) ) ) {
				$entity['address'] = array( '@type' => 'PostalAddress', 'streetAddress' => kriswa_opt( 'address', false ), 'addressCountry' => 'NP' );
			}
			$entity['areaServed'] = array( '@type' => 'Country', 'name' => 'Nepal' );
			if ( kriswa_opt( 'founded_year', false ) ) {
				$entity['foundingDate'] = kriswa_opt( 'founded_year', false );
			}
			$have = isset( $entity['sameAs'] ) ? (array) $entity['sameAs'] : array();
			if ( $have || $same ) {
				$entity['sameAs'] = array_values( array_unique( array_merge( $have, $same ) ) );
			}
			$graph[ $i ] = $entity;
		}

		$ctx = kriswa_seo_context( $args );
		$id  = $ctx['id'];
		if ( $id && ! $ctx['tax'] && 'kriswa_project' === get_post_type( $id ) ) {
			$loc  = get_post_meta( $id, '_kr_location', true );
			$work = array(
				'@type'       => 'CreativeWork',
				'@id'         => get_permalink( $id ) . '#project',
				'name'        => get_the_title( $id ),
				'url'         => get_permalink( $id ),
				'description' => kriswa_seo_project_description( $id ),
			);
			if ( $loc ) {
				$work['locationCreated'] = array( '@type' => 'Place', 'name' => $loc, 'address' => array( '@type' => 'PostalAddress', 'addressLocality' => $loc, 'addressCountry' => 'NP' ) );
			}
			if ( get_post_meta( $id, '_kr_year', true ) ) {
				$work['dateCreated'] = get_post_meta( $id, '_kr_year', true );
			}
			$cover = function_exists( 'kriswa_project_cover_id' ) ? kriswa_project_cover_id( $id ) : 0;
			if ( $cover ) {
				$work['image'] = wp_get_attachment_image_url( $cover, 'large' );
			}
			$graph[] = $work;
		}
		return $graph;
	},
	10,
	2
);

/* ------------------------------------------------------------------------- *
 * Sample content is noindexed (and so left out of the sitemap) until the owner replaces it.
 * ------------------------------------------------------------------------- */
function kriswa_sync_sample_noindex() {
	$n = 0;
	foreach ( kriswa_sample_ids() as $id ) {
		if ( '1' !== (string) get_post_meta( $id, '_genesis_noindex', true ) ) {
			update_post_meta( $id, '_genesis_noindex', 1 );
			++$n;
		}
	}
	return $n;
}

/* ------------------------------------------------------------------------- *
 * Visible breadcrumbs (TSF already outputs the matching BreadcrumbList schema)
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_breadcrumbs',
	function () {
		if ( is_front_page() || is_404() || is_search() ) {
			return '';
		}
		$home  = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
		$trail = array( array( __( 'Home', 'kriswa-core' ), $home ) );
		if ( is_singular( 'kriswa_project' ) ) {
			$trail[] = array( __( 'Projects', 'kriswa-core' ), get_post_type_archive_link( 'kriswa_project' ) );
		} elseif ( is_singular( 'kriswa_service' ) ) {
			$trail[] = array( __( 'Services', 'kriswa-core' ), kriswa_page_url( 'services' ) );
		} elseif ( is_singular( 'post' ) ) {
			$trail[] = array( __( 'Blog', 'kriswa-core' ), kriswa_page_url( 'blog' ) );
		}
		$trail[] = array( get_the_title(), '' );
		$out     = '<nav class="kr-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'kriswa-core' ) . '"><ol>';
		foreach ( $trail as $i => $t ) {
			$last = $i === count( $trail ) - 1;
			$out .= '<li>' . ( $last || ! $t[1] ? '<span aria-current="page">' . esc_html( $t[0] ) . '</span>' : '<a href="' . esc_url( $t[1] ) . '">' . esc_html( $t[0] ) . '</a>' ) . '</li>';
		}
		return $out . '</ol></nav>';
	}
);

/* ========================================================================= *
 * One-time setup of TSF and the site (fills blanks only)
 * ========================================================================= */
function kriswa_tsf_get( $key ) {
	$o = get_option( 'autodescription-site-settings', array() );
	return is_array( $o ) && isset( $o[ $key ] ) ? $o[ $key ] : '';
}

function kriswa_tsf_set( $key, $value ) {
	if ( class_exists( '\The_SEO_Framework\Data\Plugin' ) && method_exists( '\The_SEO_Framework\Data\Plugin', 'update_option' ) ) {
		\The_SEO_Framework\Data\Plugin::update_option( $key, $value );
		return;
	}
	$o         = get_option( 'autodescription-site-settings', array() );
	$o         = is_array( $o ) ? $o : array();
	$o[ $key ] = $value;
	update_option( 'autodescription-site-settings', $o );
}

/** Put a theme image in the media library once (used for the site icon and the default share picture). */
function kriswa_sideload_theme_image( $file, $title, $alt ) {
	$path = get_template_directory() . '/assets/images/' . $file;
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$up = wp_upload_bits( basename( $file ), null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$type = wp_check_filetype( $up['file'] );
	$att  = wp_insert_attachment( array( 'post_mime_type' => $type['type'], 'post_title' => $title, 'post_status' => 'inherit' ), $up['file'] );
	if ( is_wp_error( $att ) || ! $att ) {
		return 0;
	}
	wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $up['file'] ) );
	update_post_meta( $att, '_wp_attachment_image_alt', $alt );
	return (int) $att;
}

function kriswa_seo_asset( $key, $file, $title, $alt ) {
	$ids = get_option( 'kriswa_seo_assets', array() );
	$ids = is_array( $ids ) ? $ids : array();
	if ( ! empty( $ids[ $key ] ) && 'attachment' === get_post_type( $ids[ $key ] ) ) {
		return (int) $ids[ $key ];
	}
	$ids[ $key ] = kriswa_sideload_theme_image( $file, $title, $alt );
	update_option( 'kriswa_seo_assets', $ids, false );
	return (int) $ids[ $key ];
}

/**
 * Apply the recommended setup. Safe to repeat: only empty settings are filled.
 *
 * @return string[] Log of what changed.
 */
function kriswa_seo_apply() {
	$log = array();

	// Browser-tab icon (also feeds the Organization logo): only if the owner has not set one.
	if ( ! get_option( 'site_icon' ) ) {
		$icon = kriswa_seo_asset( 'icon', 'icon-512.png', 'Kriswa site icon', 'Kriswa logo' );
		if ( $icon ) {
			update_option( 'site_icon', $icon );
			$log[] = 'Set the browser-tab icon (site icon) from the Kriswa logo.';
		}
	}

	// Remove WordPress demo content that would otherwise be indexed.
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ) ) as $d ) {
		$p = get_page_by_path( $d[0], OBJECT, $d[1] );
		if ( $p && $p->post_date === $p->post_modified ) {
			wp_delete_post( $p->ID, true );
			$log[] = 'Deleted the default WordPress "' . $p->post_title . '" ' . $d[1] . ' (thin duplicate content).';
		}
	}

	// Sample content: never indexed.
	$n = kriswa_sync_sample_noindex();
	if ( $n ) {
		$log[] = "Marked $n sample item(s) noindex so they stay out of Google and the sitemap until you replace them.";
	}

	if ( ! kriswa_tsf_active() ) {
		$log[] = 'The SEO Framework is not active, so its settings were not changed. They are applied automatically once it is activated.';
		return $log;
	}

	$og = kriswa_seo_asset( 'og', 'og-default.jpg', 'Kriswa share image', 'Kriswa Consultant and Construction, Kathmandu' );
	$fill = array(
		'site_title'             => 'Kriswa Construction',
		'homepage_title'         => __( 'Home Building and Valuation in Kathmandu', 'kriswa-core' ),
		'homepage_title_tagline' => 'Kriswa Construction',
		'homepage_description'   => __( 'Kathmandu builder: residential construction, renovation and land and building valuation by qualified engineers. Honest pricing. Get a free quote.', 'kriswa-core' ),
		'knowledge_name'         => kriswa_opt( 'company_name', false ),
		'theme_color'            => '#1747b8',
	);
	foreach ( $fill as $k => $v ) {
		if ( '' === trim( (string) kriswa_tsf_get( $k ) ) ) {
			kriswa_tsf_set( $k, $v );
			$log[] = "The SEO Framework: set \"$k\".";
		}
	}
	if ( $og && ! kriswa_tsf_get( 'social_image_fb_url' ) ) {
		kriswa_tsf_set( 'social_image_fb_url', wp_get_attachment_image_url( $og, 'full' ) );
		kriswa_tsf_set( 'social_image_fb_id', $og );
		$log[] = 'The SEO Framework: set the default social share picture (used when a page has no image).';
	}
	$icon_id = (int) get_option( 'site_icon' );
	if ( $icon_id && ! kriswa_tsf_get( 'knowledge_logo_url' ) ) {
		kriswa_tsf_set( 'knowledge_logo_url', wp_get_attachment_image_url( $icon_id, 'full' ) );
		kriswa_tsf_set( 'knowledge_logo_id', $icon_id );
		$log[] = 'The SEO Framework: set the organization logo for Google.';
	}
	update_option( 'kriswa_seo_applied', time(), false );
	update_option( 'kriswa_seo_log', $log, false );
	kriswa_clear_sitemap_cache();
	return $log;
}

/* Runs once when the SEO Framework is active (also right after installing it). */
add_action(
	'admin_init',
	function () {
		if ( kriswa_tsf_active() && current_user_can( 'manage_options' ) && ! wp_doing_ajax() && ! get_option( 'kriswa_seo_applied' ) ) {
			kriswa_seo_apply();
		}
	},
	30
);

add_action(
	'admin_post_kriswa_seo_apply',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kriswa_seo_apply' ) ) {
			wp_die( 'Not allowed.', 403 );
		}
		update_option( 'kriswa_seo_log', kriswa_seo_apply(), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kriswa-settings&seo=1#tab-seo' ) );
		exit;
	}
);

/* ========================================================================= *
 * "SEO health" checklist (Site Details > SEO)
 * ========================================================================= */
function kriswa_seo_checks() {
	$tsf   = kriswa_tsf_active();
	$no_alt = 0;
	foreach ( get_posts( array( 'post_type' => 'kriswa_project', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'no_found_rows' => true ) ) as $pid ) {
		foreach ( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $pid, '_kr_gallery', true ) ) ) ) as $att ) {
			if ( '' === trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) ) ) {
				++$no_alt;
			}
		}
	}
	$sample_vis = '1' !== kriswa_opt( 'hide_samples', false ) ? count( kriswa_sample_ids() ) : 0;
	$home_t     = $tsf ? trim( (string) kriswa_tsf_get( 'homepage_title' ) ) : '';
	$home_d     = $tsf ? trim( (string) kriswa_tsf_get( 'homepage_description' ) ) : '';
	$settings   = $tsf && defined( 'THE_SEO_FRAMEWORK_SITE_OPTIONS_SLUG' ) ? admin_url( 'admin.php?page=' . THE_SEO_FRAMEWORK_SITE_OPTIONS_SLUG ) : '';

	return array(
		array( $tsf ? 'ok' : 'bad', 'The SEO Framework is active', $tsf ? 'Version ' . THE_SEO_FRAMEWORK_VERSION . '. It writes the titles, descriptions, social tags, sitemap and structured data.' : 'Install and activate it (free). Without it the site only outputs a basic description.', '' ),
		array( '1' === (string) get_option( 'blog_public' ) ? 'ok' : 'bad', 'Search engines are allowed to index the site', 'Settings > Reading must NOT have "Discourage search engines" ticked.', admin_url( 'options-reading.php' ) ),
		array( get_option( 'permalink_structure' ) ? 'ok' : 'bad', 'Readable page addresses (/about/ instead of ?p=12)', '', admin_url( 'options-permalink.php' ) ),
		array( is_ssl() || 0 === strpos( home_url(), 'https://' ) ? 'ok' : 'warn', 'The site uses HTTPS', 'HTTPS is a ranking signal. Make sure the live site address starts with https://.', '' ),
		array( has_site_icon() ? 'ok' : 'warn', 'Browser-tab icon is set', 'Settings > General > Site Icon. The theme falls back to the Kriswa emblem.', admin_url( 'options-general.php' ) ),
		array( ( $home_t && $home_d ) ? 'ok' : ( $tsf ? 'warn' : 'bad' ), 'Home page title and description are set', $tsf ? ( $home_t ? '"' . $home_t . '"' : 'Missing title.' ) . ' Aim for a title under about 60 characters and a description of 120 to 160.' : '', $settings ),
		array( $tsf && kriswa_tsf_get( 'social_image_fb_url' ) ? 'ok' : 'warn', 'A default share picture is set (shown when a page is shared without its own image)', 'Recommended size 1200 x 630.', $settings ),
		array( $tsf && kriswa_tsf_get( 'knowledge_name' ) ? 'ok' : 'warn', 'Organization (business name and logo) is set for Google', 'Used for the Google knowledge panel and rich results.', $settings ),
		array( $no_alt ? 'warn' : 'ok', 'Every project photo has alt text', $no_alt ? "$no_alt photo(s) have no alt text; it is filled in automatically when you next save the project, or write your own in Media." : 'Alt text helps image search and accessibility.', admin_url( 'upload.php' ) ),
		array( $sample_vis ? 'warn' : 'ok', 'No sample content is visible', $sample_vis ? "$sample_vis sample item(s) are still visible to visitors (they are marked noindex for Google). Replace them or tick Hide sample content under Extras." : 'Good.', admin_url( 'admin.php?page=kriswa-settings#tab-extras' ) ),
		array( 'ok', 'Sitemap', 'Submit this address in Google Search Console: ' . home_url( '/sitemap.xml' ), home_url( '/sitemap.xml' ) ),
	);
}

function kriswa_seo_panel() {
	$checks = kriswa_seo_checks();
	$icons  = array( 'ok' => '<span style="color:#00a32a">&#10003;</span>', 'warn' => '<span style="color:#dba617">&#9888;</span>', 'bad' => '<span style="color:#d63638">&#10007;</span>' );
	echo '<div style="margin:8px 0 18px;padding:16px 20px;background:#fff;border:1px solid #c3c4c7;border-radius:4px"><h3 style="margin-top:0">SEO health</h3>';
	if ( isset( $_GET['seo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success inline"><p>Recommended SEO setup applied.</p></div>';
	}
	echo '<table class="widefat striped" style="max-width:960px"><tbody>';
	foreach ( $checks as $c ) {
		echo '<tr><td style="width:28px;font-size:16px">' . $icons[ $c[0] ] . '</td><td><strong>' . esc_html( $c[1] ) . '</strong>' . ( $c[2] ? '<br><span class="description">' . esc_html( $c[2] ) . '</span>' : '' ) . '</td><td style="width:90px">' . ( $c[3] ? '<a href="' . esc_url( $c[3] ) . '">Open</a>' : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table><p><a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kriswa_seo_apply' ), 'kriswa_seo_apply' ) ) . '">Apply recommended SEO setup</a> <span class="description">Fills in blanks only (titles, descriptions, share picture, logo, icon). It never overwrites something you wrote.</span></p>';
	$log = get_option( 'kriswa_seo_log', array() );
	if ( $log ) {
		echo '<p class="description" style="margin-bottom:0"><strong>Last run:</strong> ' . esc_html( implode( ' ', (array) $log ) ) . '</p>';
	}
	echo '</div>';
}
